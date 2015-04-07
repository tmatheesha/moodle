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
 * Provides a form for filtering users.
 *
 * @package    core_user
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * User search form.
 *
 * @copyright   2015 Joseph Inhofer <jinhofer@umn.edu>
 * @license     http://www.gnu.org/copyleft/glp.html GNU GPL v3 or later
 */
class user_search_form extends moodleform {

    /**
     * This will initiate an instance of moodleform.
     */
    public function definition() {
        $mform = $this->_form;
        $content = $this->_customdata;
        $mform->addElement('header', 'filter_options', get_string('filter'));
        $mform->addElement('static', 'filter_content', null, $content);
        $mform->setExpanded('filter_options', false);
    }
}
