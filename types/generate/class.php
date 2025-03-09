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

    /** the params that holds the lists of fieldnames */
    public $sourcefieldsparam = 'param3'; // argument 1
    public $targetfieldsparam = 'param4'; // argument 2

    /**
     * Executes the action to update target fields based on source fields.
     *
     * This method is triggered when a change occurs in one of the source fields.
     * It determines whether the target fields should be updated based on the timing
     * specified in the data field settings. The process includes:
     * - Fetching source and target field names from the database.
     * - Checking if the source field content has changed.
     * - Updating or deleting content in the target fields based on the specified timing.
     * - Rendering the target fields in different display modes if required.
     *
     * @param int $recordid The ID of the record being processed (0 for new records).
     * @param string $value The content value to be updated (used primarily for target fields).
     *
     * @return void
     */
    public function execute($recordid = 0, $value = '') {
        global $DB;

        $data = $this->datafield->data;
        $field = $this->datafield->field; // The action field.
        $time = $field->{$this->datafield->timeparam};

        // A change in the content of a source field will trigger
        // a call to the "update_content" method of the target field(s).

        // Get the source/target fieldnames.
        $sourcefieldnames = $this->get_fieldnames(
            $field, $this->sourcefieldsparam
        );
        $targetfieldnames = $this->get_fieldnames(
            $field, $this->targetfieldsparam
        );

        // Sanity check on source/target fieldnames.
        if (empty($sourcefieldnames) || empty($targetfieldnames)) {
            return;
        }

        // Get ids and names of all source/target fields.
        $fields = $this->get_fields($data, $sourcefieldnames, $targetfieldnames);
        if (empty($fields)) {
            return; // Shouldn't happen !!
        }

        // Get the new values for all the source/target fields.
        $newvalues = $this->get_newvalues($recordid, $fields);

        // Get the old values for all the source/target fields.
        $oldvalues = $this->get_oldvalues($recordid, $fields);

        // If we are adding or editing,
        // we may need to update the target fields.
        if ($time == data_field_action::TIME_ADD ||
            $time == data_field_action::TIME_EDIT ||
            $time == data_field_action::TIME_ADDEDIT) {

            if ($time == data_field_action::TIME_ADDEDIT) {
                $update = true;
            } else if ($recordid == 0) {
                $update = ($time == data_field_action::TIME_ADD);
            } else {
                $update = ($time == data_field_action::TIME_EDIT);
            }

            if ($update) {
                // Check the sourcefields and update
                // any whose content value has changed.
                foreach ($sourcefieldnames as $fieldname) {
                    $fieldid = array_search($fieldname, $fields);
                    if ($fieldid === false) {
                        continue; // Invalid field name.
                    }
                    $oldvalue = ($oldvalues[$fieldid] ?? '');
                    $newvalue = ($newvalues[$fieldid] ?? '');
                    if (strcmp($oldvalue, $newvalue) === 0) {
                        continue; // Value has not changed.
                    }
                    foreach ($targetfieldnames as $name) {
                        $fieldid = array_search($name, $fields);
                        if ($fieldid === false) {
                            continue; // Invalid field name.
                        }
                        $targetfield = data_get_field_from_id($fieldid, $data);
                        if ($targetfield === false) {
                            continue; // Invalid fieldid - shouldn't happen !!.
                        }
                        // Update the target field, using
                        // the new value from the source field.
                        // The "extra2" value is stored in "param4" property.
                        if ($targetfield->field->type == 'report') {
                            $newvalue = $targetfield->display_field($recordid, 'extra2');
                        }
                        $targetfield->update_content($recordid, $newvalue);
                    }
                }
            }
        } else {
            // Otherwise, we are not updating, so take an
            // action that is suitable for the action time.
            foreach ($targetfieldnames as $name) {
                $fieldid = array_search($name, $fields);
                if ($fieldid === false) {
                    continue;
                }
                $field = data_get_field_from_id($fieldid, $data);
                if ($field === false) {
                    continue;
                }
                $value = ($newvalues[$fieldid] ?? '');

                // Determine action based on timing.
                switch ($time) {
                    case data_field_action::TIME_DELETE:
                    case data_field_action::TIME_ADDEDITDELETE:
                        $field->delete_content($recordid);
                        break;

                    case data_field_action::TIME_SHOWLIST:
                        $field->set_preview(false);
                        $field->display_browse_field($recordid, 'listtemplate');
                        break;

                    case data_field_action::TIME_SHOWSINGLE:
                        $field->set_preview(false);
                        $field->display_browse_field($recordid, 'singletemplate');
                        break;

                    // Unimplemented times: TIME_SELECT, TIME_SPECIFIC
                }
            }
        }
    }

    /**
     * Retrieves a list of field names based on a specified parameter.
     *
     * This method fetches the value of the given parameter from the field object,
     * splits it into an array, trims whitespace, and removes empty values.
     * The retrieved field names are used in the execute method to determine
     * which fields should trigger or be updated by the action.
     *
     * @param object $field The field object from which the parameter value is retrieved.
     * @param string $param The parameter name that stores a comma-separated list of field names.
     *
     * @return array An array of field names extracted from the parameter.
     */
    protected function get_fieldnames($field, $param) {
        if (isset($field->$param)) {
            if ($fieldnames = $field->$param) {
                $fieldnames = explode(',', $fieldnames);
                $fieldnames = array_map('trim', $fieldnames);
                $fieldnames = array_filter($fieldnames);
                return $fieldnames;
            }
        }
        return []; // Shouldn't happen!
    }

    protected function get_fields($data, $sourcefieldnames, $targetfieldnames) {
        global $DB;
        $fieldnames = array_merge($sourcefieldnames, $targetfieldnames);
        list($select, $params) = $DB->get_in_or_equal($fieldnames);
        $select = "dataid = ? AND name $select";
        $params = array_merge([$data->id], $params);
        return $DB->get_records_select_menu('data_fields', $select, $params, '', 'id,name');
    }

    protected function get_newvalues($recordid, $fields) {
        $values = [];
        foreach ($fields as $fieldid => $fieldname) {
            $param = 'field_'.$fieldid;
            $values[$fieldid] = optional_param($param, '', PARAM_TEXT);
        }
        return $values;
    }

    protected function get_oldvalues($recordid, $fields) {
        global $DB;
        $values = [];
        if ($recordid) {
            list($select, $params) = $DB->get_in_or_equal(array_keys($fields));
            $select = "recordid = ? AND fieldid $select";
            $params = array_merge([$recordid], $params);
            $values = $DB->get_records_select_menu(
                'data_content', $select, $params, '', 'fieldid,content'
            );
            if (empty($values)) {
                $values = []; // Shouldn't happen !!
            }
        }
        return $values;
    }
}
