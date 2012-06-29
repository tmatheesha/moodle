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
 * Page for deleting course categories in user (not global admin mode)
 * For protection from accidental deletion, this page allows deletion
 * only when category does not have sub-categories, courses and questions.
 * User must remove them manualy.
 *
 * @package    core
 * @subpackage course
 * @copyright  2012 Vadim Dvorovenko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once('lib.php');

require_login();
require_sesskey();
$id = optional_param('id', 0, PARAM_INT);
if (!$id) {
    print_error('unknowcategory');
}
if (!$category = $DB->get_record('course_categories', array('id' => $id))) {
    print_error('unknowcategory');
}
$category_context = context_coursecat::instance($category->id);
require_capability('moodle/category:manage', $category_context);
require_capability('moodle/category:manage', get_category_or_system_context($category->parent));
if ($DB->count_records('course_categories', array('parent' => $category->id)) or
        $DB->count_records('course', array('category' => $category->id))) {
    $PAGE->set_context($category_contex);
    $PAGE->set_url('/course/deletecategory.php', array('id' => $id));
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('deletecategory', '', $category->name));
    $PAGE->set_heading($category->name);
    $courseurl = new moodle_url('index.php');
    $deletenode = $PAGE->navbar->add(get_string('courses'), $courseurl);
    $deleteurl = new moodle_url('deletecategory.php', array('id' => $id, 'sesskey' => sesskey()));
    $deletenode = $PAGE->navbar->add(get_string('delete'), $deleteurl);
    $deletenode->make_active();
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deletecategory', '', $category->name));
    echo $OUTPUT->box_start();
    echo get_string('deletecategoryerrornonempty');
    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button('category.php?id=' . $id);
    echo $OUTPUT->footer();
    die();
}
require_once($CFG->libdir . '/questionlib.php');
if (question_context_has_any_questions($category_context)) {
    $PAGE->set_context($category_contex);
    $PAGE->set_url('/course/deletecategory.php', array('id' => $id));
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('deletecategory', '', $category->name));
    $PAGE->set_heading($category->name);
    $courseurl = new moodle_url('index.php');
    $deletenode = $PAGE->navbar->add(get_string('courses'), $courseurl);
    $deleteurl = new moodle_url('deletecategory.php', array('id' => $id, 'sesskey' => sesskey()));
    $deletenode = $PAGE->navbar->add(get_string('delete'), $deleteurl);
    $deletenode->make_active();
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deletecategory', '', $category->name));
    echo $OUTPUT->box_start();
    echo get_string('deletecategoryerrorhasquestions');
    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button('category.php?id=' . $id);
    echo $OUTPUT->footer();
    die();
}
category_delete_full($category, false);
if ($category->parent) {
    redirect('category.php?id=' . $category->parent);
} else {
    redirect('index.php');
}
