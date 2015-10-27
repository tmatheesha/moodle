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
 * Defines site config settings for the grade single view report
 *
 * @package    gradereport_singleview
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Add settings for this module to the $settings object (it's already defined).
    $settings->add(new admin_setting_configtext('grade_report_singleviewperpage',
        new lang_string('singleviewperpage', 'gradereport_singleview'),
        new lang_string('singleviewperpage_help', 'gradereport_singleview'),
        50
    ));

}
