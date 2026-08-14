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

/**
 * Display a VideoTrack activity.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);
$embed = optional_param('embed', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('videotrack', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$videotrack = $DB->get_record('videotrack', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videotrack:view', $context);

$PAGE->set_url('/mod/videotrack/view.php', ['id' => $cm->id, 'embed' => $embed]);
$PAGE->set_title(format_string($videotrack->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
if ($embed) {
    $PAGE->set_pagelayout('embedded');
    $PAGE->add_body_class('videotrack-embedded');
}
if (!$embed && !empty($videotrack->focusmode)) {
    $PAGE->add_body_class('videotrack-focusmode-page');
}

$event = \mod_videotrack\event\course_module_viewed::create([
    'objectid' => $videotrack->id,
    'context' => $context,
    'courseid' => $course->id,
    'other' => ['instanceid' => $videotrack->id, 'cmid' => $cm->id],
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videotrack', $videotrack);
$event->trigger();

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}

$courseurl = $embed ? '' : (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
$player = videotrack_prepare_player($videotrack, $cm, $context, $courseurl);
$PAGE->requires->js_call_amd('mod_videotrack/tracker', 'init', $player['tracker']);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrack/player', $player['template']);
echo $OUTPUT->footer();
