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
 * Class to represent a "datafield_action" type
 *
 * @package    data
 * @subpackage datafield_action
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

class data_field_action_confirm extends data_field_action_base {

    /** the field param that holds the message template */
    public $templateparam = 'param3';

    public function execute($recordid=0) {
        echo '$recordid '.$recordid;
        $param = $this->templateparam;
        if ($template = $this->datafield->field->$param) {
            print_object($template);
            echo 'To continue, you will need to make static methods in template field';
            echo '<ul><li>replace_fieldname</li><li>replace_if_blocks</li><li>check_condition</li><li>clean_condition</li></ul>';
            die;
        }
        return '';
    }
}
