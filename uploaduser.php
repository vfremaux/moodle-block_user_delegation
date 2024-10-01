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
 * Upload users
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * Bulk user registration script from a comma separated file
 * Returns list of users with their user ids
 * Based on admin/userupload.php.
 * Modified by Lorenzo Nicora and included in useradmin block
 */

require('../../config.php');
require_once($CFG->libdir.'/uploadlib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/useradminlib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');
require_once($CFG->dirroot.'/blocks/user_delegation/block_user_delegation.php');
require_once($CFG->dirroot.'/blocks/user_delegation/forms/uploaduser_form.php');

$url = new moodle_url('/blocks/user_delegation/uploaduser.php');
$PAGE->set_url($url);

$courseid = optional_param('course', SITEID, PARAM_INT);
$blockid = required_param('id', PARAM_INT); // The block id.

$instance = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);

$theblock = block_instance('user_delegation', $instance);

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/user_delegation/js/uploaduser.php?id='.$courseid);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST)

$config = get_config('block_user_delegation');

// Security.

require_login();
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

$blockcontext = context_block::instance($blockid); // Course context.
$canaddbulk = false;
if (has_capability('block/user_delegation:canbulkaddusers', $blockcontext)) {
    $canaddbulk = true;
} else {
    if (block_user_delegation::has_capability_somewhere('block/user_delegation:canbulkaddusers')) {
        $canaddbulk = true;
    }
}

if (!$canaddbulk) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$csvseparator = optional_param('fieldseparator', $config->csvseparator, PARAM_TEXT);

$defaultnomail = 'NOMAIL';
if (isset($CFG->CSV_NOMAIL)) {
    $defaultnomail = $CFG->CSV_NOMAIL;
}

$defaultfakedomain = 'NO.MAIL';
if (isset($CFG->CSV_FAKEMAILDOMAIN)) {
    $defaultfakedomain = $CFG->CSV_FAKEMAILDOMAIN;
}

// Need to convert to UTF-8?

$streditmyprofile = get_string('editmyprofile');
$stradministration = get_string('administration');
$strfile = get_string('file');
$struser = get_string('user');
$strusers = get_string('users');
$strusersnew = get_string('usersnew');
$strusersupdated = get_string('usersupdated', 'block_user_delegation');
$struploadusers = get_string('uploadusers', 'block_user_delegation');
$straddnewuser = get_string('importuser', 'block_user_delegation');

// Print the header.
$struploaduser = get_string('uploadusers', 'block_user_delegation');
$strblockname = get_string('blockname', 'block_user_delegation');

$PAGE->set_context($usercontext);
$params = ['id' => $blockid, 'course' => $courseid];
$PAGE->navbar->add($strblockname, new moodle_url('/blocks/user_delegation/myusers.php', $params));
$PAGE->navbar->add($struploaduser);
$PAGE->set_pagelayout('admin');

$coursesarr = null;
if (block_user_delegation_supports_feature('users/enrol')) {
    include_once($CFG->dirroot.'/blocks/user_delegation/pro/lib.php');
    $coursearr = block_user_delegation_get_owned_courses();
}

$mform = new UploadUserForm($url, ['courses' => $coursesarr]);

if ($mform->is_cancelled()) {
    $myusersurl = new moodle_url('/blocks/user_delegation/myusers.php', ['course' => $course->id, 'id' => $blockid]);
    redirect($myusersurl);
}
if ($data = $mform->get_data()) {
    include($CFG->dirroot.'/blocks/user_delegation/uploaduser.controller.php');
}

// Print the form.
echo $OUTPUT->header();

echo $OUTPUT->heading_with_help($struploaduser, 'uploadusers', 'block_user_delegation');

echo '<div class="userpage-toolbar">';
echo $OUTPUT->pix_icon('users', '', 'block_user_delegation');
$usersreturnurl = new moodle_url('/blocks/user_delegation/myusers.php', ['id' => $blockid, 'course' => $courseid]);
echo '<a href="'.$usersreturnurl.'">'.get_string('myusers', 'block_user_delegation').'</a>';
print '</div>';

echo '<center>';

$formdata = new StdClass();
$formdata->id = $blockid;
$formdata->course = $courseid;
$formdata->coursetoassign = optional_param('coursetoassign', '', PARAM_INT);
if ($courseid > SITEID) {
    $formdata->nomail = $defaultnomail;
}
$mform->set_data($formdata);
$mform->display();

echo $OUTPUT->footer();

