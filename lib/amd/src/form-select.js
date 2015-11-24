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
 * Needs to have a description about forms and stuff.
 *
 * @module     core/forms
 * @class      forms
 * @package    core
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/form-element'], function($, formElement) {

    var Select = function() {
        formElement.call(this);
        this.type = 'select';
        this.options = '';
        this.template = "core/form-element-select";
    };

    /**
     * Format a normal array into an object for a select form element.
     *
     * @param {array} options An array of options.
     * @param {string} selected The selected option. Needs to match the array index.
     * @return {object} An options object that can be used with a select form element.
     */
    this.formatOptions = function(options, selected) {
        var returnoptions = [];
        var select = 0;
        if (selected !== 'undefined') {
            select = selected
        }
        for (index in options) {
            if (index == selected) {
                returnoptions.push({
                    value: index,
                    text: options[index],
                    selected: select
                });
            } else {
                returnoptions.push({
                    value: index,
                    text: options[index],
                });
            }
        }
        return returnoptions;
    }

    Select.prototype.set_name = function(name) {
        formElement.prototype.set_name.call(this, name);
    };

    Select.prototype.set_id = function(id) {
        formElement.prototype.set_id.call(this, id);
    };

    Select.prototype.set_label = function(label) {
        formElement.prototype.set_label.call(this, label);
    };

    Select.prototype.set_error = function(error) {
        formElement.prototype.set_error.call(this, error);
    };

    Select.prototype.set_required = function() {
        formElement.prototype.set_required.call(this);
    };

    Select.prototype.set_options = function(options, selected) {
        this.options = formatOptions(options, selected);
    };

    Select.prototype.get_name = function() {
        return formElement.prototype.get_name.call(this);
    };

    Select.prototype.show = function() {
        return formElement.prototype.show.call(this);
    };


    return Select;

});