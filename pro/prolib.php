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
 * @categroy    blocks
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_user_delegation;

defined('MOODLE_INTERNAL') || die();

define('BLOCK_USER_DELEGATION_COMPONENT_PROVIDER_ROUTER_URL', 'http://www.mylearningfactory.com/providers/router.php');

final class pro_manager {

    private static $component = 'block_user_delegation';
    private static $componentpath = 'blocks/user_delegation';

    /**
     * this adds additional settings to the component settings (generic part of the prolib system).
     * @param objectref &$admin
     * @param objectref &$settings
     */
    public static function add_settings(&$admin, &$settings) {
        global $CFG, $PAGE;

        $PAGE->requires->js_call_amd('block_user_delegation/pro', 'init');

        $settings->add(new \admin_setting_heading('plugindisthdr', get_string('plugindist', 'block_user_delegation'), ''));

        $key = 'block_user_delegation/emulatecommunity';
        $label = get_string('emulatecommunity', 'block_user_delegation');
        $desc = get_string('emulatecommunity_desc', 'block_user_delegation');
        $settings->add(new \admin_setting_configcheckbox($key, $label, $desc, 0));

        $key = 'block_user_delegation/licenseprovider';
        $label = get_string('licenseprovider', 'block_user_delegation');
        $desc = get_string('licenseprovider_desc', 'block_user_delegation');
        $settings->add(new \admin_setting_configtext($key, $label, $desc, ''));

        $key = 'block_user_delegation/licensekey';
        $label = get_string('licensekey', 'block_user_delegation');
        $desc = get_string('licensekey_desc', 'block_user_delegation');
        $settings->add(new \admin_setting_configtext($key, $label, $desc, ''));

        // These are additional plugin context pro settings.
        if (file_exists($CFG->dirroot.'/'.self::$componentpath.'/pro/localprolib.php')) {
            include_once($CFG->dirroot.'/'.self::$componentpath.'/pro/localprolib.php');
            local_pro_manager::add_settings($admin, $settings);
        }
    }

    /**
     * Sends an empty license using advice to registered provider.
     */
    public static function send_empty_license_signal() {
        $config = get_config('block_user_delegation');

        if (block_user_delegation_supports_feature() && empty($config->licensekey)) {
            if ($config->licensekeycheckdate < time() - 30 * DAYSECS) {

                $url = BLOCK_USER_DELEGATION_COMPONENT_PROVIDER_ROUTER_URL;
                $url .= '?provider='.$config->licenseprovider.'&service=tell&component='.self::$component;
                $url .= '&host='.urlencode($CFG->wwwroot);

                $res = curl_init($url);

                curl_exec($res);
                set_config('licensekeycheckdate', time(), 'auth_digicode');
            }
        }
    }
    public static function print_empty_license_message() {
        return '<div class="licensing">-- Pro licensed version --<br/>This plugin is being used in proversion without license key for demonstration.</div>';
    }

    public static function set_and_check_license_key($customerkey, $provider = null, $interactive = false) {
        global $CFG, $DB;

        if (empty($provider)) {
            $config = get_config('block_user_delegation');
            $provider = $config->licenseprovider;
        }

        $regusers = $DB->count_records('user', array('deleted' => 0));
        $courses = $DB->count_records('course');
        $coursecats = $DB->count_records('course_categories');

        $url = BLOCK_USER_DELEGATION_COMPONENT_PROVIDER_ROUTER_URL;
        $url .= '?provider='.$provider.'&service=set&customerkey='.$customerkey.'&component='.self::$component;
        $url .= '&host='.urlencode($CFG->wwwroot).'&users='.$regusers.'&courses='.$courses.'&coursecats='.$coursecats;

        $res = curl_init($url);
        $result = curl_exec($res);

        // Get result content.
        if (!preg_match('/SET OK/', $result)) {
            // Invalidate key.
            if (!$interactive) {
                set_config('licensekey', $result, 'auth_digicode');
                die();
            }
        }

        // Give exact service result without change.
        return $result;
    }
}