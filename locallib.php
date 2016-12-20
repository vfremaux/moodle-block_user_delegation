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

/**
 * Get an array of courses where cap requested is available
 * and user is enrolled, this can be relatively slow.
 *
 * @see deprecated since 2.2
 * We need this function back here
 * @param int    $userid A user id. By default (null) checks the permissions of the current user.
 * @param string $cap - name of the capability
 * @param array  $accessdata_ignored
 * @param bool   $doanything_ignored
 * @param string $sort - sorting fields - prefix each fieldname with "c."
 * @param array  $fields - additional fields you are interested in...
 * @param int    $limit_ignored
 * @return array $courses - ordered array of course objects - see notes above
 */
function user_delegation_get_user_courses_bycap($userid, $cap, $accessdata_ignored, $doanything_ignored, $sort = 'c.sortorder ASC', $fields = null, $limit_ignored = 0) {

    $courses = enrol_get_users_courses($userid, true, $fields, $sort);
    foreach ($courses as $id => $course) {
        $context = context_course::instance($id);
        if (!has_capability($cap, $context, $userid)) {
            unset($courses[$id]);
        }
    }

    return $courses;
}
