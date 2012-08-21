<?php

/// Some constants

define ('PROFILE_VISIBLE_ALL',     '2'); // only visible for users with moodle/user:update capability
define ('PROFILE_VISIBLE_PRIVATE', '1'); // either we are viewing our own profile or we have moodle/user:update capability
define ('PROFILE_VISIBLE_NONE',    '0'); // only visible for moodle/user:update capability



/**
 * Base class for the customisable profile fields.
 */
class profile_field_base {
    private static $fields;
    private static $preload;

    /// These 2 variables are really what we're interested in.
    /// Everything else can be extracted from them
    var $fieldid;
    var $userid;

    var $field;
    var $inputname;
    var $data;
    var $dataformat;

    /**
     * Constructor method.
     * @param   int   ID of the profile from the user_info_field table.
     * @param   int   ID of the user for whom we are displaying data.
     */
    function profile_field_base($fieldid=0, $userid=0) {
        global $USER;

        $this->set_fieldid($fieldid);
        $this->set_userid($userid);
        $this->load_data();
    }


/***** The following methods must be overwritten by child classes *****/

    /**
     * Abstract method: Adds the profile field to the moodle form class
     * @param  form  instance of the moodleform class
     */
    function edit_field_add($mform) {
        print_error('mustbeoveride', 'debug', '', 'edit_field_add');
    }


/***** The following methods may be overwritten by child classes *****/

    /**
     * Display the data for this field
     */
    function display_data() {
        $options = new stdClass();
        $options->para = false;
        return format_text($this->data, FORMAT_MOODLE, $options);
    }

    /**
     * Print out the form field in the edit profile page
     * @param   object   instance of the moodleform class
     * $return  boolean
     */
    function edit_field($mform) {

        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {

            $this->edit_field_add($mform);
            $this->edit_field_set_default($mform);
            $this->edit_field_set_required($mform);
            return true;
        }
        return false;
    }

    /**
     * Tweaks the edit form
     * @param   object   instance of the moodleform class
     * $return  boolean
     */
    function edit_after_data($mform) {

        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        return false;
    }

    /**
     * Saves the data coming from form
     * @param   mixed   data coming from the form
     * @return  mixed   returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    function edit_save_data($usernew) {
        global $DB;

        if (!isset($usernew->{$this->inputname})) {
            // field not present in form, probably locked and invisible - skip it
            return;
        }

        $data = new stdClass();

        $usernew->{$this->inputname} = $this->edit_save_data_preprocess($usernew->{$this->inputname}, $data);

        $data->userid  = $usernew->id;
        $data->fieldid = $this->field->id;
        $data->data    = $usernew->{$this->inputname};

        if ($dataid = $DB->get_field('user_info_data', 'id', array('userid'=>$data->userid, 'fieldid'=>$data->fieldid))) {
            $data->id = $dataid;
            $DB->update_record('user_info_data', $data);
        } else {
            $DB->insert_record('user_info_data', $data);
        }
    }

    /**
     * Validate the form field from profile page
     * @return  string  contains error message otherwise NULL
     **/
    function edit_validate_field($usernew) {
        global $DB;

        $errors = array();
        /// Check for uniqueness of data if required
        if ($this->is_unique()) {
            $value = (is_array($usernew->{$this->inputname}) and isset($usernew->{$this->inputname}['text'])) ? $usernew->{$this->inputname}['text'] : $usernew->{$this->inputname};
            $data = $DB->get_records_sql('
                    SELECT id, userid
                      FROM {user_info_data}
                     WHERE fieldid = ?
                       AND ' . $DB->sql_compare_text('data', 255) . ' = ' . $DB->sql_compare_text('?', 255),
                    array($this->field->id, $value));
            if ($data) {
                $existing = false;
                foreach ($data as $v) {
                    if ($v->userid == $usernew->id) {
                        $existing = true;
                        break;
                    }
                }
                if (!$existing) {
                    $errors[$this->inputname] = get_string('valuealreadyused');
                }
            }
        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_default($mform) {
        if (!empty($default)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }
    }

    /**
     * Sets the required flag for the field in the form object
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_required($mform) {
        global $USER;
        if ($this->is_required() && ($this->userid == $USER->id)) {
            $mform->addRule($this->inputname, get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * HardFreeze the field if locked.
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param   mixed    $data
     * @param   stdClass $datarecord The object that will be used to save the record
     * @return  mixed
     */
    function edit_save_data_preprocess($data, $datarecord) {
        return $data;
    }

    /**
     * Loads a user object with data for this field ready for the edit profile
     * form
     * @param   object   a user object
     */
    function edit_load_user_data($user) {
        if ($this->data !== NULL) {
            $user->{$this->inputname} = $this->data;
        }
    }

    /**
     * Check if the field data should be loaded into the user object
     * By default it is, but for field types where the data may be potentially
     * large, the child class should override this and return false
     * @return boolean
     */
    function is_user_object_data() {
        return true;
    }


/***** The following methods generally should not be overwritten by child classes *****/

    /**
     * Accessor method: set the userid for this instance
     * @param   integer   id from the user table
     */
    function set_userid($userid) {
        $this->userid = $userid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @param   integer   id from the user_info_field table
     */
    function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Accessor method: Load the field record and user data associated with the
     * object's fieldid and userid
     */
    function load_data() {
        /// Load the field object
        if (($this->fieldid == 0) or (!($field = profile_field_base::get_field($this->fieldid)))) {
            $this->field = NULL;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'profile_field_'.$field->shortname;
        }

        if (!empty($this->field)) {
            if ($data = profile_field_base::get_user_field($this->fieldid, $this->userid)) {
                $this->data = $data->data;
                $this->dataformat = $data->dataformat;
            } else {
                $this->data = $this->field->defaultdata;
                $this->dataformat = FORMAT_HTML;
            }
        } else {
            $this->data = NULL;
        }
    }

    /**
     * Check if the field data is visible to the current user
     * @return  boolean
     */
    function is_visible() {
        global $USER;

        switch ($this->field->visible) {
            case PROFILE_VISIBLE_ALL:
                return true;
            case PROFILE_VISIBLE_PRIVATE:
                if ($this->userid == $USER->id) {
                    return true;
                } else {
                    return has_capability('moodle/user:viewalldetails',
                            context_user::instance($this->userid));
                }
            default:
                return has_capability('moodle/user:viewalldetails',
                        context_user::instance($this->userid));
        }
    }

    /**
     * Check if the field data is considered empty
     * return boolean
     */
    function is_empty() {
        return ( ($this->data != '0') and empty($this->data));
    }

    /**
     * Check if the field is required on the edit profile page
     * @return   boolean
     */
    function is_required() {
        return (boolean)$this->field->required;
    }

    /**
     * Check if the field is locked on the edit profile page
     * @return   boolean
     */
    function is_locked() {
        return (boolean)$this->field->locked;
    }

    /**
     * Check if the field data should be unique
     * @return   boolean
     */
    function is_unique() {
        return (boolean)$this->field->forceunique;
    }

    /**
     * Check if the field should appear on the signup page
     * @return   boolean
     */
    function is_signup_field() {
        return (boolean)$this->field->signup;
    }

    /**
     * Fetch the record for a given field.
     *
     * @param   int $fieldid   ID from the user_info_field table.
     * @return  field record or null if field with the given id does not exist.
     */
    public static function get_field($fieldid=0) {
        if (!$fieldid) {
            return null;
        }

        if (!isset(self::$fields)) {
            self::get_fields_list();
        }

        return isset(self::$fields[$fieldid]) ? self::$fields[$fieldid] : null;
    }

    /**
     * Update or insert the given field record.
     *
     * @param   object  $field  Field record to insert or update.
     * @return  bool true is success, false otherwise.
     */
    public static function set_field($field) {
        global $DB;
        if (is_array($field)) {
            $field = (object) $field;
        } else if (!$field) {
            return false;
        }
        if (empty($field->id)) {
            $id = $DB->insert_record('user_info_field', $field);
        } else {
            $DB->update_record('user_info_field', $field);
            $id = $field->id;
        }
        if (!isset(self::$fields)) {
            self::get_fields_list();
        } else {
            self::$fields[$id] = $field;
        }
        return $id;
    }

    /**
     * Delete the fields with given fieldids.
     *
     * @param array $fieldids  Field ids to delete.
     */
    public static function delete_fields($fieldids = null) {
        global $DB;
        if (empty($fieldids) || !is_array($fieldids)) {
            $fieldids = array_keys(self::get_fields_list());
        }
        if (count($fieldids) >= count(self::get_fields_list())) {
            $DB->delete_records('user_info_field');
            self::$fields = array();
        } else {
            $DB->delete_records_list('user_info_field', 'id', $fieldids);
            self::$fields = array_diff_key(self::$fields, array_fill_keys($fieldids, true));
        }
    }

    /**
     * Get all the available profile field records.
     *
     * @return  array   Array of all user_info_field records.
     */
    public static function get_fields_list() {
        global $DB;
        if (!isset(self::$fields)) {
            if (!(self::$fields = $DB->get_records('user_info_field'))) {
                self::$fields = array();
            }
        }
        return self::$fields;
    }

    /**
     * Preload into a static cache the field data for the given list of users.
     * A restricted list of fieldids to fetch for may optionally be specified
     * otherwise data for all fields will be retrieved.
     *
     * @param   array   $userids     Array of user IDs to prefetch field data for.
     * @param   array   $fieldids    Array of field IDs to prefetch data for.
     */
    public static function preload_data($userids, $fieldids=null) {
        global $DB;
        if (empty($fieldids)) {
            $fieldids = array_keys(self::get_fields_list());
        }
        if (!isset(self::$preload)) {
            self::$preload = array();
        }
        if (empty($fieldids)) {
            return;
        }

        list($where1, $params1) = $DB->get_in_or_equal($fieldids);

        foreach (array_chunk($userids, 500) as $userchunk) {
            list($where2, $params2) = $DB->get_in_or_equal($userchunk);

            $where = 'fieldid '.$where1.' AND userid '.$where2;
            $params = array_merge($params1, $params2);
            $fields = 'fieldid, userid, data, dataformat';
            $records = $DB->get_recordset_select('user_info_data', $where, $params, '', $fields);

            foreach ($records as $record) {
                $key = $record->fieldid.'_'.$record->userid;
                self::$preload[$key] = (object) $record;
            }
        }
    }

    /**
     * Clear all preloaded field data for users.
     */
    public static function preload_clear() {
        self::$preload = array();
    }

    /**
     * Get the field data for a single user/field, optionally filling the cache too.
     * This function will check the preloaded data first, then the DB on a miss.
     *
     * @param   int $fieldid id of the field to fetch data for.
     * @param   int $userid  id of the user to fetch field data for.
     * @param   bool $cache   store the retrieved value in cache.
     */
    public static function get_user_field($fieldid, $userid, $cache=false) {
        global $DB;
        $key = $fieldid.'_'.$userid;
        if (!isset(self::$preload[$key])) {
            $data = $DB->get_record('user_info_data', array('userid'=>$userid, 'fieldid'=>$fieldid), 'data, dataformat');
            if (!$cache) {
                return $data;
            }
            self::$preload[$key] = $data;
        }
        return self::$preload[$key];
    }

} /// End of class definition


/***** General purpose functions for customisable user profiles *****/

function profile_load_data($user) {
    global $CFG, $DB;

    if ($fields = profile_field_base::get_fields_list()) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $user->id);
            $formfield->edit_load_user_data($user);
        }
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 * @param  object   instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function profile_definition($mform, $userid = 0) {
    global $CFG, $DB;

    // if user is "admin" fields are displayed regardless
    $update = has_capability('moodle/user:update', context_system::instance());

    if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid'=>$category->id), 'sortorder ASC')) {

                // check first if *any* fields will be displayed
                $display = false;
                foreach ($fields as $field) {
                    if ($field->visible != PROFILE_VISIBLE_NONE) {
                        $display = true;
                    }
                }

                // display the header and the fields
                if ($display or $update) {
                    $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                        $newfield = 'profile_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        $formfield->edit_field($mform);
                    }
                }
            }
        }
    }
}

function profile_definition_after_data($mform, $userid) {
    global $CFG, $DB;

    $userid = ($userid < 0) ? 0 : (int)$userid;

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $userid);
            $formfield->edit_after_data($mform);
        }
    }
}

function profile_validation($usernew, $files) {
    global $CFG, $DB;

    $err = array();
    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $err += $formfield->edit_validate_field($usernew, $files);
        }
    }
    return $err;
}

function profile_save_data($usernew) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $formfield->edit_save_data($usernew);
        }
    }
}

function profile_display_fields($userid) {
    global $CFG, $USER, $DB;

    if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid'=>$category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, $userid);
                    if ($formfield->is_visible() and !$formfield->is_empty()) {
                        print_row(format_string($formfield->field->name.':'), $formfield->display_data());
                    }
                }
            }
        }
    }
}

/**
 * Adds code snippet to a moodle form object for custom profile fields that
 * should appear on the signup page
 * @param  object  moodle form object
 */
function profile_signup_fields($mform) {
    global $CFG, $DB;

     //only retrieve required custom fields (with category information)
    //results are sort by categories, then by fields
    $sql = "SELECT uf.id as fieldid, ic.id as categoryid, ic.name as categoryname, uf.datatype
                FROM {user_info_field} uf
                JOIN {user_info_category} ic
                ON uf.categoryid = ic.id AND uf.signup = 1 AND uf.visible<>0
                ORDER BY ic.sortorder ASC, uf.sortorder ASC";

    if ( $fields = $DB->get_records_sql($sql)) {
        foreach ($fields as $field) {
            //check if we change the categories
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            }
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->fieldid);
            $formfield->edit_field($mform);
        }
    }
}

/**
 * Returns an object with the custom profile fields set for the given user
 * @param  integer  userid
 * @return  object
 */
function profile_user_record($userid) {
    global $CFG, $DB;

    $usercustomfields = new stdClass();

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $userid);
            if ($formfield->is_user_object_data()) {
                $usercustomfields->{$field->shortname} = $formfield->data;
            }
        }
    }

    return $usercustomfields;
}

/**
 * Load custom profile fields into user object
 *
 * Please note originally in 1.9 we were using the custom field names directly,
 * but it was causing unexpected collisions when adding new fields to user table,
 * so instead we now use 'profile_' prefix.
 *
 * @param object $user user object
 * @return void $user object is modified
 */
function profile_load_custom_fields($user) {
    $user->profile = (array)profile_user_record($user->id);
}


