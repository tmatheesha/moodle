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
 * mod_assign submission updated event.
 *
 * @package    mod_assign
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assign\event;

defined('MOODLE_INTERNAL') || die();

/**
 * mod_assign submission updated event class.
 *
 * @property-read array $other {
 *     Extra information about the event.
 *
 *     @type int attemptnumber Number of attempts made on this submission.
 *     @type int submissionstatus Status of the submission.
 *     @type array submissiontype The submission types used in this assignment.
 *     @type bool teamsubmission Is this a team submission (optional)?
 *     @type int groupid The group ID if this is a team submission (optional).
 * }
 *
 * @package    mod_assign
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_updated extends \core\event\base {

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if (isset($this->other['teamsubmission'])) {
            return "The user {$this->userid} has updated the submission {$this->objectid} for group {$this->other['groupid']}.";
        } else {
            return "The user {$this->userid} has updated the submission {$this->objectid}.";
        }
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return $this->legacylogdata;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_submission_updated', 'mod_assign');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/assign/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'assign_submission';
    }

    /**
     * Sets the legacy event log data.
     *
     * @param stdClass $legacylogdata legacy log data.
     * @return void
     */
    public function set_legacy_logdata($legacylogdata) {
        $this->legacylogdata = $legacylogdata;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        if (!isset($this->other['attemptnumber'])) {
            throw new \coding_exception('Other must contain the key attemptnumber.');
        }
        if (!isset($this->other['submissionstatus'])) {
            throw new \coding_exception('Other must contain the key submissionstatus.');
        }
        if (!isset($this->other['submissiontype'])) {
            throw new \coding_exception('Other must contain the key submissiontype.');
        }
    }
}
