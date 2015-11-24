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

    var Text = function() {
        formElement.call(this);
        this.type = 'text';
        this.text = '';
        this.template = "core/form-element-text";
    };

    Text.prototype.set_name = function(name) {
        formElement.prototype.set_name.call(this, name);
    };

    Text.prototype.set_id = function(id) {
        formElement.prototype.set_id.call(this, id);
    };

    Text.prototype.set_label = function(label) {
        formElement.prototype.set_label.call(this, label);
    };

    Text.prototype.set_error = function(error) {
        formElement.prototype.set_error.call(this, error);
    };

    Text.prototype.set_required = function() {
        formElement.prototype.set_required.call(this);
    };

    Text.prototype.set_text = function(text) {
        this.text = text;
    };

    Text.prototype.get_name = function() {
        return formElement.prototype.get_name.call(this);
    };

    Text.prototype.show = function() {
        return formElement.prototype.show.call(this);
    };


    return Text;

});