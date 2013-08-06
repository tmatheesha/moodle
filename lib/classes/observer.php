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
 * Event observers for core.
 *
 * @package    core
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for core.
 */
class core_event_observer {

    /**
     * Triggered when the 'portfolio_upload_deffered' event is triggered.
     *
     * @param \core\event\portfolio_upload_deffered $eventdata
     */
    public static function portfolio_event_observer($eventdata) {
        global $CFG;

        require_once($CFG->libdir . '/portfolio/exporter.php');
        $exporter = portfolio_exporter::rewaken_object($eventdata);
        $exporter->process_stage_package();
        $exporter->process_stage_send();
        $exporter->save();
        $exporter->process_stage_cleanup();
        return true;
    }
}