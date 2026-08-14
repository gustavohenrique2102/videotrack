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

namespace mod_videotrack;

use mod_videotrack\local\progress_manager;

// PHPCS in Moodle 4.5 does not yet recognise PHPUnit's CoversClass attribute.
// phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing

/**
 * Unit tests for watched interval calculations.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(progress_manager::class)]
final class progress_manager_test extends \advanced_testcase {
    /**
     * Overlapping and adjacent ranges should count only once.
     */
    public function test_merge_segments_removes_overlap(): void {
        $segments = progress_manager::merge_segments([[10, 20], [0, 5], [4, 12], [30, 35]], 100);
        $this->assertSame([[0, 20], [30, 35]], $segments);
        $this->assertSame(25, progress_manager::watched_seconds($segments));
    }

    /**
     * Invalid values are discarded and valid values are clamped to duration.
     */
    public function test_merge_segments_validates_and_clamps_values(): void {
        $segments = progress_manager::merge_segments([[-5, 5], [90, 120], [30, 20], ['x']], 100);
        $this->assertSame([[0, 5], [90, 100]], $segments);
    }

    /**
     * Seeking is allowed only through the continuous interval watched from zero.
     */
    public function test_contiguous_end_ignores_disconnected_ranges(): void {
        $this->assertSame(20, progress_manager::contiguous_end([[0, 20], [40, 60]]));
        $this->assertSame(0, progress_manager::contiguous_end([[10, 20]]));
    }

    /**
     * Corrupt JSON is treated as no watched intervals.
     */
    public function test_decode_segments_rejects_corrupt_json(): void {
        $this->assertSame([], progress_manager::decode_segments('{broken'));
        $this->assertSame([[0, 4]], progress_manager::decode_segments('[[0,4]]'));
    }

    /**
     * A sequential heartbeat adds watched time while a forward jump does not.
     */
    public function test_save_credits_playback_but_rejects_jump(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $videotrackid = $DB->insert_record('videotrack', (object) [
            'course' => $course->id,
            'name' => 'Test video',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'videourl' => 'https://example.com/video.mp4',
            'targetpercent' => 80,
            'displaymode' => 0,
            'focusmode' => 0,
            'preventforward' => 1,
            'accentcolor' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $baseline = progress_manager::save($videotrackid, $user->id, 0, 100, 80);
        $DB->set_field('videotrack_progress', 'lastheartbeat', time() - 3, ['id' => $baseline->id]);
        $watched = progress_manager::save($videotrackid, $user->id, 3, 100, 80);
        $this->assertSame(3, (int)$watched->highestpercent);

        $DB->set_field('videotrack_progress', 'lastheartbeat', time() - 3, ['id' => $baseline->id]);
        $jumped = progress_manager::save($videotrackid, $user->id, 50, 100, 80);
        $this->assertSame(3, (int)$jumped->highestpercent);
        $this->assertSame(3, (int)$jumped->highesttime);
    }
}

// phpcs:enable moodle.PHPUnit.TestCaseCovers.Missing
