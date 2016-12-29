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

class block_user_delegation_renderer extends plugin_renderer_base {

    function unassigned_users($users) {

        $pixurl = $this->output->pix_url('user', 'block_user_delegation');

        $str = '';
        $str .= '<div class="user-delegation-course-cont">';
        $str .= '<div><h2>'.get_string('unassignedusers', 'block_user_delegation').'</h2></div>';
        $str .= '<div>';
        foreach ($myusers as $u) {
            $str .= '<div class="user-delegation-user"><img src="'.$pixurl.'" /> '.$u->firstname.' '.$u->lastname.' </div>';
        }
        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }
}