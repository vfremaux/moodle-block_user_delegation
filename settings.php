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

$systemcontext = context_system::instance();
$roles = role_fix_names(get_roles_with_capability('block/user_delegation:isbehalfof', CAP_ALLOW), $systemcontext, ROLENAME_ORIGINAL);

$rolemenu = array();
foreach ($roles as $rid => $role) {
    $rolemenu[$role->shortname] = $role->localname;
}

$key = 'block_user_delegation/corole';
$label = get_string('configdelegationownerrole', 'block_user_delegation');
$desc = get_string('configdelegationownerrole_desc', 'block_user_delegation');
$settings->add(new admin_setting_configselect($key, $label, $desc, 'courseowner', $rolemenu));

$yesnooptions = array('0' => get_string('no'), '1' => get_string('yes'));

$key = 'block_user_delegation/lastownerdeletes';
$label = get_string('configlastownerdeletes', 'block_user_delegation');
$desc = get_string('configlastownerdeletes_desc', 'block_user_delegation');
$settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions));

$key = 'block_user_delegation/useadvanced';
$label = get_string('configuseadvancedform', 'block_user_delegation');
$desc = get_string('configuseadvancedform_desc', 'block_user_delegation');
$settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions));

$key = 'block_user_delegation/useuserquota';
$label = get_string('configuseuserquota', 'block_user_delegation');
$desc = get_string('configuseuserquota_desc', 'block_user_delegation');
$settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions));

$csvseparatoroptions = array(';' => '(;) semicolon', ':' => '(:) colon', ',' => '(,) coma', "\t" => 'TAB');

$key = 'block_user_delegation/csvseparator';
$label = get_string('configcsvseparator', 'block_user_delegation');
$desc = get_string('configcsvseparator_desc', 'block_user_delegation');
$settings->add(new admin_setting_configselect($key, $label, $desc, ';', $csvseparatoroptions));
