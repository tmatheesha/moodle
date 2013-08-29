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
 * @package    mod
 * @subpackage resource
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/resource/backup/moodle2/backup_resource_stepslib.php'); // Because it exists (must)

/**
 * resource backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_resource_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new backup_resource_activity_structure_step('resource_structure', 'resource.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG, $DB;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of resources
        $search="/(".$base."\/mod\/resource\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@RESOURCEINDEX*$2@$', $content);

        // Link to resource view by moduleid
        $search="/(".$base."\/mod\/resource\/view.php\?id\=)([0-9]+)/";

        static $resource_old_exists = null;

        // Check whether there are contents in the resource old table
        if ($resource_old_exists === null) {
            $resource_old_exists = $DB->record_exists('resource_old', array());
        }

        // If there are links to items in the resource_old table, rewrite them to be links to the correct URL
        // for their new module
        if ($resource_old_exists) {
            $result = preg_match_all($search, $content, $matches, PREG_PATTERN_ORDER);

            // No resource links
            if (!$result) {
                return $content;
            }

            list($insql, $params) = $DB->get_in_or_equal($matches[2]);
            $oldrecs = $DB->get_records_select('resource_old', "cmid $insql", $params, '', 'cmid, newmodule');

            for ($i = 0; $i < count($matches[0]); $i++) {
                $cmid = $matches[2][$i];
                if (isset($oldrecs[$cmid])) {
                    // Resource_old item, rewrite it
                    $replace = '$@' . strtoupper($oldrecs[$cmid]->newmodule) . 'VIEWBYID*' . $cmid . '@$';
                } else {
                    // Not in the resource old table, don't rewrite
                    $replace = '$@RESOURCEVIEWBYID*'.$cmid.'@$';
                }
                $content = str_replace($matches[0][$i], $replace, $content);
            }
        } else {
            $content= preg_replace($search, '$@RESOURCEVIEWBYID*$2@$', $content);
        }
        return $content;
    }
}
