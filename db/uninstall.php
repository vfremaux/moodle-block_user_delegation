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
 * Uninstall sequence.
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Standard post uninstall handler
 */
function xmldb_block_user_delegation_uninstall() {
    global $CFG, $DB;

    // Switch to legacy editing teacher when bloc is removed from Moodle.
    if ($corole = $DB->get_record('role', ['shortname' => 'courseowner'])) {

        $legacyrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $DB->delete_records('role', ['shortname' => 'courseowner']);

        $sql = "
            UPDATE
                {role_assignments}
            SET
                roleid = {$legacyrole->id}
            WHERE
                roleid = {$corole->id}
        ";

        $DB->execute($sql);
        $DB->delete_records('config', ['name' => 'block_user_delegation_co_role']);

        unset($CFG->block_user_delegation_co_role);
    }
}
