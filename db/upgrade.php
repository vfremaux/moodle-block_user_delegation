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
 * Standard upgrade sequence
 *
 * @package   block_user_delegation
 * @author    Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Standard upgrade handler.
 * @param int $oldversion
 */
function xmldb_block_user_delegation_upgrade($oldversion = 0) {
    global $DB;

    $result = true;

    if ($result && $oldversion < 2016122601) {
        // New version in version.php.

        $syscontext = context_system::instance();

        $shortname = 'courseowner';
        if ($role = $DB->get_record('role', ['shortname' => $shortname])) {
            assign_capability('block/user_delegation:candeleteusers', CAP_ALLOW, $role->id, $syscontext->id, true);
        }

        // Use_stats savepoint reached.
        upgrade_block_savepoint($result, 2016122601, 'user_delegation');
    }

    return $result;
}
