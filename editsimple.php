<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     block_user_delegation
 * @category    blocks
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/editsimple_form.php');
require_once($CFG->dirroot.'/blocks/user_delegation/block_user_delegation.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

$PAGE->https_required();

$id = optional_param('id', -1, PARAM_INT);    // Edited user id; -1 if creating new user.
$blockid = required_param('blockid', PARAM_INT);    // The block instance id.
$courseid = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).

if (!$instance = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('badblockid', 'block_user_delegation');
}

$theblock = block_instance('user_delegation', $instance);

$params =  array('blockid' => $blockid, 'course' => $courseid, 'id' => $id);
$url = new moodle_url('/blocks/user_delegation/editsimple.php', $params);
$PAGE->set_url($url);

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/user_delegation/js/user_edit.php?id='.$courseid);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

// Security.

require_login();
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

$blockcontext = context_block::instance($blockid);   // Course context.
if (!has_capability('block/user_delegation:cancreateusers', $blockcontext)) {
    // Do in two steps to optimize response time.
    if (!block_user_delegation::has_capability_somewhere('block/user_delegation:cancreateusers')) {
        redirect(new moodle_url('/my'));
    }
}

$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('pluginname', 'block_user_delegation'));
$PAGE->navbar->add(get_string('edituser', 'block_user_delegation'));

if ($id == -1) {
    // Creating new user.
    // Capability is given by course ownership.
    $user = new stdClass();
    $user->id = -1;
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->deleted = 0;
} else {
    // Editing existing user.
    $personalcontext = context_user::instance($id);

    // Let be sure we are mentor.
    require_capability('block/user_delegation:isbehalfof', $personalcontext);
    if (!$user = $DB->get_record('user', array('id' => $id))) {
        print_error('errornosuchuser', 'block_user_delegation');
    }
}

if ($user->id != $USER->id and is_primary_admin($user->id)) {  // Can't edit primary admin
    print_error('adminprimarynoedit');
}
if (isguestuser($user->id)) {
    // The real guest user can not be edited.
    print_error('guestnoeditprofileother');
}

if ($user->deleted) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('userdeleted'));
    echo $OUTPUT->footer($course);
    die;
}

// Load user preferences.
useredit_load_preferences($user);

// Load custom profile fields data.
profile_load_data($user);

// User interests separated by commas.
$user->interests = core_tag_tag::get_item_tags_array('core', 'user', $id);

$ownedcourses = enrol_get_users_courses($USER->id);
$coursesarr = array('0' => get_string('noassign', 'block_user_delegation'));
if ($ownedcourses) {
    foreach ($ownedcourses as $c) {
        $coursecontext = context_course::instance($c->id);
        if (!has_capability('block/user_delegation:owncourse', $coursecontext)) {
            continue;
        }
        $course = $DB->get_record('course', array('id' => $c->id), 'id, fullname');
        $coursesarr[$course->id] = get_string('assignto', 'block_user_delegation', $course->fullname);
    }
}

// Create form.
$userform = new user_editsimple_form($url, array('userid' => $user->id, 'courses' => $coursesarr));

if ($userform->is_cancelled()) {
    redirect(new moodle_url('/blocks/user_delegation/myusers.php', array('id' => $blockid, 'course' => $course->id)));
}

if ($newuser = $userform->get_data()) {

    if (empty($newuser->auth)) {
        // User editing self.
        $authplugin = get_auth_plugin($user->auth);
        unset($newuser->auth);
        // Can not change/remove.
    } else {
        $authplugin = get_auth_plugin($newuser->auth);
    }

    $newuser->username     = trim($newuser->username);
    $newuser->timemodified = time();

    if ($newuser->id == -1) {
        // TODO check out if it makes sense to create account with this auth plugin and what to do with the password.
        unset($newuser->id);
        $newuser->mnethostid = $CFG->mnet_localhost_id; // Always local user.
        $newuser->confirmed  = 1;
        $newuser->password = hash_internal_user_password($newuser->newpassword);
        if (!$newuser->id = user_create_user($newuser, false, false)) {
            print_error('errorcreateuser', 'block_user_delegation');
        }

        // Assign the created user on behalf of the creator.
        userdelegation::attach_user($USER->id, $newuser->id);
        $usercreated = true;

    } else {

        try {
            user_update_user($newuser, false, false);
        } catch (Exception $e) {
            print_error('errorupdatinguser', 'block_user_delegation');
        }

        // Pass a true $userold here.
        if (!$authplugin->user_update($user, $userform->get_data(false))) {
            // Auth update failed, rollback for moodle.
            $DB->update_record('user', $user);
            print_error('Failed to update user data on external auth: '.$user->auth.
                    '. See the server logs for more details.');
        }

        // Set new password if specified.
        if (!empty($newuser->newpassword)) {
            if ($authplugin->can_change_password()) {
                if (!$authplugin->user_update_password($newuser, $newuser->newpassword)){
                    print_error('Failed to update password on external auth: ' . $newuser->auth .
                            '. See the server logs for more details.');
                }
            }
        }
        $usercreated = false;
    }

    // Update preferences.
    useredit_update_user_preference($newuser);

    // Update mail bounces.
    useredit_update_bounces($user, $newuser);

    // Update forum track preference.
    useredit_update_trackforums($user, $newuser);

    // Save custom profile fields data.
    profile_save_data($newuser);

    if (!empty($newuser->coursetoassign)) {
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $coursetoassign = $DB->get_record('course', array('id' => $newuser->coursetoassign));
        $coursecontext = context_course::instance($coursetoassign->id);

        // Compute enrolment end time if required by block config.
        $end = 0;
        if (!empty($theblock->config->enrolduration)) {
            $end = time() + DAYSECS * $theblock->config->enrolduration;
        }

        if ($coursetoassign) {
            // TODO : Rewrite assignation.
            if ($enrols = $DB->get_records('enrol', array('enrol' => 'manual', 'courseid' => $coursetoassign->id, 'status' => ENROL_INSTANCE_ENABLED), 'sortorder ASC')) {
                $enrol = reset($enrols);
                $enrolplugin = enrol_get_plugin('manual');
                $enrolplugin->enrol_user($enrol, $newuser->id, $studentrole->id, time(), $end, ENROL_USER_ACTIVE);
            }
        }
    }

    // Reload from db.
    $newuser = $DB->get_record('user', array('id' => $newuser->id));

    // Trigger events.
    redirect(new moodle_url('/blocks/user_delegation/myusers.php', array('id' => $blockid, 'course' => $course->id)));
}

// Display page header.

if ($user->id == -1) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('newuser', 'block_user_delegation'));
} else {
    echo $OUTPUT->header();
    $userfullname = fullname($user, true);
    echo $OUTPUT->heading($userfullname);
}

// Finally display the form.

echo('<div style="font-size:11px;">');

if (empty($user->country) && !empty($CFG->country)) {
    $user->country = $CFG->country;
}
if (empty($user->city) && !empty($CFG->city)) {
    $user->city = $CFG->city;
}
if (empty($user->lang) && !empty($CFG->lang)) {
    $user->lang = $CFG->lang;
}
if (empty($user->timezone) && !empty($CFG->timezone)) {
    $user->timezone = $CFG->timezone;
}
$user->course = $COURSE->id;
$user->blockid = $blockid;
$userform->set_data($user);
$userform->display();
echo('</div>');

// Add footer.
echo $OUTPUT->footer();
