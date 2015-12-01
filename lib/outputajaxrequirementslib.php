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
 * Library functions to facilitate the use of JavaScript in Moodle.
 *
 * Note: you can find history of this file in lib/ajax/ajaxlib.php
 *
 * @copyright 2009 Tim Hunt, 2010 Petr Skoda
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core
 * @category output
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class tracks all the things that are needed by the current page.
 *
 * Normally, the only instance of this  class you will need to work with is the
 * one accessible via $PAGE->requires.
 *
 * Typical usage would be
 * <pre>
 *     $PAGE->requires->js_call_amd('mod_forum/view', 'init');
 * </pre>
 *
 * It also supports obsoleted coding style with/without YUI3 modules.
 * <pre>
 *     $PAGE->requires->js_init_call('M.mod_forum.init_view');
 *     $PAGE->requires->css('/mod/mymod/userstyles.php?id='.$id); // not overridable via themes!
 *     $PAGE->requires->js('/mod/mymod/script.js');
 *     $PAGE->requires->js('/mod/mymod/small_but_urgent.js', true);
 *     $PAGE->requires->js_function_call('init_mymod', array($data), true);
 * </pre>
 *
 * There are some natural restrictions on some methods. For example, {@link css()}
 * can only be called before the <head> tag is output. See the comments on the
 * individual methods for details.
 *
 * @copyright 2009 Tim Hunt, 2010 Petr Skoda
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @package core
 * @category output
 */
class ajax_requirements_manager extends page_requirements_manager {

    /**
     * Generate any HTML that needs to go at the start of the <body> tag.
     *
     * Normally, this method is called automatically by the code that prints the
     * <head> tag. You should not normally need to call it in your own code.
     *
     * @return string the HTML code to go at the start of the <body> tag.
     */
    public function get_top_of_body_code() {

        $output = '';
        // First the skip links.
        // $links = '';
        // $attributes = array('class' => 'skip');
        // foreach ($this->skiplinks as $url => $text) {
        //     $links .= html_writer::link('#'.$url, $text, $attributes);
        // }
        // $output = html_writer::tag('div', $links, array('class'=>'skiplinks')) . "\n";
        // $this->js_init_call('M.util.init_skiplink');

        // YUI3 JS needs to be loaded early in the body. It should be cached well by the browser.
        $output .= $this->get_yui3lib_headcode();

        // Add hacked jQuery support, it is not intended for standard Moodle distribution!
        $output .= $this->get_jquery_headcode();

        // Link our main JS file, all core stuff should be there.
        $output .= html_writer::script('', $this->js_fix_url('/lib/javascript-static.js'));

        // All the other linked things from HEAD - there should be as few as possible.
        // if ($this->jsincludes['head']) {
        //     foreach ($this->jsincludes['head'] as $url) {
        //         $output .= html_writer::script('', $url);
        //     }
        // }

        // Then the clever trick for hiding of things not needed when JS works.
        $output .= html_writer::script("document.body.className += ' jsenabled';") . "\n";
        $this->topofbodydone = true;
        return $output;
    }



     /**
     * Generate any HTML that needs to go at the end of the page.
     *
     * Normally, this method is called automatically by the code that prints the
     * page footer. You should not normally need to call it in your own code.
     *
     * @return string the HTML code to to at the end of the page.
     */
    public function get_end_code() {
        global $CFG;

        $output = '';



        // Call amd init functions.
        $output .= $this->get_amd_footercode();

        // Add other requested modules.
        $output .= $this->get_extra_modules_code();

        // $this->js_init_code('M.util.js_complete("init");', true);

        // All the other linked scripts - there should be as few as possible.
        if ($this->jsincludes['footer']) {
            foreach ($this->jsincludes['footer'] as $url) {
                $output .= html_writer::script('', $url);
            }
        }

        // Add all needed strings.
        // First add core strings required for some dialogues.
        // $this->strings_for_js(array(
        //     'confirm',
        //     'yes',
        //     'no',
        //     'areyousure',
        //     'closebuttontitle',
        //     'unknownerror',
        // ), 'moodle');
        if (!empty($this->stringsforjs)) {
            $strings = array();
            foreach ($this->stringsforjs as $component=>$v) {
                foreach($v as $indentifier => $langstring) {
                    $strings[$component][$indentifier] = $langstring->out();
                }
            }
            $output .= html_writer::script(js_writer::set_variable('M.str', $strings));
        }

        // Add variables.
        if ($this->jsinitvariables['footer']) {
            $js = '';
            foreach ($this->jsinitvariables['footer'] as $data) {
                list($var, $value) = $data;
                $js .= js_writer::set_variable($var, $value, true);
            }
            $output .= html_writer::script($js);
        }

        $inyuijs = $this->get_javascript_code(false);
        $ondomreadyjs = $this->get_javascript_code(true);
        // See if this is still needed when we get to the ajax page.
        $jsinit = $this->get_javascript_init_code();
        $handlersjs = $this->get_event_handler_code();

        // There is a global Y, make sure it is available in your scope.
        $js = "(function() {{$inyuijs}{$ondomreadyjs}{$jsinit}{$handlersjs}})();";

        $output .= html_writer::script($js);

        return $output;

    }

}