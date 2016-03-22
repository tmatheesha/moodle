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
 * Upgrade helper functions
 *
 * This file is used for special upgrade functions - for example groups and gradebook.
 * These functions must use SQL and database related functions only- no other Moodle API,
 * because it might depend on db structures that are not yet present during upgrade.
 * (Do not use functions from accesslib.php, grades classes or group functions at all!)
 *
 * @package   core_install
 * @category  upgrade
 * @copyright 2007 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns all non-view and non-temp tables with sane names.
 * Prints list of non-supported tables using $OUTPUT->notification()
 *
 * @return array
 */
function upgrade_mysql_get_supported_tables() {
    global $OUTPUT, $DB;

    $tables = array();
    $patprefix = str_replace('_', '\\_', $DB->get_prefix());
    $pregprefix = preg_quote($DB->get_prefix(), '/');

    $sql = "SHOW FULL TABLES LIKE '$patprefix%'";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $record) {
        $record = array_change_key_case((array)$record, CASE_LOWER);
        $type = $record['table_type'];
        unset($record['table_type']);
        $fullname = array_shift($record);

        if ($pregprefix === '') {
            $name = $fullname;
        } else {
            $count = null;
            $name = preg_replace("/^$pregprefix/", '', $fullname, -1, $count);
            if ($count !== 1) {
                continue;
            }
        }

        if (!preg_match("/^[a-z][a-z0-9_]*$/", $name)) {
            echo $OUTPUT->notification("Database table with invalid name '$fullname' detected, skipping.", 'notifyproblem');
            continue;
        }
        if ($type === 'VIEW') {
            echo $OUTPUT->notification("Unsupported database table view '$fullname' detected, skipping.", 'notifyproblem');
            continue;
        }
        $tables[$name] = $name;
    }
    $rs->close();

    return $tables;
}

/**
 * Using data for a single course-module that has groupmembersonly enabled,
 * returns the new availability value that incorporates the correct
 * groupmembersonly option.
 *
 * Included as a function so that it can be shared between upgrade and restore,
 * and unit-tested.
 *
 * @param int $groupingid Grouping id for the course-module (0 if none)
 * @param string $availability Availability JSON data for the module (null if none)
 * @return string New value for availability for the module
 */
function upgrade_group_members_only($groupingid, $availability) {
    // Work out the new JSON object representing this option.
    if ($groupingid) {
        // Require specific grouping.
        $condition = (object)array('type' => 'grouping', 'id' => (int)$groupingid);
    } else {
        // No grouping specified, so require membership of any group.
        $condition = (object)array('type' => 'group');
    }

    if (is_null($availability)) {
        // If there are no conditions using the new API then just set it.
        $tree = (object)array('op' => '&', 'c' => array($condition), 'showc' => array(false));
    } else {
        // There are existing conditions.
        $tree = json_decode($availability);
        switch ($tree->op) {
            case '&' :
                // For & conditions we can just add this one.
                $tree->c[] = $condition;
                $tree->showc[] = false;
                break;
            case '!|' :
                // For 'not or' conditions we can add this one
                // but negated.
                $tree->c[] = (object)array('op' => '!&', 'c' => array($condition));
                $tree->showc[] = false;
                break;
            default:
                // For the other two (OR and NOT AND) we have to add
                // an extra level to the tree.
                $tree = (object)array('op' => '&', 'c' => array($tree, $condition),
                        'showc' => array($tree->show, false));
                // Inner trees do not have a show option, so remove it.
                unset($tree->c[0]->show);
                break;
        }
    }

    return json_encode($tree);
}

/**
 * Updates the mime-types for files that exist in the database, based on their
 * file extension.
 *
 * @param array $filetypes Array with file extension as the key, and mimetype as the value
 */
function upgrade_mimetypes($filetypes) {
    global $DB;
    $select = $DB->sql_like('filename', '?', false);
    foreach ($filetypes as $extension=>$mimetype) {
        $DB->set_field_select(
            'files',
            'mimetype',
            $mimetype,
            $select,
            array($extension)
        );
    }
}

/**
 * Marks all courses with changes in extra credit weight calculation
 *
 * Used during upgrade and in course restore process
 *
 * This upgrade script is needed because we changed the algorithm for calculating the automatic weights of extra
 * credit items and want to prevent changes in the existing student grades.
 *
 * @param int $onlycourseid
 */
function upgrade_extra_credit_weightoverride($onlycourseid = 0) {
    global $DB;

    // Find all courses that have categories in Natural aggregation method where there is at least one extra credit
    // item and at least one item with overridden weight.
    $courses = $DB->get_fieldset_sql(
        "SELECT DISTINCT gc.courseid
          FROM {grade_categories} gc
          INNER JOIN {grade_items} gi ON gc.id = gi.categoryid AND gi.weightoverride = :weightoverriden
          INNER JOIN {grade_items} gie ON gc.id = gie.categoryid AND gie.aggregationcoef = :extracredit
          WHERE gc.aggregation = :naturalaggmethod" . ($onlycourseid ? " AND gc.courseid = :onlycourseid" : ''),
        array('naturalaggmethod' => 13,
            'weightoverriden' => 1,
            'extracredit' => 1,
            'onlycourseid' => $onlycourseid,
        )
    );
    foreach ($courses as $courseid) {
        $gradebookfreeze = get_config('core', 'gradebook_calculations_freeze_' . $courseid);
        if (!$gradebookfreeze) {
            set_config('gradebook_calculations_freeze_' . $courseid, 20150619);
        }
    }
}

/**
 * Marks all courses that require calculated grade items be updated.
 *
 * Used during upgrade and in course restore process.
 *
 * This upgrade script is needed because the calculated grade items were stuck with a maximum of 100 and could be changed.
 * This flags the courses that are affected and the grade book is frozen to retain grade integrity.
 *
 * @param int $courseid Specify a course ID to run this script on just one course.
 */
function upgrade_calculated_grade_items($courseid = null) {
    global $DB, $CFG;

    $affectedcourses = array();
    $possiblecourseids = array();
    $params = array();
    $singlecoursesql = '';
    if (isset($courseid)) {
        $singlecoursesql = "AND ns.id = :courseid";
        $params['courseid'] = $courseid;
    }
    $siteminmaxtouse = 1;
    if (isset($CFG->grade_minmaxtouse)) {
        $siteminmaxtouse = $CFG->grade_minmaxtouse;
    }
    $courseidsql = "SELECT ns.id
                      FROM (
                        SELECT c.id, coalesce(" . $DB->sql_compare_text('gs.value') . ", :siteminmax) AS gradevalue
                          FROM {course} c
                          LEFT JOIN {grade_settings} gs
                            ON c.id = gs.courseid
                           AND ((gs.name = 'minmaxtouse' AND " . $DB->sql_compare_text('gs.value') . " = '2'))
                        ) ns
                    WHERE " . $DB->sql_compare_text('ns.gradevalue') . " = '2' $singlecoursesql";
    $params['siteminmax'] = $siteminmaxtouse;
    $courses = $DB->get_records_sql($courseidsql, $params);
    foreach ($courses as $course) {
        $possiblecourseids[$course->id] = $course->id;
    }

    if (!empty($possiblecourseids)) {
        list($sql, $params) = $DB->get_in_or_equal($possiblecourseids);
        // A calculated grade item grade min != 0 and grade max != 100 and the course setting is set to
        // "Initial min and max grades".
        $coursesql = "SELECT DISTINCT courseid
                        FROM {grade_items}
                       WHERE calculation IS NOT NULL
                         AND itemtype = 'manual'
                         AND (grademax <> 100 OR grademin <> 0)
                         AND courseid $sql";
        $affectedcourses = $DB->get_records_sql($coursesql, $params);
    }

    // Check for second type of affected courses.
    // If we already have the courseid parameter set in the affectedcourses then there is no need to run through this section.
    if (!isset($courseid) || !in_array($courseid, $affectedcourses)) {
        $singlecoursesql = '';
        $params = array();
        if (isset($courseid)) {
            $singlecoursesql = "AND courseid = :courseid";
            $params['courseid'] = $courseid;
        }
        $nestedsql = "SELECT id
                        FROM {grade_items}
                       WHERE itemtype = 'category'
                         AND calculation IS NOT NULL $singlecoursesql";
        $calculatedgradecategories = $DB->get_records_sql($nestedsql, $params);
        $categoryids = array();
        foreach ($calculatedgradecategories as $key => $gradecategory) {
            $categoryids[$key] = $gradecategory->id;
        }

        if (!empty($categoryids)) {
            list($sql, $params) = $DB->get_in_or_equal($categoryids);
            // A category with a calculation where the raw grade min and the raw grade max don't match the grade min and grade max
            // for the category.
            $coursesql = "SELECT DISTINCT gi.courseid
                            FROM {grade_grades} gg, {grade_items} gi
                           WHERE gi.id = gg.itemid
                             AND (gg.rawgrademax <> gi.grademax OR gg.rawgrademin <> gi.grademin)
                             AND gi.id $sql";
            $additionalcourses = $DB->get_records_sql($coursesql, $params);
            foreach ($additionalcourses as $key => $additionalcourse) {
                if (!array_key_exists($key, $affectedcourses)) {
                    $affectedcourses[$key] = $additionalcourse;
                }
            }
        }
    }

    foreach ($affectedcourses as $affectedcourseid) {
        if (isset($CFG->upgrade_calculatedgradeitemsonlyregrade) && !($courseid)) {
            $DB->set_field('grade_items', 'needsupdate', 1, array('courseid' => $affectedcourseid->courseid));
        } else {
            // Check to see if the gradebook freeze is already in affect.
            $gradebookfreeze = get_config('core', 'gradebook_calculations_freeze_' . $affectedcourseid->courseid);
            if (!$gradebookfreeze) {
                set_config('gradebook_calculations_freeze_' . $affectedcourseid->courseid, 20150627);
            }
        }
    }
}

/**
 * This upgrade script merges all tag instances pointing to the same course tag
 *
 * User id is no longer used for those tag instances
 */
function upgrade_course_tags() {
    global $DB;
    $sql = "SELECT min(ti.id)
        FROM {tag_instance} ti
        LEFT JOIN {tag_instance} tii on tii.itemtype = ? and tii.itemid = ti.itemid and tii.tiuserid = 0 and tii.tagid = ti.tagid
        where ti.itemtype = ? and ti.tiuserid <> 0 AND tii.id is null
        group by ti.tagid, ti.itemid";
    $ids = $DB->get_fieldset_sql($sql, array('course', 'course'));
    if ($ids) {
        list($idsql, $idparams) = $DB->get_in_or_equal($ids);
        $DB->execute('UPDATE {tag_instance} SET tiuserid = 0 WHERE id ' . $idsql, $idparams);
    }
    $DB->execute("DELETE FROM {tag_instance} WHERE itemtype = ? AND tiuserid <> 0", array('course'));
}

/**
 * Marks all courses that require rounded grade items be updated.
 *
 * Used during upgrade and in course restore process.
 *
 * This upgrade script is needed because it has been decided that if a grade is rounded up, and it will changed a letter
 * grade or satisfy a course completion grade criteria, then it should be set as so, and the letter will be awarded and or
 * the course completion grade will be awarded.
 *
 * @param int $courseid Specify a course ID to run this script on just one course.
 */
function upgrade_rounded_grade_items($courseid = null) {
    global $DB, $CFG;

    $coursesql = '';
    $params = array('contextlevel' => CONTEXT_COURSE);
    if (!empty($courseid)) {
        $coursesql = 'AND c.id = :courseid';
        $params['courseid'] = $courseid;
    }

    $contextselect = context_helper::get_preload_record_columns_sql('ctx');

    $sql = 'SELECT gg.id, gg.finalgrade, c.id AS courseid,
                   COALESCE(gi.decimals, ' . $DB->sql_cast_char2int('gs.value') . ') AS decimals,
                   gi.display AS display, gss.value AS coursedisplay, gsss.value AS reportoverview, '. $contextselect .'
              FROM {grade_grades} gg
              JOIN {grade_items} gi ON gi.id = gg.itemid
              JOIN {course} c ON c.id = gi.courseid
              JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
         LEFT JOIN {grade_settings} gs ON gi.courseid = gs.courseid AND gs.name = \'decimalpoints\'
         LEFT JOIN {grade_settings} gss ON gi.courseid = gss.courseid AND gss.name = \'displaytype\'
         LEFT JOIN {grade_settings} gsss ON gi.courseid = gsss.courseid
                   AND gsss.name = \'report_overview_showtotalsifcontainhidden\'
             WHERE NOT (gg.finalgrade IS NULL)
               ' . $coursesql;

    $collectedcourseids = array();
    $potentialfinalgrades = $DB->get_recordset_sql($sql, $params);

    foreach ($potentialfinalgrades as $value) {
        $gradebookfreeze = 'gradebook_calculations_freeze_' . $value->courseid;

        // Check also if this course id has already been frozen.
        // If we already have this course ID then move on to the next record.
        if (!property_exists($CFG, $gradebookfreeze)) {

            // Check course level "show totals excluding hidden items", if not here then check site level setting for this.
            if ($value->reportoverview == 1 || (is_null($value->reportoverview) &&
                    (isset($CFG->report_overview_showtotalsifcontainhidden) &&
                    $CFG->report_overview_showtotalsifcontainhidden == 1))) {
                // Flag for freeze.
                // Put in code to freeze.
                set_config('gradebook_calculations_freeze_' . $value->courseid, 20160418);
                continue;
            }

            // Check for finalgrade round / floor discrepancy.
            // Retrieve the current decimalpoint value for this course.
            $decimalpoints = (!is_null($value->decimals)) ? $value->decimals : $CFG->grade_decimalpoints;
            if ($decimalpoints != 5) {
                // If the rounded value is different to the floored value then there is a potential for a grade to be changed.
                if (round($value->finalgrade, $decimalpoints) != floatval(substr($value->finalgrade, 0, $decimalpoints - 5))) {
                    // We have a winner.
                    // Flag this course as being frozen.
                    set_config('gradebook_calculations_freeze_' . $value->courseid, 20160418);
                    continue;
                }
            }

            // Check for 57 letter grade issue.
            if (upgrade_is_gradeitem_using_letters($value->display, $value->coursedisplay)) {
                context_helper::preload_from_record($value);
                $coursecontext = context_course::instance($value->courseid);
                if (upgrade_letter_boundary_needs_freeze($coursecontext)) {
                    // We have a course with a possible score standardisation problem. Flag for freeze.
                    // Flag this course as being frozen.
                    set_config('gradebook_calculations_freeze_' . $value->courseid, 20160418);
                }
            }
        }
    }
    $potentialfinalgrades->close();
}

/**
 * Check if a grade item is using letters for display.
 *
 * @param int $gradesetting The setting for the grade item.
 * @param int $coursesetting The setting for the course.
 * @return bool True if the grade item is being displayed with letters.
 */
function upgrade_is_gradeitem_using_letters($gradesetting, $coursesetting) {
    global $CFG;
    // These numbers represent the number for letter, letter(percentage), letter(real).
    $letterdisplaytypes = array(3, 31, 32);
    return in_array($gradesetting ?: ($coursesetting ?: $CFG->grade_displaytype), $letterdisplaytypes);
}

/**
 * Returns the array of grade letters to be used in the supplied context
 *
 * @param object $context Context object or null for defaults
 * @return array of grade_boundary (minimum) => letter_string
 */
function upgrade_letter_boundary_needs_freeze($context) {
    global $DB;

    static $cache = array();

    if (array_key_exists($context->id, $cache)) {
        return $cache[$context->id];
    }

    if (count($cache) > 100) {
        $cache = array(); // Cache size limit.
    }

    $letters = array();

    $contexts = $context->get_parent_context_ids();
    array_unshift($contexts, $context->id);

    foreach ($contexts as $ctxid) {

        // Check this context against the cache to see if there is already a value.
        if (array_key_exists($context->id, $cache)) {
            return $cache[$context->id];
        }

        $letters = $DB->get_records_menu('grade_letters', array('contextid' => $ctxid), 'lowerboundary DESC',
                'lowerboundary, letter');

        if (!empty($letters)) {

            foreach ($letters as $boundary => $notused) {
                $standardisedboundary = upgrade_standardise_score($boundary, 0, 100, 0, 100);
                if ($boundary != $standardisedboundary) {
                    $cache[$context->id] = true;
                    return true;
                }
            }

        }
    }
    $cache[$context->id] = false;
    return false;
}

/**
 * Given a float value situated between a source minimum and a source maximum, converts it to the
 * corresponding value situated between a target minimum and a target maximum. Thanks to Darlene
 * for the formula :-)
 *
 * @param float $rawgrade
 * @param float $sourcemin
 * @param float $sourcemax
 * @param float $targetmin
 * @param float $targetmax
 * @return float Converted value
 */
function upgrade_standardise_score($rawgrade, $sourcemin, $sourcemax, $targetmin, $targetmax) {
    if (is_null($rawgrade)) {
        return null;
    }

    if ($sourcemax == $sourcemin or $targetmin == $targetmax) {
        // Prevent division by 0.
        return $targetmax;
    }

    $factor = ($rawgrade - $sourcemin) / ($sourcemax - $sourcemin);
    $diff = $targetmax - $targetmin;
    $standardisedvalue = $factor * $diff + $targetmin;
    return $standardisedvalue;
}