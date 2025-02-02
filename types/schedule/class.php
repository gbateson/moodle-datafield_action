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
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

// get required files
require_once($CFG->dirroot.'/mod/data/field/action/types/class.php');

class data_field_action_schedule extends data_field_action_base {

    /**
     * If a teacher/admin is updating the current record,
     * then update the conference schedule, if there is one
     */
    public function execute($recordid=0, $value='') {
        global $CFG, $DB;

        $filepath = $CFG->dirroot.'/blocks/maj_submissions/block_maj_submissions.php';
        if (file_exists($filepath) && is_readable($filepath)) {
            require_once($filepath);
        } else {
            return ''; // Required plugin not installed on this Moodle site.
        }

        // Initialize the conference schedule "page" object.
        $page = null;

        // Fetch the course id and context.
        $courseid = $this->datafield->data->course;
        $coursecontext = context_course::instance($courseid);

        if (has_capability('moodle/course:manageactivities', $coursecontext)) {
            $params = array('blockname' => 'maj_submissions',
                            'parentcontextid' => $coursecontext->id);
            if ($blockinstance = $DB->get_record('block_instances', $params)) {
                $instance = block_instance('maj_submissions', $blockinstance);
                if ($cmid = $instance->config->publishcmid) {
                    if ($cm = get_coursemodule_from_id('page', $cmid, $courseid)) {
                        $page = $DB->get_record('page', array('id' => $cm->instance));
                    }
                }
            }
        }

        if (empty($page) || empty($page->content)) {
            return ''; // There is no conference schedule in this course.
        }

        $search = '/<td[^>]*id="id_recordid_'.$recordid.'"[^>]*>(.*?)<\/td>/ius';
        if (preg_match($search, $page->content, $matches, PREG_OFFSET_CAPTURE)) {
            list($match, $start) = $matches[1];

			// get all field values in this data record
			$sql = 'SELECT df.name, dc.content '.
			         'FROM {data_content} dc, {data_fields} df '.
			        'WHERE dc.recordid = ? AND dc.fieldid = df.id '.
			          'AND df.type NOT IN (?, ?, ?, ?, ?, ?)';
            $params = array($recordid, 'action', 'constant', 'file', 'picture', 'template', 'url');
            if ($item = $DB->get_records_sql_menu($sql, $params)) {
                $item = block_maj_submissions::format_item($instance, $recordid, $item, false);
                $page->content = substr_replace($page->content, $item, $start, strlen($match));
                $DB->update_record('page', $page); // always returns TRUE
                return get_string('scheduleupdated', 'datafield_action');
            }
        }

        return ''; // This presentation record is not yet in the schedule.
    }
}
