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
 * Contains functions that may be used as backoff functions.
 *
 * @module     core/backoff_timer_functions
 * @class      backoff_timer_functions
 * @package    core
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(function() {

    /**
     * Returns an incremental function for the timer.
     *
     * @param {int} minamount The minimum amount of time we wait before checking
     * @param {int} incrementamount The amount to increment the timer by
     * @param {int} maxamount The max amount to ever increment to
     * @param {int} timeoutamount The timeout to use once we reach the max amount
     * @returns {function}
     */
    var getIncrementalCallback = function(minamount, incrementamount, maxamount, timeoutamount) {

        /**
         * An incremental function for the timer.
         *
         * @param {(int|null)} time The current timeout value or null if none set
         * @return {int} The new timeout value
         */
        return function(time) {
            if (!time) {
                return minamount;
            }

            // Don't go over the max amount.
            if (time + incrementamount > maxamount) {
                return timeoutamount;
            }

            return time + incrementamount;
        };
    };

    return /** @module core/backoff_timer_functions */ {
        getIncrementalCallback: getIncrementalCallback
    };
});
