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
 * A scheduled task.
 *
 * @package    core
 * @copyright  2014 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

/**
 * Simple task to delete failed backup records from the temp/backup area.
 *
 * @package    core
 * @copyright  2014 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class failed_backup_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskfailedbackup', 'admin');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG;

        // This is defaulted to six hours.
        $timedelay = intval(get_config('backup', 'failedbackupcheck')) * 3600;

        // Get the backup temp directory.
        $backupdirectory = $CFG->dataroot . '/temp/backup';

        // Get directory information.
        $iterator = new \DirectoryIterator($backupdirectory);
        foreach ($iterator as $fileinfo) {
            // Check that the object is a directory.
            if ($fileinfo->isDir() && ($fileinfo->getFilename() != '.' && $fileinfo->getFilename() != '..')) {
                // If the directory was modified over the time delay (six hours) then it is probably a failed backup.
                if ($fileinfo->getMTime() < (time() - $timedelay)) {
                    mtrace('  Removing: ' . $backupdirectory . '/' . $fileinfo->getFilename());
                    // Recursively remove the directory.
                    $result = remove_dir($backupdirectory . '/' . $fileinfo->getFilename());
                    if ($result) {
                        mtrace('  Success.');
                        // Remove the log file as well.
                        unlink($backupdirectory . '/' . $fileinfo->getFilename() . '.log');
                    } else {
                        mtrace('  Problem removing directory.');
                    }
                }
            }
        }
    }
}
