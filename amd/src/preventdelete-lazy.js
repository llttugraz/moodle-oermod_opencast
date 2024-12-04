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
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Templates from "core/templates";

/**
 * Replace the edit and delete icon of already releaased OER videos with an information.
 *
 * @param {{series: id, videos: {videoid, title}}}released
 * @returns {Promise<void>}
 */
export const init = async (released) => {
    const series = released.series;
    const videos = released.videos;

    for (const key in series) {
        let table = document.getElementById('opencast-videos-table-' + series[key]);
        for (let i = 0; i < table.tBodies[0].rows.length; i++) {
            let cell = document.getElementById('opencast-videos-table-' + series[key] + '_r' + i + '_c6');
            let visibility = document.getElementById('opencast-videos-table-' + series[key] + '_r' + i + '_c5');
            let content = cell.innerHTML;
            for (const key in videos) {
                if (content.includes(videos[key].videoid)) {
                    visibility.innerHTML = '-';
                    const {html,} = await Templates.renderForPromise('oermod_opencast/info', {});
                    cell.innerHTML = html;
                }
            }
        }
    }
};