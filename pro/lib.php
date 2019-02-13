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
 * @package   block_use_stats
 * @category  blocks
 * @copyright 2012 Wafa Adham,, Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function block_user_delegation_get_owned_courses() {
    global $USER;

    $coursesarr = array('0' => get_string('noassign', 'block_user_delegation'));
    $ownedcourses = userdelegation::get_user_courses_bycap($USER->id, 'block/user_delegation:owncourse', false);
    if ($ownedcourses) {
        foreach ($ownedcourses as $c) {
            $coursesarr[$c->id] = $c->fullname;
        }
    }
}

function block_user_delegation_enrol($data, $user, $theblock) {
    global $DB;

    $coursegroup = null;

    $studentrole = $DB->get_record('role', array('shortname' => 'student'));
    $course = $DB->get_record('course', array('id' => $data->coursetoassign));
    $coursecontext = context_course::instance($course->id);

    // Compute enrolment end time if required by block config.
    $end = 0;
    if (!empty($theblock->config->enrolduration)) {
        $end = time() + DAYSECS * $theblock->config->enrolduration;
    }

    if ($course) {
        // TODO : Rewrite assignation.
        $params = array('enrol' => 'manual', 'courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED);
        if ($enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
            $enrol = reset($enrols);
            $enrolplugin = enrol_get_plugin('manual');
            $enrolplugin->enrol_user($enrol, $user->id, $studentrole->id, time(), $end, ENROL_USER_ACTIVE);
            $message = get_string('userenrolled', 'block_user_delegation', $course->shortname);
            $log .= useradmin_uploaduser_notify_success($linenum, $message, $user->id, $user->username);
        }
    }

    if (is_null($coursegroup) && !empty($data->newgroupname)) {
        $newgroup = new Stdclass();
        $newgroup->courseid = $course->id;
        $newgroup->name = $data->newgroupname;
        $newgroup->description = '';
        $newgroup->descriptionformat = 0;
        $newgroup->timecreated = time();
        $newgroup->timemodified = time();
        $params = array('courseid' => $newgroup->courseid, 'name' => $newgroup->name);
        if (!$coursegroup = $DB->get_record('groups', $params)) {
            $message = get_string('groupcreated', 'block_user_delegation', $newgroup->name);
            $log .= useradmin_uploaduser_notify_success($linenum, $message, $user->id, $user->username);
            $newgroup->id = $DB->insert_record('groups', $newgroup);

            // Invalidate the grouping cache for the course.
            cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($course->id));

            // Trigger group event.
            $params = array(
                'context' => $coursecontext,
                'objectid' => $newgroup->id
            );
            $event = \core\event\group_created::create($params);
            $event->trigger();
            $coursegroup = $newgroup;
        }

        // Self enrol ourself in the created group to take control.
        $groupmember = new StdClass();
        $groupmember->groupid = $coursegroup->id;
        $groupmember->userid = $USER->id;
        $groupmember->timeadded = time();
        if (!$DB->record_exists('groups_members', array('groupid' => $coursegroup->id, 'userid' => $USER->id))) {
            $DB->insert_record('groups_members', $groupmember);
            $message = get_string('groupadded', 'block_user_delegation', $coursegroup->name);
            $log .= useradmin_uploaduser_notify_success($linenum, $message, $USER->id, $USER->username);
        }
    } else if (!empty($data->grouptoassign)) {
        $coursegroup = $DB->get_record('groups', array('id' => $data->grouptoassign));
    }

    if (!is_null($coursegroup)) {
        $groupmember = new StdClass();
        $groupmember->groupid = $coursegroup->id;
        $groupmember->userid = $user->id;
        $groupmember->timeadded = time();
        if (!$DB->record_exists('groups_members', array('groupid' => $coursegroup->id, 'userid' => $user->id))) {
            $DB->insert_record('groups_members', $groupmember);
            $message = get_string('groupadded', 'block_user_delegation', $coursegroup->name);
            $log .= useradmin_uploaduser_notify_success($linenum, $message, $user->id, $user->username);
        }
    }
}