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
 * Lesson module external API
 *
 * @package    mod_lesson
 * @category   external
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/lesson/lib.php');

/**
 * Choice module external functions
 *
 * @package    mod_lesson
 * @category   external
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class mod_lesson_external extends external_api {

    public static function add_page_parameters() {
        return new external_function_parameters(
            array(
                'lessonid' => new external_value(PARAM_INT, 'lesson module ID'),
                'pageid' => new external_value(PARAM_INT, 'lesson page ID'),
                'title' => new external_value(PARAM_TEXT, 'lesson page title', VALUE_OPTIONAL)
            )
        );
    }

    public static function add_page($lessonid, $pageid, $title) {
        // $params = self::validate_parameters(self::add_page_parameters(),
        //         array($lessonid, $pageid, $title));
        $errors = array();
        if (empty($title)) {
            $errors["code"] = "formvalidationfailed";
            $errors["message"] = "Validation Failed";
            $errors["errors"] = array('code' => 'notitlefound', 'name' => 'title', 'message' => 'Title must not be empty');
            return array('status' => false, 'warnings' => $errors);
        }
        return array('status' => true, 'warnings' => $errors);
    }

    public static function add_page_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }

}