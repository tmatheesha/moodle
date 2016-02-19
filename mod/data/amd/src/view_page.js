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
define(['jquery', 'core/form-autocomplete', 'core/str', 'mod_data/dialogue'], function($, autocomplete, str, dialogue) {

    var openBulkEditForm = function() {
        console.log('open that form');
        // Get all of the record id numbers that have been checked.
        var recordids = [];
        $('.recordcheckbox').each(function() {
            var recordobject = $(this);
            if (recordobject.prop('checked')) {
                recordids.push(recordobject.val());
            }
        });
        console.log(recordids);
        var html = '<div>Number of records to be updated: ' + recordids.length + '</div>';
        
        var editform = new dialogue('Bulk edit form', 'Some random content');
    }

    return {
        init: function() {
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