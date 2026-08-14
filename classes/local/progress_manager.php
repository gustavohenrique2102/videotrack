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

declare(strict_types=1);

namespace mod_videotrack\local;

/**
 * Stores server-validated playback intervals and derives viewing progress.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class progress_manager {
    /** Maximum supported media duration, in seconds. */
    private const MAX_DURATION = 604800;

    /** Maximum heartbeat gap which can add watched time. */
    private const MAX_CREDIT_GAP = 15;

    /**
     * Save one playback heartbeat.
     *
     * @param int $videotrackid Activity instance ID.
     * @param int $userid User ID.
     * @param int $position Current media position in seconds.
     * @param int $duration Media duration in seconds.
     * @param int $targetpercent Required percentage, or zero.
     * @return stdClass Updated progress record.
     */
    public static function save(
        int $videotrackid,
        int $userid,
        int $position,
        int $duration,
        int $targetpercent
    ): \stdClass {
        global $DB;

        $duration = max(0, min(self::MAX_DURATION, $duration));
        $position = max(0, $duration > 0 ? min($duration, $position) : $position);
        $now = time();

        $factory = \core\lock\lock_config::get_lock_factory('mod_videotrack_progress');
        $lock = $factory->get_lock($videotrackid . ':' . $userid, 10, MINSECS);
        if (!$lock) {
            throw new \moodle_exception('error_progresslock', 'mod_videotrack');
        }

        try {
            $transaction = $DB->start_delegated_transaction();
            $progress = $DB->get_record('videotrack_progress', [
                'videotrackid' => $videotrackid,
                'userid' => $userid,
            ]);

            if (!$progress) {
                $progress = (object) [
                    'videotrackid' => $videotrackid,
                    'userid' => $userid,
                    'highestpercent' => 0,
                    'highesttime' => 0,
                    'watchedsegments' => null,
                    'duration' => $duration,
                    'lastposition' => $position,
                    'lastheartbeat' => $now,
                    'iscompleted' => 0,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $progress->id = $DB->insert_record('videotrack_progress', $progress);
                $transaction->allow_commit();
                return $progress;
            }

            $segments = self::decode_segments($progress->watchedsegments ?? null);
            $effectiveduration = $duration > 0 ? $duration : (int)($progress->duration ?? 0);
            if (empty($segments) && $effectiveduration > 0 && (int)$progress->highestpercent > 0) {
                // Preserve progress recorded by releases which only stored a percentage.
                $legacyend = (int)floor($effectiveduration * (int)$progress->highestpercent / 100);
                if ($legacyend > 0) {
                    $segments[] = [0, $legacyend];
                }
            }

            $elapsed = $now - (int)($progress->lastheartbeat ?? 0);
            $delta = $position - (int)($progress->lastposition ?? 0);
            $maximumdelta = max(4, (int)ceil($elapsed * 2.25 + 2));
            if (
                $effectiveduration > 0 && $elapsed > 0 && $elapsed <= self::MAX_CREDIT_GAP
                    && $delta > 0 && $delta <= $maximumdelta
            ) {
                $segments[] = [(int)$progress->lastposition, $position];
            }

            $segments = self::merge_segments($segments, $effectiveduration);
            $watchedseconds = self::watched_seconds($segments);
            $percent = $effectiveduration > 0
                ? min(100, (int)floor($watchedseconds * 100 / $effectiveduration))
                : 0;

            $progress->highestpercent = max((int)$progress->highestpercent, $percent);
            $progress->highesttime = self::contiguous_end($segments);
            $progress->watchedsegments = json_encode($segments, JSON_UNESCAPED_SLASHES);
            $progress->duration = $effectiveduration;
            $progress->lastposition = $position;
            $progress->lastheartbeat = $now;
            $progress->iscompleted = $targetpercent > 0 && $progress->highestpercent >= $targetpercent ? 1 : 0;
            $progress->timemodified = $now;
            $DB->update_record('videotrack_progress', $progress);
            $transaction->allow_commit();

            return $progress;
        } finally {
            $lock->release();
        }
    }

    /**
     * Decode stored intervals.
     *
     * @param string|null $json JSON value.
     * @return array
     */
    public static function decode_segments(?string $json): array {
        if ($json === null || $json === '') {
            return [];
        }
        $segments = json_decode($json, true);
        return is_array($segments) ? $segments : [];
    }

    /**
     * Merge overlapping intervals.
     *
     * @param array $segments Intervals.
     * @param int $duration Duration used to clamp values.
     * @return array
     */
    public static function merge_segments(array $segments, int $duration): array {
        $normalised = [];
        foreach ($segments as $segment) {
            if (!is_array($segment) || count($segment) !== 2) {
                continue;
            }
            $start = max(0, (int)$segment[0]);
            $end = max(0, (int)$segment[1]);
            if ($duration > 0) {
                $start = min($duration, $start);
                $end = min($duration, $end);
            }
            if ($end > $start) {
                $normalised[] = [$start, $end];
            }
        }
        usort($normalised, static fn(array $a, array $b): int => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($normalised as $segment) {
            $last = count($merged) - 1;
            if ($last >= 0 && $segment[0] <= $merged[$last][1] + 1) {
                $merged[$last][1] = max($merged[$last][1], $segment[1]);
            } else {
                $merged[] = $segment;
            }
        }
        return $merged;
    }

    /**
     * Return unique watched seconds.
     *
     * @param array $segments Merged intervals.
     * @return int
     */
    public static function watched_seconds(array $segments): int {
        return array_sum(array_map(static fn(array $segment): int => $segment[1] - $segment[0], $segments));
    }

    /**
     * Return the end of the interval watched continuously from the beginning.
     *
     * @param array $segments Merged intervals.
     * @return int
     */
    public static function contiguous_end(array $segments): int {
        if (empty($segments) || $segments[0][0] > 1) {
            return 0;
        }
        return (int)$segments[0][1];
    }
}
