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
 * Handle opening a dialogue to configure scale data.
 *
 * @module     tool_lp/scaleconfig
 * @package    tool_lp
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/templates', 'core/str', 'core/ajax', 'core/dialogue'],
    function($, notification, templates, str, ajax, dialogue) {

    var scalevalues = null;
    var originalscaleid = 0;
    var scaleid = 0;

    var showConfig = function() {
        scaleid = $("#id_scale").val();
        var scalename = $("#id_scale option:selected").text();
        getScaleValues(scaleid).done(function() {

            var context = {
                scalename: scalename,
                scales: scalevalues
            };

            // Dish up the form.
            templates.render('tool_lp/scale_configuration_page', context)
                .done(function(html) {
                    var popup = new dialogue(
                        scalename,
                        html,
                        initScaleConfig
                    );
                }).fail(notification.exception);
        }).fail(notification.exception);
    };

    var retrieveOriginalScaleConfig = function() {
        var jsonstring = $('#tool_lp_scaleconfiguration').val();
        if (jsonstring !== '') {
            return $.parseJSON(jsonstring);
        }
        return '';
    };

    var initScaleConfig = function(popup) {
        var body = $(popup.getContent());
        if (originalscaleid === scaleid) {
            // Set up the popup to show the current configuration.
            var currentconfig = retrieveOriginalScaleConfig();
            // Set up the form only if there is configuration settings to set.
            if (currentconfig !== '') {
                currentconfig.forEach(function(value) {
                    if (value.scaledefault === 1) {
                        $('#tool_lp_scale_default_' + value.id).attr('checked', true);
                    }
                    if (value.proficient === 1) {
                        $('#tool_lp_scale_proficient_' + value.id).attr('checked', true);
                    }
                });
            }
        }
        body.on('click', '[data-action="close"]', function() { setScaleConfig(); popup.close(); });
        body.on('click', '[data-action="cancel"]', function() { popup.close(); });
    };

    var setScaleConfig = function() {
        // Get the data.
        var data = [];
        scalevalues.forEach(function(value) {
            var scaledefault = 0;
            var proficient = 0;
            if ($('#tool_lp_scale_default_' + value.id).is(':checked')) { scaledefault = 1; }
            if ($('#tool_lp_scale_proficient_' + value.id).is(':checked')) { proficient = 1; }
            data.push({
                name: value.name,
                id: value.id,
                scaledefault: scaledefault,
                proficient: proficient
            });
         });
        var datastring = JSON.stringify(data);
        // Send to the hidden field on the form.
        $('#tool_lp_scaleconfiguration').val(datastring);
        // Once the configuration has been saved then the original scale ID is set to the current scale ID.
        originalscaleid = scaleid;
    };

    var getScaleValues = function(scaleid) {
        var deferred = $.Deferred();
        var promises = ajax.call([{
            methodname: 'tool_lp_get_scale_values',
            args: {
               scaleid: scaleid
            }
        }]);
        promises[0].done(function(result) {
            scalevalues = result;
            deferred.resolve(result);
        }).fail(function(exception) {
            deferred.reject(exception);
        });
        return deferred.promise();
    };

    return {
        init: function() {
            // Get the current scale ID.
            // Perhaps we should find the saved scale ID in a more reliable fashion.
            originalscaleid = $("#id_scale").val();
            $('#id_scaleconfigbutton').click(showConfig);
        }
    };
});