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
 * Unit tests for get_users_suspension_state() and toggle_users_suspension().
 *
 * @package    core
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/user/lib.php');


/**
 * A class for testing bulk user suspension and activation.
 * @package    core
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activate_and_suspend_test extends advanced_testcase {

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
     * Test activating and de-activating a bulk list of users.
     */
    function test_activate_and_suspend_users() {
        // Get a list of users who are currently active. With the users being
        // newly generated everyone should be active (50 users).
        $useridcount = count(get_users_suspension_state(false));
        // Test that fifty user IDs were returned.
        $this->assertEquals($useridcount, 50);

        // Let's now suspend some users.
        $suspendedusers = array('0' => 4, '1' => 22, '2' => 32, '3' => 37, '4' => 48);
        toggle_users_suspension($suspendedusers);
        // Count the number of suspended users.
        $suspendcount = count(get_users_suspension_state());
        // Five accounts should be suspended.
        $this->assertEquals($suspendcount, 5);

        // Let's re-activate two accounts.
        $activateusers = array('0' => 22, '1' => 37);
        toggle_users_suspension($activateusers, false);
        // Count the number of suspended users again.
        $suspendcount = count(get_users_suspension_state());
        // The number of suspended acounts should now be three.
        $this->assertEquals($suspendcount, 3);
    }
}
