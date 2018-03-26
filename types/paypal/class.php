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
require_once($CFG->dirroot.'/mod/data/field/action/types/class.php');

class data_field_action_paypal extends data_field_action_base {

    // https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
    // https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/formbasics/

    // You have to enable auto return in your PayPal account,
    // otherwise it will ignore the return field.

    // The "rm" flag only applies to the Continue button, not auto-return.
    // If you want the payment details POSTed to your "return" URL,
    // you'll need to use the regular Continue button.
    // We (PayPal) advise using IPN for any post-payment processing.

    // enrol/paypal/enrol.html // sample PayPal form
    // enrol/paypal/ipn.php    // sample IPN listener

    public $buttonparam  = 'param3'; // argument 1
    public $titleparam   = 'param4'; // argument 2
    public $sandboxparam = 'param5'; // argument 3

    /**
     * generate HTML code for PayPal button
     */
    public function execute($recordid=0) {
        global $CFG, $COURSE, $DB, $USER;

        $context = $this->datafield->context;
        $cm      = $this->datafield->cm;
        $data    = $this->datafield->data;
        $field   = $this->datafield->field;
        $plugin  = 'datafield_'.$field->type;

        // don't display PayPal button if a manager is viewing someone else's record
        if ($USER->id != $DB->get_field('data_records', 'userid', array('id' => $recordid))) {
        //    return '';
        }

        // don't display PayPal button to guests
        if (is_guest($context, $USER)) {
            return '';
        }

        $content = array('fieldid' => $field->id,
                         'recordid' => $recordid);
        $content = $DB->get_field('data_content', 'content', $content);

        // don't display buttons if user has already paid
        if ($content) {
            return 'Already paid';
        }

        // perhaps we cojld store this in param2
        // and use the field->description as the button title?
        $paypallang = 'JP';

        // get locale e.g. en_AU.UTF-8
        // and remove trailing ".UTF-8"
        $lang = get_string('locale', 'langconfig');
        $lang = str_replace('.UTF-8', '', $lang);

        // extract language code, e.g. "en", "ja"
        $parentlang = substr($lang, 0, 2);

        // locale codes recognized by PayPal
        // https://developer.paypal.com/docs/integration/direct/express-checkout/integration-jsv4/customize-button/#supported-locales
        $locales = array(
            'en' => array('en_US', 'en_AU', 'en_GB'),
            'da' => 'da_DK',
            'de' => 'de_DE',
            'es' => 'es_ES',
            'fr' => array('fr_FR', 'fr_CA'),
            'he' => 'he_IL',
            'id' => 'id_ID',
            'it' => 'it_IT',
            'ja' => 'ja_JP',
            'nl' => 'nl_NL',
            'no' => 'no_NO',
            'pl' => 'pl_PL',
            'pt' => array('pt_BR', 'pt_PT'),
            'ru' => 'ru_RU',
            'sv' => 'sv_SE',
            'th' => 'th_TH',
            'zh' => array('zh_CN', 'zh_HK', 'zh_TW')
        );

        if (array_key_exists($parentlang, $locales)) {
            $locale = $locales[$parentlang];
        } else {
            $locale = reset($locales);
        }
        if (is_array($locale)) {
            if (in_array($lang, $locale) && $paypallang=='') {
                // the current Moodle $lang is a recognized PayPal locale
                $locale = $lang;
            } else {
                // use the default (=first) item for this locale
                $locale = reset($locale);
            }
        }

        // $langcode
        if ($paypallang) {
            $paypallocale = $locale.'/'.$paypallang;
        } else {
            $paypallocale = $locale;
        }

        // setup URLs (always use sandbox during development)
        if (empty($field->{$this->sandboxparam})) {
            $action = 'https://www.paypal.com/cgi-bin/webscr';
        } else {
            $action = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        }
        $notify = new moodle_url('/mod/data/field/action/paypal/notify.php');
        $return = new moodle_url('/mod/data/field/action/paypal/return.php');
        $cancel = new moodle_url('/mod/data/field/action/paypal/cancel.php');

        // set up return button text (displayed on PayPal site)
        $returntext = format_string($COURSE->fullname);
        $returntext = get_string('returntosite', $plugin, $returntext);

        // add hidden values
        $values = array(
            'cmd'           => '_s-xclick',
            'charset'       => 'utf-8',
            'no_shipping'   => '1',  // 1=address info is not required
            'rm'            => '2',  // 2=values are returned via POST
            'return'        => $return->out(false),
            'cancel_return' => $cancel->out(false),
            'notify_url'    => $notify->out(false),
            'cbt'           => $returntext,
            'locale.x'      => $locale,

            // unused fields
            //'business'    => '$buttonbusiness',
            //'item_name'   => '$coursefullname',
            //'item_number' => '$courseshortname',
            //'quantity'    => '1',
            //'on0'         => get_string('user'),
            //'os0'         => fullname($USER),
            //'custom'      => '$content->id',
            //'currency_code' => '$currency',
            //'amount'      => '$cost',
            //'for_auction' => 'false',
            //'no_note'     => '1',

            // default values for users who create a new account on PayPal
            'first_name'   => $USER->firstname,
            'last_name'    => $USER->lastname,
            'email'        => $USER->email,
            //'telephone'    => $USER->phone1
            //'billingline1' => $USER->address,
            //'billingCity'  => $USER->city,
            'country'      => $USER->country
        );

        // create HTML for button
        $button = '';
        $button .= html_writer::start_tag('form', array('action' => $action,
                                                        'method' => 'post',
                                                        'target' => 'MAJ',
                                                        'class' => $plugin.'_paypal_form'))."\n";
        foreach ($values as $name => $value) {
            if (empty($value)) {
                continue;
            }
            $params = array('type' => 'hidden',
                            'name' => $name,
                            'value' => $value);
            $button .= html_writer::empty_tag('input', $params)."\n";
        }

        // add title
        $title = trim($field->{$this->titleparam});
        if ($title=='') {
            $title = get_string('argument', $plugin, 3);
        }
        $button .= html_writer::tag('div', $title, array('class' => $plugin.'_paypal_title'))."\n";

        $option = '';
        $options = array();

        // create select options
        // we expect hosted_button_id on its own line,
        // followed by lines of multilang text
        $lines = $field->{$this->buttonparam};
        $lines = preg_split('/[\r\n]+/', $lines);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);
        foreach ($lines as $line) {
            // hosted_button_id e.g. EW5WX52W69XXS
            if (preg_match('/^[A-Z0-9]{12,16}$/', $line)) {
                $option = $line;
            } else if (empty($option)) {
                // ignore lines before first valid button_id
            } else if (empty($options[$option])) {
                // add first text line
                $options[$option] = $line;
            } else {
                // append subsequent text line
                $options[$option] .= $line;
            }
        }
        $options = array_map('format_string', $options);

        // add select menu
        if (count($options)) {
            $name = 'hosted_button_id';
            $params = array('id' => 'id_'.$name,
                            'class' => $plugin.'_paypal_select');
            $button .= html_writer::select($options, $name, '', '', $params)."\n";
        }

        // add Submit button
        $params = array('class' => $plugin.'_paypal_button');
        $button .= html_writer::start_tag('div', $params)."\n";
        $params = array('alt'    => get_string('pluginname', 'enrol_paypal'),
                        'border' => 0,
                        'height' => 62,
                        'width'  => 140,
                        'type'   => 'image',
                        'name'   => 'submit',
                        'src'    => "https://www.paypalobjects.com/$paypallocale/i/btn/btn_buynowCC_LG.gif");
        $button .= html_writer::empty_tag('input', $params)."\n";

        // add tracking gif
        $params = array('alt'    => '',
                        'border' => 0,
                        'height' => 1,
                        'width'  => 1,
                        'hidden' => 'hidden',
                        'style'  => 'display: none !important;',
                        'src'    => "https://www.paypalobjects.com/$locale/i/scr/pixel.gif");
        $button .= html_writer::empty_tag('img', $params);

        $button .= html_writer::end_tag('div')."\n";

        $button = html_writer::end_tag('form').
                  $button.
                  html_writer::start_tag('form');

        return $button;
    }
}
