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
 * Process ajax requests
 *
 * @package mod_assign
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
// require_once($CFG->dirroot . '/mod/assign/locallib.php');

$action = optional_param('action', '', PARAM_ALPHANUM);
$lessonid = optional_param('lessonid', '', PARAM_ALPHANUM); // Definitely need this.
$pageid = optional_param('pageid', '', PARAM_RAW);
$pagex = optional_param('pagex', '', PARAM_RAW);
$pagey = optional_param('pagey', '', PARAM_RAW);

$pagecookie = new stdClass();
if (isset($_COOKIE['pageinfo'])) {
    $pagecookie = json_decode($_COOKIE['pageinfo']);
}
// var_dump($pagecookie);

if ($action == 'saveposition') {

    if (!isset($pagecookie->{$lessonid})) {
        $pagecookie->{$lessonid} = new stdClass();
        $pagecookie->{$lessonid}->{$pageid} = new stdClass();
        $pagecookie->{$lessonid}->{$pageid}->id = $pageid;
        $pagecookie->{$lessonid}->{$pageid}->x = $pagex;
        $pagecookie->{$lessonid}->{$pageid}->y = $pagey;
    } else {
        if (!isset($pagecookie->{$lessonid}->{$pageid})) {
            $pagecookie->{$lessonid}->{$pageid} = new stdClass();
        }
        $pagecookie->{$lessonid}->{$pageid}->id = $pageid;
        $pagecookie->{$lessonid}->{$pageid}->x = $pagex;
        $pagecookie->{$lessonid}->{$pageid}->y = $pagey;
    }

    setcookie('pageinfo', json_encode($pagecookie));
    $response = 'this was a success!';
    echo json_encode($response);
    die();
}
