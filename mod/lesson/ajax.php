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
require_once($CFG->dirroot . '/mod/lesson/locallib.php');

$action = optional_param('action', '', PARAM_ALPHANUM);
$lessonid = optional_param('lessonid', '', PARAM_ALPHANUM); // Definitely need this.
$pageid = optional_param('pageid', '', PARAM_RAW);
$pagex = optional_param('pagex', '', PARAM_RAW);
$pagey = optional_param('pagey', '', PARAM_RAW);
$lessondata = optional_param_array('lessondata', '', PARAM_RAW);
$jsondata = optional_param('jsondata', '', PARAM_RAW);


$pagecookie = new stdClass();
if (isset($_COOKIE['pageinfo'])) {
    $pagecookie = json_decode($_COOKIE['pageinfo']);
}
// var_dump($pagecookie);

if ($action == 'saveposition') {

    $record = new stdClass();
    $record->id = $lessondata['pageid'];
    $record->positionx = $lessondata['positionx'];
    $record->positiony = $lessondata['positiony'];
    $DB->update_record('lesson_pages', $record);

    $response = 'this was a success!';
    echo json_encode($response);
    die();
} else if ($action == 'createcontent') {
    $i = 1;
    $returnid = null;
    if ($lessondata['qtype'] == 30) {
        $i = 2;
    }
    while ($i != 0) {

        if ($lessondata['qtype'] == 30 && $i == 1) {
            // Add the end of cluster data.
            $lessondata['qtype'] = 31;
            $lessondata['title'] = "End of cluster";
            $lessondata['contents'] = "End of cluster";
            $lessondata['location'] = "normal";
        }

        // Find record with zero as the previous page.
        $lastfile = $DB->get_record('lesson_pages', array('lessonid' => $lessonid, 'nextpageid' => 0));

        $lessondata['timecreated'] = time();
        $lessondata['prevpageid'] = $lastfile->id;
        // Instead of direct call, we need to use the API. With that in mind, we will probably end up using web services anyway.
        $insertid = $DB->insert_record('lesson_pages', $lessondata);

        $lastfile->nextpageid = $insertid;
        $DB->update_record('lesson_pages', $lastfile);

        // Also need an entry in lesson_answers.
        $lessonanswerrecord = new stdClass();
        $lessonanswerrecord->lessonid = $lessonid;
        $lessonanswerrecord->pageid = $insertid;
        $lessonanswerrecord->jumpto = -1;
        if ((int)$lessondata['qtype'] == 30) {
            $lessonanswerrecord->jumpto = -80;
        }
        $lessonanswerrecord->timecreated = time();
        $lessonanswerrecord->answer = 'Next page';
        $DB->insert_record('lesson_answers', $lessonanswerrecord);

        // Don't do this. Use the lesson_page::get_typeid
        if ((int)$lessondata['qtype'] < 10) {
            // Also need an entry in lesson_answers.
            $lessonanswerrecord = new stdClass();
            $lessonanswerrecord->lessonid = $lessonid;
            $lessonanswerrecord->pageid = $insertid;
            $lessonanswerrecord->jumpto = 0;
            $lessonanswerrecord->timecreated = time();
            $lessonanswerrecord->answer = 'This page';
            $DB->insert_record('lesson_answers', $lessonanswerrecord);
        }  
        if ($lessondata['qtype'] != 31) {
            $returnid = $insertid;
        }
        $i--;
    }
    // Get the complete record and send that back.
    $lessonpage = $DB->get_record('lesson_pages', array('id' => $returnid));
    
    echo json_encode($lessonpage);
    die();
} else if ($action == 'deletelessonpage') {

    $lesson = lesson::load($lessonid);
    $lessonpage = lesson_page::load($pageid, $lesson);
    $endofcluster = null;
    if ($lessonpage->get_typeid() == 30) {
        // Remove the end of cluster page as well.
        $endofcluster = lesson_page::load($lessondata['endofclusterid'], $lesson);
        $endofcluster->delete();
    }
    if ($lessonpage->get_typeid() == 20 && isset($lessondata['endofclusterid'])) {
        $endofcluster = lesson_page::load($lessondata['endofclusterid'], $lesson);
        $endofcluster->delete();
    }
    // Load again?
    $lessonpage = lesson_page::load($pageid, $lesson);
    $lessonpage->delete();

    // Delete lesson page
    echo json_encode('deleted');

    die();
} else if ($action == 'linklessonpages') {

    // See if we have a record for this already. Perhaps we should be removing the link.
    $lessonanswer1 = $DB->get_record('lesson_answers', array('pageid' => $lessondata['pageid'], 'jumpto' => $lessondata['jumpid']));

    $sql = "SELECT la.id, la.jumpto
              FROM mdl_lesson_answers la, mdl_lesson_pages lp
             WHERE la.pageid = lp.id
               AND (lp.id = :pageid AND lp.nextpageid = :jumpid AND la.jumpto = -1)";
    $params = array('pageid' => $lessondata['pageid'], 'jumpid' => $lessondata['jumpid']);
    $lessonanswer2 = $DB->get_record_sql($sql, $params);

    if ($lessonanswer1) {
        $DB->delete_records('lesson_answers', array('id' => $lessonanswer1->id));
    } else if ($lessonanswer2) {
        $lessonanswer2->jumpto = 0;
        $DB->update_record('lesson_answers', $lessonanswer2);
    } else {
        // Check to see if there is a jumpto 0 and update that record first.
        if ($record = $DB->get_record('lesson_answers', array('lessonid' => $lessonid, 'pageid' => $lessondata['pageid'], 'jumpto' => 0))) {
            $record->jumpto = $lessondata['jumpid'];
            $DB->update_record('lesson_answers', $record);
        } else {
            $record = new stdClass();
            $record->pageid = $lessondata['pageid'];
            $record->lessonid = $lessonid;
            $record->jumpto = $lessondata['jumpid'];
            $record->timecreated = time();
            $record->answer = 'jump to ' . $lessondata['jumpid'];
            $DB->insert_record('lesson_answers', $record);
        }
    }

    // Returning required information about the object might be easier.
    $newlessondata = $DB->get_records('lesson_answers', array('pageid' => $lessondata['pageid']));
    echo json_encode($newlessondata);

    die();
} else if ($action == 'getjumpoptions') {
    $lesson = lesson::load($lessonid);
    $page = lesson_page::load($pageid, $lesson);
    if ($page->qtype == LESSON_PAGE_BRANCHTABLE) {
        $pageid = false;
    }
    $options = $page->get_jumptooptions($pageid, $lesson);

    echo json_encode($options);
    die();
} else if ($action == 'getlessondata') {
    $pageid = $lessondata['pageid'];
    $lesson = lesson::load($lessonid);
    $pages = array();
    $clusters = array();
    $subclusters = array();
    $clustercount = 0;
    $subclustercount = 0;
    $currentclusterid = 0;
    $currentsubclusterid = 0;
    while ($pageid != 0) {
        $page = $lesson->load_page($pageid);
        if ($clustercount) {
            if ($page->qtype == LESSON_PAGE_ENDOFCLUSTER) {
                $clustercount --;
            } else {
                $clusters[$currentclusterid][] = $pageid;
                // Check if we are at the end of a subcluster.
                if ($subclustercount) {
                    if ($page->qtype == LESSON_PAGE_ENDOFBRANCH) {
                        $subclustercount --;
                    } else {
                        $subclusters[$currentsubclusterid][] = $pageid;
                    }
                }

                if ($page->qtype == LESSON_PAGE_BRANCHTABLE) {
                    // If a content page is in a cluster then it is considered the start of a subcluster.
                    $subclusters[$pageid][] = array();
                    $subclustercount ++;
                    $currentsubclusterid = $pageid;
                }
            }
        }
        if ($page->qtype == LESSON_PAGE_CLUSTER) {
            $clusters[$pageid] = array(); 
            $clustercount ++;
            $currentclusterid = $pageid;
        }
        $pageproperties = $page->properties();
        $answers = $page->get_answers();
        $pageproperties->answers = array();
        foreach ($answers as $key => $answer) {
            $pageproperties->answers[] = $answer->properties();
        }
        $pageproperties->qtypestr = $page->get_typestring();
        $pageproperties->pagetypename = $page->get_idstring();

        $pageproperties->x = $page->positionx;
        $pageproperties->y = $page->positiony;

        if ($clustercount) {
            if ($subclustercount) {
                $pageproperties->location = 'subcluster';
            } else {
                $pageproperties->location = 'cluster';
            }
        } else {
            $pageproperties->location = 'normal';
        }
        if ($page->qtype == LESSON_PAGE_ENDOFBRANCH) {
            $pageproperties->subclusterid = $currentsubclusterid;
        }

        // Add the cluster id to the cluster end object.
        if ($page->qtype == LESSON_PAGE_ENDOFCLUSTER) {
            $pageproperties->clusterid = $currentclusterid;
        }
        $pages[$pageid] = $pageproperties;
        $pageid = $page->nextpageid;
    }
    foreach ($clusters as $key => $value) {
        $pages[$key]->clusterchildrenids = $value;
    }
    foreach ($subclusters as $key => $value) {
        $pages[$key]->subclusterchildrenids = $value;
    }
    // Reindex the array for use with YUI.
    echo json_encode($pages);
    die();
} else if ($action == 'updatelessonpage') {

    $expandeddata = json_decode($jsondata);
    // Look to API to do this properly.
    $pagerecord = $expandeddata->page;
    $pagerecord->timemodified = time();
    $DB->update_record('lesson_pages', $pagerecord);


    $answerrecord = new stdClass();
    $answerrecord->lessonid = $expandeddata->answer->lessonid;
    $answerrecord->pageid = $expandeddata->answer->pageid;
    // Get all of the answers for this page.
    $answers = $DB->get_records('lesson_answers', array('lessonid' => $answerrecord->lessonid, 'pageid' => $answerrecord->pageid), 'id');
    $answers = array_values($answers);
    $i = 0;

    foreach ($expandeddata->answer->jumps as $value) {
        $answerrecord->jumpto = $value->jumpto;
        $answerrecord->answer = $value->answer;
        $answerrecord->response = $value->response;
        $answerrecord->score = $value->score;
        $answerrecord->timemodified = time();
        if (isset($answers[$i])) {
            $answerrecord->id = $answers[$i]->id;
            // Update. Not reliable. We are betting that there are no two records that go to the same page.
            // var_dump($answerrecord);
            $DB->update_record('lesson_answers', $answerrecord);
        } else {
            $DB->insert_record('lesson_answers', $answerrecord);
        }
        $i++;
    }

    echo json_encode('Updated!');
    die();
} else if ($action == 'movepage') {

    $pageids = explode(',', $lessondata['pageid']);
    $pageids = array_reverse($pageids);
    $lesson = lesson::load($lessonid);
    foreach ($pageids as $pageid) {
        $lesson->resort_pages($pageid, $lessondata['after']);
    }

    echo json_encode('success');
    die();
}
