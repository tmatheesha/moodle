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
 * Script to check frozen courses for actual problems.
 *
 * This script should be run after an upgrade has been done and courses
 * have been frozen. It will analyze each grade in the frozen courses and
 * determine if the course should actually really be frozen or not.
 *
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    die(); // No access from web!
}

define('CLI_SCRIPT', true);

require_once(__DIR__ .'/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/user/lib.php');

// This is going to be slow!
raise_memory_limit(MEMORY_HUGE);

// Check for setting to display details rather than immediately unfreeze courses.
list($options, $unrecognized) = cli_get_params(
    array(
        'update' => false,
        'help' => false
    ),
    array()
);

if ($options['help']) {
    mtrace('no options - Show a report of which courses can be unfrozen and which courses should remain frozen.');
    mtrace('  --update - While processing, remove the freeze if it is deemed safe.');
    mtrace('  --help   - This help text.');
    exit();
}

// Results for the summary at the end.
$result = array('frozen' => 0, 'unfrozen' => 0);

// Retrieve all frozen courses.
$sqllike = $DB->sql_like('name', '?');
$params = array('%gradebook_calculations_freeze%');
$sql = "SELECT *
         FROM {config}
        WHERE $sqllike
          AND value = '20160518'";
$configcourses = $DB->get_recordset_sql($sql, $params);
foreach ($configcourses as $value) {
    $valuedata = explode('_', $value->name);
    $courseid = $valuedata[3];
    // Should probably check that these courses do actually exist.
    if (!$DB->count_records('course', array('id' => $courseid))) {
        // No actual course. Clean up and then move on.
        unset_config('gradebook_calculations_freeze_' . $courseid);
        continue;
    }

    $context = context_course::instance($courseid);
    $badletterboundaries = get_bad_letter_boundary($context);
    if (empty($badletterboundaries)) {
        // We can remove this immediately.
        $result['unfrozen']++;
        if ($options['update']) {
            mtrace('Course (' . $courseid . ') is being unfrozen');
            unset_config('gradebook_calculations_freeze_' . $courseid);
        } else {
            mtrace('Course (' . $courseid . ') can be unfrozen');
        }
    } else {
        $goodsettingsituation = true;
        // Check nasty settings.
        if ($CFG->grade_report_user_showtotalsifcontainhidden == 1) {
            $goodsettingsituation = false;
        }
        $gradeuserreportsetting = grade_get_setting($courseid, 'report_user_showtotalsifcontainhidden');
        if (isset($gradeuserreportsetting) && $gradeuserreportsetting == 1) {
            $goodsettingsituation = false;
        }
        // Let's get each user for this course and run through their user report! :D
        $sql = "SELECT DISTINCT gg.userid
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                 WHERE gi.courseid = :courseid";
        $params = array('courseid' => $courseid);
        $users = $DB->get_records_sql($sql, $params);

        $courseokay = run_through_user_report($users, $courseid, $context, $badletterboundaries);

        // If we do not have a favourable setting situation then we need to run through it again to check the teacher perspective.
        if (!$goodsettingsituation) {
            // Run through the user reports again!.
            $courseokay = run_through_user_report($users, $courseid, $context, $badletterboundaries, true) && $courseokay;
        }

        if ($courseokay) {
            // Unfreeze.
            $result['unfrozen']++;
            if ($options['update']) {
                mtrace('Course (' . $courseid . ') is being unfrozen');
                unset_config('gradebook_calculations_freeze_' . $courseid);
            } else {
                mtrace('Course (' . $courseid . ') can be unfrozen');
            }
        } else {
            $result['frozen']++;
            if ($options['update']) {
                mtrace('Course (' . $courseid . ') is remaining frozen');
            } else {
                mtrace('Course (' . $courseid . ') should remain frozen');
            }
        }
    }
}
$configcourses->close();
cli_separator();
mtrace('Summary');
if ($options['update']) {
    mtrace('Courses that remain frozen: ' . $result['frozen']);
    mtrace('Courses that have been unfrozen: ' . $result['unfrozen']);
} else {
    mtrace('Courses that should remain frozen: ' . $result['frozen']);
    mtrace('Courses that can be unfrozen: ' . $result['unfrozen']);
}

mtrace('finished');
exit();

/**
 * Goes through the user report and checks if the grades match the bad letter boundaries.
 *
 * @param  array $users An array of user IDs.
 * @param  int $courseid Course ID
 * @param  object $context Course context.
 * @param  array $badletterboundaries An array of letter boundaries that have issues.
 * @param  boolean $forceshowtotalsifcontainhidden For checking the grades a second time. Changes the setting for showing
 *                 grades totals that include hidden activities.
 * @return boolean Return false if a grade matches a bad letter boundary.
 */
function run_through_user_report($users, $courseid, $context, $badletterboundaries, $forceshowtotalsifcontainhidden = false) {
    $courseokay = true;
    foreach ($users as $user) {
        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'user', 'courseid' => $courseid, 'userid' => $user->userid));
        $report = new grade_report_user($courseid, $gpr, $context, $user->userid);
        // We need the percentage to be included.
        $report->showpercentage = 1;
        if ($forceshowtotalsifcontainhidden) {
            $report->showtotalsifcontainhidden[$courseid] = 2;
        }
        // This next bit does all of the calculations.
        $report->fill_table();
        $tabledata = $report->tabledata;
        foreach ($tabledata as $tabledatum) {
            if (isset($tabledatum['percentage'])) {
                if (in_array((int)$tabledatum['percentage']['content'], $badletterboundaries)) {
                    $courseokay = false;
                    // This course should remain frozen.
                    return $courseokay;
                }
            }
        }
    }
    return $courseokay;
}


/**
 * Checks the letter boundary of the provided context to see if it needs freezing.
 * Each letter boundary is tested to see if receiving that boundary number will
 * result in achieving the cosponsoring letter.
 *
 * @param object $context Context object
 * @return array An array of letter boundaries that are deemed bad.
 */
function get_bad_letter_boundary($context) {
    global $DB;

    $contexts = $context->get_parent_context_ids();
    array_unshift($contexts, $context->id);

    $badboundary = array();

    foreach ($contexts as $ctxid) {

        $letters = $DB->get_records_menu('grade_letters', array('contextid' => $ctxid), 'lowerboundary DESC',
                'lowerboundary, letter');

        if (!empty($letters)) {
            foreach ($letters as $boundary => $notused) {
                $standardisedboundary = standardise_score($boundary, 0, 100, 0, 100);
                if ($standardisedboundary < $boundary) {
                    $badboundary[$boundary] = $boundary;
                }
            }
            // Return a boundary here if we found letters.
            return $badboundary;
        }
    }
    return $badboundary;
}

/**
 * Given a float value situated between a source minimum and a source maximum, converts it to the
 * corresponding value situated between a target minimum and a target maximum. Thanks to Darlene
 * for the formula :-)
 *
 * @param float $rawgrade
 * @param float $source_min
 * @param float $source_max
 * @param float $target_min
 * @param float $target_max
 * @return float Converted value
 */
function standardise_score($rawgrade, $source_min, $source_max, $target_min, $target_max) {
    if (is_null($rawgrade)) {
      return null;
    }

    if ($source_max == $source_min or $target_min == $target_max) {
        // prevent division by 0
        return $target_max;
    }

    $factor = ($rawgrade - $source_min) / ($source_max - $source_min);
    $diff = $target_max - $target_min;
    $standardised_value = $factor * $diff + $target_min;
    return $standardised_value;
}


