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
 * @package     block_user_delegation
 * @category    blocks
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Standard post install handler.
 */
function xmldb_block_user_delegation_install() {
    global $DB;

    $shortname = 'courseowner';
    $name = get_string('courseowner', 'block_user_delegation');
    $description = get_string('courseownerdescription', 'block_user_delegation');
    $legacy = 'editingteacher';

    $syscontext = context_system::instance();

    if (!$role = $DB->get_record('role', array('shortname' => $shortname))) {
        if ($roleid = create_role($name, $shortname, $description, $legacy)) {
            // boostrap courseowner to the same as editingteacher

            $editingteacher = $DB->get_record('role', array('shortname' => 'editingteacher'));

            role_cap_duplicate($editingteacher, $roleid);
            assign_capability('block/user_delegation:cancreateusers', CAP_ALLOW, $roleid, $syscontext->id, true);
            assign_capability('block/user_delegation:canbulkaddusers', CAP_ALLOW, $roleid, $syscontext->id, true);
            assign_capability('block/user_delegation:isbehalfof', CAP_ALLOW, $roleid, $syscontext->id, true);
            assign_capability('block/user_delegation:view', CAP_ALLOW, $roleid, $syscontext->id, true);

            // add role assign allowance to owner 
            // We only allow and override on standard roles.
            $assigntargetrole[] = $DB->get_field('role', 'id', array('shortname' => 'student'));
            $assigntargetrole[] = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
            $assigntargetrole[] = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
            $assigntargetrole[] = $DB->get_field('role', 'id', array('shortname' => 'guest'));

            foreach ($assigntargetrole as $t) {
                allow_assign($roleid, $t);
            }

            $overridetargetrole[] = $DB->get_field('role', 'id', array('shortname' => 'student'));
            $overridetargetrole[] = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
            $overridetargetrole[] = $DB->get_field('role', 'id', array('shortname' => 'guest'));

            foreach ($overridetargetrole as $t) {
                allow_override($roleid, $t);
            }

            set_config('block_user_delegation_co_role', $shortname);
        }
    }
}