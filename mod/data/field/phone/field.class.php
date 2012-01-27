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
 * Class declaration for the field type 'phone'
 *
 * @package    datafield
 * @subpackage phone
 * @copyright  2012 onwards Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class data field phone
 * 
 * @package    datafield
 * @subpackage phone
 * @copyright  2012 onwards Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_field_phone extends data_field_base {
    var $type = 'phone';

    /**
     * Display for adding a field into the database
     *
     * @param int $recordid
     * @return string $str html string 
     */
    function display_add_field($recordid = 0) {
        global $DB;

        $areacode = '';
        $phonenumber = '';
        // If we have a record ID for editing then fill in the form with the relevant data.
        if ($recordid) {
            if ($content = $DB->get_record('data_content', array('fieldid' => $this->field->id, 'recordid' => $recordid))) {
                $areacode    = $content->content1;
                $phonenumber = $content->content;
            } 
        }
        $str = '';
        // Check to see if the area code is required.
        if ($this->field->param1) {
            // For this simple example I'm going to keep the areacode data in an array.
            $areacodeinfo = array('code' => array('02' => 'nswact',
                                          '03' => 'victas',
                                          '04' => 'mobile',
                                          '07' => 'qld',
                                          '08' => 'wasant'));

            // because we are using two form elements, we append '_0' so we can use it later.
            $str .= '<select name="field_' . $this->field->id . '_0">';
            // Loop through and populate the select element.
            foreach($areacodeinfo as $code) {
                foreach($code as $key => $area) {
                    if ($areacode == $key) {
                        $str .= '<option value="' . $key . '" selected>' . get_string($area, 'datafield_phone') . '</option>'; 
                    } else {
                        $str .= '<option value="' . $key . '">' . get_string($area, 'datafield_phone') . '</option>'; 
                    }
                }
            }
            $str .= '</select>';
        }
        // Or second form element. This has been appended with '_1'.
        $str .= '<input type="text" name="field_' . $this->field->id . '_1" value="' . $phonenumber . '"/>';

        return $str;
    }

    /**
     * Display for browsing entries made into the database
     *
     * @param int $recordid
     * @param type $template not used in this case
     * @return string $str The combined areacode and phone number
     */
    function display_browse_field($recordid, $template) {
        global $DB;

        if ($content = $DB->get_record('data_content', array('fieldid' => $this->field->id, 'recordid' => $recordid))) {
            // Check to see if area code information is required.
            if ($this->field->param1) {
                $areacode = $content->content1;
            } else {
                $areacode = '';
            }
            $phonenumber = $content->content;
            // A simple appending of area code and the phone number.
            $str = $areacode . ' ' . $phonenumber;
            return $str;
        }
    }

    /**
     * Insert or update the database with phone number information
     *
     * @param int $recordid
     * @param string $value either area code or phone number
     * @param string $name the name of field identification as created in {@see display_add_field}
     */
    function update_content($recordid, $value, $name = '') {
        global $DB;

        // Create an object for holding phone data to be entered or updated into the database.
        $content = new stdClass();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $names = explode('_', $name);
        // Get the information from the two form elements which end with '_0' and '_1'
        switch ($names[2]) {
            case 0:
                // areacode info
                $content->content1 = $value;
                break;
            case 1:
                // phonenumber info
                $content->content = $value;
                break;
            default:
                break;
        }
        // Check to see if we are editing or inserting.
        if ($oldcontent = $DB->get_record('data_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            $content->id = $oldcontent->id;
            return $DB->update_record('data_content', $content);
        } else {
            return $DB->insert_record('data_content', $content);
        }
    }
}