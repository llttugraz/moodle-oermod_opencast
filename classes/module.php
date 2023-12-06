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

use block_opencast\local\apibridge;
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
        // This will also affect the write back function.
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
                // Possible states: INSTANTIATED, RUNNING, PAUSED, SUCCEEDED, FAILED, SKIPPED, RETRY.
                continue;
            }
            $element = new element($creator, element::OERTYPE_EXTERNAL);
            $identifier = identifier::compose('opencast', $instance,
                    'video', 'identifier', $video->identifier);
            $element->set_identifier($identifier);
            $element->set_origin('opencast', 'origin', 'oermod_opencast');
            $element->set_title($video->title);
            $license = $this->match_licence('opencast', $video->license);
            $element->set_license($license);
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
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function write_to_source(\local_oer\modules\element $element): void {
        $decompose = identifier::decompose($element->get_identifier());
        if ($decompose->platform != 'opencast' || $decompose->type != 'video' || $decompose->valuetype != 'identifier') {
            return;
        }
        $moodlelicence = $element->get_license();
        if ($moodlelicence == 'unknown') {
            return; // Do not update unknown licence.
        }
        $settings = settings_api::get_default_ocinstance();
        $api = new api($settings->id);
        $licence = $this->match_licence('moodle', $moodlelicence);
        $update = [
                'id' => 'license',
                'value' => $licence,
        ];
        $metadata = json_encode([$update]);
        $type = 'dublincore/episode';

        $response = $api->opencastapi->eventsApi->updateMetadata($decompose->value, $type, $metadata);
    }

    /**
     * Match Opencast licences with Moodle active licences and return the result.
     *
     * @return array
     */
    public function supported_licences(): array {
        $licences = \license_manager::get_active_licenses_as_array();
        $result = [];
        foreach ($this->licence_mapping() as $moodle => $opencast) {
            if (isset($licences[$moodle])) {
                $result[] = $moodle;
            }
        }
        return $result;
    }

    /**
     * Map the Moodle licences to its Opencast counterpart.
     *
     * The shortnames of the licenses will be matched, not the visible names.
     * Only the base licences are matched here.
     *
     * TODO: should this be more dynamic for custom licences?
     * TODO: what happens if some licences are deactivated in opencast?
     *
     * @return array
     */
    private function licence_mapping(): array {
        return [
                'unknown' => '', // Empty string in Opencast, also all other licenses not matchable.
                'allrightsreserved' => 'ALLRIGHTS',
                'public' => 'CC0',
                'cc-4.0' => 'CC-BY',
                'cc-nc-4.0' => 'CC-BY-NC',
                'cc-nd-4.0' => 'CC-BY-ND',
                'cc-nc-nd-4.0' => 'CC-BY-NC-ND',
                'cc-nc-sa-4.0' => 'CC-BY-NC-SA',
                'cc-sa-4.0' => 'CC-BY-SA',
        ];
    }

    /**
     * Match a given licence to its counterpart.
     *
     * @param string $source Source can be either 'moodle' or 'opencast'.
     * @param string $licence License shortname string.
     * @return string
     * @throws \coding_exception
     */
    private function match_licence(string $source, string $licence): string {
        if (!in_array($source, ['moodle', 'opencast'])) {
            throw new \coding_exception('Wrong source given, only "moodle" or "opencast" are allowed.');
        }

        foreach ($this->licence_mapping() as $moodle => $opencast) {
            if ($source == 'moodle' && $moodle == $licence) {
                return $opencast;
            }
            if ($source == 'opencast' && $opencast == $licence) {
                return $moodle;
            }
        }
        return $source == 'moodle' ? '' : 'unknown';
    }

    /**
     * When an opencast video is released, the video has to be set to be public accessible.
     * Also, the video should not be deletable for lecturers.
     *
     * @return bool
     */
    public function set_element_to_release(): bool {
        // TODO: implement release.
        return true;
    }
}
