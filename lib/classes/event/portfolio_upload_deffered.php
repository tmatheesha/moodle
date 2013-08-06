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

namespace core\event;

/**
 * Portfolio send event.
 *
 * @package    core
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class portfolio_upload_deffered extends base {
    protected function init() {
        $this->data['crud'] = 'u';
        // TODO: MDL-37658 set level
        $this->data['level'] = 50;
    }

    /**
     * Returns localised general event name.
     *
     * @return string|\lang_string
     */
    public static function get_name() {
        //TODO: MDL-37658 localise
        return get_string('porfoliouploaddeffered', 'portfolio');
    }

    /**
     * Returns localised description of what happened.
     *
     * @return string|\lang_string
     */
    public function get_description() {
        //TODO: MDL-37658 localise
        return 'The upload of a portfolio with an ID of '. $this->objectid .' was deffered for later processing.';
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        return null;
    }

    /**
     * Does this event replace legacy event?
     *
     * @return null|string legacy event name
     */
    protected function get_legacy_eventname() {
        return 'portfolio_send';
    }

    /**
     * Legacy event data if get_legacy_eventname() is not empty.
     *
     * @return mixed
     */
    protected function get_legacy_eventdata() {
        return $this->objectid;
    }
}
