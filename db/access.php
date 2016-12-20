<?php
//
// Capability definitions for the block myCourses.
//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    /*
     * marks in user context who is user's behalfer
     * this may be a good idea to isolate this role and capability in a custom "Supervisor" role.
     */
    'block/user_delegation:view' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    /*
     * marks in user context who is user's behalfer
     * this may be a good idea to isolate this role and capability in a custom "Supervisor" role.
     */
    'block/user_delegation:isbehalfof' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

    'block/user_delegation:hasasbehalf' => array(
        'riskbitmask' => RISK_PERSONAL,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => array(
            'student' => CAP_ALLOW
        )
    ),

    /*
     *
     */
    'block/user_delegation:cancreateusers' => array(
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),

    /*
     *
     */
    'block/user_delegation:candeleteusers' => array(
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),

    /*
     * Marks that current user is owning the course, thus 
     * able to get some reports on it and have strong delegated administration
     * only Owned course can be assigned to using the user delegation forms. 
     */
    'block/user_delegation:owncourse' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
        )
    ),

    /*
     * Marks that current user is owning the course, thus 
     * able to get some reports on it and have strong delegated administration
     * only Owned course can be assigned to using the user delegation forms. 
     */
    'block/user_delegation:owncoursecat' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
        )
    ),

    /*
     *
     *
     */
    'block/user_delegation:canbulkaddusers' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),

    /*
     * Can add an instance to the course the user has capability on
     */
    'block/user_delegation:addinstance' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),

    /*
     * Can add an instance on any "My" page of the user
     */
    'block/user_delegation:myaddinstance' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),

    /*
     * Can add an instance on any "My" page of the user
     */
    'block/user_delegation:configure' => array(
        'riskbitmask' => RISK_CONFIG,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    )
);
