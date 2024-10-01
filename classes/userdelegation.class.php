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
 * User Delegation manager
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/user_delegation/compatlib.php');

use block_user_delegation\compat;

/**
 * Main manager class
 */
class userdelegation {

    /**
     * get given reponsible users.
     *
     * @param int $power_uid responsible user id .
     * @param mixed $order  order field
     * @return mixed  array of user object
     */
    public static function get_delegated_users($poweruid, $sort = 'lastaccess', $dir = 'ASC', $page = 0, $recordsperpage = 0,
            $search='', $firstinitial = '', $lastinitial = '', $extraselect = []) {
        global $DB, $USER;

        $fullname = " CONCAT(firstname, '', lastname) ";
        $select = "deleted <> '1'";

        if (!empty($extraselect[0])) {
            $extrasql = $extraselect[0];
        } else {
            $extrasql = '';
        }

        if (!empty($extraselect[1])) {
            $params = $extraselect[1];
        } else {
            $params = [];
        }

        if (!empty($search)) {
            $search = trim($search);
            $like1 = $DB->sql_like($fullname, ':search1', false, false);
            $like2 = $DB->sql_like('email', ':search2', false, false);
            $like3 = $DB->sql_like('username', ':search3', false, false);
            $select .= " AND ($like1 OR $like2 OR $like3 ";
            $params += ['search1' => $search, 'search2' => $search, 'seach3' => $search];
        }

        if ($firstinitial) {
            $select .= ' AND firstname '. $like .' \''. $firstinitial .'%\' ';
        }

        if ($lastinitial) {
            $select .= ' AND lastname '. $like .' \''. $lastinitial .'%\' ';
        }

        if ($extrasql) {
            $select .= " AND $extrasql ";
        }

        if ($sort) {
            $sort = ' ORDER BY '. $sort .' '. $dir;
        }

        $sql = "
            SELECT DISTINCT
                u.*,
                ctx.id as ctxid
            FROM
                {user} u,
                {role_assignments} ra,
                {context} ctx
            WHERE
                ra.userid = {$poweruid} AND
                ra.contextid = ctx.id AND
                ctx.contextlevel = ".CONTEXT_USER." AND
                ctx.instanceid = u.id AND
                {$select}
                {$sort}
        ";
        $users = $DB->get_records_sql($sql, $params, $page, $recordsperpage);

        $behalfedusers = [];
        foreach ($users as $u) {
            $usercontext = context_user::instance($u->id);
            if (has_capability('block/user_delegation:isbehalfof', $usercontext, $USER->id, false)) {
                $behalfedusers[$u->id] = $u;
            }
        }

        return $behalfedusers;
    }

    /**
     *
     */
    public static function check_user_exist($email, $firstname, $lastname) {
        global $DB;

        $params = ['email' => $email, 'firstname' => $firstname, 'lastname' => $lastname];
        $result = $DB->get_record('user', $params);
        return $result;
    }

    /**
     * Attach a user as behalf of another user. this means :
     * Give user Student role on power user context, and give supervisor role to power user on user context
     *
     * TODO : Study how to generalise using capability tests
     *  $behalvingrole = get_roles_with_capability('block/user_delegation:hasasbehalf', CAP_ALLOW);
     *  $behalvedrole = get_roles_with_capability('block/user_delegation:isbehalfof', CAP_ALLOW);
     */
    public static function attach_user($poweruid, $fellowuid) {
        global $DB, $COURSE;

        $config = get_config('block_user_delegation');

        $fellowcontext = context_user::instance($fellowuid);
        $supervisorcontext = context_user::instance($poweruid);

        $supervisorroleid = $DB->get_field('role', 'id', ['shortname' => $config->corole]);
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);

        if (!$supervisorroleid) {
            $returnurl = new moodle_url('/course/view.php', ['id' => $COURSE->id]);
            throw new moodle_exception('errormisconfig', 'block_user_delegation', $config->corole, $returnurl);
        }

        $result = role_assign($supervisorroleid, $poweruid, $fellowcontext->id);
        $result = $result && role_assign($studentroleid, $fellowuid, $supervisorcontext->id);

        return (int)$result;
    }

    /**
     * Unattach a user from behalf of another user
     * TODO : Study how to generalise using capability tests
     *  $behalvingrole = get_roles_with_capability('block/user_delegation:hasasbehalf', CAP_ALLOW);
     *  $behalvedrole = get_roles_with_capability('block/user_delegation:isbehalfof', CAP_ALLOW);
     */
    public static function detach_user($poweruid, $fellowuid) {
        global $DB;

        $config = get_config('block_user_delegation');

        $fellowcontext = context_user::instance($fellowuid);
        $supervisorcontext = context_user::instance($poweruid);

        $supervisorroleid = $DB->get_field('role', 'id', ['shortname' => $config->corole]);
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);

        $result = role_unassign($supervisorroleid, $poweruid, $fellowcontext->id);
        $result = $result && role_unassign($studentroleid, $fellowuid, $supervisorcontext->id);

        return (int)$result;
    }

    /**
     * get the course list of the current user.
     * @return array of courses or empty array
     */
    public static function get_owned_courses() {
        global $DB, $USER;

        $config = get_config('block_user_delegation');

        $sql = "
            SELECT
                c.*
            FROM
                {course} c,
                {context} ctx,
                {role_assignments} ra
            WHERE
                c.id = ctx.instanceid AND
                ctx.contextlevel = ? AND
                ra.contextid = ctx.id AND
                ra.roleid = ? AND
                ra.userid = ?
        ";

        $role = $DB->get_record('role', ['shortname' => $config->corole]);
        $courses = $DB->get_records_sql($sql, [CONTEXT_COURSE, $role->id, $USER->id]);

        return $courses;
    }

    /**
     * checks if an owner is owned by anyone else
     * @return array of owners or false
     */
    public static function has_other_owners($userid) {
        global $USER;

        if ($userid <= 0) {
            return false;
        }
        $personalcontext = context_user::instance($userid);

        $fields = compat::user_fields('u');
        $sort = 'lastname,firstname';
        $owners = get_users_by_capability($personalcontext, 'block/user_delegation:isbehalfof', $fields, $sort, 0, 0, '', '', true);

        if ($owners) {
            foreach ($owners as $o) {
                if ($o->id != $USER->id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get an array of courses where cap requested is available
     *
     * @param int    $userid A user id. By default (null) checks the permissions of the current user.
     * @param string $cap - name of the capability
     * @param bool   $doanything
     * @param string $sort - sorting fields - prefix each fieldname with "c."
     * @param array  $fields - additional fields you are interested in...
     * @return array $courses - ordered array of course objects - see notes above
     */
    public static function get_user_courses_bycap($userid, $cap, $doanything, $sort = 'c.sortorder ASC', $fields = null) {
        global $DB;

        $courses = $DB->get_records('course', ['visible' => 1], 'fullname', 'id, shortname, fullname');
        foreach ($courses as $id => $course) {
            $context = context_course::instance($id);
            if (!has_capability($cap, $context, $userid, $doanything)) {
                unset($courses[$id]);
            }
        }

        return $courses;
    }

    /**
     * Check a CSV input line format for empty or commented lines
     * Ensures compatbility to UTF-8 BOM or unBOM formats
     */
    public static function is_empty_line_or_format(&$text, $latin2utf8 = false) {

        static $first = true;

        $text = preg_replace("/\n?\r?/", '', $text);

        if ($latin2utf8) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        return preg_match('/^$/', $text) || preg_match('/^(\(|\[|-|#|\/| )/', $text);
    }

    /**
     * Checks a serie of patterns on a string.
     * @param string $str a string
     * @param array $patterns an array of patterns to match in string.
     * @return true when at least one pattern matchs.
     */
    public static function pattern_match($str, $patterns) {

        if (!empty($patterns)) {
            foreach ($patterns as $p) {
                if (preg_match('/'.str_replace('/', '\\/', $p).'/', $str)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns the file as one big long string
     * @param string $filename
     * @param bool $use_include_path
     */
    public static function my_file_get_contents($filename, $useincludepath = 0) {

        $data = '';
        $file = fopen($filename, 'rb', $useincludepath);
        if ($file) {
            while (!feof($file)) {
                $data .= fread($file, 1024);
            }
            fclose($file);
        }
        return $data;
    }

    /**
     * Pre process custom profile data, and update it with corrected value
     *
     * @see /admin/tool/uploaduser/locallib.php
     * @param stdClass $data user profile data
     * @return stdClass pre-processed custom profile data
     */
    public static function pre_process_custom_profile_data($data) {
        global $CFG, $DB;

        // Find custom profile fields and check if data needs to converted.
        foreach ($data as $key => $value) {
            if (preg_match('/^profile_field_/', $key)) {
                $shortname = str_replace('profile_field_', '', $key);
                if ($fields = $DB->get_records('user_info_field', ['shortname' => $shortname])) {
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                        $newfield = 'profile_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $data->id);
                        if (method_exists($formfield, 'convert_external_data')) {
                            $data->$key = $formfield->convert_external_data($value);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Bind cohort to user.
     */
    public static function bind_cohort($record, $user, &$log) {
        global $DB;

        $systemcontext = context_system::instance();

        // Cohort (only system level) binding management.
        if (empty($record['cohort']) && empty($record['cohortid'])) {
            // Quick bypass.
            return;
        }

        $cancreate = 0;
        if (!empty($record['cohort'])) {
            $cohort = $DB->get_record('cohort', ['name' => $record['cohort']]);
            // We will only be able to create a cohort if we have a name.
            $cancreate = 1;
        }
        if (!empty($record->cohortid)) {
            // Cohort id prehempts on cohort name if both are present.
            $cohort = $DB->get_record('cohort', ['idnumber' => $record['cohortid']]);
        }
        $t = time();
        if (!$cohort && $cancreate) {
            $cohort = new StdClass();
            $cohort->name = $record['cohort'];
            $cohort->idnumber = $record['cohortid'] ?? 0;
            $cohort->descriptionformat = FORMAT_MOODLE;
            $cohort->contextid = $systemcontext->id;
            $cohort->timecreated = $t;
            $cohort->timemodified = $t;
            if (empty($syncconfig->simulate)) {
                $cohort->id = $DB->insert_record('cohort', $cohort);
            } else {
                $log .= 'SIMULATION : '.get_string('creatingcohort', 'tool_csvsync', $cohort->name);
            }
        }

        $log .= 'Processing cohorts in : '.$cohort->idnumber.' > '.$cohort->name;
        if (!empty($cohort->id)) {
            // Bind user to cohort.
            $params = ['userid' => $user->id, 'cohortid' => $cohort->id];
            if (!$cohortmembership = $DB->get_record('cohort_members', $params)) {
                $cohortmembership = new \StdClass();
                $cohortmembership->userid = $user->id;
                $cohortmembership->cohortid = ''.@$cohort->id;
                $cohortmembership->timeadded = $t;
                if (empty($syncconfig->simulate)) {
                    $cohortmembership->id = $DB->insert_record('cohort_members', $cohortmembership);
                    $log .= 'Adding membership : '.$cohort->idnumber.' > '.$cohort->name;
                } else {
                    $log .= 'SIMULATION : '.get_string('registeringincohort', 'tool_csvsync', $cohort->name);
                }
            }
        } else {
            $log .= 'No cohort found: '.@$record['cohort'].' > '.@$record['cohortid'];
        }
    }
}
