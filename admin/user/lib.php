<?php

require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

if (!defined('MAX_BULK_USERS')) {
    define('MAX_BULK_USERS', 2000);
}

/**
 * User bulk actions.
 */
define('BULK_CONFIRM', 1);
define('BULK_MESSAGE', 2);
define('BULK_DELETE', 3);
define('BULK_DISPLAY', 4);
define('BULK_DOWNLOAD', 5);
define('BULK_ENROL', 6);
define('BULK_FORCE_PASSWORD_CHANGE', 7);
define('BULK_COHORT_ADD', 8);
define('BULK_SUSPEND', 9);
define('BULK_ACTIVATE', 10);

function add_selection_all($ufiltering) {
    global $SESSION, $DB, $CFG;

    list($sqlwhere, $params) = $ufiltering->get_sql_filter("id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));

    $rs = $DB->get_recordset_select('user', $sqlwhere, $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    foreach ($rs as $user) {
        if (!isset($SESSION->bulk_users[$user->id])) {
            $SESSION->bulk_users[$user->id] = $user->id;
        }
    }
    $rs->close();
}

function get_selection_data($ufiltering) {
    global $SESSION, $DB, $CFG;

    // get the SQL filter
    list($sqlwhere, $params) = $ufiltering->get_sql_filter("id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));

    $total  = $DB->count_records_select('user', "id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));
    $acount = $DB->count_records_select('user', $sqlwhere, $params);
    $scount = count($SESSION->bulk_users);

    $userlist = array('acount'=>$acount, 'scount'=>$scount, 'ausers'=>false, 'susers'=>false, 'total'=>$total);
    $userlist['ausers'] = $DB->get_records_select_menu('user', $sqlwhere, $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname', 0, MAX_BULK_USERS);

    if ($scount) {
        if ($scount < MAX_BULK_USERS) {
            $in = implode(',', $SESSION->bulk_users);
        } else {
            $bulkusers = array_slice($SESSION->bulk_users, 0, MAX_BULK_USERS, true);
            $in = implode(',', $bulkusers);
        }
        $userlist['susers'] = $DB->get_records_select_menu('user', "id IN ($in)", null, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    }

    return $userlist;
}

/**
 * Activate or suspend user accounts.
 *
 * @param array $userlist Users to be activated or suspended.
 * @param bool $suspend True = suspend user accounts, False = activate.
 */
function toggle_users_suspension($userlist, $suspend = true) {
    global $DB;

    // Check that we have any users to suspend.
    if (count($userlist) > 0) {
        // if $suspend = false it won't read as 0 in the sql statement.
        $suspend = ($suspend) ? 1 : 0;
        if (user_update_users($userlist, array('suspended' => $suspend))) {
            list($in, $params) = $DB->get_in_or_equal($userlist);
            $rs = $DB->get_recordset_select('user', "id $in", $params, null, 'id');
            if ($suspend) {
                foreach ($rs as $user) {
                    // Force logout.
                    session_kill_user($user->id);
                    session_gc(); // Remove stale sessions.
                }
            }
            $rs->close();
        }
    }
}

/**
 * Get a list of currently active or suspended users.
 *
 * @param bool $suspend True = suspeneded, False = active.
 * @return array An array of current active or suspended user IDs.
 */
function get_users_suspension_state($suspend = true) {
    global $DB;
    // if $suspend = false it won't read as 0 in the sql statement.
    $suspend = ($suspend) ? 1 : 0;
    $exemptusers = array();
    $suspended = $DB->get_recordset('user', array('suspended' => $suspend), null, 'id');
    foreach ($suspended as $suspendeduser) {
        $exemptusers[] = $suspendeduser->id;
    }
    $suspended->close();
    return $exemptusers;
}

/**
 * Get user names from a list of user IDs.
 *
 * @param array $bulkusers An array of user IDs.
 * @param int $maxentries The number of maximum entries to return.
 * @return string A string of user names seperated by commas.
 */
function get_usernames($bulkusers, $maxentries = MAX_BULK_USERS) {
    global $DB;
    list($in, $params) = $DB->get_in_or_equal($bulkusers);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname',
            'id,'.$DB->sql_fullname().' AS fullname', 0, $maxentries);
    return implode(', ', $userlist);
}
