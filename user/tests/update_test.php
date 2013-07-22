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
 * Unit tests for user_update_users().
 *
 * @package    core
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/user/lib.php');


/**
 * A class for testing bulk user updates.
 * @package    core
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_users_test extends advanced_testcase {

    /**
     * Set up function. In this instance we are setting up database
     * records to be used in the unit tests.
     */
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);

        // we already have 2 users, we need 48 more.
        for($i=3;$i<=50;$i++) {
            $this->getDataGenerator()->create_user();
        }
    }

    /**
     * Test bulk updating users.
     */
    function test_updating_users() {
        global $DB;
        // Update some records using the function.
        $updateids = array('0' => 3, '1' => 7, '2' => 15, '3' => 22, '4' => 27,
            '5' => 34, '6' => 41);
        user_update_users($updateids, array('suspended' => 1, 'policyagreed' => 1));
        // Test one: check how many records  were updated with suspended = 1.
        $suspendedusers = $DB->get_records('user', array('suspended' => 1));
        $this->assertEquals(count($suspendedusers), 7);
        // Test two: Check how many records were updated with policyagreed = 1.
        $policyagreedusers = $DB->get_records('user', array('policyagreed' => 1));
        $this->assertEquals(count($policyagreedusers), 7);
    }
}
