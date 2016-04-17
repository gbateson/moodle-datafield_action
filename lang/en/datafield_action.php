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
 * Strings for the "datafield_action" component, language="en", branch="master"
 *
 * @package    data
 * @subpackage datafield_action
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

/** required strings */
$string['pluginname'] = 'Action field';

/** more string */
$string['actiontime'] = 'Action time';
$string['actiontype'] = 'Action type';
$string['argument'] = 'Argument {$a}';
$string['timeadd'] = 'When a record is added';
$string['timeaddedit'] = 'When a record is added OR edited';
$string['timeaddeditdelete'] = 'When a record is added OR edited OR deleted';
$string['timedelete'] = 'When a record is deleted';
$string['timeedit'] = 'When a record is edited';
$string['timeselect'] = 'When a record is selected (List or Search template)';
$string['timeshowlist'] = 'When a record is displayed using the List template';
$string['timeshowsingle'] = 'When a record is displayed using the Single template ';
$string['timespecific'] = 'At a specific time';
$string['fixlangpack'] = '**The Action field is not yet properly installed**

Please append language strings for the Action field to Database language file:

* EDIT: {$a->langfile}
* ADD: $string[\'action\'] = \'Action\';
* ADD: $string[\'nameaction\'] = \'Action field\';

Then purge the Moodle caches:

* Actionistration -> Site actionistration -> Development -> Purge all caches

See {$a->readfile} for more details.';