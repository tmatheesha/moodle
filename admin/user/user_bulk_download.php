<?php
/**
* script for downloading of user lists
*/

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$format = optional_param('format', '', PARAM_ALPHA);

admin_externalpage_setup('userbulk');
require_capability('moodle/user:update', context_system::instance());

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

if ($format) {
    $fields = array('id'        => 'id',
                    'username'  => 'username',
                    'email'     => 'email',
                    'firstname' => 'firstname',
                    'lastname'  => 'lastname',
                    'idnumber'  => 'idnumber',
                    'institution' => 'institution',
                    'department' => 'department',
                    'phone1'    => 'phone1',
                    'phone2'    => 'phone2',
                    'city'      => 'city',
                    'url'       => 'url',
                    'icq'       => 'icq',
                    'skype'     => 'skype',
                    'aim'       => 'aim',
                    'yahoo'     => 'yahoo',
                    'msn'       => 'msn',
                    'country'   => 'country');

    require_once($CFG->dirroot.'/user/profile/lib.php');
    if ($extrafields = profile_field_base::get_fields_list()) {
        foreach ($extrafields as $n=>$v){
            $fields['profile_field_'.$v->shortname] = 'profile_field_'.$v->shortname;
        }
    }

    user_download_generic(array_values($fields), $format);
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('download', 'admin'));

echo $OUTPUT->box_start();
echo '<ul>';
echo '<li><a href="user_bulk_download.php?format=csv">'.get_string('downloadtext').'</a></li>';
echo '<li><a href="user_bulk_download.php?format=ods">'.get_string('downloadods').'</a></li>';
echo '<li><a href="user_bulk_download.php?format=xls">'.get_string('downloadexcel').'</a></li>';
echo '</ul>';
echo $OUTPUT->box_end();

echo $OUTPUT->continue_button($return);

echo $OUTPUT->footer();

/**
 * A general file export function.
 *
 * @param type $fields  Fields being exported.
 * @param array $format  The file format.
 */
function user_download_generic($fields, $format='csv') {
    global $CFG, $SESSION, $DB, $PAGE;

    $formats = array('csv', 'ods', 'xls');
    if (!in_array($format, $formats)) {
        die;
    }

    $filename = clean_filename(get_string('users').'.'.$format);
    @set_time_limit(180);

    if ($format == 'csv') {
        require_once($CFG->libdir . '/csvlib.class.php');
        $csvexport = new csv_export_writer();
        $csvexport->set_filename(get_string('users'), '.' . $format);
        $csvexport->add_data($fields);
    } else {
        if ($format == 'xls') {
            require_once("$CFG->libdir/excellib.class.php");
            $workbook = new MoodleExcelWorkbook('-');
        } else if ($format == 'ods') {
            require_once("$CFG->libdir/odslib.class.php");
            $workbook = new MoodleODSWorkbook('-');
            @raise_memory_limit(MEMORY_EXTRA);
        }
        $workbook->send($filename);
        $worksheet =& $workbook->add_worksheet('');
        // Create a copy of $fields as the xls function of write_strings() uses $fields1 as a reference.
        $fields1 = $fields;
        $worksheet->write_strings(0, $fields1);
    }

    $profiles = count(preg_grep('#^profile_field_#', $fields)) > 0;
    $fields = array_fill_keys($fields, true);
    $strings = array();
    $row = 1;
    sort($SESSION->bulk_users, SORT_NUMERIC);
    $PAGE->set_context(context_system::instance());

    list($where, $params) = $DB->get_in_or_equal($SESSION->bulk_users);

    $users = $DB->get_recordset_select('user', 'id ' . $where, $params, 'id ASC');
    if ($users->valid()) {
        profile_field_base::preload_data($SESSION->bulk_users);
        foreach ($users as $user) {
            if ($profiles) {
                profile_load_data($user);
            }
            $user = array_intersect_key((array)$user, $fields);
            foreach ($user as $key => $value) {
                // Custom user profile textarea fields come in an array.
                // The first element is the text and the second is the format.
                // We only take the text.
                if (is_array($value)) {
                    $user[$key] = reset($value);
                }
            }
            $user = array_values(array_merge($fields, $user)); // Sort by the provided field order.
            if ($format == 'csv') {
                $csvexport->add_data($user);
            } else {
                if ($format == 'ods') {
                    // HACK: Dedup of strings in memory saves space as ODS does not write to a temporary file.
                    foreach ($user as $k => $v) {
                        if (!isset($strings[$v])) {
                            $strings[$v] = $v;
                        }
                        $user[$k] = &$strings[$v];
                    }
                }
                $worksheet->write_strings($row, $user);
                $row++;
            }
        }
        profile_field_base::preload_clear();
        $users->close(); // Important! Close the recordset.
    }
    if (isset($workbook)) {
        $workbook->close();
    } else {
        $csvexport->download_file();
    }
    die;
}
