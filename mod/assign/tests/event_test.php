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
 * Contains the event tests for the module assign.
 *
 * @package mod_assign
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Contains the event tests for the module assign.
 *
 * @package mod_assign
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_events_testcase extends mod_assign_base_testcase {

    /**
     * Tests for the submission_updated() class which is fired through assign::save_submission().
     */
    public function test_submission_updated() {
        global $PAGE;
        $this->resetAfterTest();

        $this->create_extra_users();

        $params = array(
            'teamsubmission' => 1,
            'assignsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 1,
            'requireallteammemberssubmit' => 1
        );
        $assign = $this->create_instance($params);
        $context = $assign->get_context();
        $course = $assign->get_course();

        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        $this->setUser($this->extrastudents[0]);
        // Add a submission.
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_MOODLE);

        $submission = $assign->get_group_submission($this->extrastudents[0]->id, 0, true);

        $notices = array();
        $sink = $this->redirectEvents();
        $assign->save_submission($data, $notices);

        $result = $sink->get_events();
        // The event that we want to test is currently the second one.
        $event = $result[1];
        $sink->close();

        $this->assertInstanceOf('\mod_assign\event\submission_updated', $event);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);
        $this->assertEquals($submission->userid, $event->relateduserid);
        $this->assertEquals($params['teamsubmission'], $event->other['teamsubmission']);
        $this->assertEquals($assign->get_submission_group($this->extrastudents[0])->id, $event->other['groupid']);
        $this->assertEquals($submission->status, $event->other['submissionstatus']);
        $this->assertEquals($submission->attemptnumber, $event->other['attemptnumber']);

        $expected = $assign->add_to_log('submit', $assign->testable_format_submission_for_log($submission), '', true);
        $this->assertEventLegacyLogData($expected, $event);

    }
}