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

// get required files
require_once($CFG->dirroot.'/mod/data/field/admin/field.class.php');

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
    const TIME_SELECT        = 5; // not implemented
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
        global $CFG; // maybe needed by types/xxx/class.php

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
                require_once($filepath);
                $this->actiontype = $actiontype;
                $this->actionclass = $actionclass;
                $this->actionfolder = $actionfolder;
                $this->actionfield = new $actionclass($this);
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
        data_field_admin::check_lang_strings($this);
        parent::display_edit_field();
    }

    /**
     * display a form element for adding/updating
     * content in an admin field in a user record
     *
     * @return HTML to send to browser
     */
    function display_add_field($recordid=0, $formdata=null) {
        return data_field_admin::format_hidden_field('field_'.$this->field->id, 0);
    }

    /**
     * update content for this field sent from the "Add entry" page
     *
     * @return boolean: TRUE if content was successfully updated; otherwise FALSE
     */
    function update_content($recordid, $value, $name='') {
        if (data_field_admin::is_new_record()) {
            $times = array(self::TIME_ADD,
                           self::TIME_ADDEDIT,
                           self::TIME_ADDEDITDELETE);
        } else {
            $times = array(self::TIME_EDIT,
                           self::TIME_ADDEDIT,
                           self::TIME_ADDEDITDELETE);
        }
        $this->execute_action($times, $recordid);
        return true;
    }

    /**
     * delete user generated content associated with an admin field
     * when the admin field is deleted from the "Fields" page
     */
    function delete_content($recordid=0) {
        $times = array(self::TIME_DELETE,
                       self::TIME_ADDEDITDELETE);
        $this->execute_action($times);
        return true;
    }

    /**
     * display this field on the "View list" or "View single" page
     */
    function display_browse_field($recordid, $template) {
        if ($template=='listtemplate') {
            $times = array(self::TIME_SHOWLIST);
            return $this->execute_action($times);
        }
        if ($template=='singletemplate') {
            $times = array(self::TIME_SHOWSINGLE);
            return $this->execute_action($times);
        }
        return '';
    }

    /**
     * display a form element for this field on the "Search" page
     *
     * @return HTML to send to browser
     */
    function display_search_field() {
        return '';
    }

    /**
     * parse search field from "Search" page
     * (required by view.php)
     */
    function parse_search_field() {
        return '';
    }

    /**
     * get_sort_field
     * (required by view.php)
     */
    function get_sort_field() {
        return '';
    }

    /**
     * get_sort_sql
     * (required by view.php)
     */
    function get_sort_sql($fieldname) {
        return '';
    }

    /**
     * generate sql required for search page
     * Note: this function is missing from the parent class :-(
     */
    function generate_sql($tablealias, $value) {
        return '';
    }

    /**
     * text export not supported for "action" fields
     */
    function text_export_supported() {
        return false;
    }

    function export_text_value($record) {
        return '';
    }

    ///////////////////////////////////////////
    // custom methods
    ///////////////////////////////////////////

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
            // self::TIME_SELECT     => get_string('timeselect',        $plugin),
            self::TIME_SHOWLIST      => get_string('timeshowlist',      $plugin),
            self::TIME_SHOWSINGLE    => get_string('timeshowsingle',    $plugin)
            //self::TIME_SPECIFIC    => get_string('timespecific',      $plugin)
        );
    }

    /**
     * execute action if this is one of the required times
     */
    public function execute_action($times, $recordid=0) {
        $param = $this->timeparam;
        if (in_array($this->field->$param, $times)) {
            return $this->actionfield->execute($recordid);
        } else {
            return '';
        }
    }

    ///////////////////////////////////////////
    // static methods
    ///////////////////////////////////////////

    /**
     * get list of font familes available in PDF documents
     *
     * @return array of font family names ($family => $fonts)
     */
    static public function get_pdf_font_list() {
        global $CFG;
        require_once($CFG->libdir.'/pdflib.php');
        if (method_exists('pdf', 'get_font_families')) {
            $doc = new pdf();
            $list = $doc->get_font_families();
        } else {
            $list = array('freemmono' => 'freemono',
                          'freesans'  => 'freesans',
                          'freeserif' => 'freeserif');
        }
        return $list;
    }

    /**
     * get information about the required PDF font
     *
     * @return array of font information ($family, $style, $size)
     */
    static public function get_pdf_font() {
        // preferably, these settings should come from the UI
        // but the action form is full and there is no way to
        // add settings for the data activity or the data module
        // Core:     courier   (b/bi/i) [monospace]
        //           helvetica (b/bi/i) [sans-serif]
        //           times     (b/bi/i) [serif]
        //           symbol
        //           zapfdingbats
        // TrueTypeUnicode:
        //           freemono  (b/bi/i)
        //           freesans  (b/bi/i)
        //           freeserif (b/bi/i)
        // Chinese:  msungstdlight
        //           stsongstdlight
        // Japanese: kozgopromedium (Sans-serif)
        //           kozminproregular (Serif)
        // Korean:   hysmyeongjostdmedium
        //
        // useful post about other PDF fonts in Moodle:
        // http://opensourceelearning.blogspot.jp/2014/10/multi-language-certificate-tips-for.html
        //
        //switch (substr(current_language(), 0, 2)) {
        //    case 'ja': $family = 'kozgopromedium';       break;
        //    case 'ko': $family = 'hysmyeongjostdmedium'; break;
        //    case 'zh': $family = 'msungstdlight';        break;
        //    default:   $family = 'freesans';
        //}
        $family = 'kozgopromedium';
        $style = '';
        $size = null;
        return array($family, $style, $size);
    }

    /**
     * convert string from HTML to PDF and send to specified $destination
     *
     * @param string $html
     * @param string $filepath
     * @param array  $options
     * @param string $destination (see Output method in "lib/tcpdf/tcpdf.php")
     * @return void, but will send PDF content to local file (F) or browser (I)
     */
    static public function create_pdf($html, $filepath, $options=array(), $destination='F') {
        $doc = new pdf();

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        if (empty($options['margins'])) {
            $options['margins'] = array(15, 30);
        } else if (is_scalar($options['margins'])) {
            $options['margins'] = array($options['margins'],
                                        $options['margins']);
        }

        if (empty($options['textcolor'])) {
            $options['textcolor'] = array(0, 0, 0); // black
        } else if (is_scalar($options['textcolor'])) {
            $options['textcolor'] = array($options['textcolor'],
                                          $options['textcolor'],
                                          $options['textcolor']);
        }

        foreach ($options as $name => $value) {
            switch ($name) {

                // basic info
                case 'title'   : $doc->SetTitle($value); break;
                case 'author'  : $doc->SetAuthor($value); break;
                case 'creator' : $doc->SetCreator($value); break;
                case 'keywords': $doc->SetKeywords($value); break;
                case 'subject' : $doc->SetSubject($value);  break;
                case 'margins' : $doc->SetMargins($value[0],
                                                  $value[1]); break;

                // header info
                case 'printheader' : $doc->setPrintHeader($value);  break;
                case 'headermargin': $doc->setHeaderMargin($value); break;
                case 'headerfont'  : $doc->setHeaderFont($value);   break;
                case 'headerdata'  : $doc->setHeaderData($value);   break;

                // footer info
                case 'printfooter' : $doc->setPrintFooter($value);  break;
                case 'footerpargin': $doc->setFooterMargin($value); break;
                case 'footerfont'  : $doc->setFooterFont($value);   break;

                // text and font info
                case 'textcolor': $doc->SetTextColor($value[0], $value[1], $value[2]); break;
                case 'fillcolor': $doc->SetFillColor($value[0], $value[1], $value[2]); break;
                case 'font'     : $doc->SetFont($value[0], $value[1], $value[2])     ; break;

            }
        }

        $doc->AddPage();
        $doc->writeHTML($html);
        $doc->Output($filepath, $destination);
    }
}
