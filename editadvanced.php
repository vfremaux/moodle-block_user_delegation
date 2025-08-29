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
 * Advanced edition form
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
require_once($CFG->dirroot.'/blocks/user_delegation/forms/editadvanced_form.php');
require_once($CFG->dirroot.'/blocks/user_delegation/block_user_delegation.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/webservice/lib.php');

use block_user_delegation\forms\user_editadvanced_form;

$id = optional_param('id', $USER->id, PARAM_INT);    // User id; -1 if creating new user.
$blockid = required_param('blockid', PARAM_INT);
$courseid = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).
$returnto = optional_param('returnto', null, PARAM_ALPHA);  // Code determining where to return to after save.

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$instance = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);
$theblock = block_instance('user_delegation', $instance);

$params = ['course' => $course->id, 'blockid' => $blockid, 'id' => $id];
$url = new moodle_url('/blocks/user_delegation/editadvanced.php', $params);
$PAGE->set_url($url);

$PAGE->requires->js_call_amd('block_user_delegation/check_user', 'init');

$config = get_config('block_user_delegation');

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

$systemcontext = context_system::instance();
$coursecontext = context_course::instance($course->id);

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('pluginname', 'block_user_delegation'));
$PAGE->navbar->add(get_string('edituser', 'block_user_delegation'));

if ($id == -1) {
    // Creating new user.
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

    if (!$user = $DB->get_record('user', ['id' => $id])) {
        throw new \moodle_exception('User ID was incorrect');
    }

    // Remote users cannot be edited.
    if ($user->id != -1 && is_mnet_remote_user($user)) {
        redirect(new moodle_url('/user/view.php', ['id' => $id, 'course' => $course->id]));
    }
    if ($user->id != $USER->id && is_primary_admin($user->id)) {  // Can't edit primary admin.
        throw new \moodle_exception('adminprimarynoedit');
    }

    // The real guest user can not be edited.
    if (isguestuser($user->id)) {
        throw new \moodle_exception('guestnoeditprofileother');
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
}

if ($user->id !== -1) {
    $usercontext = context_user::instance($user->id);
    $editoroptions = [
        'maxfiles'   => EDITOR_UNLIMITED_FILES,
        'maxbytes'   => $CFG->maxbytes,
        'trusttext'  => false,
        'forcehttps' => false,
        'context'    => $usercontext,
    ];

    $user = file_prepare_standard_editor($user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
} else {
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
file_prepare_draft_area($draftitemid, $filemanagercontext->id, 'user', 'newicon', 0, $filemanageroptions);
$user->imagefile = $draftitemid;

$coursesarr = user_delegation_get_owned_courses();

// Create form.
$data = [
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
    'courses' => $coursesarr,
    'user' => $user
];

$userform = new user_editadvanced_form(new moodle_url($PAGE->url, ['returnto' => $returnto]), $data);

if ($userform->is_cancelled()) {
    redirect(new moodle_url('/blocks/user_delegation/myusers.php', ['id' => $blockid, 'course' => $course->id]));
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
    $createpassword = false;

    if ($usernew->id == -1) {
        // TODO check out if it makes sense to create account with this auth plugin and what to do with the password.
        unset($usernew->id);
        $createpassword = !empty($usernew->createpassword);
        unset($usernew->createpassword);
        $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, null, 'user', 'profile', null);
        $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
        $usernew->confirmed  = 1;
        $usernew->timecreated = time();
        if ($authplugin->is_internal()) {
            if ($createpassword or empty($usernew->newpassword)) {
                $usernew->password = '';
            } else {
                $usernew->password = hash_internal_user_password($usernew->newpassword);
            }
        } else {
            $usernew->password = AUTH_PASSWORD_NOT_CACHED;
        }
        $usernew->id = user_create_user($usernew, false, false);

        if (!$authplugin->is_internal() and $authplugin->can_change_password() and !empty($usernew->newpassword)) {
            if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                // Do not stop here, we need to finish user creation.
                debugging(get_string('cannotupdatepasswordonextauth', 'error', $usernew->auth), DEBUG_NONE);
            }
        }

        // Assign the created user on behalf of the creator.
        userdelegation::attach_user($USER->id, $usernew->id);

        userdelegation::bind_cohort($cohortarr, $usernew, $log);

        $usercreated = true;
    } else {
        $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
        // Pass a true old $user here.
        if (!$authplugin->user_update($user, $usernew)) {
            // Auth update failed.
            throw new \moodle_exception('cannotupdateuseronexauth', '', '', $user->auth);
        }
        user_update_user($usernew, false, false);

        // Set new password if specified.
        if (!empty($usernew->newpassword)) {
            if ($authplugin->can_change_password()) {
                if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                    throw new \moodle_exception('cannotupdatepasswordonextauth', '', '', $usernew->auth);
                }
                unset_user_preference('create_password', $usernew); // Prevent cron from generating the password.

                if (!empty($CFG->passwordchangelogout)) {
                    // We can use SID of other user safely here because they are unique,
                    // the problem here is we do not want to logout admin here when changing own password.
                    \core\session\manager::kill_user_sessions($usernew->id, session_id());
                }
                if (!empty($usernew->signoutofotherservices)) {
                    webservice::delete_user_ws_tokens($usernew->id);
                }
            }
        }
        $usercreated = false;

        userdelegation::bind_cohort($cohortarr, $usernew, $log);
    }

    $usercontext = context_user::instance($usernew->id);

    // Update preferences.
    useredit_update_user_preference($usernew);

    // Update tags.
    if (empty($USER->newadminuser) && isset($usernew->interests)) {
        useredit_update_interests($usernew, $usernew->interests);
    }

    // Update user picture.
    if (empty($USER->newadminuser)) {
        core_user::update_picture($usernew, $filemanageroptions);
    }

    // Update mail bounces.
    useredit_update_bounces($user, $usernew);

    // Update forum track preference.
    useredit_update_trackforums($user, $usernew);

    // Save custom profile fields data.
    profile_save_data($usernew);

    if (!empty($usernew->coursetoassign)) {
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $coursetoassign = $DB->get_record('course', ['id' => $usernew->coursetoassign], '*', MUST_EXIST);
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
                $enrolplugin->enrol_user($enrol, $usernew->id, $studentrole->id, time(), $end, ENROL_USER_ACTIVE);
            }
        }
    }

    if ($createpassword) {
        setnew_password_and_mail($usernew);
        unset_user_preference('create_password', $usernew);
        set_user_preference('auth_forcepasswordchange', 1, $usernew);
    }

    // Reload from db.
    $usernew = $DB->get_record('user', ['id' => $usernew->id]);

    // Trigger update/create event, after all fields are stored.
    if ($usercreated) {
        block_user_delegation::trigger_event('user_created', $usernew);
    } else {
        block_user_delegation::events_trigger('user_updated', $usernew);
    }

    if ($user->id == $USER->id) {
        // Override old $USER session variable.
        foreach ((array)$usernew as $variable => $value) {
            if ($variable === 'description' || $variable === 'password') {
                // These are not set for security and perf reasons.
                continue;
            }
            $USER->$variable = $value;
        }
        redirect(new mooodle_url('/user/view.php', ['id' => $USER->id, 'course' => $course->id]));
    } else {
        redirect(new moodle_url('/blocks/user_delegation/myusers.php', ['course' => $course->id, 'id' => $blockid]));
    }
    // Never reached.
}

// Display page header.
if ($user->id == -1 || ($user->id != $USER->id)) {
    if ($user->id == -1) {
        echo $OUTPUT->header();
    } else {
        $streditmyprofile = get_string('editmyprofile');
        $userfullname = fullname($user, true);
        $PAGE->set_heading($userfullname);
        $coursename = $course->id !== SITEID ? "$course->shortname" : '';
        $PAGE->set_title("$streditmyprofile: $userfullname" . moodle_page::TITLE_SEPARATOR . $coursename);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($userfullname);
    }
} else {
    $streditmyprofile = get_string('editmyprofile');
    $strparticipants  = get_string('participants');
    $strnewuser       = get_string('newuser');
    $userfullname     = fullname($user, true);

    $PAGE->set_title("$course->shortname: $streditmyprofile");
    if (has_capability('moodle/course:viewparticipants', $coursecontext)
            || has_capability('moodle/site:viewparticipants', $systemcontext)) {
        $PAGE->navbar->add($strparticipants, new moodle_url('/user/index.php', ['id' => $course->id]));
    }
    $params = ['id' => $user->id, 'course' => $course->id];
    $PAGE->navbar->add($userfullname, new moodle_url('/user/view.php', $params));
    $PAGE->navbar->add($streditmyprofile);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_focuscontrol('');
    echo $OUTPUT->header();
}

// Prepare a div for user check reporting.
echo('<div id="existing_users" style="display: none"></div>');

// Finally display THE form.
echo('<div style="font-size:11px;">');
$userform->set_data($user);
$userform->display();
echo('</div>');

// And proper footer.
echo $OUTPUT->footer();
