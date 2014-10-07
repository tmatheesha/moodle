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
 * This file keeps track of upgrades to the badges block
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @since Moodle 2.8
 * @package block_add_blocks
 * @copyright 2014 Adrian Greeve
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the add blocks block
 * @param int $oldversion
 * @param object $block
 */

function xmldb_block_add_blocks_upgrade($oldversion, $block) {
    global $DB;

    if ($oldversion < 2014082005) {

        $blockname = 'add_blocks';
        $blocknames = array(BLOCK_POS_LEFT => array('add_blocks'));
        $course = get_course(SITEID);
        $page = new moodle_page();
        $page->set_context(context_system::instance());
        // The $page->blocks->is_block_present() function will not work as the old block for adding blocks is still present.
        // We need to check block_instances table to see if there is an entry there for add_blocks.
        if (!$addblock = $DB->record_exists('block_instances', array('blockname' => $blockname))) {
            $page->blocks->add_blocks($blocknames, '*', null, true);
        }
        upgrade_block_savepoint(true, 2014082005, $blockname);
    }

    return true;
}
