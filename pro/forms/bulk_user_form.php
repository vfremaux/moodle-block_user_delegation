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
 * @package     block_shop_course_seats
 * @category    blocks
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   2016 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class BulkUser_Form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'h0', get_string('traininggroup', 'block_user_delegation'), '');

        $mform->addElement('static', 'row', '', get_string('traineerow', 'block_user_delegation'));

        $group = array();
        $group[] = $mform->createElement('text', 'firstname', get_string('firstname'));
        $group[] = $mform->createElement('text', 'lastname', get_string('lastname'));
        $group[] = $mform->createElement('text', 'email', get_string('email'));
        $group = $mform->addGroup($group, 'traininguser', get_string('trainee', 'block_user_delegation'), array(' / ', ' / ' ), false);
        $mform->removeElement('traininguser');
        $repeatarray[] = $group;

        $repeateloptions = array(
            'firstname' => array(
                'type' => PARAM_TEXT,
            ),
            'lastname' => array(
                'type' => PARAM_TEXT,
            ),
            'email' => array(
                'type' => PARAM_TEXT,
            ),
        );

        $repeatno = 10;
        $addno = 10;

        $this->repeat_elements($repeatarray, $repeatno,
                    $repeateloptions, 'option_repeats', 'option_add_fields', $addno, null, true);

        $options = $this->_customdata['managers'];

        $mform->addElement('select', 'manager', get_string('manager', 'block_user_delegation'), $options);

        $this->add_action_buttons();

    }

    public function validation($data, $files = array()) {
        global $DB;

        $errors = array();

        $i = 0;
        foreach ($data['email'] as $email) {
            if (empty($data['id_firstname_'][$i]) && empty($data['lastname'][$i]) && empty($data['email'][$i])) {
                continue;
            }

            if (empty($data['firstname'][$i])) {
                $errors['id_firstname_'.$i] = get_string('missingfirstname', 'block_user_delegation');
            }

            if (empty($data['lastname'][$i])) {
                $errors['id_lastname_'.$i] = get_string('missinglastname', 'block_user_delegation');
            }

            if (empty($data['email'][$i])) {
                $errors['id_email_'.$i] = get_string('missingemail', 'block_user_delegation');
            }

            if ($DB->get_record('user', array('email' => $data['email'][$i]))) {
                $errors['id_email_'.$i] = get_string('emailexists');
            }
            $i++;
        }

        return $errors;
    }

    public function get_errors()  {
        return $this->_form->_errors;
    }
}