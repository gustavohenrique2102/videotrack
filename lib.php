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
 * Resolve a public MP4 link from a HeyGen share page.
 *
 * @param string $url The original URL
 * @return string The mp4 URL if found, otherwise the original URL.
 */
function videotrack_extract_heygen_url($url) {
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    if ($host === 'app.heygen.com' || $host === 'share.heygen.com') {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $cache = cache::make('mod_videotrack', 'sourceurls');
        $cachekey = sha1($url);
        $cached = $cache->get($cachekey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
        $curl = new curl();
        $curl->setopt([
            'CURLOPT_USERAGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' .
                                   '(KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'CURLOPT_TIMEOUT' => 10,
        ]);
        $content = $curl->get($url);
        if (!is_string($content) || $content === '') {
            return $url;
        }

        // HeyGen can expose the source through escaped JSON on different CDN hosts.
        $normalised = str_replace(['\\/', '\\u0026', '&amp;'], ['/', '&', '&'], $content);
        if (
            preg_match_all(
                '/https:\/\/(?:resource\d*|files\d*)\.heygen\.ai[^"\'<>\s]+?\.mp4(?:\?[^"\'<>\s]+)?/i',
                $normalised,
                $matches
            )
        ) {
            if (!empty($matches[0])) {
                $extracted = html_entity_decode($matches[0][0], ENT_QUOTES | ENT_HTML5);
                $cache->set($cachekey, $extracted);
                return $extracted;
            }
        }
    }
    return $url;
}

/**
 * Extracts a YouTube video ID from common YouTube URL formats.
 *
 * @param string $url Video URL.
 * @return string The 11-character YouTube ID, or an empty string when the URL is not recognised.
 */
function videotrack_get_youtube_id(string $url): string {
    $url = trim(html_entity_decode($url));
    if ($url === '') {
        return '';
    }

    $parsed = parse_url($url);
    if ($parsed === false) {
        return '';
    }

    $host = strtolower($parsed['host'] ?? '');
    $isyoutubehost = $host === 'youtube.com' || substr($host, -12) === '.youtube.com'
        || $host === 'youtube-nocookie.com' || substr($host, -21) === '.youtube-nocookie.com';
    $isyoutubeshort = $host === 'youtu.be' || substr($host, -9) === '.youtu.be';
    if (!$isyoutubehost && !$isyoutubeshort) {
        return '';
    }

    $path = $parsed['path'] ?? '';
    $query = $parsed['query'] ?? '';
    $videoid = '';

    if ($isyoutubehost && $query !== '') {
        parse_str($query, $params);
        if (!empty($params['v'])) {
            $videoid = (string)$params['v'];
        }
    }

    if ($videoid === '' && $isyoutubehost && $path !== '') {
        $pathparts = explode('/', trim($path, '/'));
        foreach ($pathparts as $index => $part) {
            if (in_array(strtolower($part), ['shorts', 'embed', 'v', 'live'], true) && isset($pathparts[$index + 1])) {
                $videoid = $pathparts[$index + 1];
                break;
            }
        }
    }

    if ($videoid === '' && $isyoutubeshort) {
        $videoid = trim($path, '/');
    }

    $videoid = preg_split('/[?&]/', $videoid)[0] ?? '';
    $videoid = substr($videoid, 0, 11);

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $videoid) ? $videoid : '';
}

/**
 * Whether an external URL is a supported source.
 *
 * @param string $url URL to validate.
 * @return bool
 */
function videotrack_is_supported_url(string $url): bool {
    if (videotrack_get_youtube_id($url) !== '') {
        return true;
    }
    $parts = parse_url($url);
    if ($parts === false || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
        return false;
    }
    $host = strtolower($parts['host'] ?? '');
    if (in_array($host, ['app.heygen.com', 'share.heygen.com'], true)) {
        return true;
    }
    $extension = strtolower(pathinfo($parts['path'] ?? '', PATHINFO_EXTENSION));
    return in_array($extension, ['mp4', 'webm', 'ogv', 'ogg', 'm4v', 'mov'], true);
}

/**
 * Obtain the first stored video and its URL.
 *
 * @param context_module $context Module context.
 * @return array|null
 */
function videotrack_get_local_video(context_module $context): ?array {
    $files = get_file_storage()->get_area_files(
        $context->id,
        'mod_videotrack',
        'video',
        0,
        'sortorder, id',
        false
    );
    foreach ($files as $file) {
        if (!$file->is_directory() && strpos((string)$file->get_mimetype(), 'video/') === 0) {
            return [
                'url' => moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(),
                'mimetype' => $file->get_mimetype(),
                'contenthash' => $file->get_contenthash(),
            ];
        }
    }
    return null;
}

/**
 * Resolve the player source, with a local-file fallback.
 *
 * @param stdClass $videotrack Activity record.
 * @param context_module $context Module context.
 * @return array
 */
function videotrack_resolve_source(stdClass $videotrack, context_module $context): array {
    $url = trim((string)($videotrack->videourl ?? ''));
    $youtubeid = videotrack_get_youtube_id($url);
    $error = '';
    if ($youtubeid !== '') {
        return ['hassource' => true, 'url' => $url, 'isyoutube' => true, 'ytid' => $youtubeid, 'mimetype' => ''];
    }
    if ($url !== '') {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $isheygen = in_array($host, ['app.heygen.com', 'share.heygen.com'], true);
        $resolved = videotrack_extract_heygen_url($url);
        if ($resolved !== $url || (!$isheygen && videotrack_is_supported_url($resolved))) {
            $extension = strtolower(pathinfo(parse_url($resolved, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            $mimetypes = [
                'mp4' => 'video/mp4',
                'm4v' => 'video/mp4',
                'mov' => 'video/quicktime',
                'webm' => 'video/webm',
                'ogv' => 'video/ogg',
                'ogg' => 'video/ogg',
            ];
            return [
                'hassource' => true,
                'url' => $resolved,
                'isyoutube' => false,
                'ytid' => '',
                'mimetype' => $mimetypes[$extension] ?? '',
            ];
        }
        $error = get_string('error_unsupportedurl', 'mod_videotrack');
    }
    $local = videotrack_get_local_video($context);
    if ($local) {
        return [
            'hassource' => true,
            'url' => $local['url'],
            'isyoutube' => false,
            'ytid' => '',
            'mimetype' => $local['mimetype'],
            'warning' => $error,
        ];
    }
    if ($error === '') {
        $error = get_string('error_nouploadorurl', 'mod_videotrack');
    }
    return ['hassource' => false, 'url' => '', 'isyoutube' => false, 'ytid' => '', 'mimetype' => '', 'error' => $error];
}

/**
 * Generate a stable fingerprint for progress invalidation.
 *
 * @param stdClass $videotrack Activity record.
 * @param context_module $context Module context.
 * @return string
 */
function videotrack_source_fingerprint(stdClass $videotrack, context_module $context): string {
    $url = trim((string)($videotrack->videourl ?? ''));
    if ($url !== '') {
        return 'url:' . sha1($url);
    }
    $local = videotrack_get_local_video($context);
    return $local ? 'file:' . $local['contenthash'] : 'none';
}

/**
 * Prepare one player instance and its tracker arguments.
 *
 * @param stdClass $videotrack Activity record.
 * @param stdClass|cm_info $cm Course module.
 * @param context_module $context Module context.
 * @param string $backurl Optional course URL.
 * @param bool $isinline Whether this is embedded in the course page.
 * @return array{template: array, tracker: array}
 */
function videotrack_prepare_player(
    stdClass $videotrack,
    $cm,
    context_module $context,
    string $backurl = '',
    bool $isinline = false
): array {
    global $DB, $USER;

    $source = videotrack_resolve_source($videotrack, $context);
    $progress = $DB->get_record('videotrack_progress', [
        'videotrackid' => $videotrack->id,
        'userid' => $USER->id,
    ]);
    $percent = $progress ? (int)$progress->highestpercent : 0;
    $allowedtime = $progress ? (int)$progress->highesttime : 0;
    $resumetime = $progress ? (int)($progress->lastposition ?? $allowedtime) : 0;
    $isfree = (int)$videotrack->targetpercent <= 0;
    $iscompleted = !$isfree && $percent >= (int)$videotrack->targetpercent;
    $formattedtime = $resumetime >= 3600
        ? sprintf('%02d:%02d:%02d', floor($resumetime / 3600), floor(($resumetime / 60) % 60), $resumetime % 60)
        : sprintf('%02d:%02d', floor($resumetime / 60), $resumetime % 60);

    $captionurl = '';
    $captionfiles = get_file_storage()->get_area_files(
        $context->id,
        'mod_videotrack',
        'captions',
        0,
        'sortorder, id',
        false
    );
    foreach ($captionfiles as $caption) {
        if (!$caption->is_directory()) {
            $captionurl = moodle_url::make_pluginfile_url(
                $caption->get_contextid(),
                $caption->get_component(),
                $caption->get_filearea(),
                $caption->get_itemid(),
                $caption->get_filepath(),
                $caption->get_filename()
            )->out();
            break;
        }
    }

    $accent = (string)($videotrack->accentcolor ?? '');
    $accentstyle = preg_match('/^#[0-9a-f]{6}$/i', $accent) ? '--vt-accent:' . $accent . ';' : '';
    $template = [
        'cmid' => $cm->id,
        'videourl' => $source['url'],
        'videomimetype' => $source['mimetype'],
        'hassource' => $source['hassource'],
        'sourceerror' => $source['error'] ?? '',
        'sourcewarning' => $source['warning'] ?? '',
        'isyoutube' => $source['isyoutube'],
        'ytid' => $source['ytid'],
        'name' => format_string($videotrack->name),
        'accentstyle' => $accentstyle,
        'targetpercent' => (int)$videotrack->targetpercent,
        'currentpercent' => $percent,
        'iscompleted' => $iscompleted,
        'isfree' => $isfree,
        'preventforward' => !empty($videotrack->preventforward),
        'focusmode' => !empty($videotrack->focusmode),
        'showresumebutton' => $resumetime > 0 && !$iscompleted,
        'hascaptions' => $captionurl !== '',
        'captionurl' => $captionurl,
        'captionlang' => str_replace('_', '-', current_language()),
        'captionlabel' => get_string('captions', 'mod_videotrack'),
        'progresstitle' => get_string('progresstitle', 'mod_videotrack'),
        'progresshint' => $isfree
            ? get_string('progressfree', 'mod_videotrack')
            : get_string('progresshint', 'mod_videotrack', $videotrack->targetpercent),
        'successmsg' => get_string('successmsg', 'mod_videotrack'),
        'completedtext' => get_string('completed', 'mod_videotrack'),
        'targetmarker' => get_string('targetmarker', 'mod_videotrack', (int)$videotrack->targetpercent),
        'resumebtntext' => get_string('resumebutton', 'mod_videotrack', $formattedtime),
        'backtocourseurl' => $backurl,
        'backtocoursetext' => get_string('backtocourse', 'mod_videotrack'),
        'focuspausedtitle' => get_string('focusmode_paused_title', 'mod_videotrack'),
        'focuspausedmsg' => get_string('focusmode_paused_msg', 'mod_videotrack'),
        'seeklockedmsg' => get_string('seek_locked_msg', 'mod_videotrack'),
        'seekunlockedmsg' => get_string('seek_unlocked_msg', 'mod_videotrack'),
        'isinline' => $isinline,
    ];
    return [
        'template' => $template,
        'tracker' => [
            $cm->id,
            (int)$videotrack->targetpercent,
            $source['isyoutube'],
            $source['ytid'],
            $percent,
            $resumetime,
            $allowedtime,
            (int)($videotrack->preventforward ?? 0),
            (int)($videotrack->focusmode ?? 0),
        ],
    ];
}


/**
 * Add a new instance of the videotrack activity.
 *
 * @param stdClass $videotrack The activity instance object.
 * @param mod_videotrack_mod_form|null $mform The form.
 * @return int The ID of the new instance.
 */
function videotrack_add_instance($videotrack, $mform = null) {
    global $DB;
    $videotrack->timecreated = time();
    $videotrack->timemodified = $videotrack->timecreated;

    $id = $DB->insert_record('videotrack', $videotrack);

    if (isset($videotrack->video)) {
        // In add_instance $videotrack->coursemodule is the ID of the newly created cm.
        $context = context_module::instance($videotrack->coursemodule);
        file_save_draft_area_files(
            $videotrack->video,
            $context->id,
            'mod_videotrack',
            'video',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }
    if (isset($videotrack->captions)) {
        $context = context_module::instance($videotrack->coursemodule);
        file_save_draft_area_files(
            $videotrack->captions,
            $context->id,
            'mod_videotrack',
            'captions',
            0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.vtt']]
        );
    }
    return $id;
}

/**
 * Update an existing instance of the videotrack activity.
 *
 * @param stdClass $videotrack The activity instance object.
 * @param mod_videotrack_mod_form|null $mform The form.
 * @return bool True on success.
 */
function videotrack_update_instance($videotrack, $mform = null) {
    global $DB, $CFG;
    $videotrack->timemodified = time();
    $videotrack->id = $videotrack->instance;

    $context = context_module::instance($videotrack->coursemodule);
    $oldrecord = $DB->get_record('videotrack', ['id' => $videotrack->id], '*', MUST_EXIST);
    $oldfingerprint = videotrack_source_fingerprint($oldrecord, $context);

    $DB->update_record('videotrack', $videotrack);

    if (isset($videotrack->video)) {
        file_save_draft_area_files(
            $videotrack->video,
            $context->id,
            'mod_videotrack',
            'video',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }
    if (isset($videotrack->captions)) {
        file_save_draft_area_files(
            $videotrack->captions,
            $context->id,
            'mod_videotrack',
            'captions',
            0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.vtt']]
        );
    }
    $newrecord = $DB->get_record('videotrack', ['id' => $videotrack->id], '*', MUST_EXIST);
    if ($oldfingerprint !== videotrack_source_fingerprint($newrecord, $context)) {
        $DB->delete_records('videotrack_progress', ['videotrackid' => $videotrack->id]);
        require_once($CFG->libdir . '/completionlib.php');
        $completion = new completion_info(get_course($newrecord->course));
        $cm = get_coursemodule_from_id('videotrack', $videotrack->coursemodule, 0, false, MUST_EXIST);
        $completion->reset_all_state($cm);
    }
    return true;
}

/**
 * Delete an instance of the videotrack activity.
 *
 * @param int $id The ID of the instance.
 * @return bool True on success.
 */
function videotrack_delete_instance($id) {
    global $DB;
    if (!$videotrack = $DB->get_record('videotrack', ['id' => $id])) {
        // If the record does not exist, we MUST return true.
        // Returning false would cause Moodle to get stuck in an infinite deletion loop.
        return true;
    }

    $DB->delete_records('videotrack_progress', ['videotrackid' => $videotrack->id]);

    // Get the CM and delete files BEFORE deleting the videotrack record.
    $cm = get_coursemodule_from_instance('videotrack', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_videotrack');
    }

    $DB->delete_records('videotrack', ['id' => $videotrack->id]);
    return true;
}

/**
 * Serves the files from the videotrack file areas.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context.
 * @param string $filearea The file area.
 * @param array $args The arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Options.
 * @return bool False if file not found, otherwise does not return.
 */
function videotrack_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videotrack:view', $context);
    if (!in_array($filearea, ['video', 'captions', 'intro'], true)) {
        return false;
    }

    $itemid = (int)array_shift($args);

    if (empty($args)) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_videotrack', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Indicates API features that the videotrack supports.
 *
 * @param string $feature The feature to check.
 * @return mixed True if supported, null if unknown.
 */
function videotrack_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Provides cached information used by course listings and custom completion.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|false
 */
function videotrack_get_coursemodule_info($coursemodule) {
    global $DB;

    $videotrack = $DB->get_record(
        'videotrack',
        ['id' => $coursemodule->instance],
        'id, name, intro, introformat, videourl, targetpercent, displaymode, focusmode, preventforward, accentcolor'
    );
    if (!$videotrack) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $videotrack->name;
    $result->customdata['targetpercent'] = (int)$videotrack->targetpercent;
    $result->customdata['displaymode'] = (int)$videotrack->displaymode;
    $result->customdata['focusmode'] = (int)$videotrack->focusmode;
    $result->customdata['preventforward'] = (int)($videotrack->preventforward ?? 0);
    $result->customdata['videourl'] = $videotrack->videourl ?? '';
    $result->customdata['accentcolor'] = $videotrack->accentcolor ?? '';

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC && $videotrack->targetpercent > 0) {
        $result->customdata['customcompletionrules']['targetpercent'] = (int)$videotrack->targetpercent;
    }

    // The standard display keeps Moodle's normal description behaviour.
    if ($coursemodule->showdescription && (int)$videotrack->displaymode === 0) {
        $result->content = format_module_intro('videotrack', $videotrack, $coursemodule->id, false);
    }

    return $result;
}

/**
 * Adjusts the course-page activity link when the popup card display is enabled.
 *
 * @param cm_info $cm Course module information.
 */
function videotrack_cm_info_dynamic(cm_info $cm) {
    global $PAGE;

    if (strpos((string)$PAGE->pagetype, 'course-view') !== 0) {
        return;
    }

    $mode = (int)($cm->customdata['displaymode'] ?? 0);
    if ($mode === 1) {
        // The custom card becomes the launch target on the course page.
        $cm->set_no_view_link();
        $cm->set_extra_classes('videotrack-popup-activity');
    } else if ($mode === 2) {
        // The embedded inline player renders directly in the course section.
        $cm->set_no_view_link();
        $cm->set_extra_classes('videotrack-inline-activity');
    }
}

/**
 * Renders the popup card or inline player on the main course page.
 *
 * @param cm_info $cm Course module information.
 */
function videotrack_cm_info_view(cm_info $cm) {
    global $PAGE;

    if (strpos((string)$PAGE->pagetype, 'course-view') !== 0) {
        return;
    }

    $mode = (int)($cm->customdata['displaymode'] ?? 0);
    if ($mode === 1) {
        $cm->set_content(videotrack_render_course_card($cm), true);
    } else if ($mode === 2) {
        $cm->set_content(videotrack_render_inline_player($cm), true);
    }
}

/**
 * Builds the inline embedded player for a VideoTrack activity directly on the course page.
 *
 * @param cm_info $cm Course module information.
 * @return string Rendered HTML.
 */
function videotrack_render_inline_player(cm_info $cm): string {
    global $DB, $OUTPUT, $PAGE, $USER, $CFG;

    $videotrack = $DB->get_record('videotrack', ['id' => $cm->instance], '*', MUST_EXIST);
    $player = videotrack_prepare_player($videotrack, $cm, $cm->context, '', true);
    $PAGE->requires->js_call_amd('mod_videotrack/tracker', 'init', $player['tracker']);

    // Rendering an inline player is a genuine module view, just like opening view.php.
    $event = \mod_videotrack\event\course_module_viewed::create([
        'objectid' => $videotrack->id,
        'context' => $cm->context,
        'courseid' => $cm->course,
        'userid' => $USER->id,
        'other' => ['instanceid' => $videotrack->id, 'cmid' => $cm->id],
    ]);
    $event->trigger();
    require_once($CFG->libdir . '/completionlib.php');
    $completion = new completion_info(get_course($cm->course));
    if ($completion->is_enabled($cm)) {
        $completion->set_module_viewed($cm, $USER->id);
    }

    return $OUTPUT->render_from_template('mod_videotrack/player', $player['template']);
}

/**
 * Builds the course-page card for a VideoTrack activity.
 *
 * @param cm_info $cm Course module information.
 * @return string Rendered HTML.
 */
function videotrack_render_course_card(cm_info $cm): string {
    global $DB, $OUTPUT, $USER;

    static $progresscache = [];
    if (!array_key_exists($cm->course, $progresscache)) {
        $sql = "SELECT p.videotrackid, p.highestpercent, p.iscompleted
                  FROM {videotrack_progress} p
                  JOIN {videotrack} v ON v.id = p.videotrackid
                 WHERE p.userid = :userid AND v.course = :courseid";
        $progresscache[$cm->course] = $DB->get_records_sql($sql, [
            'userid' => $USER->id,
            'courseid' => $cm->course,
        ]);
    }

    $progress = $progresscache[$cm->course][$cm->instance] ?? null;
    $currentpercent = $progress ? (int)$progress->highestpercent : 0;
    $targetpercent = (int)($cm->customdata['targetpercent'] ?? 0);
    $isfree = $targetpercent <= 0;
    $iscompleted = !$isfree && $currentpercent >= $targetpercent;

    $videourl = (string)($cm->customdata['videourl'] ?? '');
    $youtubeid = videotrack_get_youtube_id($videourl);
    $posterurl = $youtubeid !== '' ? 'https://i.ytimg.com/vi/' . rawurlencode($youtubeid) . '/hqdefault.jpg' : '';
    $accent = (string)($cm->customdata['accentcolor'] ?? '');

    $context = [
        'cmid' => $cm->id,
        'accentstyle' => preg_match('/^#[0-9a-f]{6}$/i', $accent) ? '--vt-accent:' . $accent . ';' : '',
        'name' => format_string($cm->name),
        'launchurl' => (new moodle_url('/mod/videotrack/view.php', ['id' => $cm->id, 'embed' => 1]))->out(false),
        'focusmode' => !empty($cm->customdata['focusmode']),
        'posterurl' => $posterurl,
        'hasposter' => $posterurl !== '',
        'currentpercent' => $currentpercent,
        'targetpercent' => $targetpercent,
        'iscompleted' => $iscompleted,
        'isfree' => $isfree,
        'launchlabel' => get_string('launchvideo', 'mod_videotrack'),
        'focuslabel' => get_string('focusmodebadge', 'mod_videotrack'),
        'progresstitle' => get_string('progresstitle', 'mod_videotrack'),
        'completiontext' => $isfree
            ? get_string('progressfree', 'mod_videotrack')
            : get_string('completiondetail:targetpercent', 'mod_videotrack', $targetpercent),
        'completedtext' => get_string('completed', 'mod_videotrack'),
    ];

    return $OUTPUT->render_from_template('mod_videotrack/coursecard', $context);
}

/**
 * Extends settings secondary course/activity navigation (tabs) with report.php for teachers.
 *
 * @param settings_navigation $settingsnav The settings navigation object.
 * @param navigation_node|null $node The navigation node.
 */
function videotrack_extend_settings_navigation(settings_navigation $settingsnav, ?navigation_node $node = null) {
    global $PAGE;
    $cm = $PAGE->cm;

    // If there is no course module information or the main node does not exist, exit.
    if (!$cm || !$node) {
        return;
    }

    $context = context_module::instance($cm->id);
    if (has_capability('mod/videotrack:viewreport', $context)) {
        $url = new moodle_url('/mod/videotrack/report.php', ['id' => $cm->id]);

        // Add the report node directly to the activity node.
        $reportnode = $node->add(
            get_string('report', 'mod_videotrack'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'videotrackreport',
            new pix_icon('i/report', '')
        );

        $reportnode->showinflatnavigation = true;
    }
}
