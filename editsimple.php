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
 * Edit a simple add user form.
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/locallib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/forms/editsimple_form.php');
require_once($CFG->dirroot.'/blocks/user_delegation/block_user_delegation.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

use block_user_delegation\forms\user_editsimple_form;

$id = optional_param('id', -1, PARAM_INT);    // Edited user id; -1 if creating new user.
$blockid = required_param('blockid', PARAM_INT);    // The block instance id.
$courseid = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).

$instance = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);
$theblock = block_instance('user_delegation', $instance);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$params = ['blockid' => $blockid, 'course' => $courseid, 'id' => $id];
$url = new moodle_url('/blocks/user_delegation/editsimple.php', $params);
$PAGE->set_url($url);

$PAGE->requires->js_call_amd('block_user_delegation/check_user', 'init');

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

if ($id > 0) {
    // Editing existing user.
    $personalcontext = context_user::instance($id);

    // Let be sure we are mentor.
    require_capability('block/user_delegation:isbehalfof', $personalcontext);
    $user = $DB->get_record('user', ['id' => $id], '*', MUST_EXIST);

    $usercontext = context_user::instance($user->id);
    $editoroptions = [
        'maxfiles'   => EDITOR_UNLIMITED_FILES,
        'maxbytes'   => $CFG->maxbytes,
        'trusttext'  => false,
        'forcehttps' => false,
        'context'    => $usercontext,
    ];

    $user = file_prepare_standard_editor($user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);

    // Load user preferences.
    useredit_load_preferences($user);

    // Load custom profile fields data.
    profile_load_data($user);

    // User interests separated by commas.
    $user->interests = block_user_delegation::user_interests($id);

    if ($user->id != $USER->id && is_primary_admin($user->id)) {  // Can't edit primary admin.
        throw new moodle_exception('adminprimarynoedit');
    }
    if (isguestuser($user->id)) {
        // The real guest user can not be edited.
        throw new moodle_exception('guestnoeditprofileother');
    }

    if ($user->deleted) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('userdeleted'));
        echo $OUTPUT->footer($course);
        die;
    }
} else {
    $user = null;

    $usercontext = null;
    // This is a new user, we don't want to add files here.
    $editoroptions = [
        'maxfiles' => 0,
        'maxbytes' => 0,
        'trusttext' => false,
        'forcehttps' => false,
        'context' => $coursecontext,
    ];
}

// Prepare filemanager draft area.
$draftitemid = 0;
$filemanagercontext = $editoroptions['context'];
$filemanageroptions = [
    'maxbytes'       => $CFG->maxbytes,
    'subdirs'        => 0,
    'maxfiles'       => 1,
    'accepted_types' => 'web_image',
];

$coursesarr = user_delegation_get_owned_courses();

// Create form.
$params = [
    'user' => $user,
    'courses' => $coursesarr,
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
];
$userform = new user_editsimple_form($url, $params);

if ($userform->is_cancelled()) {
    redirect(new moodle_url('/blocks/user_delegation/myusers.php', ['id' => $blockid, 'course' => $course->id]));
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
            throw new moodle_exception('errorcreateuser', 'block_user_delegation');
        }

        // Assign the created user on behalf of the creator.
        userdelegation::attach_user($USER->id, $newuser->id);
        $usercreated = true;

    } else {

        $newuser->username = $user->username;

        try {
            user_update_user($newuser, false, false);
        } catch (Exception $e) {
            throw new moodle_exception('errorupdatinguser', 'block_user_delegation');
        }

        // Pass a true $userold here.
        if (!$authplugin->user_update($user, $userform->get_data(false))) {
            // Auth update failed, rollback for moodle.
            $DB->update_record('user', $user);
            throw new moodle_exception('Failed to update user data on external auth: '.$user->auth.
                    '. See the server logs for more details.');
        }

        // Set new password if specified.
        if (!empty($newuser->newpassword)) {
            if ($authplugin->can_change_password()) {
                if (!$authplugin->user_update_password($newuser, $newuser->newpassword)) {
                    throw new moodle_exception('Failed to update password on external auth: ' . $newuser->auth .
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
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $coursetoassign = $DB->get_record('course', ['id' => $newuser->coursetoassign]);
        $coursecontext = context_course::instance($coursetoassign->id);

        // Compute enrolment end time if required by block config.
        $end = 0;
        if (!empty($theblock->config->enrolduration)) {
            $end = time() + DAYSECS * $theblock->config->enrolduration;
        }

        if ($coursetoassign) {
            // TODO : Rewrite assignation.
            $params = ['enrol' => 'manual', 'courseid' => $coursetoassign->id, 'status' => ENROL_INSTANCE_ENABLED];
            if ($enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                $enrol = reset($enrols);
                $enrolplugin = enrol_get_plugin('manual');
                $enrolplugin->enrol_user($enrol, $newuser->id, $studentrole->id, time(), $end, ENROL_USER_ACTIVE);
            }
        }
    }

    // Reload from db.
    $newuser = $DB->get_record('user', ['id' => $newuser->id]);

    // Trigger events.
    redirect(new moodle_url('/blocks/user_delegation/myusers.php', ['id' => $blockid, 'course' => $course->id]));
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

// Prepare a div for user check reporting.
echo('<div id="existing_users" style="display: none"></div>');

// Finally display the form.
echo('<div style="font-size:11px;">');

if (is_null($user)) {
    $user = new Stdclass();
}
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
