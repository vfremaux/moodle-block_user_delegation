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
require_once($CFG->dirroot.'/blocks/user_delegation/block_user_delegation.php');
require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');

$blockid = required_param('id', PARAM_INT);   // block id (defaults to Site)
$courseid = optional_param('course', SITEID, PARAM_INT);   // course id (defaults to Site)
$cancelemailchange = optional_param('cancelemailchange', false, PARAM_INT);   // course id (defaults to Site)
$page = optional_param('page', 1, PARAM_INT);   // course id (defaults to Site)

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/user_delegation/js/mycourses.php?id='.$courseid);

$url = new moodle_url('/blocks/user_delegation/mycourses.php', array('id' => $blockid, 'course' => $courseid));
$PAGE->set_url($url);

require_login();
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

// Security.

$blockcontext = context_block::instance($blockid);   // Course context

$cancreate = false;
if (has_capability('block/user_delegation:cancreateusers', $blockcontext)) {
    $cancreate = true;
} else {
    // Do in two steps to optimize response time.
    if (block_user_delegation::has_capability_somewhere('block/user_delegation:cancreateusers')) {
        $cancreate = true;
    }
}

$canaddbulk = false;
if (has_capability('block/user_delegation:canbulkaddusers', $blockcontext)) {
    $canaddbulk = true;
} else {
    if (block_user_delegation::has_capability_somewhere('block/user_delegation:canbulkaddusers')) {
        $canaddbulk = true;
    }
}

$candelete = false;
if (has_capability('block/user_delegation:candeleteusers', $blockcontext)) {
    $candelete = true;
} else {
    if (block_user_delegation::has_capability_somewhere('block/user_delegation:candeleteusers')) {
        $candelete = true;
    }
}

// Guest can not edit

if (isguestuser()) {
    print_error('guestnoeditprofile');
}

$PAGE->set_title(get_string('mycourses', 'block_user_delegation'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('user_delegation', 'block_user_delegation'));
$PAGE->navbar->add(get_string('mycourses', 'block_user_delegation'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

$perpage = 10;
$user_courses = userdelegation::get_user_courses_bycap($USER->id, 'block/user_delegation:owncourse', false);
$coursescount = count($user_courses);

echo '<div class="userpage-toolbar">';
echo '<img src="'.$OUTPUT->pix_url('users', 'block_user_delegation').'" /> ';
$usersurl = new moodle_url('/blocks/user_delegation/myusers.php');
$usersurl->param('id', $blockid);
if ($course->id > SITEID) {
    $usersurl->param('course', $course->id);
}
echo '<a href="'.$usersurl.'">'.get_string('myusers', 'block_user_delegation').'</a>'; 
echo '</div>';

echo $OUTPUT->heading(get_string('mydelegatedcourses', 'block_user_delegation'));

$totalcoursesstr = get_string('totalcourses', 'block_user_delegation');
echo '<div id="user-delegation-toolbar">'; //toolbar
echo '<div><b>'.$totalcoursesstr.': </b>'.$coursescount.'</div>';
echo '</div>';

$changeenrolmentstr = get_string('changeenrolment', 'block_user_delegation');
$uploadusersstr = get_string('uploadusers', 'block_user_delegation');

$myusers = userdelegation::get_delegated_users($USER->id);

if (!empty($user_courses)) {
    foreach ($user_courses as $c) {
        $c = $DB->get_record('course', array('id' => $c->id));
        $coursecontext = context_course::instance($c->id);
        echo '<div class="user-delegation-course-cont">'; //course-cont
        echo '<div>';
        $linkurl = new moodle_url('/course/view.php', array('id' => $c->id));
        echo '<div><h2><a href="'.$linkurl.'">'.$c->fullname.'</a></h2></div>';
        echo '<div>';
        /*
        $linkurl = new moodle_url('/blocks/user_delegation/myusers.php', array('course' => $course->id, 'id' => $blockid));
        echo '<div><b><a href="'.$linkurl.'" >'.$changeenrolmentstr.'</a></b></div>';
        */

        if ($canaddbulk) {
            $params = array('course' => $course->id, 'coursetoassign' => $c->id, 'id' => $blockid);
            $linkurl = new moodle_url('/blocks/user_delegation/uploaduser.php', $params);
            echo '<div><b><img src="'.$OUTPUT->pix_url('upload', 'block_user_delegation').'" /><a href="'.$linkurl.'" >'.$uploadusersstr.'</a></b></div>';
        }

        echo '</div>';
        echo '</div>';

        echo '<p></p>';

        $course_teachers = get_users_by_capability($coursecontext, 'moodle/course:grade', 'u.id,'.get_all_user_name_fields(true, 'u'));
        $teachersstr = get_string('teachers', 'block_user_delegation');
        echo "<b>$teachersstr <a href='#' class='courseteachers-btn' id='".$c->id."'  >+</a></b>";
        echo '<div class="cteacherscont" id="cteacherscont-'.$c->id.'">';//all users 

        if (!empty($course_teachers)) {
            foreach ($course_teachers as $uid => $u) {
                if (!array_key_exists($u->id, $myusers)) {
                    unset($course_teachers[$uid]);
                };
            }
        }
        if (!empty($course_teachers)) {
              foreach ($course_teachers as $u) {
                echo '<div class="user-delegation-user"><img src="'.$OUTPUT->pix_url('user-teacher', 'block_user_delegation').'" /> '.$u->firstname.' '.$u->lastname.' </div>';
                unset($myusers[$u->id]);
            }
        } else {
            echo '<div class="user-delegation-user">'.get_string('noteachers', 'block_user_delegation').'</div>';
        }
        echo '</div>';//allteachers

        $course_students = get_enrolled_users($coursecontext);
        $studentsstr = get_string('students');

        echo '</br>';
        echo "<b>$studentsstr <a href='#' class='coursestudents-btn' id='".$c->id."'  >+</a></b>";
        echo '<div class="cstudentscont" id="cstudentscont-'.$c->id.'">';//all users 
        if (!empty($course_students)) {
            foreach ($course_students as $uid => $u) {
                if (!array_key_exists($u->id, $myusers)) {
                    unset($course_students[$uid]);
                };
            }
        }
        if (!empty($course_students)) {
            foreach ($course_students as $u) {
                $groups = groups_get_all_groups($c->id, $u->id);
                $groupnamestr = '';
                if (!empty($groups)) {
                    $groupnames = array();
                    foreach ($groups as $g) {
                        $groupnames[] = $g->name;
                    }
                    $groupnamesstr = ' ('.implode(', ', $groupnames).')';
                }
                echo '<div class="user-delegation-user"><img src="'.$OUTPUT->pix_url('user', 'block_user_delegation').'" /> '.$u->firstname.' '.$u->lastname.' '.$groupnamesstr.'</div>';
                unset($myusers[$u->id]);
            }
        } else {
            echo '<div class="user-delegation-user">'.get_string('nostudents', 'block_user_delegation').'</div>';
        }
        echo '</div>';//allusers
        echo '</div>';
    }
    echo $OUTPUT->paging_bar($coursescount, $page, $perpage, 'mycourses.php');
} else {
    echo '<br/>';
    echo $OUTPUT->box(get_string('noownedcourses', 'block_user_delegation'));
    echo '<br/>';
}

// Print all unassigned users belonging to me
if (!empty($myusers)) {
    echo '<div class="user-delegation-course-cont">'; //course-cont
    echo '<div>';
    echo '<div><h2>'.get_string('unassignedusers', 'block_user_delegation').'</h2></div>';
    echo '<div>';
    foreach ($myusers as $u) {
        echo '<div class="user-delegation-user"><img src="'.$OUTPUT->pix_url('user', 'block_user_delegation').'" /> '.$u->firstname.' '.$u->lastname.' </div>';
    }
    echo '</div>';
    echo '</div>';
}

if ($courseid == SITEID) {
    echo '<center><br/>';
    echo $OUTPUT->single_button($CFG->wwwroot, get_string('backtohome', 'block_user_delegation'), 'get');
    echo '<br/><center>';
} else {
    echo '<center><br/>';
    echo $OUTPUT->single_button(new moodle_url('/course/view.php', array('id' => $course->id)), get_string('backtocourse', 'block_user_delegation'), 'get');
    echo '<br/><center>';
}

echo $OUTPUT->footer();
