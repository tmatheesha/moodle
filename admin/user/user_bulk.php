<?php

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/user_bulk_forms.php');

admin_externalpage_setup('userbulk');

if (!isset($SESSION->bulk_users)) {
    $SESSION->bulk_users = array();
}
// create the user filter form
$ufiltering = new user_filtering();

// array of bulk operations
// create the bulk operations form
$action_form = new user_bulk_action_form();
if ($data = $action_form->get_data()) {
    // check if an action should be performed and do so
    switch ($data->action) {
        case BULK_CONFIRM: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_confirm.php');
        case BULK_MESSAGE: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_message.php');
        case BULK_DELETE: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_delete.php');
        case BULK_DISPLAY: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_display.php');
        case BULK_DOWNLOAD: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_download.php');
        //case BULK_ENROL: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_enrol.php'); //TODO: MDL-24064
        case BULK_FORCE_PASSWORD_CHANGE: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_forcepasswordchange.php');
        case BULK_COHORT_ADD: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_cohortadd.php');
        case BULK_SUSPEND: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_suspend.php');
        case BULK_ACTIVATE: redirect($CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk_activate.php');
    }
}

$user_bulk_form = new user_bulk_form(null, get_selection_data($ufiltering));

if ($data = $user_bulk_form->get_data()) {
    if (!empty($data->addall)) {
        add_selection_all($ufiltering);

    } else if (!empty($data->addsel)) {
        if (!empty($data->ausers)) {
            if (in_array(0, $data->ausers)) {
                add_selection_all($ufiltering);
            } else {
                foreach($data->ausers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    if (!isset($SESSION->bulk_users[$userid])) {
                        $SESSION->bulk_users[$userid] = $userid;
                    }
                }
            }
        }

    } else if (!empty($data->removeall)) {
        $SESSION->bulk_users= array();

    } else if (!empty($data->removesel)) {
        if (!empty($data->susers)) {
            if (in_array(0, $data->susers)) {
                $SESSION->bulk_users= array();
            } else {
                foreach($data->susers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    unset($SESSION->bulk_users[$userid]);
                }
            }
        }
    }

    // reset the form selections
    unset($_POST);
    $user_bulk_form = new user_bulk_form(null, get_selection_data($ufiltering));
}
// do output
echo $OUTPUT->header();

$ufiltering->display_add();
$ufiltering->display_active();

$user_bulk_form->display();

$action_form->display();

echo $OUTPUT->footer();
