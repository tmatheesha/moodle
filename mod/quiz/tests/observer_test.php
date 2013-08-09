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
 * Quiz observer tests
 *
 * @package    mod_quiz
 * @category   phpunit
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/quiz/classes/observer.php');

/**
 * Unit tests for quiz observer.php
 *
 * @package    mod_quiz
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_observer_testcase extends advanced_testcase {

    private function prepare_quiz_data() {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        // Create a course
        $course = $this->getDataGenerator()->create_course();
        role_assign(1, $user->id, context_course::instance($course->id));

        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance(array('course'=>$course->id, 'questionsperpage' => 0, 'grade' => 100.0,
                                                      'sumgrades' => 2, 'timelimit' => 60));

        $attemptrecord = array();
        $attemptrecord['quiz'] = $quiz->id;
        $attemptrecord['userid'] = $user->id;
        $attemptrecord['state'] = 'inprogress';
        $attemptrecord['timestart'] = 100;
        $attemptrecord['timecheckstate'] = 0;
        $attemptrecord['layout'] = '';
        $attemptrecord['uniqueid'] = 1;

        $attemptrecord['id'] = $DB->insert_record('quiz_attempts', $attemptrecord);

        $quizdata = array();
        $quizdata['context'] = context_module::instance($quiz->id);
        $quizdata['courseid'] = $course->id;
        $quizdata['relateduserid'] = $user->id;
        $quizdata['objectid'] = $attemptrecord['id'];
        $quizdata['other'] = array();
        $quizdata['other']['quizid'] = $quiz->id;
        $quizdata['other']['submitterid'] = $user->id;
        $quizdata['other']['timefinish'] = time();

        assign_capability('mod/quiz:emailwarnoverdue', CAP_ALLOW, 1, $quizdata['context']->id);
        assign_capability('mod/quiz:emailnotifysubmission', CAP_ALLOW, 1, $quizdata['context']->id);
        return $quizdata;
    }

    public function test_attempt_submitted_observer() {

        $eventdata = $this->prepare_quiz_data();
        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        $event = \mod_quiz\event\attempt_submitted::create($eventdata);
        $success = mod_quiz_observer::attempt_submitted_observer($event);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
    }

    public function test_attempt_timelimit_exceeded_observer() {

        $eventdata = $this->prepare_quiz_data();
        $this->preventResetByRollback();
        $sink = $this->redirectMessages();

        $event = \mod_quiz\event\attempt_timelimit_exceeded::create($eventdata);
        $success = mod_quiz_observer::attempt_timelimit_exceeded_observer($event);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
    }
}
