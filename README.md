# moodle-oermod_opencast

Subplugin for [local_oer](https://github.com/llttugraz/moodle-local_oer) plugin

# Requirements

* Moodle 4.1+
* [local_oer](https://github.com/llttugraz/moodle-local_oer) v2.3.0+
* [tool_opencast](https://moodle.org/plugins/tool_opencast) v4.3-r1+
* [block_opencast](https://moodle.org/plugins/block_opencast) v4.3-r1+

# Content

This plugin loads video metadata from opencast to 
be released as OER resource (Open Educational Resources).
It uses block_opencast to get the series attached 
to Moodle courses and the API from tool_opencast to load the video metadata.

When a video is set to OER it also stores changes in metadata back to the video (license).

## On OER release

The released videos remain in Opencast and the link is provided in the release metadata.
The video will be set to public.

## TODOs / Known issues

* Deleting of videos cannot be prevented yet.
* Support for multiple OpenCast instances, connected to one Moodle, has to be added