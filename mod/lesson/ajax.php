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
$page = optional_param('page', '', PARAM_RAW);

$pagecookie = array();
if (isset($_COOKIE['pageinfo'])) {
    $pagecookie = json_decode($_COOKIE['pageinfo']);
}
// var_dump($pagecookie);

if ($action == 'saveposition') {

    // var_dump($page);

    $lessonpage = json_decode($page);
    // var_dump($lessonpage);

    // foreach ($pagecookie as $key => $pageinfo) {
    //     # code...
    // }

    // if (!isset($pagecookie[$lessonpage->id])) {

    // }
    // $pagecookie[$lessonpage->id] = $lessonpage;
    var_dump($lessonpage['id']);



    // setcookie('pageinfo', json_encode($pagecookie));
    $response = 'this was a success!';
    echo json_encode($response);
    // print_object($lessonpage);
    // echo json_encode($lessonpage['title']);
    die();
}
