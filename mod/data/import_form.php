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
 * Data module import form.
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-data
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');

class mod_data_import_form extends moodleform {

    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $cmid = $this->_customdata['id'];

        $mform->addElement('filepicker', 'recordsfile', get_string('csvfile', 'data'));

        $delimiters = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'fielddelimiter', get_string('fielddelimiter', 'data'), $delimiters);
        $mform->setDefault('fielddelimiter', 'comma');

        $mform->addElement('text', 'fieldenclosure', get_string('fieldenclosure', 'data'));
        $mform->setType('fieldenclosure', PARAM_CLEANHTML);
        $choices = textlib::get_encodings();
        $mform->addElement('select', 'encoding', get_string('fileencoding', 'mod_data'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('10'=>10, '20'=>20, '100'=>100, '1000'=>1000, '100000'=>100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'mod_data'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $submit_string = get_string('submit');
        // data id
        $mform->addElement('hidden', 'd');
        $mform->setType('d', PARAM_INT);

        $this->add_action_buttons(false, $submit_string);
    }
}

/**
 * Database module field matching form.
 */
class mod_data_import_form2 extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $columns = $this->_customdata['columns'];
        $fields = $this->_customdata['fields'];
        $data = $this->_customdata['formdata'];

        $maptooptions = array('none' => 'none');
        foreach ($columns as $key => $value) {
            $value = trim($value);
            $maptooptions[$key] = s($value);
        }

        $mform->addElement('header', 'fieldmappings', get_string('fieldmappings', 'mod_data'));

        foreach ($fields as $fieldname => $field) {
            // Picture and file fields can not be imported into the database.
            if ($field->type != 'picture' && $field->type != 'file') {
                $fieldname = trim($fieldname);
                $mform->addElement('select', 'fieldmap_'. s($fieldname) . '_' . $field->id, $fieldname . ' (' . get_string($field->type, 'mod_data') . ')', $maptooptions);
                $fieldmatch = array_search($fieldname, $columns);
                if ($fieldmatch !== false) {
                    $mform->setDefault('fieldmap_'. s($fieldname) . '_' . $field->id, $fieldmatch);
                } else {
                    $mform->setDefault('fieldmap_'. s($fieldname) . '_' . $field->id, 'none');
                }
            }
        }

        // Hidden fields.
        // Import ID.
        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_INT);

        // Data id.
        $mform->addElement('hidden', 'd');
        $mform->setType('d', PARAM_INT);

        $this->set_data($data);

        $submit_string = get_string('submit');
        $this->add_action_buttons(true, $submit_string);
    }
}
