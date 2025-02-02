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

// get required files
require_once($CFG->dirroot.'/lib/pdflib.php');
require_once($CFG->dirroot.'/mod/data/field/action/types/class.php');
require_once($CFG->dirroot.'/mod/data/field/template/field.class.php');

class data_field_action_confirm extends data_field_action_base {

    /** the field params that hold the message info */
    public $subjectparam = 'param3'; // argument 1
    public $messageparam = 'param4'; // argument 2
    public $pdfparam     = 'param5'; // argument 3

    public function execute($recordid=0, $value='') {
        global $CFG, $DB, $USER;

        $context = $this->datafield->context;
        $cm      = $this->datafield->cm;
        $data    = $this->datafield->data;
        $field   = $this->datafield->field;

        // don't send mail if mailing is disabled (development sites)
        if (! empty($CFG->noemailever)) {
            return '';
        }

        // don't send mail from localhost
        if (preg_match('/^https?:\/\/localhost/', $CFG->wwwroot)) {
            return '';
        }

        // don't send mail if a manager has just added/edited someone else's record
        if ($USER->id != $DB->get_field('data_records', 'userid', array('id' => $recordid))) {
            return ''; // shouldn't happen !!
        }

        // to prevent this action being used to send spam,
        // we don't send email to guest users
        if (is_guest($context, $USER)) {
            return '';
        }

        // define template format to be used
        // when formatting fields in template
        $template = 'singlelist';

        // define options for manipulating files and text formats
        $fileoptions = data_field_admin::get_fileoptions($context);
        $formatoptions = data_field_admin::get_formatoptions();

        if (empty($field->id)) {
            return ''; // shouldn't happen !!
        }
        $itemid = $field->id;

        // "subject" is a required field
        $param = $this->subjectparam;
        if (! $subject = $field->$param) {
            return '';
        }

        // "message" is a required field
        $param = $this->messageparam;
        if (! $message = $field->$param) {
            return '';
        }

        // replace fieldnames with field values in $message
        // and then generate an HTML version of the $message
        $message = file_rewrite_pluginfile_urls($message, 'pluginfile.php', $context->id, 'mod_data', 'content', $itemid, $fileoptions);
        $message = data_field_template::replace_if_blocks($context, $cm, $data, $field, $recordid, $template, $USER, $message);
        $message = data_field_template::replace_fieldnames($context, $cm, $data, $field, $recordid, $template, $USER, $message);
        $messagehtml = format_text($message, FORMAT_MOODLE, $formatoptions);

        // the pdf will be sent as an attachment
        // if a separate pdf message is not supplied
        // the email $message will be used as the default
        $param = $this->pdfparam;
        if ($pdf = $field->$param) {
            $pdf = file_rewrite_pluginfile_urls($pdf, 'pluginfile.php', $context->id, 'mod_data', 'content', $itemid, $fileoptions);
            $pdf = data_field_template::replace_if_blocks($data, $field, $recordid, $template, $pdf);
            $pdf = data_field_template::replace_fieldnames($context, $cm, $data, $field, $recordid, $template, $USER, $pdf);
            $pdf = format_text($pdf, FORMAT_MOODLE, $formatoptions);
        } else {
            $pdf = $messagehtml; // the default
        }

        // create fullpath to PDF file
        $attachname = "$field->name.$recordid.pdf";
        $attachment = $CFG->tempdir.'/'.$attachname;

        // convert HTML content to PDF and store in $attachment file
        $options = array('subject' => $subject,
                         'font' => data_field_action::get_pdf_font());
        data_field_action::create_pdf($pdf, $attachment, $options);

        // email the message with PDF $attachment
        if (class_exists('core_user')) {
            // Moodle >= 2.6
            $noreply = core_user::get_noreply_user();
        } else {
            // Moodle <= 2.5
            $noreply = generate_email_supportuser();
        }
        email_to_user($USER, $noreply, $subject, $message, $messagehtml, $attachment, $attachname);

        // remove PDF file from server
        if (file_exists($attachment)) {
            unlink($attachment);
        }

        return 'Confirmation email was sent to '.$USER->email;
    }
}
