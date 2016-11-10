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
 * A timer that will execute a callback with decreasing frequency. Useful for
 * doing polling on the server without overwhelming it with requests.
 *
 * @module     core/backoff_timer
 * @class      backoff_timer
 * @package    core
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(function() {

    /**
     * Constructor for the back off timer.
     *
     * @param {function} callback The function to execute after each tick
     * @param {function} backoffFunction The function to determine what the next timeout value should be
     */
    var Timer = function(callback, backoffFunction) {
        this.callback = callback;
        this.backOffFunction = backoffFunction;
    };

    /**
     * @type {function} callback The function to execute after each tick
     */
    Timer.prototype.callback = null;

    /**
     * @type {function} backoffFunction The function to determine what the next timeout value should be
     */
    Timer.prototype.backOffFunction = null;

    /**
     * @type {int} time The timeout value to use
     */
    Timer.prototype.time = null;

    /**
     * Generate the next timeout in the back off time sequence
     * for the timer.
     *
     * The back off function is called to calculate the next value.
     * It is given the current value and an array of all previous values.
     *
     * @method generateNextTime
     * @return {int} The new timeout value (in milliseconds)
     */
    Timer.prototype.generateNextTime = function() {
        var newTime = this.backOffFunction(this.time);
        this.time = newTime;

        return newTime;
    };

    /**
     * Stop the current timer and clear the previous time values
     *
     * @method reset
     * @return {object} this
     */
    Timer.prototype.reset = function() {
        this.time = null;
        this.stop();

        return this;
    };

    /**
     * Clear the current timeout, if one is set.
     *
     * @method stop
     * @return {object} this
     */
    Timer.prototype.stop = function() {
        if (this.timeout) {
            window.clearTimeout(this.timeout);
            this.timeout = null;
        }

        return this;
    };

    /**
     * Start the current timer by generating the new timeout value and
     * starting the ticks.
     *
     * This function recurses after each tick with a new timeout value
     * generated each time.
     *
     * The callback function is called after each tick.
     *
     * @method start
     * @return {object} this
     */
    Timer.prototype.start = function() {
        // If we haven't already started.
        if (!this.timeout) {
            var time = this.generateNextTime();
            this.timeout = window.setTimeout(function() {
                this.callback();
                // Clear the existing timer.
                this.stop();
                // Start the next timer.
                this.start();
            }.bind(this), time);
        }

        return this;
    };

    /**
     * Reset the timer and start it again from the initial timeout
     * values
     *
     * @method restart
     * @return {object} this
     */
    Timer.prototype.restart = function() {
        return this.reset().start();
    };

    return Timer;
});
