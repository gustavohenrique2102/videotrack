// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Track server-validated VideoTrack playback heartbeats.
 *
 * @module     mod_videotrack/tracker
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* global YT */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    var youtubeApiPromise = null;

    var loadYouTubeApi = function() {
        if (window.YT && typeof window.YT.Player === 'function') {
            return Promise.resolve(window.YT);
        }
        if (youtubeApiPromise) {
            return youtubeApiPromise;
        }

        youtubeApiPromise = new Promise(function(resolve, reject) {
            if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
                var script = document.createElement('script');
                script.src = 'https://www.youtube.com/iframe_api';
                script.async = true;
                document.head.appendChild(script);
            }
            var attempts = 0;
            var timer = window.setInterval(function() {
                attempts++;
                if (window.YT && typeof window.YT.Player === 'function') {
                    window.clearInterval(timer);
                    resolve(window.YT);
                } else if (attempts >= 200) {
                    window.clearInterval(timer);
                    reject(new Error('YouTube IFrame API did not load.'));
                }
            }, 100);
        });
        return youtubeApiPromise;
    };

    return {
        init: function(
            cmid,
            targetPercent,
            isYouTube,
            videoId,
            currentPercent,
            resumeTime,
            allowedTime,
            preventForward,
            focusMode
        ) {
            var container = $('[data-videotrack-theater="' + cmid + '"]');
            if (!container.length) {
                return;
            }

            var htmlVideo = container.find('[data-vt-role="html5-player"]')[0] || null;
            var youtubeElement = container.find('[data-vt-role="youtube-player"]')[0] || null;
            var ytPlayer = null;
            var heartbeatTimer = null;
            var seekTimer = null;
            var inFlight = false;
            var saveQueued = false;
            var isPlaying = false;
            var completed = targetPercent > 0 && Number(currentPercent) >= targetPercent;
            var seekLocked = !completed && targetPercent > 0 && Boolean(preventForward);
            var serverPercent = Math.max(0, Math.min(100, Number(currentPercent) || 0));
            var serverAllowedTime = Math.max(0, Number(allowedTime) || 0);
            var savedResumeTime = Math.max(0, Number(resumeTime) || 0);
            var completedText = container.attr('data-completed-text') || '';

            var getCurrentTime = function() {
                if (isYouTube && ytPlayer && typeof ytPlayer.getCurrentTime === 'function') {
                    return Math.max(0, Number(ytPlayer.getCurrentTime()) || 0);
                }
                return htmlVideo ? Math.max(0, Number(htmlVideo.currentTime) || 0) : 0;
            };

            var getDuration = function() {
                var duration = 0;
                if (isYouTube && ytPlayer && typeof ytPlayer.getDuration === 'function') {
                    duration = Number(ytPlayer.getDuration()) || 0;
                } else if (htmlVideo) {
                    duration = Number(htmlVideo.duration) || 0;
                }
                return Number.isFinite(duration) ? Math.max(0, duration) : 0;
            };

            var notifyParent = function() {
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'mod_videotrack_progress',
                        cmid: cmid,
                        percent: Math.floor(serverPercent),
                        completed: completed,
                        completedtext: completedText
                    }, window.location.origin);
                }
            };

            var unlockSeeking = function() {
                seekLocked = false;
                container.find('[data-vt-role="seek-locked-msg"]').slideUp('fast');
                var badge = container.find('[data-vt-role="lock-badge"]');
                badge.removeClass('vt-badge-locked').addClass('vt-badge-unlocked');
                badge.find('i').removeClass('fa-lock').addClass('fa-unlock');
                badge.find('[data-vt-role="lock-text"]').text(completedText);
            };

            var updateUI = function(result) {
                serverPercent = Math.max(0, Math.min(100, Number(result.percent) || 0));
                serverAllowedTime = Math.max(serverAllowedTime, Number(result.allowedtime) || 0);
                savedResumeTime = Math.max(0, Number(result.resumetime) || savedResumeTime);
                completed = Boolean(result.completed);

                container.find('[data-vt-role="progress-bar"]')
                    .css('width', serverPercent + '%')
                    .attr('aria-valuenow', Math.floor(serverPercent));
                container.find('[data-vt-role="progress-text"]').text(Math.floor(serverPercent) + '%');
                if (completed) {
                    unlockSeeking();
                    container.find('[data-vt-role="success-msg"]').removeClass('d-none');
                }
                notifyParent();
            };

            var heartbeatArgs = function() {
                return {
                    cmid: cmid,
                    currenttime: Math.max(0, Math.floor(getCurrentTime())),
                    duration: Math.max(0, Math.floor(getDuration())),
                    percent: 0
                };
            };

            var saveProgress = function() {
                var args = heartbeatArgs();
                if (args.duration <= 0) {
                    return;
                }
                if (inFlight) {
                    saveQueued = true;
                    return;
                }

                inFlight = true;
                Ajax.call([{
                    methodname: 'mod_videotrack_save_progress',
                    args: args
                }])[0].then(updateUI).catch(Notification.exception).then(function() {
                    inFlight = false;
                    if (saveQueued) {
                        saveQueued = false;
                        saveProgress();
                    }
                    return null;
                }).catch(Notification.exception);
            };

            var startHeartbeats = function() {
                isPlaying = true;
                saveProgress();
                if (!heartbeatTimer) {
                    heartbeatTimer = window.setInterval(saveProgress, 3000);
                }
            };

            var stopHeartbeats = function() {
                isPlaying = false;
                if (heartbeatTimer) {
                    window.clearInterval(heartbeatTimer);
                    heartbeatTimer = null;
                }
                saveProgress();
            };

            var enforceSeekLimit = function() {
                if (!seekLocked) {
                    return;
                }
                var current = getCurrentTime();
                if (current <= serverAllowedTime + 5) {
                    return;
                }
                if (isYouTube && ytPlayer && typeof ytPlayer.seekTo === 'function') {
                    ytPlayer.seekTo(serverAllowedTime, true);
                } else if (htmlVideo) {
                    htmlVideo.currentTime = serverAllowedTime;
                }
            };

            var pauseForFocus = function() {
                if (!focusMode || !isPlaying) {
                    return;
                }
                if (isYouTube && ytPlayer && typeof ytPlayer.pauseVideo === 'function') {
                    ytPlayer.pauseVideo();
                } else if (htmlVideo && !htmlVideo.paused) {
                    htmlVideo.pause();
                }
                container.find('[data-vt-role="focus-overlay"]').addClass('is-visible');
            };

            var resumePlayback = function(time) {
                var position = Math.max(0, Number(time) || 0);
                if (seekLocked) {
                    position = Math.min(position, serverAllowedTime);
                }
                container.find('[data-vt-role="focus-overlay"]').removeClass('is-visible');
                if (isYouTube && ytPlayer) {
                    ytPlayer.seekTo(position, true);
                    ytPlayer.playVideo();
                } else if (htmlVideo) {
                    htmlVideo.currentTime = position;
                    htmlVideo.play().catch(function() {
                        // Browser autoplay policy may require a second explicit click.
                    });
                }
            };

            container.on('click', '[data-vt-role="resume-progress"]', function() {
                resumePlayback(savedResumeTime);
                $(this).closest('.vt-resume-wrapper').slideUp('fast');
            });
            container.on('click', '[data-vt-role="resume-focus"]', function() {
                resumePlayback(getCurrentTime());
            });

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    pauseForFocus();
                    saveProgress();
                }
            });
            window.addEventListener('blur', pauseForFocus);
            window.addEventListener('pagehide', function() {
                var args = heartbeatArgs();
                if (args.duration <= 0 || !window.fetch || typeof M === 'undefined') {
                    return;
                }
                window.fetch(M.cfg.wwwroot + '/lib/ajax/service.php?sesskey=' +
                    encodeURIComponent(M.cfg.sesskey) + '&info=mod_videotrack_save_progress', {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify([{index: 0, methodname: 'mod_videotrack_save_progress', args: args}])
                }).catch(function() {
                    // The regular heartbeat already limits any unsaved interval to about three seconds.
                });
            });

            if (htmlVideo) {
                htmlVideo.addEventListener('loadedmetadata', saveProgress);
                htmlVideo.addEventListener('play', startHeartbeats);
                htmlVideo.addEventListener('pause', stopHeartbeats);
                htmlVideo.addEventListener('ended', stopHeartbeats);
                htmlVideo.addEventListener('seeking', enforceSeekLimit);
                htmlVideo.addEventListener('error', function() {
                    container.find('[data-vt-role="source-error"]').removeClass('d-none');
                });
            } else if (isYouTube && youtubeElement) {
                loadYouTubeApi().then(function() {
                    ytPlayer = new YT.Player(youtubeElement.id, {
                        videoId: videoId,
                        playerVars: {rel: 0, modestbranding: 1, playsinline: 1},
                        events: {
                            onReady: saveProgress,
                            onStateChange: function(event) {
                                if (event.data === YT.PlayerState.PLAYING) {
                                    startHeartbeats();
                                } else if (event.data === YT.PlayerState.PAUSED ||
                                        event.data === YT.PlayerState.ENDED) {
                                    stopHeartbeats();
                                }
                            },
                            onError: function() {
                                container.find('[data-vt-role="source-error"]').removeClass('d-none');
                            }
                        }
                    });
                    seekTimer = window.setInterval(enforceSeekLimit, 500);
                    return null;
                }).catch(function(error) {
                    container.find('[data-vt-role="source-error"]').removeClass('d-none');
                    Notification.exception(error);
                });
            }

            window.addEventListener('unload', function() {
                if (heartbeatTimer) {
                    window.clearInterval(heartbeatTimer);
                }
                if (seekTimer) {
                    window.clearInterval(seekTimer);
                }
            });
        }
    };
});
