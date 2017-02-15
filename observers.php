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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');

/**
 * Observes role assignements and add hasasbehalf/isbehalfof 
 * It will search for an hasbehalfon enabled role in the nearest context
 * This event will be executed by admin/tool/sync users management with group assigns
 * @see (non standard) admin/tool/sync
 */
class block_user_delegation_event_observer {

    function on_group_member_added($eventdata) {
        global $DB;

        $userid = $eventdata->userid;
        $group = $DB->get_record('groups', array('id' => $eventdata->objectid));

        $context = context_course::instance($group->courseid);

        $members = groups_get_members($eventdata->objectid, 'u.id, u.username');

        // Search for behalving potentials (teachers) in the entered group.
        if (has_capability('block/user_delegation:hasasbehalf', $context, $userid)) {
            if ($members) {
                foreach ($members as $gm) {
                    if (has_capability('block/user_delegation:isbehalfof', $context, $gm->id)) {
                        userdelegation::attach_user($gm->id, $userid);
                    }
                }
            }
        }

        // Search for behalved potentials (students) in the entered group.
        if (has_capability('block/user_delegation:isbehalfof', $context, $userid)) {
            if ($members) {
                foreach ($members as $gm) {
                    if (has_capability('block/user_delegation:hasasbehalf', $context, $gm->id)) {
                        userdelegation::attach_user($userid, $gm->id);
                    }
                }
            }
        }
    }
}
