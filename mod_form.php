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
 * Activity settings form for VideoTrack.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module form.
 *
 * @package    mod_videotrack
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videotrack_mod_form extends moodleform_mod {
    /**
     * File manager options used consistently by definition and preprocessing.
     *
     * @return array
     */
    private function get_video_filemanager_options(): array {
        $course = $this->get_course();
        return [
            'subdirs' => 0,
            'maxbytes' => $course->maxbytes ?? 0,
            'maxfiles' => 1,
            'accepted_types' => ['video'],
        ];
    }

    /**
     * Caption file manager options.
     *
     * @return array
     */
    private function get_caption_filemanager_options(): array {
        return [
            'subdirs' => 0,
            'maxbytes' => 1048576,
            'maxfiles' => 1,
            'accepted_types' => ['.vtt'],
        ];
    }

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name', 'moodle'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', 'moodle', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'videosettings', get_string('videosettings', 'mod_videotrack'));
        $mform->setExpanded('videosettings', true);

        // External URL is optional when a local file is uploaded.
        $mform->addElement('text', 'videourl', get_string('videourl', 'mod_videotrack'), ['size' => '64']);
        $mform->setType('videourl', PARAM_URL);
        $mform->addHelpButton('videourl', 'videourl', 'mod_videotrack');

        $mform->addElement(
            'filemanager',
            'video',
            get_string('videofile', 'mod_videotrack'),
            null,
            $this->get_video_filemanager_options()
        );
        $mform->addHelpButton('video', 'videofile', 'mod_videotrack');

        $mform->addElement(
            'filemanager',
            'captions',
            get_string('captions', 'mod_videotrack'),
            null,
            $this->get_caption_filemanager_options()
        );
        $mform->addHelpButton('captions', 'captions', 'mod_videotrack');

        $mform->addElement('header', 'displaysettings', get_string('displaysettings', 'mod_videotrack'));
        $mform->addElement('select', 'displaymode', get_string('displaymode', 'mod_videotrack'), [
            0 => get_string('displaymode:page', 'mod_videotrack'),
            1 => get_string('displaymode:popup', 'mod_videotrack'),
            2 => get_string('displaymode:inline', 'mod_videotrack'),
        ]);
        $mform->setDefault('displaymode', 0);
        $mform->addHelpButton('displaymode', 'displaymode', 'mod_videotrack');

        $mform->addElement('text', 'accentcolor', get_string('accentcolor', 'mod_videotrack'), [
            'size' => 10,
            'maxlength' => 7,
            'placeholder' => '#0f6cbf',
        ]);
        $mform->setType('accentcolor', PARAM_TEXT);
        $mform->addHelpButton('accentcolor', 'accentcolor', 'mod_videotrack');

        $mform->addElement('advcheckbox', 'preventforward', '', get_string('preventforward', 'mod_videotrack'), [], [0, 1]);
        $mform->setDefault('preventforward', 1);
        $mform->addHelpButton('preventforward', 'preventforward', 'mod_videotrack');

        $mform->addElement('advcheckbox', 'focusmode', '', get_string('focusmode', 'mod_videotrack'), [], [0, 1]);
        $mform->setDefault('focusmode', 0);
        $mform->addHelpButton('focusmode', 'focusmode', 'mod_videotrack');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepare stored files and custom completion values before set_data().
     *
     * @param array $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if (!empty($this->current->instance)) {
            $draftitemid = file_get_submitted_draft_itemid('video');
            file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_videotrack',
                'video',
                0,
                $this->get_video_filemanager_options()
            );
            $defaultvalues['video'] = $draftitemid;

            $captiondraftid = file_get_submitted_draft_itemid('captions');
            file_prepare_draft_area(
                $captiondraftid,
                $this->context->id,
                'mod_videotrack',
                'captions',
                0,
                $this->get_caption_filemanager_options()
            );
            $defaultvalues['captions'] = $captiondraftid;
        }

        $suffix = method_exists($this, 'get_suffix') ? $this->get_suffix() : '';
        $targetkey = 'targetpercent' . $suffix;
        $enabledkey = 'targetpercentenabled' . $suffix;
        $target = (int)($defaultvalues[$targetkey] ?? $defaultvalues['targetpercent'] ?? 0);
        $defaultvalues[$enabledkey] = $target > 0 ? 1 : 0;
        $defaultvalues[$targetkey] = $target > 0 ? $target : 80;
    }

    /**
     * Add the percentage rule to Moodle's Activity completion section.
     *
     * @return array List of form element names used by the rule.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = method_exists($this, 'get_suffix') ? $this->get_suffix() : '';
        $enabledname = 'targetpercentenabled' . $suffix;
        $targetname = 'targetpercent' . $suffix;
        $unitname = 'targetpercentunit' . $suffix;
        $groupname = 'targetpercentgroup' . $suffix;

        $group = [
            $mform->createElement(
                'advcheckbox',
                $enabledname,
                '',
                get_string('completionwatchpercent', 'mod_videotrack'),
                [],
                [0, 1]
            ),
            $mform->createElement('text', $targetname, '', ['size' => 4, 'maxlength' => 3]),
            // A named element is required here. An empty name is added to Moodle's
            // completion dependencies and collides with unnamed file-manager inputs.
            $mform->createElement('static', $unitname, '', '%'),
        ];
        $mform->addGroup($group, $groupname, '', ' ', false);
        $mform->setType($targetname, PARAM_INT);
        // Keep the group structurally stable; Moodle itself controls its visibility based on completion mode.
        $mform->disabledIf($targetname, $enabledname, 'notchecked');
        $mform->addHelpButton($groupname, 'completionwatchpercent', 'mod_videotrack');

        return [$groupname];
    }

    /**
     * Check whether the custom percentage completion rule is enabled.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = method_exists($this, 'get_suffix') ? $this->get_suffix() : '';
        return !empty($data['targetpercentenabled' . $suffix])
            && !empty($data['targetpercent' . $suffix]);
    }

    /**
     * Normalise custom completion values before the activity record is saved.
     *
     * @param stdClass $data Form data.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        $suffix = method_exists($this, 'get_suffix') ? $this->get_suffix() : '';
        $completionname = 'completion' . $suffix;
        $enabledname = 'targetpercentenabled' . $suffix;
        $targetname = 'targetpercent' . $suffix;

        // Do not rewrite locked completion settings. Moodle sets completionunlocked=1 for
        // new/unlocked activities and keeps it at 0 while completion rules are frozen.
        if (!empty($data->completionunlocked)) {
            $completion = $data->{$completionname} ?? $data->completion ?? null;
            $automatic = isset($completion) && (int)$completion === COMPLETION_TRACKING_AUTOMATIC;
            $enabled = !empty($data->{$enabledname});
            $targetvalue = ($automatic && $enabled)
                ? (int)($data->{$targetname} ?? $data->targetpercent ?? 0)
                : 0;
            $data->{$targetname} = $targetvalue;
            if ($suffix === '') {
                $data->targetpercent = $targetvalue;
            }
        }
    }

    /**
     * Validate video source and custom completion rule.
     *
     * @param array $data Data.
     * @param array $files Files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);
        $url = trim($data['videourl'] ?? '');
        $draftitemid = (int)($data['video'] ?? 0);

        $filecontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $hasuploadedfile = $draftitemid > 0
            && !$fs->is_area_empty($filecontext->id, 'user', 'draft', $draftitemid);

        if ($url === '' && !$hasuploadedfile) {
            $message = get_string('error_nouploadorurl', 'mod_videotrack');
            $errors['videourl'] = $message;
            $errors['video'] = $message;
        }
        if ($url !== '' && !videotrack_is_supported_url($url)) {
            $errors['videourl'] = get_string('error_unsupportedurl', 'mod_videotrack');
        }

        $accent = trim((string)($data['accentcolor'] ?? ''));
        if ($accent !== '' && !preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
            $errors['accentcolor'] = get_string('error_accentcolor', 'mod_videotrack');
        }

        $suffix = method_exists($this, 'get_suffix') ? $this->get_suffix() : '';
        $enabledname = 'targetpercentenabled' . $suffix;
        $targetname = 'targetpercent' . $suffix;
        if (!empty($data[$enabledname])) {
            $target = (int)($data[$targetname] ?? 0);
            if ($target < 1 || $target > 100) {
                $errors['targetpercentgroup' . $suffix] = get_string('completionpercenterror', 'mod_videotrack');
            }
        }

        return $errors;
    }
}
