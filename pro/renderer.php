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

require_once($CFG->dirroot.'/blocks/user_delegation/renderer.php');

class block_user_delegation_pro_renderer extends block_user_delegation_renderer {

    public function unassigned_users($users) {
        global $OUTPUT;

        $template = new StdClass;
        $template->unassignedusersstr = get_string('unassignedusers', 'block_user_delegation');
        $template->pixurl = $this->output->pix_url('user', 'block_user_delegation');

        foreach ($users as $u) {
            $usertpl = new StdClass;
            $usertpl->firstname = $u->firstname;
            $usertpl->lastname = $u->lastname;
            $template->users[] = $usertpl;
        }

        return $OUTPUT->render_from_template('block_user_delegation/unassignedusers', $template);
    }

    public function addbulk_link($blockid) {
        global $COURSE, $OUTPUT;

        $str = '&nbsp;<img src="'.$OUTPUT->pix_url('i/group', 'core').'" />';
        $params = array('blockid' => $blockid, 'course' => $COURSE->id);
        $uploadurl = new moodle_url('/blocks/user_delegation/pro/edit_bulk.php', $params);
        $str .= '&nbsp;<a href="'.$uploadurl.'">'.get_string('addbulkusers', 'block_user_delegation').'</a>';

        return $str;
    }
}