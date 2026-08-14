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

namespace mod_videotrack;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

// PHPCS does not yet recognise PHPUnit's CoversFunction attribute.
// phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing
/**
 * Tests for VideoTrack helper functions.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('videotrack_get_youtube_id')]
#[\PHPUnit\Framework\Attributes\CoversFunction('videotrack_extract_heygen_url')]
#[\PHPUnit\Framework\Attributes\CoversFunction('videotrack_is_supported_url')]
#[\PHPUnit\Framework\Attributes\CoversFunction('videotrack_update_instance')]
final class lib_test extends \advanced_testcase {
    /**
     * Common YouTube URL formats should resolve to the same video ID.
     */
    public function test_get_youtube_id_supports_common_urls(): void {
        $expected = 'dQw4w9WgXcQ';
        $urls = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/live/dQw4w9WgXcQ?feature=share',
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        ];

        foreach ($urls as $url) {
            $this->assertSame($expected, videotrack_get_youtube_id($url));
        }
    }

    /**
     * Invalid or unrelated URLs must not be treated as YouTube videos.
     */
    public function test_get_youtube_id_rejects_invalid_urls(): void {
        $urls = [
            '',
            'https://example.com/video.mp4',
            'https://youtube.example.com/watch?v=dQw4w9WgXcQ',
            'https://example.com/youtube.com/watch?v=dQw4w9WgXcQ',
            'https://notyoutube.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=too-short',
        ];

        foreach ($urls as $url) {
            $this->assertSame('', videotrack_get_youtube_id($url));
        }
    }

    /**
     * A URL merely containing the HeyGen hostname must not trigger server-side fetching.
     */
    public function test_heygen_extractor_requires_exact_host(): void {
        $url = 'https://example.com/?next=https%3A%2F%2Fapp.heygen.com%2Fvideos%2Fexample';
        $this->assertSame($url, videotrack_extract_heygen_url($url));
    }

    /**
     * Only known player URLs and direct video files are accepted.
     */
    public function test_supported_url_validation(): void {
        $this->assertTrue(videotrack_is_supported_url('https://example.com/media/video.mp4?token=abc'));
        $this->assertTrue(videotrack_is_supported_url('https://app.heygen.com/videos/example'));
        $this->assertTrue(videotrack_is_supported_url('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertFalse(videotrack_is_supported_url('https://vimeo.com/12345'));
        $this->assertFalse(videotrack_is_supported_url('javascript:alert(1)'));
    }

    /**
     * Replacing the video URL must invalidate progress from the old source.
     */
    public function test_update_instance_resets_progress_when_source_changes(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $activity = $this->getDataGenerator()->create_module('videotrack', [
            'course' => $course->id,
            'videourl' => 'https://example.com/old.mp4',
            'targetpercent' => 80,
        ]);
        $cm = get_coursemodule_from_instance('videotrack', $activity->id, $course->id, false, MUST_EXIST);
        $DB->insert_record('videotrack_progress', (object) [
            'videotrackid' => $activity->id,
            'userid' => $user->id,
            'highestpercent' => 80,
            'highesttime' => 80,
            'watchedsegments' => '[[0,80]]',
            'duration' => 100,
            'lastposition' => 80,
            'lastheartbeat' => time(),
            'iscompleted' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $updated = (object) [
            'instance' => $activity->id,
            'coursemodule' => $cm->id,
            'videourl' => 'https://example.com/new.mp4',
        ];
        videotrack_update_instance($updated);

        $this->assertFalse($DB->record_exists('videotrack_progress', [
            'videotrackid' => $activity->id,
            'userid' => $user->id,
        ]));
    }
}
// phpcs:enable moodle.PHPUnit.TestCaseCovers.Missing
