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
 * VideoTrack (mod_videotrack)
 *
 * @package     mod_videotrack
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



/**
 * Structure step class for backup.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_videotrack_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure for the activity.
     *
     * @return \backup_nested_element
     */
    protected function define_structure() {
        // XML structure element.
        $videotrack = new backup_nested_element('videotrack', ['id'], [
            'course', 'name', 'intro', 'introformat', 'videourl', 'targetpercent', 'displaymode', 'focusmode', 'preventforward',
            'accentcolor',
            'timecreated', 'timemodified',
        ]);

        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'highestpercent', 'highesttime', 'watchedsegments', 'duration', 'lastposition', 'lastheartbeat',
            'iscompleted', 'timecreated', 'timemodified',
        ]);
        $videotrack->add_child($progresses);
        $progresses->add_child($progress);

        // Connect database table fields.
        $videotrack->set_source_table('videotrack', ['id' => backup::VAR_ACTIVITYID]);
        if ($this->get_setting_value('userinfo')) {
            $progress->set_source_table('videotrack_progress', ['videotrackid' => backup::VAR_PARENTID]);
            $progress->annotate_ids('user', 'userid');
        }

        // Backup files in 'intro' and 'video' fileareas.
        $videotrack->annotate_files('mod_videotrack', 'intro', null); // Default intro files.
        $videotrack->annotate_files('mod_videotrack', 'video', null); // Local uploaded video files.
        $videotrack->annotate_files('mod_videotrack', 'captions', null); // Optional WebVTT captions.

        return $this->prepare_activity_structure($videotrack);
    }
}
