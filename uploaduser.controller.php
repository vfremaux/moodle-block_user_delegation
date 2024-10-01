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
 * MVC controller for upload
 *
 * @package    block_user_delegation
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/user_delegation/classes/userdelegation.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

// Fix the weird POST bounce form field loss when ajax changing a form.
$data->grouptoassign = clean_param($_REQUEST['grouptoassign'] ?? '', PARAM_TEXT);

$fs = get_file_storage();
$usercontext = context_user::instance($USER->id);

// If a file has been uploaded, then process it.

if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $data->userfile)) {

    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->userfile, 'filename', false);
    $file = array_pop($files);

    $filename = $file->get_filename();

    /*
     * Large files are likely to take their time and memory. Let PHP know
     * that we'll take longer, and that the process should be recycled soon
     * to free up memory.
     */
    @set_time_limit(0);
    @raise_memory_limit('128M');

    // Fix file.

    $text = $file->get_content();

    // Trim UTF-8 BOM.
    $text = block_user_delegation::trim_utf8_bom($text);

    // Fix mac/dos newlines.
    $text = preg_replace('!\r\n?!', "\n", $text);

    $lines = explode("\n", $text);
    $log = '';

    if (empty($lines)) {
        throw new moodle_exception('emptyfile', 'block_user_delegation');
    }

    // Make arrays of valid fields for error checking.
    $requiredfields = [
        'username' => 1,
        'password' => !$data->createpassword,
        'firstname' => 1,
        'lastname' => 1,
        'email' => 1,
    ];

    $cloneuserfields = [
        'mnethostid' => 1,
        'institution' => 1,
        'department' => 1,
    ];

    // Optional fields.
    $optionalfields = [
        'mnethostid' => 1,
        'institution' => 1,
        'department' => 1,
        'city' => 1,
        'country' => 1,
        'lang' => 1,
        'auth' => 1,
        'cohort' => 1,
        'cohortid' => 1,
        'timezone' => 1,
        'idnumber' => 1,
        'icq' => 1,
        'skype' => 1,
        'yahoo' => 1,
        'msn' => 1,
        'aim' => 1,
        'phone1' => 1,
        'phone2' => 1,
        'address' => 1,
        'url' => 1,
        'description' => 1,
        'mailformat' => 1,
        'maildisplay' => 1,
        'htmleditor' => 1,
        'autosubscribe' => 1,
        'password' => $data->createpassword,
        'emailstop' => 1,
        'trackforums' => 1,
        'screenreader' => 1,
        'suspended' => 1,
        'deleted' => 1,
        'oldusername' => 1,
    ];

    $metas = [
            'profile_field_.*'];

    // Default values for optional fields (only for  NOT null fields without DEFAULT in db schema).
    $optionaldefaults = [
        'idnumber' => '',
        'cohort' => '',
        'cohortid' => '',
        'icq' => '',
        'skype' => '',
        'yahoo' => '',
        'msn' => '',
        'aim' => '',
        'phone1' => '',
        'phone2' => '',
        'address' => '',
        'url' => '',
        'lastip' => '',
        'secret' => '',
        'country' => (!empty($CFG->country)) ? $CFG->country : '',
        'city' => (!empty($CFG->city)) ? $CFG->city : '',
        'timezone' => (!empty($CFG->timezone)) ? $CFG->timezone : 99,
        'lang' => $CFG->lang,
        'institution' => '',
        'department' => '',
        'auth' => 'manual',
        'deleted' => 0,
        'suspended' => 0,
        'mnethostid' => $CFG->mnet_localhost_id,
    ];

    // String fields length.
    $fieldlength = ['username' => 100,
                        'password' => 32,
                        'firstname' => 100,
                        'lastname' => 100,
                        'email' => 100,
                        'institution' => 40,
                        'department' => 40,
                        'city' => 30,
                        'cohort' => 254,
                        'cohortid' => 100,
                        'lang' => 30,
                        'auth' => 20,
                        'timezone' => 100,
                        'idnumber' => 64,
                        'icq' => 15,
                        'skype' => 50,
                        'yahoo' => 50,
                        'msn' => 50,
                        'aim' => 50,
                        'phone1' => 20,
                        'phone2' => 20,
                        'address' => 70,
                        'url' => 255,
                        'description' => 255,
                        'oldusername' => 100];

    // Get header (field names).
    $l = array_shift($lines);
    $header = explode($csvseparator, $l);

    // Check for valid field names.
    foreach ($header as $i => $h) {
        $h = trim($h); $header[$i] = $h; // Remove whitespace.

        $ismeta = false;
        foreach ($metas as $meta) {
            if (preg_match("/{$meta}/", $h)) {
                $ismeta = true;
                break;
            }
        }

        if (!$ismeta) {
            if (!(array_key_exists($h, $requiredfields)) && !(array_key_exists($h, $optionalfields))) {
                echo $OUTPUT->notification(get_string('invalidfieldname_areyousure', 'block_user_delegation', $h));
                throw new moodle_exception('invalidfieldname_areyousure', 'block_user_delegation', '', $h);
            }
            if (array_key_exists($h, $requiredfields)) {
                // Release required field as we know it is present in file.
                $requiredfields[$h] = 0;
            }
        }
    }

    $hcount = count($header);

    // Check for required fields.
    foreach ($requiredfields as $key => $value) {
        if ($value) {
            // Required field missing, as still marked for requirement.
            throw new moodle_exception('fieldrequired', 'error', '', $key);
        }
    }
    $linenum = 1; // Since header is line 0.

    // Prepare counts.
    $usersnew     = 0;
    $usersupdated = 0;
    $userserrors  = 0;
    $renames      = 0;
    $renameerrors = 0;
    $fakemails    = 0;
    $invalidmails = 0;
    $duplicatemails = 0;

    // Preload all available Roles.
    $roles = $DB->get_records('role', null, '', 'id, shortname, name');

    while ($l = array_shift($lines)) {
        $user = new StdClass();
        $log .= '<hr />';

        // Setup optional-fields-with-admin-defaults using the operator's own information.
        foreach ($cloneuserfields as $key => $value) {
            $user->$key = $USER->$key;
        }

        // Setup optional-fields defaults.
        foreach ($optionaldefaults as $key => $value) {
            $user->$key = $optionaldefaults[$key];
        }

        // Note: separator within a field should be encoded as &#XX (for semicolon separated csv files).
        if (userdelegation::is_empty_line_or_format($l, ($data->fileencoding == 'Latin'))) {
            continue;
        }

        $line = explode($csvseparator, $l);

        if (!empty($line)) {
            // The line is not empty.

            $lcount = count($line);
            if ($lcount != $hcount) {
                $msg = get_string('errorcountdiff', 'block_user_delegation');
                $log .= useradmin_uploaduser_notify_error($linenum, $msg, null, null, null);
            }
            $record = array_combine($header, $line);

            // Add fields to object $user.
            foreach ($record as $name => $value) {

                if (in_array($name, ['cohort', 'cohortid'])) {
                    continue;
                }

                // Trim fields.
                $value = trim($value);

                // Truncate string fields.
                if (isset($fieldlength[$name]) && strlen($value) > $fieldlength[$name] ) {
                    $value = substr($value, 0, $fieldlength[$name] );
                    $a = new StdClass();
                    $a->fieldname = $name;
                    $a->length = $fieldlength[$name];
                    $msg = get_string('truncatefield', 'block_user_delegation', $a);
                    $log .= useradmin_uploaduser_notify_error($linenum, $msg, null, $record['username'], null);
                }

                // TODO add other fields validation.
                // Check for required values.
                if (@$requiredfields[$name] && !$value) {
                    $params = ['sesskey' => sesskey(), 'id' => $blockid, 'ocurse' => $courseid];
                    $returnurl = new moodle_url('/blocks/user_delegation/uploaduser.php', $params);
                    $a = new StdClass();
                    $a->fieldname = $name;
                    $msg = get_string('missingvalue', 'block_user_delegation', $a);
                    $log .= useradmin_uploaduser_notify_error($linenum, $msg, null, $record['username'], null);
                    continue;

                } else if ($name == 'password' && !empty($value)) {
                    // Password (needs to be encrypted).
                    $user->password = hash_internal_user_password($value);

                } else if ($name == 'username') {
                    // Username (escape and force lowercase).
                    $user->username = block_user_delegation::strtolower($value);

                } else {
                    // Normal entry (escape only).
                    if (!in_array($name, array_keys($requiredfields)) &&
                            !in_array($name, array_keys($optionalfields)) &&
                                    !userdelegation::pattern_match($name, $metas)) {
                        $params = ['sesskey' => sesskey(), 'id' => $blockid, 'courseid' => $courseid];
                        $returnurl = new moodle_url('/blocks/user_delegation/uploaduser.php', $params);
                        throw new moodle_exception('unexpectedfield', 'block_userdelegation', $name, $returnurl);
                    }
                    $user->{$name} = $value;
                }
            }

            // By default the user is confirmed and modified now.

            $user->confirmed = 1;
            $user->timemodified = time();
            $linenum++;
            $username = $user->username;

            // Check if trying to upload 'changeme' user. If not, skip the line.

            if ($user->username === 'changeme') {
                $msg = get_string('invaliduserchangeme', 'admin');
                $log .= useradmin_uploaduser_notify_error($linenum, $msg, null, $user->username, true);
                $userserrors++;
                continue; // Skip line.
            }

            // If a real mail has been specified, check it is a valid address (if not, skip line).

            if ($user->email != $data->nomail) {
                $params = [$user->username, $user->email];
                $select = " username != ? AND email = ? ";
                if (!validate_email($user->email)) {
                    $msg = get_string('invalidemail').": $user->email";
                    $log .= useradmin_uploaduser_notify_error($linenum, $msg, null, $user->username, true);
                    $invalidmails++;
                    $userserrors++;
                    continue; // Skip line.
                } else if ($otheruser = $DB->get_record_select('user', $select, $params)) {
                    // Check duplicate mail with other username.
                    if ($otheruser) {
                        $msg = get_string('emailexists').": $user->email";
                        $log .= useradmin_uploaduser_notify_error($linenum, $msg, null, $user->username, true);
                        $duplicatemails++;
                        $userserrors++;
                        continue; // Skip line.
                    }
                }
            }

            // If mnethost ist not localhost, check if mnethost exist.
            // This should NOT happen in user delegation as this field is not given in documentation.

            if (($user->mnethostid != $CFG->mnet_localhost_id) &&
                    !$DB->record_exists('mnet_host', ['id' => $user->mnethostid])) {
                $msg = get_string('mnethostidnotexists', 'block_user_delegation', $user->mnethostid);
                $log .= useradmin_uploaduser_notify_error($linenum, $msg, null, $user->username, true);
                $userserrors++;
                continue;
            }

            // Check if username already exists, eventually deleted or suspended.

            if ($olduser = $DB->get_record('user', ['username' => $username])) {
                // If update is allowed, update record.
                $user->id = $olduser->id;
                if ($data->updateaccounts) {

                    // You are creating users that might have been previously deleted. Revive them.
                    $user->deleted = 0;
                    $user->suspended = 0;

                    // Record is being updated.
                    try {
                        $DB->update_record('user', $user);
                    } catch (Exception $ex) {
                        $msg = get_string('usernotupdatederror', 'block_user_delegation');
                        $log .= useradmin_uploaduser_notify_error($linenum, $msg, $user->id, $user->username, true);
                        $userserrors++;
                        continue;
                    }

                    $msg = get_string('useraccountupdated', 'block_user_delegation');
                    $log .= useradmin_uploaduser_notify_success($linenum, $msg, $user->id, $user->username);
                    $usersupdated++;
                } else {
                    // If update is not allowed, just revive.
                    $olduser->deleted = 0;
                    $olduser->suspended = 0;
                    try {
                        $DB->update_record('user', $olduser);
                    } catch (Exception $ex) {
                        $msg = get_string('usernotaddedregistered', 'block_user_delegation');
                        $log .= useradmin_uploaduser_notify_error($linenum, $msg, $user->id, $user->username, false);
                        $userserrors++;
                        continue;
                    }
                    $msg = get_string('usernotupdatederror', 'block_user_delegation');
                    $log .= useradmin_uploaduser_notify_error($linenum, $msg, $user->id, $user->username, true);
                    $userserrors++;
                    // Do not skip line, as enrolments and groups should be processed.
                }
            } else {
                // New user.
                // Username does not exists, so create a new user.
                try {
                    $user->id = $DB->insert_record('user', $user);
                    $msg = get_string('newuseradded', 'block_user_delegation');
                    $log .= useradmin_uploaduser_notify_success($linenum, $msg, $user->id, $user->username);
                    $usersnew++;

                    if (empty($user->password) && $data->createpassword) {
                        // Passwords will be created and sent out on cron.
                        $DB->insert_record('user_preferences', ['userid' => $user->id,
                                    'name'   => 'create_password',
                                    'value'  => 1]);
                        $DB->insert_record('user_preferences', ['userid' => $user->id,
                                    'name'   => 'auth_forcepasswordchange',
                                    'value'  => 1]);
                    }
                } catch (Exception $e) {
                    // Record not added -- possibly some other error.
                    $msg = get_string('usernotaddederror', 'block_user_delegation');
                    $log .= useradmin_uploaduser_notify_error($linenum, $msg, $user->id, $user->username, true);
                    $userserrors++;
                    continue;
                }
            }

            // Add the uploaded/updated user on behalf of the uploader.
            userdelegation::attach_user($USER->id, $user->id);

            userdelegation::bind_cohort($record, $user, $log);

            // Assign it to the selected course if any.
            if (!empty($data->coursetoassign)) {
                include_once($CFG->dirroot.'/blocks/user_delegation/pro/lib.php');
                block_user_delegation_enrol($data, $user, $theblock);
            }

            $user = userdelegation::pre_process_custom_profile_data($user);
            profile_save_data($user);

            unset ($user);
        }
    }

    // Print a small report.

    echo $OUTPUT->header();
    echo '<hr height="2" />';

    if (!empty($log)) {
        echo '<pre>';
        echo $log;
        echo '</pre>';
    }

    echo $OUTPUT->notification("$strusersnew: $usersnew");
    echo $OUTPUT->notification(get_string('usersupdated', 'block_user_delegation') . ": $usersupdated");
    echo $OUTPUT->notification(get_string('errors', 'block_user_delegation').": $userserrors");
    echo $OUTPUT->notification(get_string('fakemails', 'block_user_delegation') . ": $fakemails");
    echo $OUTPUT->notification(get_string('invalidmails', 'block_user_delegation') . ": $invalidmails");
    echo $OUTPUT->notification(get_string('duplicatemails', 'block_user_delegation') . ": $duplicatemails");
    echo '<hr />';

    $continueurl = new moodle_url('/blocks/user_delegation/myusers.php', ['id' => $blockid, 'course' => $courseid]);

    echo $OUTPUT->continue_button($continueurl);

    echo $OUTPUT->footer();
    die;
}
