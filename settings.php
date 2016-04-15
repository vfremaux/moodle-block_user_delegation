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

$systemcontext = context_system::instance();
$roles = role_fix_names(get_all_roles(), $systemcontext, ROLENAME_ORIGINAL);

$rolemenu = array();
foreach ($roles as $rid => $role) {
    $rolemenu[$role->shortname] = $role->localname ;
}

$settings->add(new admin_setting_configselect('block_user_delegation/corole', get_string('delegationownerrole', 'block_user_delegation'),
                   get_string('configdelegationownerrole', 'block_user_delegation'), 0, $rolemenu));

$yesnooptions = array('0' => get_string('no'), '1' => get_string('yes'));

$settings->add(new admin_setting_configselect('block_user_delegation/lastownerdeletes', get_string('lastownerdeletes', 'block_user_delegation'),
                   get_string('configlastownerdeletes', 'block_user_delegation'), 0, $yesnooptions));

$settings->add(new admin_setting_configselect('block_user_delegation/useadvanced', get_string('useadvancedform', 'block_user_delegation'),
                   get_string('configuseadvancedform', 'block_user_delegation'), 0, $yesnooptions));

$settings->add(new admin_setting_configselect('block_user_delegation/useuserquota', get_string('useuserquota', 'block_user_delegation'),
                   get_string('configuseadvancedform', 'block_user_delegation'), 0, $yesnooptions));

$csvseparatoroptions = array(';' => '(;) semicolon', ':' => '(:) colon', ',' => '(,) coma', "\t" => 'TAB');

$settings->add(new admin_setting_configselect('block_user_delegation/csvseparator', get_string('csvseparator', 'block_user_delegation'),
                   get_string('configcsvseparator', 'block_user_delegation'), ';', $csvseparatoroptions));
