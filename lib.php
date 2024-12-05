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
 * Open Educational Resources Plugin
 *
 * @package    oermod_opencast
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Inject javascript to the block_opencast overview.
 *
 * When Opencast Videos are released as OER they should not be deletable in the "normal" way.
 * So this JavaScript replaces the delete and edit button with information on how to delete a video.
 *
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 */
function oermod_opencast_before_footer() {
    \oermod_opencast\hook_callbacks::inject_javascript_to_block_opencast();
}
