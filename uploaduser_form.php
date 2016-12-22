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

require_once($CFG->dirroot.'/lib/formslib.php');

class UploadUserForm extends moodleform {

    function definition() {
        $mform = $this->_form;

        $config = get_config('block_user_delegation');

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        // The block id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'head0', get_string('inputfile', 'block_user_delegation'));

        $mform->addElement('filepicker', 'userfile');

        // $mform->addElement('header', 'head1', get_string('settings'));

        if (!empty($this->_customdata['courses'])) {
            $mform->addElement('select', 'coursetoassign', get_string('coursetoassign', 'block_user_delegation'), $this->_customdata['courses']);

            $mform->addElement('select', 'grouptoassign', get_string('grouptoassign', 'block_user_delegation'), array('0' => get_string('nogroupswaitcourseslection', 'block_user_delegation')));

            $mform->addElement('text', 'newgroupname', get_string('newgroupname',  'block_user_delegation'), '');
            $mform->setType('newgroupname', PARAM_TEXT);
        }

        $mform->addElement('text', 'nomail', get_string('nomailplaceholder', 'block_user_delegation'));
        $mform->setType('nomail', PARAM_ALPHANUM);
        $mform->addHelpButton('nomail', 'nomail', 'block_user_delegation');
        $mform->setAdvanced('nomail');

        $passwordopts = array(
            0 => get_string('infilefield', 'auth'),
            1 => get_string('createpasswordifneeded', 'auth'),
        );
        $mform->addElement('select', 'createpassword', get_string('passwordhandling', 'auth'), $passwordopts);
        $mform->addHelpButton('createpassword', 'createpassword', 'block_user_delegation');
        $mform->setDefault('createpassword', 0);

        $yesnoopts = array(0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'updateaccounts', get_string('updateaccounts', 'admin'), $yesnoopts);
        $mform->setDefault('updateaccounts', 1);

        $mform->addElement('header', 'head0', get_string('fileformat', 'block_user_delegation'));

        $sepoptions = array(';' => '(;) semicolon', ':' => '(:) colon', '(,) coma' => ',', "\t" => 'TAB');
        $mform->addElement('select', 'fieldseparator', get_string('fieldseparator', 'block_user_delegation'), $sepoptions);
        $mform->addHelpButton('fieldseparator', 'fieldseparator', 'block_user_delegation');
        $mform->setDefault('fieldseparator', $config->csvseparator);
        $mform->setAdvanced('fieldseparator');

        $encoptions = array('Latin' => 'ISO-5589-1', 'UTF-8' => 'UTF-8');
        $mform->addElement('select', 'fileencoding', get_string('fileencoding', 'block_user_delegation'), $encoptions);
        $mform->addHelpButton('fileencoding', 'fileencoding', 'block_user_delegation');
        $mform->setDefault('fileencoding', 'UTF-8');
        $mform->setAdvanced('fileencoding');

        $this->add_action_buttons();
    }
}