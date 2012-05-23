<?php

// Page for deleting course categories in user (not global admin mode)
// For protection from accidental deletion, this page allows deletion 
// only when category does not have sub-categories, courses and questions.
// User must remove them manualy

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
$category_contex = get_context_instance(CONTEXT_COURSECAT, $category->id);
require_capability('moodle/category:manage', $category_contex);
require_capability('moodle/category:manage', get_category_or_system_context($category->parent));
if ($DB->count_records('course_categories', array('parent' => $category->id)) or
        $DB->count_records('course', array('category' => $category->id))) {
    $PAGE->set_context($category_contex);
    $PAGE->set_url('/course/deletecategory.php', array('id' => $id));
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('deletecategory', '', $category->name));
    $PAGE->set_heading($category->name);
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
if (question_context_has_any_questions($category_contex)) {
    $PAGE->set_context($category_contex);
    $PAGE->set_url('/course/deletecategory.php', array('id' => $id));
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('deletecategory', '', $category->name));
    $PAGE->set_heading($category->name);
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
