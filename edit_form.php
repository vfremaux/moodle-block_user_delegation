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
 * Form for editing HTML block instances.
 *
 * @package   block_user_delegation
 * @author   Wafa Adham & Valery Fremaux
 * @copyright Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing Random glossary entry block instances.
 */
class block_user_delegation_edit_form extends block_edit_form {

    /**
     * Specific block form definition.
     * @param object $mform
     */
    protected function specific_definition($mform) {
        global $DB;

        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('checkbox', 'config_allowenrol', get_string('configallowenrol', 'block_user_delegation'), '', 1);

        $mform->addElement('text', 'config_enrolduration', get_string('configenrolduration', 'block_user_delegation'), '');
        $mform->setType('config_enrolduration', PARAM_TEXT);
    }
}
