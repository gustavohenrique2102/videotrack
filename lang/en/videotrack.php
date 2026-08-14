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


defined('MOODLE_INTERNAL') || die();

$string['accentcolor'] = 'Accent colour';
$string['accentcolor_help'] = 'Optional hexadecimal colour (for example #0f6cbf). Leave blank to inherit the Moodle theme.';
$string['backtocourse'] = 'Back to course';
$string['captions'] = 'Captions (WebVTT)';
$string['captions_help'] = 'Optional .vtt caption file for locally hosted or direct-link videos.';
$string['completed'] = 'Completed';
$string['completiondetail:targetpercent'] = 'Watch at least {$a}% of the video';
$string['completionpercenterror'] = 'The required viewing percentage must be between 1 and 100.';
$string['completionwatchpercent'] = 'Student must watch at least';
$string['completionwatchpercent_help'] = 'When enabled, Moodle marks the activity complete only after the student reaches the selected viewing percentage.';
$string['displaymode'] = 'Course page display';
$string['displaymode:inline'] = 'Player embedded directly on course page';
$string['displaymode:page'] = 'Standard activity page';
$string['displaymode:popup'] = 'Card on course page (opens player in popup)';
$string['displaymode_help'] = 'Choose whether VideoTrack opens on its normal activity page, is launched from a card in a popup, or is embedded directly in the course section.';
$string['displaysettings'] = 'Display and playback';
$string['error_accentcolor'] = 'Enter a valid hexadecimal colour such as #0f6cbf, or leave the field blank.';
$string['error_nouploadorurl'] = 'You must either provide a Video URL or upload a Video File.';
$string['error_novideosupport'] = 'Your browser does not support HTML5 video.';
$string['error_progresslock'] = 'Progress is already being updated. Wait a moment and try again.';
$string['error_sourceplayback'] = 'The video could not be played. Check the URL, access permissions, and video format.';
$string['error_unsupportedurl'] = 'Use a YouTube or HeyGen share URL, a direct video URL (MP4, WebM, OGG, M4V or MOV), or upload a video file.';
$string['eventcoursemoduleviewed'] = 'VideoTrack course module viewed';
$string['focusmode'] = 'Enable focus mode';
$string['focusmode_help'] = 'When enabled, popup display uses a distraction-reduced full-screen layout and playback pauses if the learner leaves the active tab or window.';
$string['focusmode_paused_msg'] = 'Video paused because you switched tabs or minimized the window. Return to this tab to resume.';
$string['focusmode_paused_title'] = 'Focus Mode Active';
$string['focusmodebadge'] = 'Focus mode';
$string['highestpercent'] = 'Highest Percent Watched';
$string['lastaccess'] = 'Last Access';
$string['launchvideo'] = 'Watch video';
$string['modulename'] = 'VideoTrack';
$string['modulename_help'] = 'The VideoTrack activity allows you to embed a video and require the student to watch a specific percentage.';
$string['modulenameplural'] = 'VideoTracks';
$string['noresponses'] = 'No progress recorded yet for this video.';
$string['notapplicable'] = 'Not applicable';
$string['openvideo'] = 'Open video';
$string['pluginadministration'] = 'VideoTrack administration';
$string['pluginname'] = 'VideoTrack';
$string['preventforward'] = 'Lock forward scrubbing until completion';
$string['preventforward_help'] = 'When enabled, students cannot skip forward past what they have already watched until they reach the required completion percentage. Once completed, free navigation is automatically unlocked.';
$string['privacy:metadata:videotrack_progress'] = 'Stores the user\'s video playback progress and completion status.';
$string['privacy:metadata:videotrack_progress:duration'] = 'The duration reported by the video player.';
$string['privacy:metadata:videotrack_progress:highestpercent'] = 'The highest percentage of the video the user has watched.';
$string['privacy:metadata:videotrack_progress:highesttime'] = 'The highest video playback time reached by the user in seconds.';
$string['privacy:metadata:videotrack_progress:iscompleted'] = 'Whether the user has completed the required target percent.';
$string['privacy:metadata:videotrack_progress:lastheartbeat'] = 'The time of the latest playback heartbeat.';
$string['privacy:metadata:videotrack_progress:lastposition'] = 'The latest playback position used to resume the video.';
$string['privacy:metadata:videotrack_progress:timecreated'] = 'The time the progress record was created.';
$string['privacy:metadata:videotrack_progress:timemodified'] = 'The time the progress record was last modified.';
$string['privacy:metadata:videotrack_progress:userid'] = 'The ID of the user.';
$string['privacy:metadata:videotrack_progress:watchedsegments'] = 'The non-overlapping video intervals that the user watched.';
$string['progressfree'] = 'This video is for free exploration. You can watch it and skip ahead at your own pace.';
$string['progresshint'] = 'You must watch at least <strong>{$a}%</strong> of the video to complete this activity.';
$string['progresstitle'] = 'Viewing progress';
$string['progressupdated'] = 'Progress last updated';

$string['report'] = 'Progress Report';
$string['resumebutton'] = 'Resume from {$a}';
$string['resumeplayback'] = 'Resume video';
$string['seek_locked_msg'] = 'Forward skipping is locked until you complete the required view percentage.';
$string['seek_unlocked_msg'] = 'Congratulations! Activity completed and free navigation is now unlocked.';
$string['student'] = 'Student';
$string['successmsg'] = 'Congratulations! You have reached the required percentage. You may now continue.';
$string['targetmarker'] = 'Required target: {$a}%';
$string['targetpercent'] = 'Required percentage (%)';
$string['targetpercent_help'] = 'The percentage of the video the student must watch to complete the activity (default is 80%). Enter 0 if you want the video to be free and allow fast-forwarding without restrictions.';
$string['videofile'] = 'Video File (Local)';
$string['videofile_help'] = 'Upload your MP4 video file here. Note: If you enter an external URL above, it will be prioritized over this file.';
$string['videosettings'] = 'Video source';
$string['videotrack:addinstance'] = 'Add a new VideoTrack';
$string['videotrack:view'] = 'View VideoTrack';
$string['videotrack:viewreport'] = 'View progress report';
$string['videourl'] = 'Video URL (External)';
$string['videourl_help'] = 'Paste the YouTube link or a direct MP4 URL here. If you prefer to upload a file directly to Moodle, leave this blank and use the file uploader below.';
