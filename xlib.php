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
 *
 * A library to interact with other parts of Moodle
 */

/**
 * checks if user has some user creation cap somewhere
 */
function user_delegation_has_delegation_somewhere() {
    global $USER;

    // TODO : explore caps for a moodle/local:overridemy positive answer.
    $hassome = get_user_capability_course('block/user_delegation:cancreateusers', $USER->id, false); 
    if (!empty($hassome)) {
        return true;
    }

    return false;
}

/**
 * Get the count of users that are on my behalf
 */
function get_onbehalf_user_count() {
    global $USER;

    return 0;
}

function user_delegation_is_owner($userid, $ownerid = 0) {
    global $USER;

    if (!$ownerid) $ownerid = $USER->id;

    $usercontext = context_user::instance($userid);
    return has_capability('block/user_delegation:hasasbehalf', $usercontext, $ownerid);
}