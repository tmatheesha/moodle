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
 * Unit tests for /lib/gradelib.php.
 *
 * @package   core_grade
 * @category  phpunit
 * @copyright 2012 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/gradelib.php');

class core_gradelib_testcase extends advanced_testcase {

    /**
     * Define a local decimal separator.
     *
     * It is not possible to directly change the result of get_string in
     * a unit test. Instead, we create a language pack for language 'xx' in
     * dataroot and make langconfig.php with the string we need to change.
     * The example separator used here is 'X'; on PHP 5.3 and before this
     * must be a single byte character due to PHP bug/limitation in
     * number_format, so you can't use UTF-8 characters.
     */
    protected function define_local_decimal_separator() {
        global $SESSION, $CFG;

        $SESSION->lang = 'xx';
        $langconfig = "<?php\n\$string['decsep'] = 'X';";
        $langfolder = $CFG->dataroot . '/lang/xx';
        check_dir_exists($langfolder);
        file_put_contents($langfolder . '/langconfig.php', $langconfig);
    }

    public function test_grade_update_mod_grades() {

        $this->resetAfterTest(true);

        // Create a broken module instance.
        $modinstance = new stdClass();
        $modinstance->modname = 'doesntexist';

        $this->assertFalse(grade_update_mod_grades($modinstance));
        // A debug message should have been generated.
        $this->assertDebuggingCalled();

        // Create a course and instance of mod_assign.
        $course = $this->getDataGenerator()->create_course();

        $assigndata['course'] = $course->id;
        $assigndata['name'] = 'lightwork assignment';
        $modinstance = self::getDataGenerator()->create_module('assign', $assigndata);

        // Function grade_update_mod_grades() requires 2 additional properties, cmidnumber and modname.
        $cm = get_coursemodule_from_instance('assign', $modinstance->id, 0, false, MUST_EXIST);
        $modinstance->cmidnumber = $cm->id;
        $modinstance->modname = 'assign';

        $this->assertTrue(grade_update_mod_grades($modinstance));
    }

    /**
     * Test rounding grade values.
     */
    public function test_grade_round_value() {
        // Special case for null.
        $this->assertEquals('', grade_round_value(null));
        // Default of 5 decimal places.
        $this->assertEquals('5.43247', grade_round_value(5.43247));
        $this->assertEquals('5.98374', grade_round_value(5.9837483));

        // Custom number of decimal places.
        $this->assertEquals('5.434', grade_round_value(5.4347, 3));
        $this->assertEquals('64', grade_round_value(64.59999, 0));

        // Tests Marina wanted put in.
        $this->assertEquals('5.40', grade_round_value(5.4, 2));
        $this->assertEquals('5.000', grade_round_value(5, 3));
        $this->assertEquals('0.000', grade_round_value(0, 3));
        $this->assertEquals('0.90', grade_round_value(0.9, 2));

        // Tests with a localised decimal separator.
        $this->define_local_decimal_separator();

        $this->assertEquals('5X43247', grade_round_value(5.43247));
        $this->assertEquals('24X87398', grade_round_value(24.87398473));
        $this->assertEquals('33X3', grade_round_value(33.35, 1));
        // No localisation.
        $this->assertEquals('120.2345', grade_round_value(120.23455, 4, false));
    }
}
