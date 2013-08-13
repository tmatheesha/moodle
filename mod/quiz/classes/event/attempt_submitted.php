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
 * Quiz module event class.
 *
 * @package    mod_quiz
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_quiz\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event for when a quiz attempt is submitted.
 *
 * @package    mod_quiz
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_submitted extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'quiz_attempts';
        $this->data['crud'] = 'u';
        $this->data['level'] = 50; // TODO MDL-41040.
    }

    /**
     * Returns localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return 'A quiz with the id of ' . $this->other['quizid'] . 'has been marked as submitted for the user with the id of '. $this->relateduserid;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return new \lang_string('eventquizattemptsubmitted', 'mod_quiz');
    }

    /**
     * Does this event replace a legacy event?
     *
     * @return string legacy event name
     */
    static public function get_legacy_eventname() {
        return 'quiz_attempt_submitted';
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $url = "/mod/quiz/view.php";
        return new \moodle_url($url, array('id'=>$this->context->instanceid));
    }

    /**
     * Legacy event data if get_legacy_eventname() is not empty.
     *
     * @return stdClass
     */
    protected function get_legacy_eventdata() {
        $legacyeventdata = new \stdClass();
        $legacyeventdata->component = 'mod_quiz';
        $legacyeventdata->attemptid = $this->objectid;
        $legacyeventdata->timestamp = $this->timecreated;
        $legacyeventdata->userid = $this->relateduserid;
        $legacyeventdata->quizid = $this->other['quizid'];
        $legacyeventdata->cmid = $this->context->instanceid;
        $legacyeventdata->courseid = $this->courseid;
        $legacyeventdata->submitterid = $this->other['submitterid'];
        $legacyeventdata->timefinish = $this->other['timefinish'];

        return $legacyeventdata;
    }

    /**
     * Custom validation.
     *
     * @throws coding_exception
     * @return void
     */
    protected function validate_data() {
        if (!isset($this->other['quizid'])) {
            throw new \coding_exception('Other must contain the key quizid');
        }
        if (!isset($this->other['submitterid'])) {
            throw new \coding_exception('Other must contain the key submitterid');
        }
        if (!isset($this->other['timefinish'])) {
            throw new \coding_exception('Other must contain the key timefinish');
        }
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('relateduserid can not be left empty.');
        }
        if (!isset($this->objectid)) {
            throw new \coding_exception('objectid can not be left empty.');
        }
        if (!isset($this->courseid)) {
            throw new \coding_exception('courseid can not be left empty.');
        }
    }
}
