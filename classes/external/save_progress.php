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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_videotrack\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_videotrack\local\progress_manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Save a server-validated playback heartbeat.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_progress extends external_api {
    /**
     * Describe parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID.'),
            'currenttime' => new external_value(PARAM_INT, 'Current playback time in seconds.'),
            'duration' => new external_value(PARAM_INT, 'Media duration in seconds.'),
            // Retained for a safe transition from cached clients; it is never trusted.
            'percent' => new external_value(PARAM_INT, 'Legacy client percentage.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Save a heartbeat.
     *
     * @param int $cmid Course module ID.
     * @param int $currenttime Current position.
     * @param int $duration Media duration.
     * @param int $percent Ignored legacy percentage.
     * @return array
     */
    public static function execute(int $cmid, int $currenttime, int $duration, int $percent = 0): array {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'currenttime' => $currenttime,
            'duration' => $duration,
            'percent' => $percent,
        ]);
        if ($params['currenttime'] < 0 || $params['duration'] < 0) {
            throw new \invalid_parameter_exception('Playback time and duration cannot be negative.');
        }

        $cm = get_coursemodule_from_id('videotrack', $params['cmid'], 0, false, MUST_EXIST);
        $videotrack = $DB->get_record('videotrack', ['id' => $cm->instance], 'id, targetpercent', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $course = get_course($cm->course);

        require_login($course, true, $cm);
        self::validate_context($context);
        require_capability('mod/videotrack:view', $context);

        $progress = progress_manager::save(
            (int)$videotrack->id,
            (int)$USER->id,
            (int)$params['currenttime'],
            (int)$params['duration'],
            (int)$videotrack->targetpercent
        );

        require_once($CFG->libdir . '/completionlib.php');
        $completion = new \completion_info($course);
        if ($completion->is_enabled($cm)) {
            if ((int)$progress->lastposition > 0) {
                $completion->set_module_viewed($cm, $USER->id);
            }
            if ((int)$videotrack->targetpercent > 0) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $USER->id);
            }
        }

        return [
            'success' => true,
            'percent' => (int)$progress->highestpercent,
            'completed' => !empty($progress->iscompleted),
            'resumetime' => (int)$progress->lastposition,
            'allowedtime' => (int)$progress->highesttime,
        ];
    }

    /**
     * Describe result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the heartbeat was saved.'),
            'percent' => new external_value(PARAM_INT, 'Server-calculated watched percentage.'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the target has been reached.'),
            'resumetime' => new external_value(PARAM_INT, 'Latest playback position.'),
            'allowedtime' => new external_value(PARAM_INT, 'Continuous watched time available for seeking.'),
        ]);
    }
}
