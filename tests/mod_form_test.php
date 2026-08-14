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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

namespace mod_videotrack;

/**
 * Tests for the VideoTrack activity form.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_videotrack_mod_form::class)]
final class mod_form_test extends \advanced_testcase {
    /**
     * Completion-rule children must all have names to avoid unrelated form dependencies.
     */
    public function test_completion_group_has_no_unnamed_elements(): void {
        global $CFG, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $PAGE->set_course($course);
        require_once($CFG->dirroot . '/mod/videotrack/mod_form.php');

        $form = new \mod_videotrack_mod_form((object) ['instance' => 0], 0, null, $course);
        $reflection = new \ReflectionClass($form);
        $formproperty = $reflection->getProperty('_form');
        $mform = $formproperty->getValue($form);
        $quickformreflection = new \ReflectionClass($mform);
        $elementsproperty = $quickformreflection->getProperty('_elements');
        $groups = array_filter(
            $elementsproperty->getValue($mform),
            static fn($element) => str_starts_with((string) $element->getName(), 'targetpercentgroup')
        );

        $this->assertCount(1, $groups);
        $group = reset($groups);
        $names = array_map(static fn($element) => $element->getName(), $group->getElements());

        $this->assertNotContains('', $names);
        $this->assertNotEmpty(array_filter(
            $names,
            static fn($name) => str_starts_with((string) $name, 'targetpercentunit')
        ));
    }
}
