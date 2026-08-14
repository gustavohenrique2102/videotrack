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

namespace mod_videotrack\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for VideoTrack.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Gets the completion state for a custom rule.
     *
     * @param string $rule Rule name.
     * @return int Completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $target = (int)($this->cm->customdata['customcompletionrules']['targetpercent'] ?? 0);
        if ($target <= 0) {
            return COMPLETION_INCOMPLETE;
        }

        $highestpercent = $DB->get_field('videotrack_progress', 'highestpercent', [
            'videotrackid' => $this->cm->instance,
            'userid' => $this->userid,
        ]);
        $highestpercent = $highestpercent === false ? 0 : (int)$highestpercent;

        return $highestpercent >= $target ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns all custom completion rules defined by this activity.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['targetpercent'];
    }

    /**
     * Returns human-readable rule descriptions.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $target = (int)($this->cm->customdata['customcompletionrules']['targetpercent'] ?? 0);
        return [
            'targetpercent' => get_string('completiondetail:targetpercent', 'mod_videotrack', $target),
        ];
    }

    /**
     * Defines the order in which completion rules are displayed.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'targetpercent',
        ];
    }
}
