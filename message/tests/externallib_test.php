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
 * External message functions unit tests
 *
 * @package    core_message
 * @category   external
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/message/externallib.php');
require_once($CFG->dirroot . '/message/lib.php');

class core_message_external_testcase extends externallib_advanced_testcase {

    /**
     * Test send_instant_messages
     */
    public function test_send_instant_messages() {

        global $DB, $USER, $CFG;

        $this->resetAfterTest(true);

        // Turn off all message processors (so nothing is really sent)
        require_once($CFG->dirroot . '/message/lib.php');
        $messageprocessors = get_message_processors();
        foreach($messageprocessors as $messageprocessor) {
            $messageprocessor->enabled = 0;
            $DB->update_record('message_processors', $messageprocessor);
        }

        // Set the required capabilities by the external function
        $contextid = context_system::instance()->id;
        $roleid = $this->assignUserCapability('moodle/site:sendmessage', $contextid);

        $user1 = self::getDataGenerator()->create_user();

        // Create test message data.
        $message1 = array();
        $message1['touserid'] = $user1->id;
        $message1['text'] = 'the message.';
        $message1['clientmsgid'] = 4;
        $messages = array($message1);

        $sentmessages = core_message_external::send_instant_messages($messages);

        $themessage = $DB->get_record('message', array('id' => $sentmessages[0]['msgid']));

        // Confirm that the message was inserted correctly.
        $this->assertEquals($themessage->useridfrom, $USER->id);
        $this->assertEquals($themessage->useridto, $message1['touserid']);
        $this->assertEquals($themessage->smallmessage, $message1['text']);
        $this->assertEquals($sentmessages[0]['clientmsgid'], $message1['clientmsgid']);
    }

    public function test_get_instant_messages() {

        global $DB;

        $this->resetAfterTest(true);

        // Generate some users.
        for ($i = 1; $i < 19; $i++) {
            $this->getDataGenerator()->create_user();
        }

        // Generate some random messages to retrieve.
        $messages = array();
        for ($i = 0; $i < 48; $i++) {
            $messages[] = $this->getDataGenerator()->send_message();
        }

        // Create a couple of messages that will definitely be found to stop exception errors.
        $messages[] = $this->getDataGenerator()->send_message(array(
                'useridfrom' => 3,
                'useridto' => 7,
                'timecreated' => 1072936812));
        $messages[] = $this->getDataGenerator()->send_message(array(
                'useridfrom' => 3,
                'useridto' => 7,
                'timecreated' => 1072936710));

        $this->setUser(3);

        // Retrieve all messages for the current user.
        $allmessages = core_message_external::get_messages();

        $records = $DB->get_records_select('message', 'useridfrom = ? OR useridto = ? ', array(3, 3));
        $recordcount = count($records);

        $this->assertEquals($recordcount, count($allmessages));

        // Retrieve messages from a certain date.

        $startdate = 1072936800; // 1st of January 2004
        $sentmessages = core_message_external::get_messages(0, $startdate);

        // DB query to check the results.
        // Currently all messages are new.
        $sql = "SELECT * FROM {message} WHERE (useridfrom = ? OR useridto = ?) AND timecreated >= ?";
        $params = array(3, 3, $startdate);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEquals(count($sentmessages), count($records));

        // Retrieve messages up to a certain data.

        $enddate = 1072936800; // 1st of January 2004
        $sentmessages = core_message_external::get_messages(0, 0, $enddate);

        // DB query to check the results.
        // Currently all messages are new.
        $sql = "SELECT * FROM {message} WHERE (useridfrom = ? OR useridto = ?) AND timecreated <= ?";
        $params = array(3, 3, $enddate);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEquals(count($sentmessages), count($records));

        // Retrieve messages from a certain time to another with all details.

        // Create records from user 5 to user 7.
        $custommessage = array();
        $custommessage['useridfrom'] = 5;
        $custommessage['useridto'] = 7;
        $custommessage['timecreated'] = 1172936800;
        for ($i = 0; $i < 200; $i++) {
            $messages[] = $this->getDataGenerator()->send_message($custommessage);
        }

        // Set the current user to user 5.
        $this->setUser(5);

        $contactid = 7;
        $startdate = 1072936800; // 1st of January 2004
        $enddate =  1325397600; // 1st of January 2012
        $readmessage = MESSAGE_UNREAD;
        $limitfrom = 100; // Start at the 100th record.
        $limitnum = 10; // Show only 10 records.

        $sentmessages = core_message_external::get_messages($contactid, $startdate, $enddate, $readmessage ,$limitfrom, $limitnum);

        // DB query to check the results.
        // Currently all messages are new.
        $sql = "SELECT * FROM {message} WHERE ((useridto = ? AND useridfrom = ?)
                OR (useridto = ? AND useridfrom = ?))
                AND timecreated >= ? AND timecreated <= ?";
        $params = array(5, 7, 7, 5, $startdate, $enddate);
        $records = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $this->assertEquals(count($sentmessages), count($records));

        // Mark some messages as being read.
        // This will mark all messages from user 5 to user 7 as being read.
        message_mark_messages_read(7, 5);

        $contactid = 7;
        $readmessage = MESSAGE_READ;
        $sentmessages = core_message_external::get_messages($contactid, 0, 0, $readmessage);

        $sql = "SELECT * FROM {message_read} WHERE ((useridto = ? AND useridfrom = ?)
                OR (useridto = ? AND useridfrom = ?))";
        $params = array(5, 7, 7, 5);
        $records = $DB->get_records_sql($sql, $params);

        $this->assertEquals(count($sentmessages), count($records));

        // The last thing we do is check for exceptions.

        // this should be outside the range of generated users and generate an exception.
        $contactid = 40;

        $this->setExpectedException('moodle_exception');
        $sentmessages = core_message_external::get_messages($contactid);
    }

    public function test_get_instant_messages_no_records() {

        $this->resetAfterTest(true);

        // Generate some users.
        for ($i = 1; $i < 19; $i++) {
            $this->getDataGenerator()->create_user();
        }

        // Create a random message to user 15.
        $this->getDataGenerator()->send_message(array('useridto' => 15));

         // The date in this query is outside the generated messages and should return no records.
        $contactid = 15;
        $startdate =  504943200; // 1st of January 1986
        $enddate =  599637600; // 1st of January 1989

        $this->setExpectedException('moodle_exception');
        $sentmessages = core_message_external::get_messages($contactid, $startdate, $enddate);
    }
}