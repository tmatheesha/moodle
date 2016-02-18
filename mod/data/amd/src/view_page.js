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
define(['jquery', 'core/form-autocomplete', 'core/str'], function($, autocomplete, str) {

    var openBulkEditForm = function() {
        console.log('open that form');
    }

    return {
        init: function() {
            // console.log('Time to bulk edit.');
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