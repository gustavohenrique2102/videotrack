// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course-page popup launcher for VideoTrack.
 *
 * @module     mod_videotrack/coursecard
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/modal', 'core/modal_events'], function(Modal, ModalEvents) {
    var initialised = false;

    var buildIframe = function(url, title) {
        var safeUrl = String(url).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        var safeTitle = String(title).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        return '<div class="videotrack-modal-framewrap">' +
            '<iframe class="videotrack-modal-frame" src="' + safeUrl + '" title="' + safeTitle + '" ' +
            'allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>' +
            '</div>';
    };

    var applyFocusClass = function(modal, focusmode) {
        var root = modal.getRoot();
        if (!root) {
            return;
        }
        if (root.classList) {
            root.classList.add('videotrack-player-modal');
            if (focusmode) {
                root.classList.add('videotrack-focus-modal');
            }
        } else if (root.addClass) {
            root.addClass('videotrack-player-modal');
            if (focusmode) {
                root.addClass('videotrack-focus-modal');
            }
        }
    };

    var launch = function(trigger) {
        var url = trigger.getAttribute('data-url');
        var title = trigger.getAttribute('data-title') || '';
        var focusmode = trigger.getAttribute('data-focus') === '1';

        Modal.create({
            title: title,
            body: buildIframe(url, title),
            large: true,
            scrollable: false,
            removeOnClose: true,
            returnElement: trigger
        }).then(function(modal) {
            applyFocusClass(modal, focusmode);

            var root = modal.getRoot();
            if (root && typeof root.on === 'function') {
                root.on(ModalEvents.hidden, function() {
                    var iframe = root.find('iframe.videotrack-modal-frame')[0];
                    if (iframe) {
                        iframe.src = 'about:blank';
                    }
                });
            }

            return modal.show();
        }).catch(function(err) {
            if (window.console && window.console.error) {
                window.console.error(err);
            }
            window.location.assign(url);
        });
    };

    var updateCard = function(data) {
        var card = document.querySelector('[data-videotrack-card="' + data.cmid + '"]');
        if (!card) {
            return;
        }
        var percent = Math.max(0, Math.min(100, Math.floor(Number(data.percent) || 0)));
        var bar = card.querySelector('[data-videotrack-progressbar]');
        var text = card.querySelector('[data-videotrack-percent]');
        var completed = card.querySelector('[data-videotrack-completed]');
        if (bar) {
            bar.style.width = percent + '%';
            bar.setAttribute('aria-valuenow', percent);
        }
        if (text) {
            text.textContent = percent + '%';
        }
        if (completed && data.completed) {
            completed.classList.remove('text-muted');
            completed.classList.add('text-success', 'font-weight-bold', 'fw-bold');
            completed.textContent = '';
            var icon = document.createElement('i');
            icon.className = 'fa fa-check-circle';
            icon.setAttribute('aria-hidden', 'true');
            completed.appendChild(icon);
            completed.appendChild(document.createTextNode(' ' + (data.completedtext || '')));
        }
    };

    return {
        init: function() {
            if (initialised) {
                return;
            }
            initialised = true;

            document.addEventListener('click', function(event) {
                var trigger = event.target.closest('[data-videotrack-launch]');
                if (!trigger) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                launch(trigger);
            }, true);

            window.addEventListener('message', function(event) {
                if (event.origin !== window.location.origin || !event.data || event.data.type !== 'mod_videotrack_progress') {
                    return;
                }
                updateCard(event.data);
            });
        }
    };
});
