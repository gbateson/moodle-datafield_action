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
 * Class to represent a "datafield_action" field
 *
 * this field acts as an extra API layer to restrict view and
 * edit access to any other type of field in a database activity
 *
 * @package    data
 * @subpackage datafield_action
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

class data_field_action extends data_field_base {

    /**
     * the main type of this database field
     * as required by the database module
     */
    var $type = 'action';

    ///////////////////////////////////////////
    // custom properties
    ///////////////////////////////////////////

    /**
     * the actiontype of this action field
     * e.g. radiobutton
     */
    var $actiontype = '';

    /**
     * the PHP class of the actiontype of this action field
     * e.g. database_field_radiobutton
     */
    var $actionclass = '';

    /**
     * the full path to the folder of the actionfield of this field
     * e.g. /PATH/TO/MOODLE/mod/data/field/action/types/confirm
     */
    var $actionfolder = '';

    /**
     * an object containing the actionfield for this field
     */
    var $actionfield = null;

    /**
     * the $field property that contains the actiontype of this field
     */
    var $typeparam = 'param1';

    /**
     * the $field property that contains the actiontime of this field
     */
    var $timeparam = 'param2';

    ///////////////////////////////////////////
    // Custom constants
    ///////////////////////////////////////////

    const TIME_ADD           = 0;
    const TIME_EDIT          = 1;
    const TIME_DELETE        = 2;
    const TIME_ADDEDIT       = 3;
    const TIME_ADDEDITDELETE = 4;
    const TIME_SELECT        = 5;
    const TIME_SHOWLIST      = 6;
    const TIME_SHOWSINGLE    = 7;
    const TIME_SPECIFIC      = 8; // not implemented

    ///////////////////////////////////////////
    // standard methods
    ///////////////////////////////////////////

    /**
     * constructor
     *
     * @param object $field record from "data_fields" table
     * @param object $data record from "data" table
     * @param object $cm record from "course_modules" table
     */
    function __construct($field=0, $data=0, $cm=0) {

        // set up this field in the normal way
        parent::__construct($field, $data, $cm);

        // fetch the actionfield if there is one
        $type = $this->typeparam;
        if (isset($field->$type)) {
            $actiontype = $field->$type; // e.g. confirm
            $actionclass = 'data_field_action_'.$actiontype;
            $actionfolder = self::get_action_types_path($actiontype);
            $filepath = $actionfolder.'/class.php';
            if (file_exists($filepath)) {
                require_once(self::get_action_types_path('class.php'));
                require_once($filepath);
                $this->actiontype = $actiontype;
                $this->actionclass = $actionclass;
                $this->actionfolder = $actionfolder;
                $this->actionfield = new $actionclass($field, $data, $cm);
            }
        }
    }

    /**
     * default field values for new ACTION field
     */
    function define_default_field() {
        parent::define_default_field();
        $this->field->param1 = '';
        $this->field->param2 = '';
        $this->field->param3 = '';
        $this->field->param4 = '';
        $this->field->param5 = '';
        return true;
    }

    /**
     * displays the settings for this action field on the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function display_edit_field() {
        global $CFG, $OUTPUT;
        if (empty($this->field->id)) {
            $strman = get_string_manager();
            if (! $strman->string_exists($this->type, 'data')) {
                $msg = (object)array(
                    'langfile' => $CFG->dirroot.'/mod/data/lang/en/data.php',
                    'readfile' => $CFG->dirroot.'/mod/data/field/action/README.txt',
                );
                $msg = get_string('fixlangpack', 'datafield_'.$this->type, $msg);
                $msg = format_text($msg, FORMAT_MARKDOWN);
                $msg = html_writer::tag('div', $msg, array('class' => 'alert', 'style' => 'width: 100%; max-width: 640px;'));
                echo $msg;
            }
        }
        parent::display_edit_field();
    }

    /**
     * update content for this field sent from the "Add entry" page
     *
     * @return boolean: TRUE if content was sccessfully updated; otherwise FALSE
     */
    function update_content($recordid, $value, $name='') {
        die('update_content in ACTION field - what do we do here?');
        return false;
    }

    /**
     * display this field on the "View list" or "View single" page
     */
    function display_browse_field($recordid, $template) {
        if ($this->is_viewable) {
            if ($this->actionfield) {
                return $this->actionfield->display_browse_field($recordid, $template);
            } else {
                return parent::display_browse_field($recordid, $template);
            }
        }
        return ''; // field is not viewable
    }

    /**
     * display a form element for this field on the "Search" page
     *
     * @return HTML to send to browser
     */
    function display_search_field() {
        return ''; // field is not searchable
    }

    /**
     * add extra HTML before the form on the "Add entry" page
     * Note: this method doesn't seem to be used anywhere !!
     */
    function print_before_form() {
        return '';
    }

    /**
     * add extra HTML after the form on the "Add entry" page
     */
    function print_after_form() {
        return '';
    }

    /**
     * parse search field from "Search" page
     */
    function parse_search_field() {
        return '';
    }

    function get_sort_field() {
        if ($this->actionfield) {
            return $this->actionfield->get_sort_field();
        } else {
            return parent::get_sort_field();
        }
    }

    function get_sort_sql($fieldname) {
        if ($this->actionfield) {
            return $this->actionfield->get_sort_sql($fieldname);
        } else {
            return parent::get_sort_sql($fieldname);
        }
    }

    function text_export_supported() {
        if ($this->actionfield) {
            return $this->actionfield->text_export_supported();
        } else {
            return parent::text_export_supported();
        }
    }

    function export_text_value($record) {
        if ($this->actionfield) {
            return $this->actionfield->export_text_value($record);
        } else {
            return parent::export_text_value($record);
        }
    }

    function file_ok($relativepath) {
        if ($this->actionfield) {
            return $this->actionfield->file_ok($relativepath);
        } else {
            return parent::file_ok($relativepath);
        }
    }

    /**
     * generate sql required for search page
     * Note: this function is missing from the parent class :-(
     */
    function generate_sql($tablealias, $value) {
        if ($this->is_viewable && $this->actionfield) {
            return $this->actionfield->generate_sql($tablealias, $value);
        } else {
            return '';
        }
    }

    ///////////////////////////////////////////
    // custom methods
    ///////////////////////////////////////////

    /**
     * Allow access to actionfield values
     * even though the actionfield may not be viewable.
     * This allows the value to be used in IF-THEN-ELSE
     * conditions within "template" fields.
     */
    function get_condition_value($recordid, $template) {
        $is_viewable = $this->is_viewable;
        $this->is_viewable = true;
        $value = $this->display_browse_field($recordid, $template);
        $this->is_viewable = $is_viewable;
        return $value;
    }

    /**
     * get options for field accessibility (for display in mod.html)
     */
    public function get_access_types() {
        return array(self::ACCESS_NONE => get_string('accessnone', 'datafield_action'),
                     self::ACCESS_VIEW => get_string('accessview', 'datafield_action'),
                     self::ACCESS_EDIT => get_string('accessedit', 'datafield_action'));
    }

    /**
     * format a table row in mod.html
     */
    public function format_table_row($name, $label, $text) {
        $label = $this->format_edit_label($name, $label);
        $output = $this->format_table_cell($label, 'c0').
                  $this->format_table_cell($text, 'c1');
        $output = html_writer::tag('tr', $output, array('class' => $name));
        return $output;
    }

    /**
     * format a table cell in mod.html
     */
    public function format_table_cell($text, $class) {
        return html_writer::tag('td', $text, array('class' => $class));
    }

    /**
     * format a label in mod.html
     */
    public function format_edit_label($name, $label) {
        return html_writer::tag('label', $label, array('for' => $name));
    }

    /**
     * format a hidden field in mod.html
     */
    public function format_edit_hiddenfield($name, $value) {
        $params = array('type'  => 'hidden',
                        'name'  => $name,
                        'value' => $value);
        return html_writer::empty_tag('input', $params);
    }

    /**
     * format a text field in mod.html
     */
    public function format_edit_textfield($name, $value, $class, $size=10) {
        $params = array('type'  => 'text',
                        'id'    => 'id_'.$name,
                        'name'  => $name,
                        'value' => $value,
                        'class' => $class,
                        'size'  => $size);
        return html_writer::empty_tag('input', $params);
    }

    /**
     * format a textarea field in mod.html
     */
    public function format_edit_textarea($name, $value, $class, $rows=3, $cols=40) {
        $params = array('id'    => 'id_'.$name,
                        'name'  => $name,
                        'class' => $class,
                        'rows'  => $rows,
                        'cols'  => $cols);
        return html_writer::tag('textarea', $value, $params);
    }

    /**
     * format a select field in mod.html
     */
    public function format_edit_selectfield($name, $values, $default='') {
        if (isset($this->field->$name)) {
            $default = $this->field->$name;
        }
        return html_writer::select($values, $name, $default, '');
    }

    /**
     * get path to action types folder
     */
    public function get_action_types_path($extra='') {
        global $CFG;
        $path = $CFG->dirroot.'/mod/data/field/action/types';
        if ($extra) {
            $path .= '/'.$extra;
        }
        return $path;
    }

    /**
     * get list of action types
     */
    public function get_action_types() {
        $types = array();
        $plugin = 'datafield_action';
        $strman = get_string_manager();
        $items = $this->get_action_types_path();
        $items = new DirectoryIterator($items);
        foreach ($items as $item) {
            if ($item->isDot() || substr($item, 0, 1)=='.' || trim($item)=='') {
                continue;
            }
            if ($item->isDir()) {
                $type = "$item"; // convert $item to string
                if ($strman->string_exists($type, $plugin)) {
                    $types[$type] = get_string($type, $plugin);
                } else {
                    $types[$type] = $type;
                }
            }
        }
        asort($types);
        return $types;
    }

    /**
     * get list of action times
     */
    public function get_action_times() {
        $plugin = 'datafield_action';
        return array(
            self::TIME_ADD           => get_string('timeadd',           $plugin),
            self::TIME_EDIT          => get_string('timeedit',          $plugin),
            self::TIME_DELETE        => get_string('timedelete',        $plugin),
            self::TIME_ADDEDIT       => get_string('timeaddedit',       $plugin),
            self::TIME_ADDEDITDELETE => get_string('timeaddeditdelete', $plugin),
            self::TIME_SELECT        => get_string('timeselect',        $plugin),
            self::TIME_SHOWLIST      => get_string('timeshowlist',      $plugin),
            self::TIME_SHOWSINGLE    => get_string('timeshowsingle',    $plugin)
            //self::TIME_SPECIFIC      => get_string('timespecific',    $plugin)
        );
    }
}
