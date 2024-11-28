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
 * OER subplugin for loading opencast videos
 *
 * @package    oermod_opencast
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addpeoplesetting'] = 'Add people to OER element';
$string['addpeoplesetting_description'] = 'Add the people and their roles automatically to the OER element ' .
        'when loading the videos from opencast. People will only be added when the element metadata has not been edited and ' .
        'stored. More people can be added manually and also the automatically added people can be removed. <br>' .
        'Following roles will be added:  Presenter, Contributor, Rightsholder.';
$string['contributor'] = 'Contributor';
$string['creator'] = 'Creator';
$string['duration'] = 'Duration';
$string['origin'] = 'Opencast';
$string['pluginname'] = 'OER subplugin for loading opencast videos';
$string['presenter'] = 'Presenter';
$string['privacy:metadata'] = 'This plugin does not store any personal data.';
$string['rightsholder'] = 'Rightsholder';
$string['rolestoremovewrite'] = 'Roles from which write access is removed';
$string['rolestoremovewrite_description'] = 'Opencast roles, where the write privileges will be ' .
        'removed after an OER object is released. ' .
        'This will prevent, that the video in Opencast is altered, or deleted. Enter one role per line. ' .
        'Placeholder {{courseid}} can be used.' .
        '<p><strong>Important:</strong>For this to work, Opencast workflows must be restricted to roles, ' .
        'and the Opencast admin user defined in tool_opencast must not have write access to the default ' .
        'admin role in Opencast (default name: ROLE_ADMIN)</p>';
$string['series'] = 'Series';
