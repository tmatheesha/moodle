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
 * Additional enhancement of the mod data edit form
 *
 * @module     mod/data
 * @class      view_page
 * @package    mod_data
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/form-autocomplete', 'core/str', 'mod_data/dialogue', 'core/fragment', 'core/templates', 'core/notification'],
        function($, autocomplete, str, dialogue, fragment, templates, notification) {

    var contextid;

    var openBulkEditForm = function() {
        var recordids = [];
        $('.recordcheckbox').each(function() {
            var recordobject = $(this);
            if (recordobject.prop('checked')) {
                recordids.push(recordobject.val());
            }
        });
        
        // Add the function to switch the fragment stuff.
        var spintemplate = templates.render('mod_data/spinner', {});
        var formtemplate = templates.render('mod_data/bulk_edit_form', {'recordcount': recordids.length});
        $.when(spintemplate, formtemplate).done(function(spinner, formtemp) {
            console.log(spintemplate);
            console.log(formtemp[0]);
            // This should also be a template.
            // var temphtml = '<div id="mod_data_bulk_edit_form">' + spinner + '</div>';
            var editform = new dialogue('Bulk edit form', formtemp[0], loadForm);
            templates.replaceNodeContents('#mod_data_bulk_edit_form', spinner, '');
            $('#mod_data_bulk_update_cancel_btn').click(function() {
                console.log('close damn you!');
                editform.close();
            });
            // editform.close();
        });


    }

    var loadForm = function() {
        // Get all of the record id numbers that have been checked.
        // var recordids = [];
        // $('.recordcheckbox').each(function() {
        //     var recordobject = $(this);
        //     if (recordobject.prop('checked')) {
        //         recordids.push(recordobject.val());
        //     }
        // });
        // var pagehtml = '<div>Number of records to be updated: ' + recordids.length + '</div><br>';
        $.when(fragment.loadFragment('mod_data', 'thing', contextid, '')).done(function(html, javascript) {
            // pagehtml += html;
            templates.replaceNodeContents('#mod_data_bulk_edit_form', html, javascript);
        }).fail(notification.exception);
        
    }

    return {
        init: function(context_id) {
            contextid = context_id;
            $('#checkall').click(function() {
                $('.recordcheckbox').prop('checked', true);
            });
            $('#checknone').click(function() {
                $('.recordcheckbox').prop('checked', false);
            });
            $('#mod_data_bulk_edit').on({
                click: openBulkEditForm
            });
        }
    };
});