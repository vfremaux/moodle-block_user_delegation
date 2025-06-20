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
 * Version details.
 *
 * @package     block_user_delegation
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2013 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025042900;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2022112801;        // Requires this Moodle version.
$plugin->component = 'block_user_delegation'; // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '4.5.0 (Build 2025042900)';
$plugin->supported = [403, 405];
if (function_exists('block_user_delegation_supports_feature') && block_user_delegation_supports_feature() === 'pro') {
    $plugin->dependencies = ['local_vfcore' => 2024053100];
}

// Non moodle attributes.
$plugin->codeincrement = '4.5.0006';
$plugin->privacy = 'dualrelease';
