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
 * Provides some custom settings for the "action" data field
 *
 * @package    data
 * @subpackage datafield_action
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $plugin  = 'datafield_action';
    $setting = 'pdffont';
    $label   = get_string($setting, $plugin);
    $help    = get_string($setting.'_help', $plugin);
    $default = 'freeserif';
    require_once($CFG->libdir.'/pdflib.php');
    if (method_exists('pdf', 'get_font_families')) {
        $options = new pdf();
        $options = $options->get_font_families();
        $options = array_keys($options); // families
    } else {
        $options = array('freeserif', 'freesans');
    }
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect($plugin, $label, $help, $default, $options));
}
