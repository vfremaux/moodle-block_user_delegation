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
 * Global settings
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/user_delegation/lib.php');

$systemcontext = context_system::instance();
$roles = get_roles_with_capability('block/user_delegation:isbehalfof', CAP_ALLOW);
$roles = role_fix_names($roles, $systemcontext, ROLENAME_ORIGINAL);

$rolemenu = [];
foreach ($roles as $rid => $role) {
    $rolemenu[$role->shortname] = $role->localname;
}

if ($ADMIN->fulltree) {

    if (!empty($rolemenu)) {
        $key = 'block_user_delegation/corole';
        $label = get_string('configdelegationownerrole', 'block_user_delegation');
        $desc = get_string('configdelegationownerrole_desc', 'block_user_delegation');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 'courseowner', $rolemenu));
    }

    $yesnooptions = ['0' => get_string('no'), '1' => get_string('yes')];

    $key = 'block_user_delegation/lastownerdeletes';
    $label = get_string('configlastownerdeletes', 'block_user_delegation');
    $desc = get_string('configlastownerdeletes_desc', 'block_user_delegation');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions));

    $key = 'block_user_delegation/useadvanced';
    $label = get_string('configuseadvancedform', 'block_user_delegation');
    $desc = get_string('configuseadvancedform_desc', 'block_user_delegation');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions));

    if (is_dir($CFG->dirroot.'/local/resource_limiter')) {
        $key = 'block_user_delegation/useuserquota';
        $label = get_string('configuseuserquota', 'block_user_delegation');
        $desc = get_string('configuseuserquota_desc', 'block_user_delegation');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions));
    }

    $csvseparatoroptions = [';' => '(;) semicolon', ':' => '(:) colon', ',' => '(,) coma', "\t" => 'TAB'];

    $key = 'block_user_delegation/csvseparator';
    $label = get_string('configcsvseparator', 'block_user_delegation');
    $desc = get_string('configcsvseparator_desc', 'block_user_delegation');
    $settings->add(new admin_setting_configselect($key, $label, $desc, ';', $csvseparatoroptions));

    if (block_user_delegation_supports_feature('emulate/community') == 'pro') {
        // This will accept any.
        include_once($CFG->dirroot.'/blocks/user_delegation/pro/prolib.php');
        $promanager = block_user_delegation\pro_manager::instance();
        $promanager->add_settings($ADMIN, $settings);
    } else {
        $label = get_string('plugindist', 'block_user_delegation');
        $desc = get_string('plugindist_desc', 'block_user_delegation');
        $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
    }
}
