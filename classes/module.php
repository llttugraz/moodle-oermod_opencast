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
use local_oer\modules\person;
use tool_opencast\local\api;
use tool_opencast\local\settings_api;

/**
 * Class module
 *
 * Implements the interface required to be used in local_oer plugin.
 */
class module implements \local_oer\modules\module {
    /**
     * Supported roles from opencast.
     */
    const ROLES = [
        // Creator, not interesting for OER.
            'Presenter',
            'Contributor',
            'Rightsholder',
    ];

    /**
     * Load all files from a given course.
     *
     * @param int $courseid Moodle courseid
     * @return elements
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function load_elements(int $courseid): \local_oer\modules\elements {
        // MDL-0 TODO: Implement behaviour for multiple instances.
        // This will also affect the write back function.
        $settings = settings_api::get_default_ocinstance();
        $videos = $this->load_videos($courseid, $settings->id);
        $elements = new elements();
        if (empty($videos)) {
            return $elements;
        }
        $addpeople = get_config('oermod_opencast', 'addpeopleandroles');

        $instance = settings_api::get_apiurl($settings->id);
        $creator = "oermod_opencast\module";
        foreach ($videos as $video) {
            if ($video->processing_state != 'SUCCEEDED' || empty($video->publications)) {
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
            if ($addpeople) {
                foreach ($video->presenter as $presenter) {
                    $pres = new person();
                    $pres->set_role(self::ROLES[1]);
                    $pres->set_fullname($presenter);
                    $element->add_person($pres);
                }
                foreach ($video->contributor as $contributor) {
                    $contrib = new person();
                    $contrib->set_role(self::ROLES[2]);
                    $contrib->set_fullname($contributor);
                    $element->add_person($contrib);
                }
                if (!empty($video->rightsholder)) {
                    $rolerightsholder = new person();
                    $rolerightsholder->set_role(self::ROLES[3]);
                    $rolerightsholder->set_fullname($video->rightsholder);
                    $element->add_person($rolerightsholder);
                }
            }

            $element->set_source($video->publications[0]->url);
            if (!empty($video->series)) {
                $element->add_information('series', 'oermod_opencast', $video->series, null, '');
            }
            $element->add_information('origin', 'local_oer',
                    get_string('url', 'moodle'), null, '',
                    $element->get_source());

            if (isset($video->publications[0]->media)) {
                $durations = [];
                foreach ($video->publications[0]->media as $media) {
                    $durations[$media->duration] = isset($durations[$media->duration]) ? $durations[$media->duration]++ : 0;
                }
                $milliseconds = array_keys($durations, max($durations));
                $milliseconds = reset($milliseconds);
                $duration = $milliseconds / 1000;
                $minutes = floor($duration / 60);
                $seconds = $duration % 60;
                $result = $minutes > 0 ? $minutes . 'min' : '';
                $result .= $minutes > 0 && $seconds > 0 ? ' ' : '';
                $result .= $seconds > 0 ? $seconds . 's' : '';
                $element->add_information('duration', 'oermod_opencast', $result, 'duration', $milliseconds);
            }
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
        $success = $this->republish_metadata($api, $decompose->value, $response['code']);
        if (!$success) {
            global $DB;
            $courseid = $DB->get_field('local_oer_elements', 'courseid', ['identifier' => $element->get_identifier()]);
            logger::add($courseid, logger::LOGERROR,
                    'Workflow could not be started, so license not visible: ' . $element->get_identifier(),
                    'oermod_opencast');
        }
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
     * Return supported roles.
     *
     * @return array[]
     */
    public function supported_roles(): array {
        return [
                [self::ROLES[2], 'rightsholder', 'oermod_opencast', self::ROLE_REQUIRED],
                [self::ROLES[0], 'presenter', 'oermod_opencast'],
                [self::ROLES[1], 'contributor', 'oermod_opencast'],
        ];
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
     * @param string $source The source can be either 'moodle' or 'opencast'.
     * @param string $licence Licence shortname string.
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
     * When an opencast video is released, the video has to be set to be publicly accessible.
     * Also, the video should not be deletable for lecturers.
     *
     * @param element $element
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function set_element_to_release(\local_oer\modules\element $element): bool {
        $decompose = identifier::decompose($element->get_identifier());
        $settings = settings_api::get_default_ocinstance();
        $api = new api($settings->id);
        $response = $api->opencastapi->eventsApi->getAcl($decompose->value);
        if (empty($response) || $response['code'] != 200 || $response['reason'] != 'OK') {
            // Webservice call did not succeed.
            // MDL-0 TODO: maybe this should be retried later? Add an adhoc task for this?
            global $DB;
            $courseid = $DB->get_field('local_oer_snapshot', 'courseid', ['identifier' => $element->get_identifier()]);
            logger::add($courseid, logger::LOGERROR,
                    'Could not set element to release: ' . $element->get_identifier(),
                    'oermod_opencast');
            return false;
        }
        $success = false;
        $anonymousrole = "ROLE_ANONYMOUS";
        $update = false;
        $found = false;

        // All entries have to be returned, else they will be deleted.
        // Test if anonymous is already in the list, if true, test allow and action.
        // If false, add it to the list.
        $aclsettings = $response['body'];
        foreach ($aclsettings as $key => $role) {
            switch ($role->role) {
                case $anonymousrole:
                    if ($role->action == 'write') {
                        // Why does the anonymous role has write access? Remove it.
                        unset($aclsettings[$key]);
                        $update = true;
                    } else {
                        $found = true;
                        $success = true; // Nothing to do then.
                    }
                    break;
                default:
                    // We are only interested in the anonymous role.
            }
        }

        // If the setting has not been found, add it and trigger update.
        if (!$found) {
            $update = true;
            $acl = new \stdClass();
            $acl->allow = true;
            $acl->role = $anonymousrole;
            $acl->action = "read";
            $aclsettings[] = $acl;
        }

        if ($update && !$success) {
            $response = $api->opencastapi->eventsApi->updateAcl($decompose->value, $aclsettings);
            $success = $this->republish_metadata($api, $decompose->value, $response['code']);

        }
        return $success;
    }

    /**
     * After something has been written back to opencast, the video has to run a workflow so that the changes are visible.
     *
     * @param api $api tool_opencast api
     * @param string $videoid Opencast video id
     * @param int $code Http response code
     * @return bool
     */
    private function republish_metadata(api $api, string $videoid, int $code) {
        if ($code == 204) {
            // Workflow to republish metadata needs to be triggered.
            $workflow = $api->opencastapi->workflowsApi->run(
                    $videoid,
                    'republish-metadata',
                    [],
                    false,
                    false
            );
            if ($workflow) {
                return true;
            }
        }
        return false;

    }
}
