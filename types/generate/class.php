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

// Get required files (probably lib.php has already been included).
require_once($CFG->dirroot.'/mod/data/lib.php');
require_once($CFG->dirroot.'/mod/data/field/action/types/class.php');

class data_field_action_generate extends data_field_action_base {

    /** the param that holds the list of fieldnames */
    public $fieldnamesparam = 'param3'; // argument 1

    public function execute($recordid=0, $value='') {

        $data = $this->datafield->data;
        $field = $this->datafield->field; // The action field.
        $time = $field->{$this->datafield->timeparam};

        // "fieldnames" is a required field
        $param = $this->fieldnamesparam;
        if ($fieldnames = $field->$param) {
            $fieldnames = explode(',', $fieldnames);
            $fieldnames = array_map('trim', $fieldnames);
            $fieldnames = array_filter($fieldnames);
        } else {
            $fieldnames = []; // Shouldn't happen !!
        }
        foreach ($fieldnames as $fieldname) {
            $field = data_get_field_from_name($fieldname, $data);
            switch ($time) {
                // We are being called just *after* the add/edit.
                case data_field_action::TIME_ADD:
                case data_field_action::TIME_EDIT:
                case data_field_action::TIME_ADDEDIT:
                    $field->update_content($recordid, $value);
                    break;

                case data_field_action::TIME_DELETE:
                case data_field_action::TIME_ADDEDITDELETE:
                    $field->delete_content($recordid);
                    break;

                case data_field_action::TIME_SHOWLIST:
                    $field->set_preview(false); // Disable preview mode.
                    $field->display_browse_field($recordid, 'listtemplate');
                    break;

                case data_field_action::TIME_SHOWSINGLE:
                    $field->set_preview(false); // Disable preview mode.
                    $field->display_browse_field($recordid, 'singletemplate');
                    break;

                // The following times are not implemented.
                // data_field_action::TIME_SELECT
                // data_field_action::TIME_SPECIFIC
            }
        }
    }
}
