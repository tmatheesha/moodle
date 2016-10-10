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
 * Contains the test class testing the \core\php_utils static helper class functions.
 *
 * @package    core
 * @copyright  2016 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This tests the static helper functions contained in the class '\core\ip_utils'.
 *
 * @package    core
 * @copyright  2016 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_ip_utils_testcase extends advanced_testcase {
    /**
     * Test for \core\ip_utils::is_domain_name().
     *
     * @param string $domainname the domain name to validate.
     * @param bool $expected the expected result.
     * @dataProvider domain_name_data_provider
     */
    public function test_is_domain_name($domainname, $expected) {
        $this->assertEquals($expected, \core\ip_utils::is_domain_name($domainname));
    }

    /**
     * Data provider for test_is_domain_name().
     *
     * @return array
     */
    public function domain_name_data_provider() {
        return [
            ["com", true],
            ["example.com", true],
            ["sub.example.com", true],
            ["sub-domain.example-domain.net", true],
            ["123.com", true],
            ["123.a11", true],
            [str_repeat('sub.', 60) . "1-example.com", true], // Max number of domain name chars = 253.
            [str_repeat('example', 9) . ".com", true], // Max number of domain name label chars = 63.
            ["localhost", true],
            [" example.com", false],
            ["example.com ", false],
            ["example.com/", false],
            ["*.example.com", false],
            ["*example.com", false],
            ["example.123", false],
            ["-example.com", false],
            ["example-.com", false],
            [".example.com", false],
            ["127.0.0.1", false],
            [str_repeat('sub.', 60) . "11-example.com", false], // Name length is 254 chars, which exceeds the max allowed.
            [str_repeat('example', 9) . "1.com", false], // Label length is 64 chars, which exceed the max allowed.
        ];
    }

    /**
     * Test for \core\ip_utils::is_wildcard_domain_name().
     *
     * @param string $wildcard the wildcard domain name to validate.
     * @param bool $expected the expected result.
     * @dataProvider wildcard_domain_name_data_provider
     */
    public function test_is_wildcard_domain_name($wildcard, $expected) {
        $this->assertEquals($expected, \core\ip_utils::is_wildcard_domain_name($wildcard));
    }

    /**
     * Data provider for test_is_wildcard_domain_name().
     *
     * @return array
     */
    public function wildcard_domain_name_data_provider() {
        return [
            ["*.com", true],
            ["*.example.com", true],
            ["*.example.com", true],
            ["*.sub.example.com", true],
            ["*.sub-domain.example-domain.com", true],
            ["*." . str_repeat('sub.', 60) . "example.com", true], // Max number of domain name chars = 253.
            ["*." . str_repeat('example', 9) . ".com", true], // Max number of domain name label chars = 63.
            ["*com", false],
            ["*example.com", false],
            [" *.example.com", false],
            ["*.example.com ", false],
            ["*-example.com", false],
            ["*.-example.com", false],
            ["*.example.com/", false],
            ["sub.*.example.com", false],
            ["sub.*example.com", false],
            ["*.*.example.com", false],
            ["example.com", false],
            ["*." . str_repeat('sub.', 60) . "1example.com", false], // Name length is 254 chars, which exceeds the max allowed.
            ["*." . str_repeat('example', 9) . "1.com", false], // Label length is 64 chars, which exceed the max allowed.
        ];
    }

    /**
     * Test checking domains against a list of allowed domains.
     *
     * @param  bool $expected Expected result
     * @param  string $domain domain address
     * @dataProvider data_domain_addresses
     */
    public function test_check_domain_against_allowed_domains($expected, $domain) {
        $alloweddomains = ['example.com',
                           '*.moodle.com',
                           '*.per.this.penny-arcade.com',
                           'bad.*.url.com',
                           ' trouble.com.au'];
        $this->assertEquals($expected, \core\ip_utils::is_domain_in_allowed_list($domain, $alloweddomains));
    }

    /**
     * Data provider for test_check_domain_against_allowed_domains.
     *
     * @return array
     */
    public function data_domain_addresses() {
        return [
            [true, 'example.com'],
            [false, 'sub.example.com'],
            [false, 'example.com.au'],
            [false, ' example.com'], // A space at the front of the domain is invalid.
            [false, 'example.123'], // Numbers at the end is invalid.
            [false, 'test.example.com'],
            [false, 'moodle.com'],
            [true, 'test.moodle.com'],
            [false, 'test.moodle.com.au'],
            [true, 'nice.address.per.this.penny-arcade.com'],
            [false, 'normal.per.this.penny-arcade.com.au'],
            [false, 'bad.thing.url.com'], // The allowed domain (above) has a bad wildcard and so this address will return false.
            [false, 'trouble.com.au'] // The allowed domain (above) has a space at the front and so will return false.
        ];
    }
}
