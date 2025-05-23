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
 * Version information for the "datafield_action" plugin
 *
 * @package    data
 * @subpackage datafield_action
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'datafield_action';
$plugin->dependencies = array(
    'datafield_admin'    => ANY_VERSION,
    'datafield_template' => ANY_VERSION
);
$plugin->requires  = 2012062500; // Moodle 2.3
$plugin->version   = 2025040537;
$plugin->release   = '2025-04-05 (37)';
