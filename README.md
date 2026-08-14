# VideoTrack (mod_videotrack)

**VideoTrack** is a Moodle 4.5/5.2 activity module that embeds videos and tracks the unique playback intervals watched by each student. Teachers can condition activity completion on a minimum watched percentage.

> **Upstream review notice:** This repository contains an independent compatibility and
> hardening proposal for the original VideoTrack plugin. It is published so the original
> author can review, adapt, or merge the changes. It is not presented as an official
> upstream release.

## Key Features

* **Flexible Video Sources**: Supports embedding videos via external links (including YouTube, YouTube Shorts, Heygen, and other direct URLs), as well as direct video file uploads.
* **Server-validated Progress Tracking**: Stores merged playback intervals, avoids counting repeated sections twice, and rejects jumps as watched time.
* **Resume Playback**: A smart resume button allows students to return to the exact minute and second where they left off across multiple sessions.
* **Playback Control (No Forwarding)**: Prevents students from fast-forwarding or skipping ahead through parts of the video they have not yet watched when a completion target is configured.
* **Native Moodle Completion Rule**: Teachers can enable a custom "watch at least X%" rule directly in Moodle's **Activity completion** section.
* **Course-page Card + Popup**: Optionally display a video card on the main course page and launch the tracked player in a Moodle modal instead of navigating to a separate activity page.
* **Optional Focus Mode**: Teachers can make the popup use a distraction-reduced, near-fullscreen layout.
* **Detailed Progress Reports**: Provides teachers with a dashboard showing each student's highest playback percentage and current activity completion status.
* **Accessibility and Theme Options**: Supports an optional WebVTT caption file and an optional per-activity accent colour; otherwise it inherits the Moodle theme.

## How to Use It

### 1. Adding the Activity
1. In your Moodle course, turn on **Edit mode**.
2. Click **Add an activity or resource** in the desired section and select **VideoTrack**.
3. Provide an **Activity name**, and upload a video file or enter a supported external video URL.

### 2. Choosing the Course-page Display
Under **Display**, choose one of the following:

* **Standard activity page**: keeps the original VideoTrack behaviour.
* **Card on course page → popup player**: shows a launch card directly in the course section and opens the tracked player in a Moodle modal.
* **Player embedded directly on course page**: renders the player in the course section and still records Moodle's viewed event.

When popup display is selected, **Focus mode** can also be enabled to expand the player into a near-fullscreen, distraction-reduced modal.

### 3. Setting up the Completion Condition
1. Open the **Activity completion** section.
2. Set **Completion tracking** to **Show activity as complete when conditions are met**.
3. Enable **Student must watch at least** and enter the required percentage, for example `80`.
4. Optionally enable Moodle's standard **View the activity** condition as an additional requirement.
5. Save the activity. VideoTrack will notify Moodle's completion API as viewing progress changes.

If the percentage rule is not enabled, VideoTrack allows free navigation through the video.

### 4. Accessing the Progress Report
1. As a teacher or manager, open the VideoTrack activity.
2. In the secondary navigation menu (tabs), click **Progress report**.
3. The report shows each student's name, highest percentage watched, completion status, and last update time.

## Version 1.3.1

This maintenance release fixes a Moodle form dependency collision. The percentage
unit in the custom completion rule now has a unique element name, so switching
between no completion, manual completion, and automatic requirements no longer
hides the local video and WebVTT file managers.

## Version 1.3.0

This release adds interval-based progress, concurrent-save locking, source-change resets, Moodle 5.2 modal support, runtime HeyGen resolution, URL validation and local-file fallback, captions, accent colours, report paging, and complete backup/privacy coverage.

### Note to the original author

The work began after the previous package showed compatibility problems on Moodle 5.2,
including missing language strings, URL videos that no longer opened, stale compiled AMD
files, and completion settings that could produce inconsistent states.

The proposed 1.3.0 version changes the following areas:

* **Progress integrity:** the server now derives the percentage from merged playback
  intervals. Replayed intervals are counted once, forward jumps are not credited, client
  percentages are ignored, and concurrent updates use Moodle locks.
* **Reliable persistence:** the player sends heartbeats every three seconds and saves on
  pause, end, visibility changes, and page exit. Resume position and continuously watched
  time are stored separately.
* **Source handling:** YouTube privacy-enhanced URLs are supported; HeyGen share pages are
  resolved at display time with a short cache; unsupported page URLs are rejected; and an
  uploaded video acts as a fallback when an external source cannot be resolved.
* **Safe source replacement:** changing the video invalidates progress and recalculates
  Moodle completion so a learner cannot reuse completion from another video.
* **Moodle 5.2 UI:** the popup uses the current Modal and ModalEvents APIs, the card remains
  a working link without JavaScript, and inline display records the normal viewed event.
* **Completion migration:** automatic completion records with no active percentage or view
  rule are converted to Moodle's standard view condition instead of remaining incorrectly
  complete.
* **Theme and accessibility:** the interface inherits the Moodle theme, supports an optional
  per-activity accent colour, and accepts an optional WebVTT caption file for HTML5 videos.
* **Reports and data APIs:** the report is paginated, free-view activities are labelled not
  applicable, suspended enrolments are excluded, and backup, restore, and privacy exports
  include all new progress fields.
* **Packaging:** stale build output, the original ZIP, and a captured HeyGen page containing
  personal/signed URL data were removed from the public plugin directory.

Database changes are applied by upgrade step 2026081403. Existing percentage-only progress
is preserved and converted to a compatible initial interval when the video duration becomes
available.

Validation completed on Moodle 5.2.1+ with PHP 8.3 and MariaDB:

* 10 PHPUnit tests and 29 assertions;
* Moodle PHP CodeSniffer with zero errors and zero warnings;
* PHP syntax checks for every plugin file;
* ESLint/Rollup for AMD modules and Stylelint for CSS;
* Moodle database schema verification;
* routed HTTP checks for the Moodle 5.2 API and authentication boundary.

The GitHub CI matrix additionally targets Moodle 4.5 and 5.2 on MariaDB and PostgreSQL.
See [WORKFLOW_REVIEW.md](WORKFLOW_REVIEW.md) for the workflow assessment and release
requirements.

### Known limits

Server validation substantially improves progress integrity, but no browser-based player can
fully prevent a determined user from simulating valid real-time heartbeats with a modified
client. HeyGen resolution also depends on the structure of its public share page and may need
maintenance if that third-party format changes. YouTube captions remain managed by YouTube;
the uploaded WebVTT track applies to HTML5 video sources.

### Theme integration

VideoTrack uses Moodle/Bootstrap CSS variables so actions and progress indicators follow the active site's primary colour unless an activity accent colour is configured. The video canvas remains black for predictable media contrast.

### Uploaded video files

Uploaded videos are served through Moodle's standard `videotrack_pluginfile()` callback and keep normal course/module access control.
