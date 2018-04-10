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
 * @package block_user_delegation
 * @category  blocks
 * @authors Wafa Adham & Valery Fremaux
 * @copyright  2013 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/user_delegation/lib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');

class block_user_delegation extends block_base {

    public function init() {
        $this->title = get_string('user_delegation', 'block_user_delegation');
    }

    public function applicable_formats() {
        return array('all' => true, 'my' => true);
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function instance_allow_config() {
        $blockcontext = context_block::instance($this->instance->id);
        return has_capability('block/user_delegation:configure', $blockcontext);
    }

    public function user_can_edit() {
        if (has_capability('block/user_delegation:configure', $this->context)) {
            return true;
        }
        return false;
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $USER, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $context = context_course::instance($COURSE->id);
        $blockcontext = context_block::instance($this->instance->id);

        $canbulkimport = false;
        if ($COURSE->id != SITEID) {
            if (!has_capability('block/user_delegation:view', $blockcontext)) {
                return $this->content;
            }
            $canbulkimport = has_capability('block/user_delegation:canbulkaddusers', $blockcontext);
        } else {
            $contexts = CONTEXT_COURSE.','.CONTEXT_COURSECAT;
            if (!block_user_delegation::has_capability_somewhere('block/user_delegation:view', true, true, false, $contexts)) {
                return $this->content;
            }
            $canbulkimport = block_user_delegation::has_capability_somewhere('block/user_delegation:canbulkaddusers', true, true, false, $contexts);
        }

        $importusersstr = get_string('importusers', 'block_user_delegation');
        $viewmyusersstr = get_string('viewmyusers', 'block_user_delegation');

        $menu = '<ul>';
        $params = array('id' => $this->instance->id, 'course' => $COURSE->id);
        $linkurl = new moodle_url('/blocks/user_delegation/myusers.php', $params);
        $menu .= ' <li><a href="'.$linkurl.'">'.$viewmyusersstr.'</a></li>';

        $userownedcourses = userdelegation::get_user_courses_bycap($USER->id, 'block/user_delegation:owncourse', false);

        if (block_user_delegation_supports_feature('users/enrol')) {
            if (!empty($userownedcourses)) {
                $viewmycoursesstr = get_string('viewmycourses', 'block_user_delegation');
                $linkurl = new moodle_url('/blocks/user_delegation/pro/mycourses.php', $params);
                $menu .= ' <li><a href="'.$linkurl.'">'.$viewmycoursesstr.'</a></li>';
            }
        }

        $menu .= '</ul>';

        $this->content = new stdClass;
        $this->content->text = $menu;
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * This function makes all the necessary calls to {@link restore_decode_content_links_worker()}
     * function in order to decode contents of this block from the backup 
     * format to destination site/course in order to mantain inter-activities 
     * working in the backup/restore process. 
     * 
     * This is called from {@link restore_decode_content_links()} function in the restore process.
     *
     * NOTE: There is no block instance when this method is called.
     *
     * @param object $restore Standard restore object
     * @return boolean
     **/
    public function get_required_javascript() {
        $this->page->requires->jquery();
    }

    /**
     * checks if a user has a some named capability effective somewhere in a course.
     */
    static public function has_capability_somewhere($capability, $excludesystem = true, $excludesite = true, $doanything = false, $contextlevels = '') {
        global $USER, $DB;

        $contextclause = '';

        if ($contextlevels) {
            list($sql, $params) = $DB->get_in_or_equal(explode(',', $contextlevels), SQL_PARAMS_NAMED);
            $contextclause = "
               AND ctx.contextlevel $sql
            ";
        }
        $params['capability'] = $capability;
        $params['userid'] = $USER->id;

        // This is a a quick rough query that may not handle all role override possibility.

        $sql = "
            SELECT DISTINCT
                CONCAT(ctx.contextlevel, ':', ctx.instanceid) as ctxkey,
                ctx.id as ctkid
            FROM
                {role_capabilities} rc,
                {role_assignments} ra,
                {context} ctx
            WHERE
                rc.roleid = ra.roleid AND
                ra.contextid = ctx.id AND
                rc.capability = :capability
                $contextclause
                AND ra.userid = :userid AND
                rc.permission = 1
        ";
        $hassome = $DB->get_records_sql($sql, $params);

        $key = CONTEXT_COURSE.':'.SITEID;
        if ($excludesite && !empty($hassome) && array_key_exists($key, $hassome)) {
            unset($hassome[$key]);
        }

        if (!empty($hassome)) {
            return true;
        }

        $systemcontext = context_system::instance();
        if (!$excludesystem && has_capability($capability, $systemcontext, $USER->id, $doanything)) {
            return true;
        }

        return false;
    }
}
