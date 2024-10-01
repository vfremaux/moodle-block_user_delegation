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
 * Server side services
 *
 * @package     block_user_delegation
 * @author      Wafa Adham <admin@adham.ps>, Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');
require_once($CFG->dirroot.'/blocks/user_delegation/xlib.php');

// Security.

require_login();
if (!user_delegation_has_delegation_somewhere()) {
    // Services are restricted to people in charge of some delegation.
    die;
}

$action = required_param('action', PARAM_TEXT);
$sesskey = required_param('sesskey', PARAM_TEXT);

require_sesskey($sesskey);

// Execute the necessary function, according to the operation code in the post variables.
switch ($action) {

    case 'CheckUserExist':
        $email = required_param('e', PARAM_TEXT);
        $firstname = required_param('f_name', PARAM_TEXT);
        $lastname = required_param('l_name', PARAM_TEXT);
        $users = userdelegation::check_user_exist($email, $firstname, $lastname);
        $data = new stdClass();

        if (count($users) > 0) {
            $data->result = 1;
            $data->users = $users;
        } else {
            $data->result = 0;
        }

        echo json_encode($data);
        exit;
        break;

    case 'AttachUser':
        $poweruid = required_param('puid', PARAM_TEXT);
        $fellowuid = required_param('fuid', PARAM_TEXT);
        $result = userdelegation::attach_user($poweruid, $fellowuid);
        $data = new StdClass();
        $data->result = $result;
        echo json_encode($data);
        break;

    case 'GetCourseGroups':
        $courseid = required_param('cid', PARAM_TEXT);
        $result = groups_get_all_groups($courseid);
        $data = new StdClass();
        $data->result = $result;

        echo json_encode($data);
        break;
}
