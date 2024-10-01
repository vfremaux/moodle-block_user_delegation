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
 * Main block class
 *
 * @package block_user_delegation
 * @author Wafa Adham & Valery Fremaux
 * @copyright  2013 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * phpcs:disable moodle.Commenting.ValidTags.Invalid
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/user_delegation/lib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');
require_once($CFG->dirroot.'/user/lib.php');
if (is_dir($CFG->dirroot.'/local/moodlescript')) {
    require_once($CFG->dirroot.'/local/moodlescript/lib.php');
}

/**
 * Main block class
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
class block_user_delegation extends block_base {

    /**
     * Standard init
     */
    public function init() {
        $this->title = get_string('user_delegation', 'block_user_delegation');
    }

    /**
     * Where the block can be added.
     */
    public function applicable_formats() {
        return ['all' => true, 'my' => true];
    }

    /**
     * Can we have several inténces in context ?
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Can we configure the instance ?
     */
    public function instance_allow_config() {
        $blockcontext = context_block::instance($this->instance->id);
        return has_capability('block/user_delegation:configure', $blockcontext);
    }

    /**
     * Can the user edit the block ?
     */
    public function user_can_edit() {
        if (has_capability('block/user_delegation:configure', $this->context)) {
            return true;
        }
        return false;
    }

    /**
     * Does the block has global config ?
     */
    public function has_config() {
        return true;
    }

    /**
     * Main content
     */
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
            if (!self::has_capability_somewhere('block/user_delegation:view', true, true, false, $contexts)) {
                return $this->content;
            }
            $canbulkimport = self::has_capability_somewhere('block/user_delegation:canbulkaddusers', true, true, false, $contexts);
        }

        $importusersstr = get_string('importusers', 'block_user_delegation');
        $viewmyusersstr = get_string('viewmyusers', 'block_user_delegation');

        $menu = '<ul>';
        $params = ['id' => $this->instance->id, 'course' => $COURSE->id];
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
     * @param string $capability
     * @param bool $excludesystem
     * @param bool $excludesite
     * @param bool $doanything
     * @param string $contextlevels
     */
    public static function has_capability_somewhere($capability, $excludesystem = true, $excludesite = true, $doanything = false, $contextlevels = '') {
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

    /**
     * Bulk process users.
     * @param object $data
     */
    public static function process_bulk($data) {
        global $USER, $COURSE, $CFG;

        $report = '';
        $config = get_config('block_user_delegation');
        $script = $config->prescript ?? '';

        $i = 0;
        foreach ($data->firstname as $fn) {

            if (empty($fn) && empty($data->lastname[$i]) && empty($data->email[$i])) {
                continue;
            }

            $user = new StdClass();
            $user->firstname = $fn;
            $user->lastname = $data->lastname[$i];
            $user->email = $data->email[$i];

            $user->username = self::compute_username($user, $olduser);

            if (!$olduser) {
                $user->id = user_create_user($user);

                // Assign the created user on behalf of the creator.
                userdelegation::attach_user($USER->id, $user->id);

                // Check and record a user.
                set_user_preference('create_password', 1, $user);
                $olduser = $user;
                $report .= get_string('userbulkcreated', 'block_user_delegation', $user);
            } else {
                $report .= get_string('userbulkexists', 'block_user_delegation', $olduser);
            }

            // Now we have a olduser, enrol it and group it with the manager.

            $script .= "
                ENROL username:{$olduser->username} INTO current AS shortname:student USING manual
            ";
            $i++;
        }

        // Enrol manager if we have some.

        if (!empty($data->manager)) {
            $script .= "
                ENROL id:{$data->manager} INTO current AS shortname:manager USING manual
            ";
        }

        $globalcontext = [
            'courseid' => $COURSE->id,
            'userid' => $USER->id,
        ];

        if (is_dir($CFG->dirroot.'/local/moodlescript')) {

            $script = $config->postscript ?? '';

            // Make a script engine and run it.
            $engine = local_moodlescript_get_engine($script);
            $report = local_moodlescript_execute($engine, (object) $globalcontext);
        }

        return $report;
    }

    /**
     * Computes a free username or return a very potential old user record.
     * @param object $user
     * @param object $moodleuser
     */
    protected static function compute_username($user, &$moodleuser) {
        global $DB;
        static $antiloop = 0;

        $username = core_text::strtolower($user->firstname)[0].core_text::strtolower($user->lastname);
        $baseusername = $username;

        $pass = false;
        $index = 1;
        $antiloop = 0;
        while ((($olduser = $DB->get_record('user', ['username' => $username])) || $pass) && ($antiloop < 10)) {

            if (!$olduser) {
                break;
            }

            if (($olduser->deleted == 1) && ($olduser->email == $user->email)) {
                $moodleuser = $olduser;
                $pass = true;
            }

            if ($olduser->email != $user->email) {
                $username = $baseusername.$index;
                $index++;
                echo "Trying with $username <br/>";
            } else {
                $moodleuser = $olduser;
                $pass = true;
            }

            $antiloop++;
        }

        return $username;
    }

    /**
     * Utility function
     * @param string $text
     */
    public static function trim_utf8_bom($text) {
        return core_text::trim_utf8_bom($text);
    }

    /**
     * Utility function
     * @param string $text
     * @todo use core_text functions.
     */
    public static function strtolower($text) {
        return core_text::strtolower($text);
    }

    /**
     * Get user's fields of interest.
     * @param int $id
     */
    public static function user_interests($id) {
        return core_tag_tag::get_item_tags_array('core', 'user', $id);
    }

    /**
     * Trigger a user event
     * @param string $eventname
     * @param object $usernew
     */
    public static function trigger_event($eventname, $usernew) {
        $class = "\\core\\event\\".$eventname;
        $func = $class."::create_from_userid";
        $func($usernew->id)->trigger();
    }
}
