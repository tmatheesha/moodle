<?php
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
 * Script for bulk user activation operations.
 *
 * @package    core
 * @subpackage user
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');

$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
require_capability('moodle/user:update', context_system::instance());

$return = new moodle_url('/admin/user/user_bulk.php');

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

if ($confirm and confirm_sesskey()) {
    $activateusers = $SESSION->bulk_users;
    // We don't want to include users that are already activated, so we do a search to exclude them.
    $exemptusers = get_users_suspension_state(false);
    // Remove exempt users from the activate users list.
    foreach ($exemptusers as $user) {
        if (in_array($user, $activateusers)) {
            unset($activateusers[$user]);
        }
    }
    toggle_users_suspension($activateusers, false);
    redirect($return, get_string('changessaved'));
} else {
    echo $OUTPUT->header();
    $usernames = get_usernames($SESSION->bulk_users);
    if (count($SESSION->bulk_users) > MAX_BULK_USERS) {
        $usernames .= ', ...';
    }

    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
    $formcontinue = new single_button(new moodle_url('/admin/user/user_bulk_activate.php', array('confirm' => 1)), get_string('yes'));
    $formcancel = new single_button(new moodle_url('/admin/user/user_bulk.php'), get_string('no'), 'get');
    echo $OUTPUT->confirm(get_string('activatecheckfull', 'admin', $usernames), $formcontinue, $formcancel);
    echo $OUTPUT->footer();
}
