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
 * Simple user form.
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Form definition class.
 */
class user_editsimple_form extends moodleform {

    /**
     * Standard definition.
     */
    public function definition() {
        global $USER, $CFG, $COURSE;

        $mform =& $this->_form;
        $editoroptions = null;
        $filemanageroptions = null;

        $user = null;
        if (is_array($this->_customdata)) {
            if (array_key_exists('editoroptions', $this->_customdata)) {
                $editoroptions = $this->_customdata['editoroptions'];
            }
            if (array_key_exists('filemanageroptions', $this->_customdata)) {
                $filemanageroptions = $this->_customdata['filemanageroptions'];
            }
            if (array_key_exists('user', $this->_customdata)) {
                $user = $this->_customdata['user'];
            }
        }

        // Accessibility: "Required" is bad legend text.
        $strrequired = get_string('required');

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('text', 'username', get_string('username'), 'size="20"');
        $mform->addRule('username', $strrequired, 'required', null, 'client');
        $mform->setType('username', PARAM_RAW);

        if (is_object($this->_customdata['user'])) {
            if (!userdelegation::has_other_owners($this->_customdata['user']->id)) {
                $mform->addElement('advcheckbox', 'suspended', get_string('suspended', 'auth'));
                $mform->addHelpButton('suspended', 'suspended', 'auth');
            }
        }

        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }

        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'), 'size="20"');
        $mform->addHelpButton('newpassword', 'newpassword');
        $mform->setType('newpassword', PARAM_RAW);

        $mform->addElement('advcheckbox', 'preference_auth_forcepasswordchange', get_string('forcepasswordchange'));
        $mform->addHelpButton('preference_auth_forcepasswordchange', 'forcepasswordchange');

        // Shared fields.
        $this->useredit_shared_fields($mform, $editoroptions, $filemanageroptions);

        // Next the customisable profile fields.

        if (is_null($this->_customdata['user'])) {
            $btnstring = get_string('createuser');
            if (!empty($this->_customdata['courses'])) {
                $lbl = get_string('coursetoassign', 'block_user_delegation');
                $mform->addElement('select', 'coursetoassign', $lbl, $this->_customdata['courses']);
            }
        } else {
            $btnstring = get_string('update');
        }

        $mform->disable_form_change_checker();

        $this->add_action_buttons(true, $btnstring);
    }

    /**
     * Shared fields.
     * @param object $mform
     * @param array $editoroptions
     * @param array $filemanageroptions
     */
    protected function useredit_shared_fields($mform, $editoroptions, $filemanageroptions) {
        global $CFG;

        $strrequired = get_string('required');

        // Shared fields.
        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->addRule('firstname', $strrequired, 'required', null, 'client');
        $mform->setType('firstname', PARAM_TEXT);

        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->addRule('lastname', $strrequired, 'required', null, 'client');
        $mform->setType('lastname', PARAM_TEXT);

        $mform->addElement('text', 'email', get_string('email'));
        $mform->addRule('email', $strrequired, 'required', null, 'client');
        $mform->setType('email', PARAM_TEXT);

        $mform->addElement('text', 'institution', get_string('institution', 'block_user_delegation'));
        $mform->setType('institution', PARAM_TEXT);

        $mform->addElement('text', 'department', get_string('department'));
        $mform->setType('department', PARAM_TEXT);
        $mform->setAdvanced('department');

        $choices = get_string_manager()->get_list_of_countries();
        $choices = ['' => get_string('selectacountry').'...'] + $choices;
        $mform->addElement('select', 'country', get_string('selectacountry'), $choices);
        $mform->addRule('country', $strrequired, 'required', null, 'client');
        if (!empty($CFG->country)) {
            $mform->setDefault('country', $CFG->country);
        }

        $mform->addElement('text', 'phone1', get_string('phone'));
        $mform->setType('phone1', PARAM_TEXT);
        $mform->setAdvanced('phone1');

        $mform->addElement('text', 'phone2', get_string('phone2'));
        $mform->setType('phone2', PARAM_TEXT);
        $mform->setAdvanced('phone2');

        $mform->addElement('hidden', 'lang', $CFG->lang);
        $mform->setType('lang', PARAM_TEXT);
    }

    /**
     * Changes the form after data is loaded.
     */
    public function definition_after_data() {
        global $USER, $DB;

        $mform =& $this->_form;
        if ($userid = $mform->getElementValue('id')) {
            $user = $DB->get_record('user', ['id' => $userid]);
        } else {
            $user = false;
        }

        // Require password for new users.
        if ($userid == -1) {
            $mform->addRule('newpassword', get_string('required'), 'required', null, 'client');
        }

        if ($user && is_mnet_remote_user($user)) {
            // Only local accounts can be suspended.
            if ($mform->elementExists('suspended')) {
                $mform->removeElement('suspended');
            }
        }
        if ($user && ($user->id == $USER->id || is_siteadmin($user))) {
            // Prevent self and admin mess ups.
            if ($mform->elementExists('suspended')) {
                $mform->hardFreeze('suspended');
            }
        }

        // Next the customisable profile fields.
        profile_definition_after_data($mform, $userid);
    }

    /**
     * Standard validation.
     */
    public function validation($usernew, $files) {
        global $CFG, $DB;

        $usernew = (object)$usernew;
        $usernew->username = trim($usernew->username);

        $user = $DB->get_record('user', ['id' => $usernew->id]);
        $err = [];

        if (!empty($usernew->newpassword)) {
            $errmsg = '';
            if (!check_password_policy($usernew->newpassword, $errmsg)) {
                $err['newpassword'] = $errmsg;
            }
        }

        if (empty($usernew->username)) {
            // Might be only whitespace.
            $err['username'] = get_string('required');
        } else if (!$user || $user->username !== $usernew->username) {
            // Check new username does not exist.
            $params = ['username' => $usernew->username, 'mnethostid' => $CFG->mnet_localhost_id];
            if ($DB->record_exists('user', $params)) {
                $err['username'] = get_string('usernameexists');
            }
            // Check allowed characters.
            if ($usernew->username !== block_user_delegation::strtolower($usernew->username)) {
                $err['username'] = get_string('usernamelowercase');
            } else {
                if ($usernew->username !== clean_param($usernew->username, PARAM_USERNAME)) {
                    $err['username'] = get_string('invalidusername');
                }
            }
        }

        if (!$user || $user->email !== $usernew->email) {
            $params = ['email' => $usernew->email, 'mnethostid' => $CFG->mnet_localhost_id];
            if (!validate_email($usernew->email)) {
                $err['email'] = get_string('invalidemail');
            } else if ($DB->record_exists('user', $params)) {
                $err['email'] = get_string('emailexists');
            }
        }

        // Next the customisable profile fields.
        $err += profile_validation($usernew, $files);

        if (count($err) == 0) {
            return true;
        } else {
            return $err;
        }
    }
}
