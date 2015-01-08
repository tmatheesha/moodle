<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999-onwards Moodle Pty Ltd  http://moodle.com          //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

class data_field_text extends data_field_base {

    var $type = 'text';

    function display_search_field($value = '') {
        return '<label class="accesshide" for="f_' . $this->field->id . '">'. $this->field->name.'</label>' . '<input type="text" size="16" id="f_'.$this->field->id.'" name="f_'.$this->field->id.'" value="'.$value.'" />';
    }

    function parse_search_field() {
        return optional_param('f_'.$this->field->id, '', PARAM_NOTAGS);
    }

    /**
     * Print the relevant form element in the ADD template for this field
     *
     * @global object
     * @param int $recordid
     * @return string
     */
    function display_add_field($recordid=0){
        global $DB;

        if ($recordid){
            $content = $DB->get_field('data_content', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid));
        } else {
            $content = '';
        }

        // beware get_field returns false for new, empty records MDL-18567
        if ($content===false) {
            $content='';
        }

        $str = '<div title="'.s($this->field->description).'">';
        $str .= '<label class="accesshide" for="field_'.$this->field->id.'">'.$this->field->description.'</label>';
        // Param 2 is the field for the default value of the text box.
        if ($content == '') {
            $str .= '<input class="basefieldinput" type="text" name="field_'.$this->field->id.'" id="field_'.$this->field->id.'" value="'.s($this->field->param2).'" />';
        } else {
            $str .= '<input class="basefieldinput" type="text" name="field_'.$this->field->id.'" id="field_'.$this->field->id.'" value="'.s($content).'" />';
        }
        $str .= '</div>';

        return $str;
    }

    function generate_sql($tablealias, $value) {
        global $DB;

        static $i=0;
        $i++;
        $name = "df_text_$i";
        return array(" ({$tablealias}.fieldid = {$this->field->id} AND ".$DB->sql_like("{$tablealias}.content", ":$name", false).") ", array($name=>"%$value%"));
    }

}


