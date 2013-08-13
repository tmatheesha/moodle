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
 * An event observer file for mod_quiz
 *
 * @package    mod_quiz
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/quiz/locallib.php');

/**
 * mod_quiz observer class.
 *
 * @package    mod_quiz
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_observer {

    /**
     * Triggered when 'attempt_submitted' event is triggered.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     * @return bool
     */
    public static function attempt_submitted_observer(\mod_quiz\event\attempt_submitted $event) {
        global $DB;

        $context = $event->get_context();
        $course  = get_course($event->courseid);
        $quiz    = $DB->get_record('quiz', array('id' => $event->other['quizid']));
        $cm      = get_coursemodule_from_id('quiz', $context->instanceid, $event->courseid);
        $attempt = $DB->get_record('quiz_attempts', array('id' => $event->objectid));

        if (!($course && $quiz && $cm && $attempt)) {
            // Something has been deleted since the event was raised. Therefore, the
            // event is no longer relevant.
            return true;
        }

        return quiz_send_notification_messages($course, $quiz, $attempt, $context, $cm);
    }

    /**
     * Triggered when 'attempt_timelimit_exceeded' event is triggered.
     *
     * @param \mod_quiz\event\attempt_timelimit_exceeded $event
     * @return bool
     */
    public static function attempt_timelimit_exceeded_observer(\mod_quiz\event\attempt_timelimit_exceeded $event) {
        global $DB;

        $context = $event->get_context();
        $course  = get_course($event->courseid);
        $quiz    = $DB->get_record('quiz', array('id' => $event->other['quizid']));
        $cm      = get_coursemodule_from_id('quiz', $context->instanceid, $event->courseid);
        $attempt = $DB->get_record('quiz_attempts', array('id' => $event->objectid));

        if (!($course && $quiz && $cm && $attempt)) {
            // Something has been deleted since the event was raised. Therefore, the
            // event is no longer relevant.
            return true;
        }

        return quiz_send_overdue_message($course, $quiz, $attempt, $context, $cm);
    }
}
