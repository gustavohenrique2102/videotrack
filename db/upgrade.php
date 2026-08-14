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
 * Upgrade script for mod_videotrack.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool
 */
function xmldb_videotrack_upgrade($oldversion): bool {
    global $DB;

    if ($oldversion < 2026063000) {
        // Define field highesttime to be added to videotrack_progress.
        $table = new xmldb_table('videotrack_progress');
        $field = new xmldb_field('highesttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'highestpercent');

        $dbman = $DB->get_manager();

        // Conditionally launch add field highesttime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Videotrack savepoint reached.
        upgrade_mod_savepoint(true, 2026063000, 'videotrack');
    }

    if ($oldversion < 2026081301) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('videotrack');

        // A zero default means the percentage rule is disabled until the teacher enables it.
        // Existing activity values are preserved; only the database default changes.
        $targetpercent = new xmldb_field(
            'targetpercent',
            XMLDB_TYPE_INTEGER,
            '3',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'videourl'
        );
        $dbman->change_field_default($table, $targetpercent);

        $displaymode = new xmldb_field(
            'displaymode',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'targetpercent'
        );
        if (!$dbman->field_exists($table, $displaymode)) {
            $dbman->add_field($table, $displaymode);
        }

        $focusmode = new xmldb_field(
            'focusmode',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'displaymode'
        );
        if (!$dbman->field_exists($table, $focusmode)) {
            $dbman->add_field($table, $focusmode);
        }

        upgrade_mod_savepoint(true, 2026081301, 'videotrack');
    }

    if ($oldversion < 2026081401) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('videotrack');

        $preventforward = new xmldb_field(
            'preventforward',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'focusmode'
        );
        if (!$dbman->field_exists($table, $preventforward)) {
            $dbman->add_field($table, $preventforward);
        }

        upgrade_mod_savepoint(true, 2026081401, 'videotrack');
    }

    if ($oldversion < 2026081403) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('videotrack');
        $accentcolor = new xmldb_field(
            'accentcolor',
            XMLDB_TYPE_CHAR,
            '7',
            null,
            null,
            null,
            null,
            'preventforward'
        );
        if (!$dbman->field_exists($table, $accentcolor)) {
            $dbman->add_field($table, $accentcolor);
        }

        $table = new xmldb_table('videotrack_progress');
        $fields = [
            new xmldb_field('watchedsegments', XMLDB_TYPE_TEXT, null, null, null, null, null, 'highesttime'),
            new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'watchedsegments'),
            new xmldb_field('lastposition', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'duration'),
            new xmldb_field('lastheartbeat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'lastposition'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Old automatic-completion records without an active rule could be marked complete incorrectly.
        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videotrack} v ON v.id = cm.instance
                 WHERE cm.completion = :automatic
                       AND cm.completionview = 0
                       AND v.targetpercent = 0";
        $affectedcms = $DB->get_fieldset_sql($sql, [
            'modname' => 'videotrack',
            'automatic' => COMPLETION_TRACKING_AUTOMATIC,
        ]);
        foreach ($affectedcms as $cmid) {
            $DB->set_field('course_modules', 'completionview', 1, ['id' => $cmid]);
            $DB->delete_records('course_modules_completion', ['coursemoduleid' => $cmid]);
        }

        upgrade_mod_savepoint(true, 2026081403, 'videotrack');
    }

    return true;
}
