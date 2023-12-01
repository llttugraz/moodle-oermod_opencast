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

namespace oermod_opencast;

use local_oer\identifier;
use local_oer\logger;
use local_oer\modules\elements;
use local_oer\modules\element;
use tool_opencast\local\api;
use tool_opencast\local\settings_api;

/**
 * Class module
 *
 * Implements the interface required to be used in local_oer plugin.
 */
class module implements \local_oer\modules\module {
    /**
     * Load all files from a given course.
     *
     * @param int $courseid Moodle courseid
     * @return elements
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function load_elements(int $courseid): \local_oer\modules\elements {
        // TODO: Implement behaviour for multiple instances.
        $settings = settings_api::get_default_ocinstance();
        $videos = $this->load_videos($courseid, $settings->id);
        $elements = new elements();
        if (empty($videos)) {
            return $elements;
        }

        $instance = settings_api::get_apiurl($settings->id);
        $creator = "oermod_opencast\module";
        foreach ($videos as $video) {
            if ($video->processing_state != 'SUCCEEDED') {
                // Only show working videos.
                // TODO check possible states.
                continue;
            }
            $element = new element($creator, element::OERTYPE_EXTERNAL);
            $identifier = identifier::compose('opencast', $instance,
                    'video', 'identifier', $video->identifier);
            $element->set_identifier($identifier);
            $element->set_origin('opencast', 'origin', 'oermod_opencast');
            $element->set_title($video->title);
            // TODO: license mapping will be necessary
            $element->set_license(empty($video->license) ? 'unknown' : $video->license);
            // TODO: What other positions are possible in this array? Is the video url always in key 0?
            $element->set_source($video->publications[0]->url);
            $element->add_information('origin', 'local_oer',
                    get_string('url', 'moodle'),
                    $element->get_source());
            $elements->add_element($element);
        }
        return $elements;
    }

    /**
     * Use the opencast API to load all videos for a Moodle course.
     *
     * @param int $courseid Moodle course id.
     * @param int $instanceid Opencast instance id stored in tool_opencast settings.
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function load_videos(int $courseid, int $instanceid): array {
        // A course can have more than one series.
        global $DB;
        $list = $DB->get_records('tool_opencast_series', ['courseid' => $courseid]);
        $videos = [];
        foreach ($list as $series) {
            $params = [
                    'sign' => false,
                    'withacl' => false,
                    'withmetadata' => false,
                    'withpublications' => true,
                    'sort' => [
                            'start_date' => 'DESC',
                    ],
            ];

            $api = new api($instanceid);
            $response = $api->opencastapi->eventsApi->getBySeries($series->series, $params);
            $code = $response['code'];

            if ($code != 200) {
                logger::add($courseid, logger::LOGERROR,
                        "OERmod opencast: could not reach opencast server. Status Code:$code", 'oermod_opencast');
                continue;
            }
            if (empty($response['body'])) {
                continue;
            }
            $videos = array_merge($videos, $response['body']);
        }
        return $videos;
    }

    /**
     * Fields that can be written back from local_oer to the source.
     *
     * @return array[]
     */
    public function writable_fields(): array {
        return [
                ['license', 'moodle'],
        ];
    }

    /**
     * Write back the fields that are allowed to overwrite in the source.
     *
     * @param element $element
     * @return void
     */
    public function write_to_source(\local_oer\modules\element $element): void {
    }
}
