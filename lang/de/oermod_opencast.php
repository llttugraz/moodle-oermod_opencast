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

$string['pluginname'] = 'OER Subplugin zum Laden von Opencast Videos';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine Daten';
$string['origin'] = 'Opencast';
$string['series'] = 'Serie';
$string['creator'] = 'Ersteller:in';
$string['presenter'] = 'Präsentator:in';
$string['contributor'] = 'Beitragende:r';
$string['rightsholder'] = 'Rechteinhaber:in';
$string['addpeoplesetting'] = 'Personen zum OER-Element hinzufügen';
$string['addpeoplesetting_description'] = 'Fügen Sie die Personen und ihre Rollen automatisch zum OER-Element hinzu, ' .
        'wenn Sie die Videos aus opencast laden. People will only be added when the element metadata has not been edited ' .
        'and stored. More people can be added manually and also the automatically added people can be removed.' .
        'Die folgenden Rollen werden hinzugefügt: Präsentator:in, Beitragende:r, Rechteinhaber:in.';
$string['duration'] = 'Dauer';
