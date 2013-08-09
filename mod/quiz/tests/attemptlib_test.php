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
 * Quiz attemptlib tests
 *
 * @package    mod_quiz
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->dirroot . '/mod/quiz/editlib.php');

/**
 * Unit tests for quiz attemptlib.php
 *
 * @package    mod_quiz
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_attemptlib_testcase extends advanced_testcase {

    private function prepare_quiz_data() {

        $this->resetAfterTest(true);

        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance(array('course'=>$course->id, 'questionsperpage' => 0, 'grade' => 100.0,
                                                      'sumgrades' => 2));

        $cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));

        // Add them to the quiz.
        quiz_add_quiz_question($saq->id, $quiz);
        quiz_add_quiz_question($numq->id, $quiz);

        // Make a user to do the quiz.
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $quizobj = quiz::create($quiz->id, $user1->id);

        // Start the attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow);

        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);

        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $prefix1 = $quba->get_field_prefix(1);
        $prefix2 = $quba->get_field_prefix(2);

        $tosubmit = array(1 => array('answer' => 'frog'),
                          2 => array('answer' => '3.14'));

        $data = array();
        $data['attemptid'] = $attempt->id;
        $data['cm'] = $cm;
        $data['quizmoduleid'] = $quiz->id;
        $data['userid'] = $user1->id;
        $data['courseid'] = $course->id;
        return $data;
    }

    public function test_attempt_submitted() {

        $quizdata = $this->prepare_quiz_data();
        $attemptobj = quiz_attempt::create($quizdata['attemptid']);

        // Catch the event.
        $sink = $this->redirectEvents();

        $timefinish = time();
        $attemptobj->process_finish($timefinish, false);

        $events = $sink->get_events();
        $sink->close();

        // Validate the event.
        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf('\mod_quiz\event\attempt_submitted', $event);
        $this->assertEquals('quiz_attempts', $event->objecttable);
        $this->assertEquals($quizdata['cm']->id, $event->get_context()->instanceid);
        $this->assertEquals(context_module::instance($quizdata['quizmoduleid'])->id, $event->get_context()->id);
        $expected = new stdClass();
        $expected->component = 'mod_quiz';
        $expected->attemptid = $quizdata['attemptid'];
        $expected->timestamp = $event->timecreated;
        $expected->userid = $quizdata['userid'];
        $expected->cmid = $quizdata['cm']->id;
        $expected->courseid = $quizdata['courseid'];
        $expected->quizid = $quizdata['quizmoduleid'];
        $expected->submitterid = $quizdata['userid'];
        $expected->timefinish = $timefinish;
        $this->assertEventLegacyData($expected, $event);
    }

    public function test_attempt_timelimit_exceeded() {

        $quizdata = $this->prepare_quiz_data();
        $attemptobj = quiz_attempt::create($quizdata['attemptid']);

        // Catch the event.
        $sink = $this->redirectEvents();
        $timefinish = time();
        $attemptobj->process_going_overdue($timefinish, false);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf('\mod_quiz\event\attempt_timelimit_exceeded', $event);
        $this->assertEquals('quiz_attempts', $event->objecttable);
        $this->assertEquals($quizdata['cm']->id, $event->get_context()->instanceid);
        $this->assertEquals(context_module::instance($quizdata['quizmoduleid'])->id, $event->get_context()->id);
        $expected = new stdClass();
        $expected->component = 'mod_quiz';
        $expected->attemptid = $quizdata['attemptid'];
        $expected->timestamp = $event->timecreated;
        $expected->userid = $quizdata['userid'];
        $expected->cmid = $quizdata['cm']->id;
        $expected->courseid = $quizdata['courseid'];
        $expected->quizid = $quizdata['quizmoduleid'];
        $expected->submitterid = $quizdata['userid'];
        $this->assertEventLegacyData($expected, $event);
    }

    public function test_attempt_abandoned() {

        $quizdata = $this->prepare_quiz_data();
        $attemptobj = quiz_attempt::create($quizdata['attemptid']);

        // Catch the event.
        $sink = $this->redirectEvents();
        $timefinish = time();
        $attemptobj->process_abandon($timefinish, false);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf('\mod_quiz\event\attempt_abandoned', $event);
        $this->assertEquals('quiz_attempts', $event->objecttable);
        $this->assertEquals($quizdata['cm']->id, $event->get_context()->instanceid);
        $this->assertEquals(context_module::instance($quizdata['quizmoduleid'])->id, $event->get_context()->id);
        $expected = new stdClass();
        $expected->component = 'mod_quiz';
        $expected->attemptid = $quizdata['attemptid'];
        $expected->timestamp = $event->timecreated;
        $expected->userid = $quizdata['userid'];
        $expected->cmid = $quizdata['cm']->id;
        $expected->courseid = $quizdata['courseid'];
        $expected->quizid = $quizdata['quizmoduleid'];
        $expected->submitterid = $quizdata['userid'];
        $this->assertEventLegacyData($expected, $event);
    }
}
