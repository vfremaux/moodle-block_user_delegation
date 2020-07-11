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
require_once($CFG->dirroot.'/blocks/user_delegation/locallib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/editadvanced_form.php');
require_once($CFG->dirroot.'/blocks/user_delegation/block_user_delegation.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

$id = optional_param('id', $USER->id, PARAM_INT);    // User id; -1 if creating new user.
$blockid = required_param('blockid', PARAM_INT);
$courseid = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).
$returnto = optional_param('returnto', null, PARAM_ALPHA);  // Code determining where to return to after save.

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

if (!$instance = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('badblockid', 'block_user_delegation');
}

$theblock = block_instance('user_delegation', $instance);

$params = array('course' => $course->id, 'blockid' => $blockid, 'id' => $id);
$url = new moodle_url('/blocks/user_delegation/editadvanced.php', $params);
$PAGE->set_url($url);

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/user_delegation/js/user_edit.php?id='.$courseid);

$config = get_config('block_user_delegation');

// Security.

require_login();
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

$blockcontext = context_block::instance($blockid);   // Course context
if (!has_capability('block/user_delegation:cancreateusers', $blockcontext)) {
    // Do in two steps to optimize response time.
    if (!block_user_delegation::has_capability_somewhere('block/user_delegation:cancreateusers')) {
        redirect(new moodle_url('/my'));
    }
}

$systemcontext = context_system::instance();
$coursecontext = context_course::instance($course->id);

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('pluginname', 'block_user_delegation'));
$PAGE->navbar->add(get_string('edituser', 'block_user_delegation'));

if ($id == -1) {
    // Creating new user.
    require_capability('block/user_delegation:cancreateusers', $coursecontext);
    $user = new stdClass();
    $user->id = -1;
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->deleted = 0;
    $user->timezone = '99';
} else {
    // Editing existing user.
    $personalcontext = context_user::instance($id); 

    require_capability('block/user_delegation:isbehalfof', $personalcontext);
    if (!$user = $DB->get_record('user', array('id' => $id))) {
        error('User ID was incorrect');
    }
}

// Remote users cannot be edited.
if ($user->id != -1 and is_mnet_remote_user($user)) {
    redirect(new moodle_url('/user/view.php', array('id' => $id, 'course' => $course->id)));
}
if ($user->id != $USER->id and is_primary_admin($user->id)) {  // Can't edit primary admin
    print_error('adminprimarynoedit');
}
if (isguestuser($user->id)) { // the real guest user can not be edited
    print_error('guestnoeditprofileother');
}

if ($user->deleted) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('userdeleted'));
    echo $OUTPUT->footer();
    die;
}

// Load user preferences.
useredit_load_preferences($user);

// Load custom profile fields data.
profile_load_data($user);

// User interests.
$user->interests = block_user_delegation::user_interests($id);

if ($user->id !== -1) {
    $usercontext = context_user::instance($user->id);
    $editoroptions = array(
        'maxfiles'   => EDITOR_UNLIMITED_FILES,
        'maxbytes'   => $CFG->maxbytes,
        'trusttext'  => false,
        'forcehttps' => false,
        'context'    => $usercontext
    );

    $user = file_prepare_standard_editor($user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
} else {
    $usercontext = null;
    // This is a new user, we don't want to add files here.
    $editoroptions = array(
        'maxfiles' => 0,
        'maxbytes' => 0,
        'trusttext' => false,
        'forcehttps' => false,
        'context' => $coursecontext
    );
}

// Prepare filemanager draft area.
$draftitemid = 0;
$filemanagercontext = $editoroptions['context'];
$filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                             'subdirs'        => 0,
                             'maxfiles'       => 1,
                             'accepted_types' => 'web_image');
file_prepare_draft_area($draftitemid, $filemanagercontext->id, 'user', 'newicon', 0, $filemanageroptions);
$user->imagefile = $draftitemid;

$coursesarr = user_delegation_get_owned_courses();

// Create form.
$userform = new user_editadvanced_form(new moodle_url($PAGE->url, array('returnto' => $returnto)), array(
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
    'user' => $user,
    'courses' => $coursesarr));

$userform->disable_form_change_checker();

if ($userform->is_cancelled()) {
    redirect(new moodle_url('/blocks/user_delegation/myusers.php', array('id' => $blockid, 'course' => $course->id)));
}

if ($usernew = $userform->get_data()) {
    if (empty($usernew->auth)) {
        // User editing self.
        $authplugin = get_auth_plugin($user->auth);
        unset($usernew->auth); // Can not change/remove.
    } else {
        $authplugin = get_auth_plugin($usernew->auth);
    }

    // Pass to a cohort arr eventual cohort binding values.
    $cohortarr = [];
    if (!empty($usernew->cohort)) {
        $cohortarr['cohort'] = $usernew->cohort;
        unset($usernew->cohort);
    }
    if (!empty($usernew->cohortid)) {
        $cohortarr['cohortid'] = $usernew->cohortid;
        unset($usernew->cohortid);
    }

    $usernew->username     = trim($usernew->username);
    $usernew->timemodified = time();

    if ($usernew->id == -1) {
        // TODO check out if it makes sense to create account with this auth plugin and what to do with the password.
        unset($usernew->id);
        $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
        $usernew->confirmed  = 1;
        $usernew->password = hash_internal_user_password($usernew->newpassword);
        try {
            $usernew->id = $DB->insert_record('user', $usernew);
        } catch(Exception $e) {
            error('Error creating user record');
        }

        // Assign the created user on behalf of the creator.
        userdelegation::attach_user($USER->id, $usernew->id);

        userdelegation::bind_cohort($cohortarr, $usernew, $log);

        $usercreated = true;
    } else {
        $DB->update_record('user', $usernew);

        // Pass a true $userold here.
        if (! $authplugin->user_update($user, $userform->get_data(false))) {
            // Auth update failed, rollback for moodle.
            $DB->update_record('user', $user);
            error('Failed to update user data on external auth: '.$user->auth.
                    '. See the server logs for more details.');
        }

        // Set new password if specified.
        if (!empty($usernew->newpassword)) {
            if ($authplugin->can_change_password()) {
                if (!$authplugin->user_update_password($usernew, $usernew->newpassword)){
                    error('Failed to update password on external auth: ' . $usernew->auth .
                            '. See the server logs for more details.');
                }
            }
        }
        $usercreated = false;

        userdelegation::bind_cohort($cohortarr, $usernew, $log);
    }

    // Update preferences.
    useredit_update_user_preference($usernew);

    // Update tags.
    if (!empty($CFG->usetags)) {
        useredit_update_interests($usernew, $usernew->interests);
    }

    // Update user picture.
    if (!empty($CFG->gdversion)) {
        useredit_update_picture($usernew, $userform);
    }

    // Update mail bounces.
    useredit_update_bounces($user, $usernew);

    // Update forum track preference.
    useredit_update_trackforums($user, $usernew);

    // Save custom profile fields data.
    profile_save_data($usernew);

    if (!empty($usernew->coursetoassign)) {
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $coursetoassign = $DB->get_record('course', array('id' => $usernew->coursetoassign));
        $coursecontext = context_course::instance($coursetoassign->id);

        // Compute enrolment end time if required by block config.
        $end = 0;
        if (!empty($theblock->config->enrolduration)) {
            $end = time() + DAYSECS * $theblock->config->enrolduration;
        }

        if ($coursetoassign) {
            // TODO : Rewrite assignation.
            $params = array('enrol' => 'manual', 'courseid' => $coursetoassign->id, 'status' => ENROL_INSTANCE_ENABLED);
            if ($enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                $enrol = reset($enrols);
                $enrolplugin = enrol_get_plugin('manual');
                $enrolplugin->enrol_user($enrol, $usernew->id, $studentrole->id, time(), $end, ENROL_USER_ACTIVE);
            }
        }
    }

    // Reload from db.
    $usernew = $DB->get_record('user', array('id' => $usernew->id));

    // Trigger update/create event, after all fields are stored.
    if ($usercreated) {
        block_user_delegation::trigger_event('user_created', $usernew);
    } else {
        block_user_delegation::events_trigger('user_updated', $usernew);
    }

    if ($user->id == $USER->id) {
        // Override old $USER session variable.
        foreach ((array)$usernew as $variable => $value) {
            if ($variable === 'description' or $variable === 'password') {
                // These are not set for security and perf reasons.
                continue;
            }
            $USER->$variable = $value;
        }
        if (!empty($USER->newadminuser)) {
            unset($USER->newadminuser);

            // Apply defaults again - some of them might depend on admin user info, backup, roles, etc.
            admin_apply_default_settings(NULL , false);

            // Redirect to admin/ to continue with installation.
            redirect(new moodle_url('/'.$CFG->admin.'/index.php'));
        } else {
            redirect(new mooodle_url('/user/view.php', array('id' => $USER->id, 'course' => $course->id)));
        }
    } else {
        redirect(new moodle_url('/blocks/user_delegation/myusers.php', array('course' => $course->id, 'id' => $blockid)));
    }
    // Never reached.
}

// Display page header.

if ($user->id == -1 or ($user->id != $USER->id)) {
    if ($user->id == -1) {
        //admin_externalpage_setup('addnewuser', '', array('id' => -1));
        //admin_externalpage_print_header();
                    echo $OUTPUT->header();
    } else {
       // admin_externalpage_setup('editusers', '', array('id' => $user->id, 'course' => SITEID), $CFG->wwwroot . '/user/editadvanced.php');
      //  admin_externalpage_print_header();
                      echo $OUTPUT->header();
        $userfullname = fullname($user, true);
        echo $OUTPUT->heading($userfullname);
    }
} else if (!empty($USER->newadminuser)) {
    $strprimaryadminsetup = get_string('primaryadminsetup');
    $PAGE->set_title($strprimaryadminsetup);
    $PAGE->set_heading($strprimaryadminsetup);
    echo $OUTPUT->header();
    print_simple_box(get_string('configintroadmin', 'admin'), 'center', '50%');
    echo '<br />';
} else {
    $streditmyprofile = get_string('editmyprofile');
    $strparticipants  = get_string('participants');
    $strnewuser       = get_string('newuser');
    $userfullname     = fullname($user, true);

    $PAGE->set_title("$course->shortname: $streditmyprofile");
    if (has_capability('moodle/course:viewparticipants', $coursecontext) || has_capability('moodle/site:viewparticipants', $systemcontext)) {
        $PAGE->navbar->add($strparticipants, new moodle_url('/user/index.php', array('id' => $course->id)));
    }
    $PAGE->navbar->add($userfullname, new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id)));
    $PAGE->navbar->add($streditmyprofile);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_focuscontrol('');
    echo $OUTPUT->header();
    // Print tabs at the top.
    $showroles = 1;
    $currenttab = 'editprofile';
    require('tabs.php');
}

// Finally display THE form.
echo('<div style="font-size:11px;">');
$userform->set_data($user);
$userform->display();
echo('</div>');

// And proper footer.
echo $OUTPUT->footer();
