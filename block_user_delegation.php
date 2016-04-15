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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');

/**
 * @package block_user_delegation
 * @category  blocks
 * @authors Wafa Adham & Valery Fremaux
 * @copyright  2013 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_user_delegation extends block_base {

    function init() {
        $this->title = get_string('user_delegation', 'block_user_delegation');
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        // $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('new_user_delegation', 'block_userdelegation'));
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_allow_config() {
        return true;
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $COURSE;

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
            if (!block_user_delegation::has_capability_somewhere('block/user_delegation:view')) {
                return $this->content;
            }
            $canbulkimport = block_user_delegation::has_capability_somewhere('block/user_delegation:canbulkaddusers');
        }

        $importusersstr = get_string('importusers', 'block_user_delegation');
        $viewmyusersstr = get_string('viewmyusers', 'block_user_delegation');
        $viewmycoursesstr = get_string('viewmycourses', 'block_user_delegation');

        $menu = '<ul>';
        $linkurl = new moodle_url('/blocks/user_delegation/myusers.php', array('id' => $this->instance->id, 'course' => $COURSE->id));
        $menu .= ' <li><a href="'.$linkurl.'">'.$viewmyusersstr.'</a></li>';

        $userownedcourses = userdelegation::get_user_courses_bycap($USER->id, 'block/user_delegation:owncourse', false);

        if (!empty($userownedcourses)) {
            $linkurl = new moodle_url('/blocks/user_delegation/mycourses.php', array('id' => $this->instance->id, 'course' => $COURSE->id));
            $menu .= ' <li><a href="'.$linkurl.'">'.$viewmycoursesstr.'</a></li>';
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
    static function has_capability_somewhere($capability, $excludesystem = true, $excludesite = true, $doanything = false) {
        global $USER;

        $hassome = get_user_capability_course($capability, $USER->id, false);
        if ($excludesite && !empty($hassome) && array_key_exists(SITEID, $hassome)) {
            unset($hassome[SITEID]);
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
