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
 *
 * Manage behalfed users
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');
require_once($CFG->dirroot.'/blocks/user_delegation/block_user_delegation.php');
require_once($CFG->dirroot.'/blocks/user_delegation/lib.php');
require_once($CFG->dirroot.'/blocks/user_delegation/locallib.php');

$blockid      = required_param('id', PARAM_INT);
$courseid     = optional_param('course', SITEID, PARAM_INT);
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);        // How many per page.
$ru           = optional_param('ru', '2', PARAM_INT);            // Show remote users.
$lu           = optional_param('lu', '2', PARAM_INT);            // Show local users.
$acl          = optional_param('acl', '0', PARAM_INT);           // ID of user to tweak mnet ACL (requires $access).

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

// Security.

require_login($course);
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

$blockcontext = context_block::instance($blockid);   // Course context.

$cancreate = false;
if (has_capability('block/user_delegation:cancreateusers', $blockcontext)) {
    $cancreate = true;
} else {
    // Do in two steps to optimize response time.
    if (block_user_delegation::has_capability_somewhere('block/user_delegation:cancreateusers')) {
        $cancreate = true;
    }
}

$canaddbulk = false;
if (has_capability('block/user_delegation:canbulkaddusers', $blockcontext)) {
    $canaddbulk = true;
} else {
    if (block_user_delegation::has_capability_somewhere('block/user_delegation:canbulkaddusers')) {
        $canaddbulk = true;
    }
}

$candelete = false;
if (has_capability('block/user_delegation:candeleteusers', $blockcontext)) {
    $candelete = true;
} else {
    if (block_user_delegation::has_capability_somewhere('block/user_delegation:candeleteusers')) {
        $candelete = true;
    }
}

if (empty($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
}

// Prepare page.

$url = new moodle_url('/blocks/user_delegation/myusers.php', array('id' => $blockid, 'course' => $course->id));
$PAGE->set_url($url);

$stredit   = get_string('edit');
$strdelete = get_string('delete');
$strdeletecheck = get_string('deletecheck');
$strshowallusers = get_string('showallusers');

$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->shortname);
$PAGE->set_pagelayout('admin');

if (block_user_delegation_supports_feature('emulate/community') == 'pro') {
    require_once($CFG->dirroot.'/blocks/user_delegation/pro/renderer.php');
    $renderer = new block_user_delegation_pro_renderer($PAGE, 'html');
} else {
    $renderer = $PAGE->get_renderer('block_user_delegation');
}
// Start page content.

$config = get_config('block_user_delegation');

if (!isset($config->corole)) {
    set_config('corole', 'editingteacher', 'block_user_delegation');
}

echo $OUTPUT->header();

// Adding a user.

if ($confirmuser and confirm_sesskey() && $cancreate) {

    if (!$user = $DB->get_record('user', array('id' => $confirmuser))) {
        print_error('errornouser', 'block_user_delegation');
    }

    $auth = get_auth_plugin($user->auth);
    $result = $auth->user_confirm($user->username, $user->secret);
    if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
        echo $OUTPUT->notification(get_string('userconfirmed', '', fullname($user, true)));
    } else {
        echo $OUTPUT->notification(get_string('usernotconfirmed', '', fullname($user, true)));
    }

} else if ($delete and confirm_sesskey() && $candelete) {
    // Delete a selected user, after confirmation.

    if (!$user = $DB->get_record('user', array('id' => $delete))) {
        print_error('errornouser', 'block_user_delegation');
    }

    // You'll never be able to delete administrators.
    if (is_primary_admin($user->id)) {
        print_error('errorprimaryadmindeletion', 'block_user_delegation');
    }

    if ($confirm != md5($delete)) {
        $fullname = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'), 3);

        $optionsyes = array('id' => $blockid,
                            'course' => $courseid,
                            'delete' => $delete,
                            'confirm' => md5($delete),
                            'sesskey' => sesskey());
        $continueurl = new moodle_url('/blocks/user_delegation/myusers.php', $optionsyes);
        $formcontinue = $OUTPUT->single_button($continueurl, get_string('yes'), 'post');
        $params = array('id' => $blockid, 'course' => $courseid);
        $buttonurl = new moodle_url('/blocks/user_delegation/myusers.php', $params);
        $formcancel = $OUTPUT->single_button($buttonurl, get_string('no'), 'get');

        echo '<div class="user-delegation-delete-form">';
        echo $formcontinue;
        echo $formcancel;
        echo '</div>';

        echo $OUTPUT->footer();
        die;
    } else if (data_submitted() && !$user->deleted) {
        userdelegation::detach_user($USER->id, $user->id);

        // Unenroll from all my owned courses.
        if ($courses = user_delegation_get_user_courses_bycap($USER->id, 'block/user_delegation:cancreateusers', $USER->access, false)) {
            foreach ($courses as $c) {
                $ccontext = context_course::instance($c->id);
                $userroles = get_user_roles($ccontext, $user->id, false);
                foreach ($userroles as $r) {
                    role_unassign($r->id, $user->id, $ccontext->id);
                }
            }
        }

        // If no more owners and need to delete, delete.
        if (!userdelegation::has_other_owners($user->id) && @$config->lastownerdeletes) {
            if (delete_user($user)) {
                echo $OUTPUT->notification(get_string('deletedactivity', '', fullname($user, true)));
            } else {
                echo $OUTPUT->notification(get_string('deletednot', '', fullname($user, true)));
            }
        }
    }
} else if ($acl and confirm_sesskey()) {

    if (!has_capability('block/user_delegation:candeleteusers', $sitecontext)) {
        print_error('You are not permitted to modify the MNET access control list.');
    }

    if (!$user = $DB->get_record('user', array('id' => $acl))) {
        print_error('errornouser', 'block_user_delegation');
    }

    if (!is_mnet_remote_user($user)) {
        print_error('Users in the MNET access control list must be remote MNET users.');
    }

    $accessctrl = strtolower(required_param('accessctrl', PARAM_ALPHA));

    if ($accessctrl != 'allow' and $accessctrl != 'deny') {
        print_error('errorinvalidaccess', 'block_user_delegation');
    }
    $params = array('username' => $user->username, 'mnet_host_id' => $user->mnethostid);
    $aclrecord = $DB->get_record('mnet_sso_access_control', $params);
    if (empty($aclrecord)) {
        $aclrecord = new stdClass();
        $aclrecord->mnet_host_id = $user->mnethostid;
        $aclrecord->username = $user->username;
        $aclrecord->accessctrl = $accessctrl;

        if (!$DB->insert_record('mnet_sso_access_control', $aclrecord)) {
            print_error("Database error - Couldn't modify the MNET access control list.");
        }
    } else {
        $aclrecord->accessctrl = $accessctrl;
        if (!$DB->update_record('mnet_sso_access_control', $aclrecord)) {
            print_error("Database error - Couldn't modify the MNET access control list.");
        }
    }
    $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id, wwwroot, name');
    echo $OUTPUT->notification("MNET access control list updated: username '$user->username' from host '"
        . $mnethosts[$user->mnethostid]->name
        . "' access now set to '$accessctrl'.");
}

// Create the user filter form.

$fieldnames = array('realname' => 0, 'lastname' => 1, 'firstname' => 1, 'email' => 1, 'city' => 1, 'country' => 1,
                    'firstaccess' => 1, 'lastaccess' => 1, 'neveraccessed' => 1, 'username' => 1);
$filterparams = array('id' => $blockid, 'course' => $courseid, 'sort' => $sort);
$ufiltering = new user_filtering($fieldnames, $url, $filterparams);

// Carry on with the user listing.

$columns = array('firstname', 'lastname', 'email', 'city', 'country', 'lastaccess');
foreach ($columns as $column) {
    $string[$column] = get_string("$column");
    if ($sort != $column) {
        $columnicon = '';
        if ($column == 'lastaccess') {
            $columndir = 'DESC';
        } else {
            $columndir = 'ASC';
        }
    } else {
        $columndir = $dir == 'ASC' ? 'DESC' : 'ASC';
        if ($column == "lastaccess") {
            $columnicon = $dir == 'ASC' ? 'up' : 'down';
        } else {
            $columnicon = $dir == 'ASC' ? 'down' : 'up';
        }
        $columnicon = ' '.$OUTPUT->pix_icon('/t/$columnicon', '');
    }
    $params = array('id' => $blockid, 'course' => $courseid, 'sort' => $column, 'dir' => $columndir);
    $linkurl = new moodle_url('/blocks/user_delegation/myusers.php', $params);
    $$column = '<a href="'.$linkurl.'">'.$string[$column].'</a>'.$columnicon;
}

if ($sort == 'name') {
    $sort = 'firstname';
}

$extrasqlparts = $ufiltering->get_sql_filter();
$users = userdelegation::get_delegated_users($USER->id, $sort, $dir, $page * $perpage, $perpage, '', '', '', $extrasqlparts);
if ($users) {
     $usercount = count($users);
} else {
     $usercount = 0;
}

// Not optimized, makes whole query again.

if ($allusers = userdelegation::get_delegated_users($USER->id, $sort, $dir, 0, 0, '', '', '', $extrasqlparts)){
    $usersearchcount = count($allusers);
} else {
    $usersearchcount = 0;
}

// Print heading and extra parts.
if (@$extrasqlparts[0] !== '') {
    echo $OUTPUT->heading(get_string('myusers', 'block_user_delegation').": $usersearchcount / $usercount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading(get_string('myusers', 'block_user_delegation').': '.$usercount.' '.get_string('users'));
}

$alphabet = explode(',', get_string('alphabet', 'block_user_delegation'));
$strall = get_string('all');
$params = array('id' => $blockid, 
                'course' => $courseid,
                'sort' => $sort,
                'dir' => $dir,
                'perpage' => $perpage);
$pagingurl = new moodle_url('/blocks/user_delegation/myusers.php', $params);
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $pagingurl);

flush();

if (!$users) {
    $match = array();
    echo $OUTPUT->notification(get_string('nousersfound'), 'user-delegation-notification');
    $table = null;
} else {
    $countries = get_string_manager()->get_list_of_countries();

    if (empty($mnethosts)) {
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id, wwwroot, name');
    }

    foreach ($users as $key => $user) {
        if (!empty($user->country)) {
            $users[$key]->country = $countries[$user->country];
        }
    }

    if ($sort == 'country') {
        // Need to resort by full country name, not code.
        foreach ($users as $user) {
            $susers[$user->id] = $user->country;
        }
        asort($susers);
        foreach ($susers as $key => $value) {
            $nusers[] = $users[$key];
        }
        $users = $nusers;
    }

    $mainadmin = get_admin();
    $override = new stdClass();
    $override->firstname = 'firstname';
    $override->lastname = 'lastname';
    $fullnamelanguage = get_string('fullnamedisplay', '', $override);
    if (($CFG->fullnamedisplay == 'firstname lastname') or
        ($CFG->fullnamedisplay == 'firstname') or
        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
        $fullnamedisplay = "$firstname / $lastname";
    } else {
        $fullnamedisplay = "$lastname / $firstname";
    }

    $table = new html_table();
    $table->head = array ($fullnamedisplay, $email, $city, $country, $lastaccess, '', '', '');
    $table->align = array ('left', 'left', 'left', 'left', 'left', 'center', 'center', 'center');
    $table->width = '100%';

    foreach ($users as $user) {

        if ($user->username == 'guest') {
            // Do not display dummy new user and guest here.
            continue;
        }

        $deletebutton = '';
        if ((!userdelegation::has_other_owners($user->id) && $candelete) || is_siteadmin()) {
            $params = array('id' => $blockid, 'course' => $courseid, 'delete' => $user->id, 'sesskey' => $USER->sesskey);
            $deleteurl = new moodle_url('/blocks/user_delegation/myusers.php', $params);
            $deletebutton = '<a href="'.$deleteurl.'">'.$strdelete.'</a>';
        }

        // Get the user context.

        $usercontext = context_user::instance($user->id);

        $isbehalfof = has_capability('block/user_delegation:isbehalfof', $usercontext);

        if ($isbehalfof and ($user->id != $mainadmin->id) and !is_mnet_remote_user($user)) {

            $params = array('course' => $courseid, 'blockid' => $blockid, 'id' => $user->id);
            $editurl = new moodle_url('/blocks/user_delegation/editsimple.php', $params);
            $editbutton = '<a href="'.$editurl.'">'.$stredit.'</a>';

            if ($user->confirmed == 0) {
                $params = array('id' => $blockid,
                                'course' => $courseid,
                                'confirmuser' => $user->id,
                                'sesskey' => $USER->sesskey);
                $confirmurl = new moodle_url('/blocks/user_delegation/myusers.php', $params);
                $confirmbutton = '<a href="'.$confirmurl.'">'.get_string('confirm').'</a>';
            } else {
                $confirmbutton = '';
            }
        } else {
            $editbutton = '';
            if ($user->confirmed == 0) {
                $confirmbutton = '<span class="dimmed_text">'.get_string('confirm').'</span>';
            } else {
                $confirmbutton = '';
            }
        }

        // For remote users, shuffle columns around and display MNET stuff.

        if (is_mnet_remote_user($user)) {
            $accessctrl = 'allow';
            if ($acl = $DB->get_record('mnet_sso_access_control', array('username' => $user->username, 'mnet_host_id' => $user->mnethostid))) {
                $accessctrl = $acl->accessctrl;
            }
            $changeaccessto = ($accessctrl == 'deny' ? 'allow' : 'deny');

            // Delete button in confirm column.
            // TODO: no delete for remote users, for now. new userid, delete flag, unique on username/host...
            // No delete if users has other owners.
            $confirmbutton = '';

            // ACL in delete column.
            $deletebutton = get_string($accessctrl, 'mnet');
            if ($candelete) {
                // TODO: this should be under a separate capability.
                $params = array('id' => $blockid,
                                'course' => $courseid,
                                'acl' => $user->id,
                                'accessctrl' => $changeaccessto,
                                'sesskey' => $USER->sesskey);
                $deleteurl = new moodle_url('/blocks/user_delegation/myusers.php', $params);
                $deletebutton .= ' (<a href="'.$deleteurl.'">'
                        . get_string($changeaccessto, 'mnet') . " access</a>)";
            }

            // Mnet info in edit column.
            if (isset($mnethosts[$user->mnethostid])) {
                $editbutton = $mnethosts[$user->mnethostid]->name;
            }
        }

        if ($user->lastaccess) {
            $strlastaccess = format_time(time() - $user->lastaccess);
        } else {
            $strlastaccess = get_string('never');
        }

        $fullname = fullname($user, true);
        $userurl = new moodle_url('/user/view.php', array('id' => $user->id));
        $table->data[] = array ('<a href="'.$userurl.'">'.$fullname.'</a>',
                            "$user->email",
                            "$user->city",
                            "$user->country",
                            $strlastaccess,
                            $editbutton,
                            $deletebutton,
                            $confirmbutton);
    }
}

// Add filters.

$ufiltering->display_add();
$ufiltering->display_active();

if ($cancreate) {
    if (!empty($config->useadvanced)) {
        $params = array('course' => $courseid, 'blockid' => $blockid, 'id' => -1);
        $adduserurl = new moodle_url('/blocks/user_delegation/editadvanced.php', $params);
        echo $OUTPUT->heading('<a href="'.$adduserurl.'">'.get_string('newuser', 'block_user_delegation').'</a>');
    } else {
        $params = array('course' => $courseid, 'blockid' => $blockid, 'id' => -1);
        $adduserurl = new moodle_url('/blocks/user_delegation/editsimple.php', $params);
        echo $OUTPUT->heading('<a href="'.$adduserurl.'">'.get_string('newuser', 'block_user_delegation').'</a>');
    }
}

// Print link to my courses.

$userownedcourses = userdelegation::get_user_courses_bycap($USER->id, 'block/user_delegation:cancreateusers', false);
echo '<div class="userpage-toolbar">';

if (block_user_delegation_supports_feature('users/enrol')) {
    if (!empty($userownedcourses)) {
        // Only if owned courses.
        $params = array('id' => $blockid, 'course' => $courseid);
        $coursesurl = new moodle_url('/blocks/user_delegation/pro/mycourses.php', $params);
        echo $OUTPUT->pix_icon('folders', '', 'block_user_delegation');
        echo ' <a href="'.$coursesurl.'">'.get_string('mycourses').'</a>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    }
}

// Print upload users link.
if ($canaddbulk) {

    echo $OUTPUT->pix_icon('upload', get_string('uploadusers', 'block_user_delegation'), 'block_user_delegation');
    $params = array('id' => $blockid, 'course' => $courseid);
    $uploadurl = new moodle_url('/blocks/user_delegation/uploaduser.php', $params);
    echo '<a href="'.$uploadurl.'">'.get_string('uploadusers', 'block_user_delegation').'</a>';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
}

if (block_user_delegation_supports_feature('users/addbulk') && $canaddbulk) {
    echo $renderer->addbulk_link($blockid);
}

echo '</div>';

// Print user table.
if (!empty($table)) {

    echo '<div class="user-delegation-myuserstable"> ';
    echo html_writer::table($table);
    echo '</div>';
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $pagingurl);

    if ($cancreate) {
        if (!empty($config->useadvanced)) {
            $params = array('course' => $courseid, 'blockid' => $blockid, 'id' => -1);
            $adduserurl = new moodle_url('/blocks/user_delegation/editadvanced.php', $params);
            echo $OUTPUT->heading('<a href="'.$adduserurl.'">'.get_string('newuser', 'block_user_delegation').'</a>');
        } else {
            $params = array('course' => $courseid, 'blockid' => $blockid, 'id' => -1);
            $adduserurl = new moodle_url('/blocks/user_delegation/editsimple.php', $params);
            echo $OUTPUT->heading('<a href="'.$adduserurl.'">'.get_string('newuser', 'block_user_delegation').'</a>');
        }
    }
}

if (!$users) {
    $noownedusersstr = get_string('noownedusers', 'block_user_delegation');
    echo "<div>$noownedusersstr</div>";
}

if ($courseid == SITEID) {
    echo '<center><br/>';
    echo $OUTPUT->single_button($CFG->wwwroot, get_string('backtohome', 'block_user_delegation'), 'get');
    echo '<br/><center>';
} else {
    echo '<center><br/>';
    $buttonurl = new moodle_url('/course/view.php', array('id' => $course->id));
    echo $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'block_user_delegation'), 'get');
    echo '<br/><center>';
}

echo $OUTPUT->footer();
