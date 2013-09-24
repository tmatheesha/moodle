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
 * Events tests.
 *
 * @package    mod_choice
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/choice/lib.php');

/**
 * Events tests class.
 *
 * @package    mod_choice
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_choice_events_testcase extends advanced_testcase {

    /**
     * Test to ensure that event data is being stored correctly.
     */
    public function test_answer_created() {
        global $DB;

        $this->resetAfterTest();

        // Generate course, user and module data.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $choice = $this->getDataGenerator()->create_module('choice', array('course' => $course->id));
        $cm = $DB->get_record('course_modules', array('id' => $choice->cmid));

        // Redirect event.
        $sink = $this->redirectEvents();
        choice_user_submit_response(2, $choice, $user->id, $course, $cm);
        $events = $sink->get_events();

        // Data checking.
        $this->assertCount(1, $events);
        $this->assertInstanceOf('\mod_choice\event\answer_created', $events[0]);
        $this->assertEquals($user->id, $events[0]->userid);
        $this->assertEquals(context_module::instance($choice->id), $events[0]->get_context());
        $this->assertEquals(1, $events[0]->other['choiceid']);
        $this->assertEquals(2, $events[0]->other['optionid']);
        $expected = array($course->id, "choice", "choose", "view.php?id=$cm->id", $choice->id, $cm->id);
        $this->assertEventLegacyLogData($expected, $events[0]);
        $sink->close();
    }

    /**
     * Test to ensure that event data is being stored correctly.
     */
    public function test_answer_updated() {
        global $DB;

        $this->resetAfterTest();

        // Generate course, user and module data.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $choice = $this->getDataGenerator()->create_module('choice', array('course' => $course->id));
        $cm = $DB->get_record('course_modules', array('id' => $choice->cmid));

        // Create the first answer.
        choice_user_submit_response(2, $choice, $user->id, $course, $cm);

        // Redirect event.
        $sink = $this->redirectEvents();
        // Now choose a different answer.
        choice_user_submit_response(3, $choice, $user->id, $course, $cm);

        $events = $sink->get_events();

        // Data checking.
        $this->assertCount(1, $events);
        $this->assertInstanceOf('\mod_choice\event\answer_updated', $events[0]);
        $this->assertEquals($user->id, $events[0]->userid);
        $this->assertEquals(context_module::instance($choice->id), $events[0]->get_context());
        $this->assertEquals(1, $events[0]->other['choiceid']);
        $this->assertEquals(3, $events[0]->other['optionid']);
        $expected = array($course->id, "choice", "choose again", "view.php?id=$cm->id", $choice->id, $cm->id);
        $this->assertEventLegacyLogData($expected, $events[0]);
        $sink->close();
    }

    /**
     * Test to ensure that event data is being stored correctly.
     */
    public function test_report_viewed() {
        global $DB, $USER;

        $this->resetAfterTest();

        // Generate course, user and module data.
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $choice = $this->getDataGenerator()->create_module('choice', array('course' => $course->id));
        $context = context_module::instance($choice->id);

        $eventdata = array();
        $eventdata['objectid'] = $choice->id;
        $eventdata['context'] = $context;
        $eventdata['courseid'] = $course->id;

        // This is fired in a page view so we can't run this through a function.
        $event = \mod_choice\event\report_viewed::create($eventdata);

        // Redirect event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $event = $sink->get_events();

        // Data checking.
        $this->assertCount(1, $event);
        $this->assertInstanceOf('\mod_choice\event\report_viewed', $event[0]);
        $this->assertEquals($USER->id, $event[0]->userid);
        $this->assertEquals(context_module::instance($choice->id), $event[0]->get_context());
        $expected = array($course->id, "choice", "report", "report.php?id=$context->instanceid", $choice->id, $context->instanceid);
        $this->assertEventLegacyLogData($expected, $event[0]);
        $sink->close();
    }

    /**
     * Test to ensure that event data is being stored correctly.
     */
    public function test_course_module_viewed() {
        global $DB, $USER;

        $this->resetAfterTest();

        // Generate course, user and module data.
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $choice = $this->getDataGenerator()->create_module('choice', array('course' => $course->id));
        $context = context_module::instance($choice->id);

        $eventdata = array();
        $eventdata['objectid'] = $choice->id;
        $eventdata['context'] = $context;
        $eventdata['courseid'] = $course->id;

        // This is fired in a page view so we can't run this through a function.
        $event = \mod_choice\event\course_module_viewed::create($eventdata);

        // Redirect event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $event = $sink->get_events();

        // Data checking.
        $this->assertCount(1, $event);
        $this->assertInstanceOf('\mod_choice\event\course_module_viewed', $event[0]);
        $this->assertEquals($USER->id, $event[0]->userid);
        $this->assertEquals(context_module::instance($choice->id), $event[0]->get_context());
        $expected = array($course->id, "choice", "view", "view.php?id=$context->instanceid", $choice->id, $context->instanceid);
        $this->assertEventLegacyLogData($expected, $event[0]);
        $sink->close();
    }
}
