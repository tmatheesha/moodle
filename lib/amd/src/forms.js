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

    var formData;

    return {

        handleErrors: function(errors, formdata) {
            // name attribute of the error.
            var errorname = errors.errors.name;
            var templatedata;
            for (index in formdata) {
                if (typeof formdata[index] == "object") {
                    if (formdata[index].name == errorname) {
                        formdata[index].error = errors.errors.message;
                        templatedata = formdata[index];
                    }
                }
            }
            // for (index in errors.errors) {

            //     console.log(errors.errors["name"]);
            // }

            // Need to loop through all potential errors.

            var errorelementid = $(":input[name=" + errorname + "]").attr("id");
            var errorelement = $("#fitem_" + errorelementid);
            // Get the form element type.
            var elementtype = errorelement.attr("data-field-type");
            var template = 'core/form-element-' + elementtype;
            var templatepromise = templates.render(template, templatedata);
            $.when(templatepromise).then(function(newhtml) {
                templates.replaceNode(errorelement, newhtml, '');
                $("#fitem_" + errorelementid).find("span.error").focus();
            });
        },

        getFormData: function() {
            formData = $('form').serializeArray();
            var tempstorage = {};
            for (index in formData) {
                var name = formData[index].name;
                if (name !== 'query') {
                    tempstorage[name] = formData[index].value;
                }
            }
            return tempstorage;
        },

        /**
         * Format a normal array into an object for a select form element.
         *
         * @param {array} options An array of options.
         * @param {string} selected The selected option. Needs to match the array index.
         * @return {object} An options object that can be used with a select form element.
         */
        formatOptions: function(options, selected) {
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
    };
});