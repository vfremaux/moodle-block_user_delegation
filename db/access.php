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
 * Capabilities.
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux (valery@gmail.com)
 * @copyright   2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    /*
     * marks in user context who is user's behalfer
     * this may be a good idea to isolate this role and capability in a custom "Supervisor" role.
     */
    'block/user_delegation:view' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * marks in user context who is user's behalfer
     * this may be a good idea to isolate this role and capability in a custom "Supervisor" role.
     */
    'block/user_delegation:isbehalfof' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
        ],
    ],

    'block/user_delegation:hasasbehalf' => [
        'riskbitmask' => RISK_PERSONAL,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'student' => CAP_ALLOW,
        ],
    ],

    /*
     *
     */
    'block/user_delegation:cancreateusers' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     *
     */
    'block/user_delegation:candeleteusers' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Marks that current user is owning the course, thus
     * able to get some reports on it and have strong delegated administration
     * only Owned course can be assigned to using the user delegation forms.
     */
    'block/user_delegation:owncourse' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
        ],
    ],

    /*
     * Marks that current user is owning the course, thus
     * able to get some reports on it and have strong delegated administration
     * only Owned course can be assigned to using the user delegation forms.
     */
    'block/user_delegation:owncoursecat' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
        ],
    ],

    /*
     *
     */
    'block/user_delegation:canbulkaddusers' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
        ],
    ],

    /*
     * Can add an instance to the course the user has capability on
     */
    'block/user_delegation:addinstance' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
        ],
    ],

    /*
     * Can add an instance on any "My" page of the user.
     */
    'block/user_delegation:myaddinstance' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
        ],
    ],

    /*
     * Can configure the instances.
     */
    'block/user_delegation:configure' => [
        'riskbitmask' => RISK_CONFIG,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
