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

require_once($CFG->dirroot.'/blocks/user_delegation/editsimple_form.php');

class user_editadvanced_form extends user_editsimple_form {

    // Define the form.
    public function definition() {
        global $USER, $CFG, $COURSE;

        $mform = $this->_form;
        $editoroptions = null;
        $filemanageroptions = null;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for user_edit_form');
        }
        $editoroptions = $this->_customdata['editoroptions'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $user = $this->_customdata['user'];
        $userid = $user->id;

        // Accessibility: "Required" is bad legend text.
        $strgeneral  = get_string('general');
        $strrequired = get_string('required');

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_INT);

        // Print the required moodle fields first.
        $mform->addElement('header', 'moodle', $strgeneral);

        $mform->addElement('text', 'username', get_string('username'), 'size="20"');
        $mform->addHelpButton('username', 'username', 'auth');
        $mform->setType('username', PARAM_RAW);
        $mform->addRule('username', $strrequired, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'suspended', get_string('suspended','auth'));
        $mform->addHelpButton('suspended', 'suspended', 'auth');

        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }

        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'), 'size="20"');
        $mform->addHelpButton('newpassword', 'newpassword');
        $mform->setType('newpassword', PARAM_RAW);

        $mform->addElement('advcheckbox', 'preference_auth_forcepasswordchange', get_string('forcepasswordchange'));
        $mform->addHelpButton('preference_auth_forcepasswordchange', 'forcepasswordchange');

        // Shared fields.
        useredit_shared_definition($mform, $editoroptions, $filemanageroptions, $user);

        $mform->addElement('text', 'cohort', get_string('cohort'), 'size="40"');
        $mform->addHelpButton('cohort', 'cohort', 'block_userdelegation');
        $mform->setType('cohort', PARAM_TEXT);

        $mform->addElement('text', 'cohortid', get_string('cohortidnumber', 'block_usergation'), 'size="40"');
        $mform->addHelpButton('cohortid', 'cohortid', 'block_userdelegation');
        $mform->setType('cohortid', PARAM_TEXT);

        // Next the customisable profile fields.
        profile_definition($mform, $userid);

        if ($this->_customdata['userid'] == -1) {
            $btnstring = get_string('createuser');
            if (!empty($this->_customdata['courses'])) {
                $mform->addElement('select', 'coursetoassign', get_string('coursetoassign', 'block_user_delegation'), $this->_customdata['courses']);
            }
        } else {
            $btnstring = get_string('update');
        }

        $this->add_action_buttons(false, $btnstring);
    }

    public function definition_after_data() {
        global $USER, $CFG, $DB, $OUTPUT;

        $mform =& $this->_form;
        if ($userid = $mform->getElementValue('id')) {
            $user = $DB->get_record('user', array('id' => $userid));
        } else {
            $user = false;
        }

        // If language does not exist, use site default lang.
        if ($langsel = $mform->getElementValue('lang')) {
            $lang = reset($langsel);
            // Check lang exists.
            if (!get_string_manager()->translation_exists($lang, false)) {
                $lang_el =& $mform->getElement('lang');
                $lang_el->setValue($CFG->lang);
            }
        }

        // User can not change own auth method.
        if ($userid == $USER->id) {
            $mform->hardFreeze('auth');
            $mform->hardFreeze('preference_auth_forcepasswordchange');
        }

        // Admin must choose some password and supply correct email.
        if (!empty($USER->newadminuser)) {
            $mform->addRule('newpassword', get_string('required'), 'required', null, 'client');
            if ($mform->elementExists('suspended')) {
                $mform->removeElement('suspended');
            }
        }

        // Require password for new users.
        if ($userid == -1) {
            $mform->addRule('newpassword', get_string('required'), 'required', null, 'client');
        }

        if ($user and is_mnet_remote_user($user)) {
            // Only local accounts can be suspended.
            if ($mform->elementExists('suspended')) {
                $mform->removeElement('suspended');
            }
        }
        if ($user and ($user->id == $USER->id or is_siteadmin($user))) {
            // Prevent self and admin mess ups.
            if ($mform->elementExists('suspended')) {
                $mform->hardFreeze('suspended');
            }
        }

        // Print picture.
        if (empty($USER->newadminuser)) {
            if ($user) {
                $context = context_user::instance($user->id, MUST_EXIST);
                $fs = get_file_storage();
                $hasuploadedpicture = ($fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.png') || $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.jpg'));
                if (!empty($user->picture) && $hasuploadedpicture) {
                    $imagevalue = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 64));
                } else {
                    $imagevalue = get_string('none');
                }
            } else {
                $imagevalue = get_string('none');
            }
            $imageelement = $mform->getElement('currentpicture');
            $imageelement->setValue($imagevalue);

            if ($user && $mform->elementExists('deletepicture') && !$hasuploadedpicture) {
                $mform->removeElement('deletepicture');
            }
        }

        // Next the customisable profile fields.
        profile_definition_after_data($mform, $userid);
    }

    /**
     * Validate the form data.
     * @param array $usernew
     * @param array $files
     * @return array|bool
     */
    public function validation($usernew, $files) {
        global $CFG, $DB;

        $usernew = (object)$usernew;
        $usernew->username = trim($usernew->username);

        $user = $DB->get_record('user', array('id' => $usernew->id));
        $err = array();

        if (!empty($usernew->newpassword)) {
            $errmsg = ''; // Prevent eclipse warning.
            if (!check_password_policy($usernew->newpassword, $errmsg)) {
                $err['newpassword'] = $errmsg;
            }
        }

        if (empty($usernew->username)) {
            // Might be only whitespace.
            $err['username'] = get_string('required');
        } else if (!$user or $user->username !== $usernew->username) {
            // Check new username does not exist.
            if ($DB->record_exists('user', array('username' => $usernew->username, 'mnethostid' => $CFG->mnet_localhost_id))) {
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

        if (!$user or $user->email !== $usernew->email) {
            if (!validate_email($usernew->email)) {
                $err['email'] = get_string('invalidemail');
            } else if ($DB->record_exists('user', array('email' => $usernew->email, 'mnethostid' => $CFG->mnet_localhost_id))) {
                $err['email'] = get_string('emailexists');
            }
        }

        // Next the customisable profile fields.
        $err += profile_validation($usernew, $files);

        if (count($err) == 0){
            return true;
        } else {
            return $err;
        }
    }

    public function disable_form_change_checker() {
        $this->_form->disable_form_change_checker();
    }

}
