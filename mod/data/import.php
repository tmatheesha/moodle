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
 * This file is part of the Database module for Moodle
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-data
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once('import_form.php');

$id              = optional_param('id', 0, PARAM_INT);  // course module id
$d               = optional_param('d', 0, PARAM_INT);   // database id
$rid             = optional_param('rid', 0, PARAM_INT); // record id
$fielddelimiter  = optional_param('fielddelimiter', ',', PARAM_CLEANHTML); // characters used as field delimiters for csv file import
$fieldenclosure  = optional_param('fieldenclosure', '', PARAM_CLEANHTML);   // characters used as record delimiters for csv file import
$iid             = optional_param('iid', null, PARAM_INT); // Import ID.

$url = new moodle_url('/mod/data/import.php');
if ($rid !== 0) {
    $url->param('rid', $rid);
}
if ($fielddelimiter !== '') {
    $url->param('fielddelimiter', $fielddelimiter);
}
if ($fieldenclosure !== '') {
    $url->param('fieldenclosure', $fieldenclosure);
}

if ($id) {
    $url->param('id', $id);
    $PAGE->set_url($url);
    $cm     = get_coursemodule_from_id('data', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $data   = $DB->get_record('data', array('id'=>$cm->instance), '*', MUST_EXIST);

} else {
    $url->param('d', $d);
    $PAGE->set_url($url);
    $data   = $DB->get_record('data', array('id'=>$d), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$data->course), '*', MUST_EXIST);
    $cm     = get_coursemodule_from_instance('data', $data->id, $course->id, false, MUST_EXIST);
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/data:manageentries', $context);

$returnurl = new moodle_url('/mod/data/view.php', (array('id' => $cm->id)));

$forminfo    = new stdClass();
$forminfo->d = $data->id;

if (empty($iid)) {
    $form = new mod_data_import_form(new moodle_url('/mod/data/import.php'));

    // Print the page header.
    $PAGE->navbar->add(get_string('add', 'data'));
    $PAGE->set_title($data->name);
    $PAGE->set_heading($course->fullname);
    navigation_node::override_active_url(new moodle_url('/mod/data/import.php', array('d' => $data->id)));
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('uploadrecords', 'mod_data'), 'uploadrecords', 'mod_data');

    // Groups needed for Add entry tab.
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    if ($formdata = $form->get_data()) {

        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        @set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $iid = csv_import_reader::get_new_iid('moddata');
        $csvimporter = new csv_import_reader($iid, 'moddata');
        $filecontent = $form->get_file_content('recordsfile');
        $readcount = $csvimporter->load_csv_content($filecontent, $formdata->encoding, $formdata->fielddelimiter);
        $errormessage = $csvimporter->get_error();
        print_object($errormessage);
        unset($filecontent);

        $headers = $csvimporter->get_columns();
        $header = array();
        foreach ($headers as $key => $headerdata) {
            $headerdata = trim($headerdata); // Remove whitespace.
            $headerdata = clean_param($headerdata, PARAM_RAW); // Clean the header.
            $header[$key] = $headerdata;
        }

        $table = new html_table();
        $table->id = "udbmpreview";
        $table->attributes['class'] = 'generaltable';
        $table->tablealign = 'center';
        $table->head = $header;
        $csvimporter->init();
        $previewdata = array();
        // Print a preview of the data.
        $numlines = 0; // 0 lines previewed so far.
        while ($numlines <= $formdata->previewrows) {
            $lines = $csvimporter->next();
            if ($lines) {
                $previewdata[] = $lines;
            }
            $numlines ++;
        }
        $table->data = $previewdata;

        echo html_writer::tag('div', html_writer::table($table), array('class'=>'flexible-wrap'));

        $forminfo->iid = $iid;

    } else {
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
        $form->set_data($forminfo);
        $form->display();
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        die();
    }
}

// Large files are likely to take their time and memory. Let PHP know
// that we'll take longer, and that the process should be recycled soon
// to free up memory.
@set_time_limit(0);
raise_memory_limit(MEMORY_EXTRA);

$cir = new csv_import_reader($iid, 'moddata');
if (!$header = $cir->get_columns()) {
        print_error('cannotreadtmpfile', 'error');
}

$fields = $DB->get_records('data_fields', array('dataid'=>$data->id), '', 'name, id, type');
$form2 = new mod_data_import_form2(null, array('columns' => $header, 'fields' => $fields, 'formdata' => $forminfo));

if ($formdata = $form2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);
}

if ($formdata = $form2->get_data()) {

    $PAGE->navbar->add(get_string('add', 'data'));
    $PAGE->set_title($data->name);
    $PAGE->set_heading($course->fullname);
    navigation_node::override_active_url(new moodle_url('/mod/data/import.php', array('d' => $data->id)));
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('uploadrecords', 'mod_data'), 'uploadrecords', 'mod_data');

    // Groups needed for Add entry tab.
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    $fields = $DB->get_records('data_fields', array('dataid'=>$data->id), '', 'name, id, type');

    $pattern = '/fieldmap_/';
    $fieldinfo = array();
    foreach ($formdata as $key => $value) {
        if (preg_match($pattern, $key)) {
            $fieldname = explode('_', $key);
            if ($value != 'none') {
                $fieldinfo[$value] = $fieldname[1];
            }
        }
    }

    $cir->init();
    $recordsadded = 0;
    while ($record = $cir->next()) {
        if ($recordid = data_add_record($data, 0)) {  // add instance to data_record.

            // Insert new data_content fields with NULL contents:
            foreach ($fields as $field) {
                $content = new stdClass();
                $content->recordid = $recordid;
                $content->fieldid = $field->id;
                $DB->insert_record('data_content', $content);
            }

            // Import values that match fields selected from the preview page.
            foreach ($fieldinfo as $key => $value) {
                $field = $fields[$value];
                $content = new stdClass();
                $content->fieldid = $field->id;
                $content->recordid = $recordid;
                if ($field->type == 'textarea') {
                    // the only field type where HTML is possible
                    $value = clean_param($record[$key], PARAM_CLEANHTML);
                } else {
                    // remove potential HTML:
                    $patterns[] = '/</';
                    $replacements[] = '&lt;';
                    $patterns[] = '/>/';
                    $replacements[] = '&gt;';
                    $value = preg_replace($patterns, $replacements, $record[$key]);
                }

                // for now, only for "latlong" and "url" fields, but that should better be looked up from
                // $CFG->dirroot . '/mod/data/field/' . $field->type . '/field.class.php'
                // once there is stored how many contents the field can have.
                if (preg_match("/^(latlong|url)$/", $field->type)) {
                    $values = explode(" ", $value, 2);
                    $content->content  = $values[0];
                    // The url field doesn't always have two values (unforced autolinking).
                    if (count($values) > 1) {
                        $content->content1 = $values[1];
                    }
                } else {
                    $content->content = $value;
                }

                $oldcontent = $DB->get_record('data_content', array('fieldid'=>$field->id, 'recordid'=>$recordid));
                $content->id = $oldcontent->id;
                $DB->update_record('data_content', $content);
            }
            $recordsadded++;
            print get_string('added', 'moodle', $recordsadded) . ". " . get_string('entry', 'data') . " (ID $recordid)<br />\n";
        }
    }
    $cir->close();
    $cir->cleanup(true);

    if ($recordsadded > 0) {
        echo $OUTPUT->notification($recordsadded. ' '. get_string('recordssaved', 'data'), '');
    } else {
        echo $OUTPUT->notification(get_string('recordsnotsaved', 'data'), 'notifysuccess');
    }

    echo $OUTPUT->continue_button('import.php?d='.$data->id);
    // }
} else {
    $form2->display();
}
// Finish the page.
echo $OUTPUT->footer();
