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

include('../../../config.php');

require_once($CFG->dirroot.'/blocks/user_delegation/pro/forms/bulk_user_form.php');

$PAGE->https_required();

$blockid = required_param('blockid', PARAM_INT);    // The block instance id.
$courseid = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).

$params =  array('blockid' => $blockid, 'course' => $courseid);
$url = new moodle_url('/blocks/user_delegation/pro/edit_bulk.php', $params);
$PAGE->set_url($url);

if (!$instance = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('badblockid', 'block_user_delegation');
}

$theblock = block_instance('user_delegation', $instance);

require_login($courseid);
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);
$PAGE->requires->js('/blocks/user_delegation/pro/js/formrepeaterrors.js', true);

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

$coursecontext = context_course::instance($courseid);
$managers = get_users_by_capability($coursecontext, 'moodle/course:viewhiddenactivities', 'u.id,'.get_all_user_name_fields(true, 'u'));
$managersmenu = array('' => get_string('unmanaged', 'block_user_delegation'));
if (!empty($managers)) {
    foreach($managers as $mid => $manager) {
        $managersmenu[$mid] = fullname($manager);
    }
}

$mform = new BulkUser_Form($url, array('managers' => $managersmenu));

if ($mform->is_cancelled()) {
    if ($courseid == SITED) {
        $returnurl = $CFG->wwwroot;
    } else {
        $returnurl = new moodle_url('/course/view.php', array('id' => $courseid));
    }
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    block_user_delegation::process_bulk($data);

    echo $OUTPUT->header();

    $returnurl = new moodle_url('/course/view.php', array('course' => $courseid));
    echo $OUTPUT->continue_button($returnurl);
    echo $OUTPUT->footer();
    die;
}

$formerrors = $mform->get_errors();

$jserrors = json_encode($formerrors);

echo $OUTPUT->header();

// defaults manager as me.
$mformdata = new StdClass;
$mformdata->manager = $USER->id;
$mform->set_data($mformdata);
$mform->display();

echo '
<script type="text/javascript">
var formerrors = '.$jserrors.';
$( document ).ready( markerrors );
</script>
';

echo $OUTPUT->footer();