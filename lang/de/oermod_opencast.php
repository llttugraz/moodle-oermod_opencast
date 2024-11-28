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

$string['addpeoplesetting'] = 'Personen zum OER-Element hinzufügen';
$string['addpeoplesetting_description'] = 'Fügen Sie die Personen und ihre Rollen automatisch zum OER-Element hinzu, ' .
        'wenn Sie die Videos aus opencast laden. People will only be added when the element metadata has not been edited ' .
        'and stored. More people can be added manually and also the automatically added people can be removed.' .
        'Die folgenden Rollen werden hinzugefügt: Präsentator:in, Beitragende:r, Rechteinhaber:in.';
$string['contributor'] = 'Beitragende:r';
$string['creator'] = 'Ersteller:in';
$string['duration'] = 'Dauer';
$string['origin'] = 'Opencast';
$string['pluginname'] = 'OER Subplugin zum Laden von Opencast Videos';
$string['presenter'] = 'Präsentator:in';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine Daten';
$string['releasedvideo'] = 'Dieses Video wurde als Open Educational Resource (OER) veröffentlicht.<br>' .
        'Auf dieser Oberfläche ist es nicht mehr möglich die Metadaten des Videos zu editieren, oder das Video zu löschen.<br>' .
        'Um das Video zu löschen, wenden Sie sich bitte an den Support.';
$string['rightsholder'] = 'Rechteinhaber:in';
$string['rolestoremovewrite'] = 'Rollen, von denen Schreibrechte entfernt werden';
$string['rolestoremovewrite_description'] = 'Opencast-Rollen, bei denen die Schreibrechte ' .
        'nach Freigabe eines OER-Objekts entfernt werden. Dadurch wird verhindert, ' .
        'dass das Video in Opencast verändert oder gelöscht wird. Eine Rolle pro Zeile. ' .
        'Der Platzhalter {{courseid}} kann verwendet werden.' .
        '<p><strong>Wichtig:</strong>Damit dies funktioniert, müssen Opencast-Workflows auf ' .
        'Rollen beschränkt werden, und der Opencast-Admin-Benutzer, der in tool_opencast festgelegt ist, ' .
        'darf keinen Schreibzugriff auf die Standard-Admin-Rolle in Opencast haben (Standardname: ROLE_ADMIN)</p>';
$string['series'] = 'Serie';
