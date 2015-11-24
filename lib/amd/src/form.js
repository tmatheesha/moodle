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
define(['jquery', 'core/templates'], function($, templates) {

    var Form = function() {
        this._fieldsets = {};
        this.elements = {};
        this.formPage = '';

    };

    // These two methods would actually be using classes for the form.
    Form.prototype.add_top = function() {
        this.formPage += '<div class="mod_lesson_page_editor"><form action="">';
    };

    Form.prototype.add_bottom = function() {
        this.formPage += '<div><button type="button" id="mod_lesson_editor_save_btn">Save</button>';
        this.formPage += '<button type="button" id="mod_lesson_editor_cancel_btn">Cancel</button></div></form></div>';
    };



    Form.prototype.addFieldset = function(key, title) {
        this._fieldsets[key] = title;
    };

    Form.prototype.handleError = function(errorname, errorelement, elementtype, errormessage) {
        var index = elementtype + "-" + errorname;
        this.elements[index].set_error(errormessage);
        var htmlpromise = templates.render(this.elements[index].template, this.elements[index]);
        $.when(htmlpromise).then(function(newhtml) {
            templates.replaceNode(errorelement, newhtml);
        });
    }

    Form.prototype.handleErrors = function(errors) {

        // Need to loop through all potential errors.

        var errorname = errors.errors.name;
        var errorelementid = $(":input[name=" + errorname + "]").attr("id");
        // console.log(errorelementid);
        var errorelement = $("#fitem_" + errorelementid);
        // console.log(errorelement);
        // Get the form element type.
        var elementtype = errorelement.attr("data-field-type");
        Form.prototype.handleError.call(this, errorname, errorelement, elementtype, errors.errors.message);
    }


    Form.prototype.addElement = function(element) {
        var index = element.type + "-" + element.name;
        this.elements[index] = element;
    };

    Form.prototype.showElements = function() {
        console.log(this.elements);
    };

    Form.prototype.printForm = function() {

        var allpromises = [];
        for (index in this.elements) {
            allpromises.push(this.elements[index].show());
        }

        return $.when.apply($.when, allpromises).then(function() {
            Form.prototype.add_top.call(this);
            var schema = arguments;
            for (index in schema) {
                this.formPage += schema[index][0];
            }
            Form.prototype.add_bottom.call(this);
            return this.formPage;
        }, function() {
           return 'default html'; 
        });
    }

    return Form;

});