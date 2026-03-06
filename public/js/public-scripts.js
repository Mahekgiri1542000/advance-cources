/**
 * Advanced Course Manager - Frontend JavaScript
 * File: public/js/public-scripts.js
 */

(function($) {
    'use strict';

    var ACM = {
        init: function() {
            this.lessonTracking();
            this.markComplete();
            this.tabNavigation();
            this.noteManagement();
            this.discussionManagement();
            this.videoTracking();
            this.bookmarkManagement();
            this.notificationCenter();
            this.agreementBuilder();
            this.clearQuizFilter();
        },

        // Track time spent on lesson
        lessonTracking: function() {
            if (typeof acmLessonData === 'undefined') return;

            var startTime = Date.now();
            var updateInterval = 30000; // Update every 30 seconds

            setInterval(function() {
                var timeSpent = Math.floor((Date.now() - startTime) / 1000);
                
                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_update_time_spent',
                        nonce: acmLessonData.nonce,
                        lesson_id: acmLessonData.lessonId,
                        seconds: 30
                    }
                });

                startTime = Date.now();
            }, updateInterval);
        },

        // Mark lesson as complete
        markComplete: function() {
            $('#acm-mark-complete').on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var lessonId = $btn.data('lesson-id');
                
                $btn.prop('disabled', true).text('Marking...');
                $('.loading-overlay').css('display', 'flex');

                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_mark_lesson_complete',
                        nonce: acmLessonData.nonce,
                        lesson_id: lessonId
                    },
                    success: function(response) {
                        if (response.success == true) {
                            $btn.removeClass('acm-btn-primary')
                                .addClass('acm-btn-success')
                                .html('✓ Completed');
                            
                            // Update progress bar
                            if (response.data.progress) {
                                var percentage = response.data.progress.percentage;
                                $('.acm-progress-fill').css('width', percentage + '%');
                                $('.acm-progress-text').text(percentage + '% Complete');
                            }

                            // Update navigation
                            $('.acm-lesson-item[data-lesson-id="' + lessonId + '"]')
                                .addClass('completed')
                                .find('.lesson-status').html('✓');

                            ACM.showNotification('Lesson completed successfully!', 'success');
                            $('.loading-overlay').css('display', 'none');
                            location.reload();
                        } else {
                            $btn.prop('disabled', false).text('Mark as Complete');
                            ACM.showNotification('Failed to mark lesson complete', 'error');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Mark as Complete');
                        ACM.showNotification('An error occurred', 'error');
                    }
                });
            });
        },

        // Tab navigation
        tabNavigation: function() {
            $('.acm-tab-nav a').on('click', function(e) {
                e.preventDefault();
                
                var targetTab = $(this).attr('href');
                
                // Update active states
                $('.acm-tab-nav a').removeClass('active');
                $(this).addClass('active');
                
                $('.acm-tab-content').removeClass('active');
                $(targetTab).addClass('active');
            });
        },

        // Note management
        noteManagement: function() {
            // Load notes
            this.loadNotes();

            // Save note
            $('#acm-save-note').on('click', function() {
                var content = $('#acm-note-input').val().trim();
                var isShared = $('#acm-share-note').is(':checked');

                if (!content) {
                    ACM.showNotification('Please enter a note', 'warning');
                    return;
                }

                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_save_note',
                        nonce: acmLessonData.nonce,
                        lesson_id: acmLessonData.lessonId,
                        content: content,
                        is_shared: isShared
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#acm-note-input').val('');
                            $('#acm-share-note').prop('checked', false);
                            ACM.loadNotes();
                            ACM.showNotification('Note saved', 'success');
                        }
                    }
                });
            });

            // Delete note
            $(document).on('click', '.acm-delete-note', function() {
                if (!confirm('Are you sure you want to delete this note?')) return;

                var noteId = $(this).data('note-id');

                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_delete_note',
                        nonce: acmLessonData.nonce,
                        note_id: noteId
                    },
                    success: function(response) {
                        if (response.success) {
                            ACM.loadNotes();
                            ACM.showNotification('Note deleted', 'success');
                        }
                    }
                });
            });
        },

        loadNotes: function() {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_get_notes',
                    nonce: acmLessonData.nonce,
                    lesson_id: acmLessonData.lessonId
                },
                success: function(response) {
                    if (response.success && response.data.notes) {
                        var notesHtml = '';
                        
                        response.data.notes.forEach(function(note) {
                            var isOwn = note.user_id == acmLessonData.userId;
                            notesHtml += '<div class="acm-note-item ' + (isOwn ? 'own' : 'shared') + '">';
                            notesHtml += '<div class="note-header">';
                            notesHtml += '<span class="note-date">' + note.created_date + '</span>';
                            if (note.is_shared) {
                                notesHtml += '<span class="note-badge">Shared</span>';
                            }
                            if (isOwn) {
                                notesHtml += '<button class="acm-delete-note" data-note-id="' + note.id + '">×</button>';
                            }
                            notesHtml += '</div>';
                            notesHtml += '<div class="note-content">' + note.note_content + '</div>';
                            notesHtml += '</div>';
                        });

                        $('#acm-notes-list').html(notesHtml || '<p class="no-notes">No notes yet</p>');
                    }
                }
            });
        },

        // Discussion management
        discussionManagement: function() {
            if (!acmLessonData.partnerId) return;

            // Load messages
            this.loadMessages();

            // Auto-refresh messages every 10 seconds
            setInterval(function() {
                ACM.loadMessages();
            }, 10000);

            // Send message
            $('#acm-send-message').on('click', function() {
                var message = $('#acm-message-input').val().trim();

                if (!message) {
                    ACM.showNotification('Please enter a message', 'warning');
                    return;
                }

                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_post_message',
                        nonce: acmLessonData.nonce,
                        lesson_id: acmLessonData.lessonId,
                        message: message
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#acm-message-input').val('');
                            ACM.loadMessages();
                        }
                    }
                });
            });

            // Enter key to send
            $('#acm-message-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    $('#acm-send-message').click();
                }
            });
        },

        loadMessages: function() {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_get_messages',
                    nonce: acmLessonData.nonce,
                    lesson_id: acmLessonData.lessonId
                },
                success: function(response) {
                    if (response.success && response.data.messages) {
                        var messagesHtml = '';
                        
                        response.data.messages.forEach(function(msg) {
                            var isOwn = msg.user_id == acmLessonData.userId;
                            messagesHtml += '<div class="acm-message ' + (isOwn ? 'own' : 'partner') + '">';
                            messagesHtml += '<div class="message-header">';
                            messagesHtml += '<span class="message-author">' + msg.display_name + '</span>';
                            messagesHtml += '<span class="message-time">' + msg.created_date + '</span>';
                            messagesHtml += '</div>';
                            messagesHtml += '<div class="message-content">' + msg.message + '</div>';
                            messagesHtml += '</div>';
                        });

                        var $messagesList = $('#acm-messages-list');
                        var shouldScroll = $messagesList.scrollTop() + $messagesList.innerHeight() >= $messagesList[0].scrollHeight - 50;
                        
                        $messagesList.html(messagesHtml || '<p class="no-messages">No messages yet</p>');
                        
                        if (shouldScroll) {
                            $messagesList.scrollTop($messagesList[0].scrollHeight);
                        }
                    }
                }
            });
        },

        // Video tracking
        videoTracking: function() {
            var player = document.getElementById('acm-video-player');
            if (!player) return;

            var lastUpdateTime = 0;
            var updateInterval = 5; // seconds

            // For HTML5 video
            if (player.tagName === 'VIDEO') {
                player.addEventListener('timeupdate', function() {
                    var currentTime = Math.floor(player.currentTime);
                    
                    if (currentTime - lastUpdateTime >= updateInterval) {
                        var percentage = Math.floor((player.currentTime / player.duration) * 100);
                        ACM.updateVideoProgress(percentage);
                        lastUpdateTime = currentTime;
                    }
                });
            }
        },

        updateVideoProgress: function(percentage) {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_update_video_progress',
                    nonce: acmLessonData.nonce,
                    lesson_id: acmLessonData.lessonId,
                    percentage: percentage
                }
            });
        },

        // Bookmark management
        bookmarkManagement: function() {
            $('#acm-add-bookmark').on('click', function() {
                var player = document.getElementById('acm-video-player');
                var timestamp = player ? Math.floor(player.currentTime) : null;

                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_save_bookmark',
                        nonce: acmLessonData.nonce,
                        lesson_id: acmLessonData.lessonId,
                        timestamp: timestamp,
                        title: 'Bookmark at ' + ACM.formatTime(timestamp)
                    },
                    success: function(response) {
                        if (response.success) {
                            ACM.showNotification('Bookmark saved', 'success');
                        }
                    }
                });
            });
        },

        // Notification center
        notificationCenter: function() {
            // Load notifications count
            this.updateNotificationCount();

            // Poll for new notifications every 30 seconds
            setInterval(function() {
                ACM.updateNotificationCount();
            }, 30000);
        },

        updateNotificationCount: function() {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_get_notifications',
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.count > 0) {
                        $('.acm-notification-badge').text(response.data.count).show();
                    } else {
                        $('.acm-notification-badge').hide();
                    }
                }
            });
        },

        // Helper: Show notification
        showNotification: function(message, type) {
            var $notification = $('<div class="acm-notification acm-notification-' + type + '">' + message + '</div>');
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);

            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },

        // Helper: Format time
        formatTime: function(seconds) {
            var mins = Math.floor(seconds / 60);
            var secs = seconds % 60;
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        },
        
        // Agreement builder
        agreementBuilder: function() {
            // Handle agreement option checkboxes
            $(document).on('change', '.acm-agreement-choice', function() {
                var $checkbox = $(this);
                var isChecked = $checkbox.is(':checked');
                
                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_save_agreement_choice',
                        nonce: acmLessonData.nonce,
                        section_id: $checkbox.data('section-id'),
                        section_title: $checkbox.data('section-title'),
                        section_order: $checkbox.data('section-order'),
                        choice_id: $checkbox.data('choice-id'),
                        choice_text: $checkbox.data('choice-text'),
                        choice_value: $checkbox.data('choice-value') || $checkbox.data('choice-text'),
                        choice_order: $checkbox.data('choice-order'),
                        is_checked: isChecked
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update agreement builder count if visible
                            $('.agreement-count').text(response.data.total_choices);
                            
                            // Show subtle feedback
                            if (isChecked) {
                                ACM.showNotification('Added to your agreement', 'success');
                            }
                        }
                    }
                });
            });
        },

        clearQuizFilter: function() {
            $(document).on('click', '.acm-clear-quiz-filter-btn', function(e) {
                e.preventDefault();

                var $btn = $(this);
                var nonce = $btn.data('nonce') || (typeof acmLessonData !== 'undefined' ? acmLessonData.nonce : '');
                var ajaxUrl = $btn.data('ajax-url') || (typeof acmLessonData !== 'undefined' ? acmLessonData.ajaxUrl : '');
                var $container = $btn.closest('.acm-personalize-prompt, .course-start-card');
                var $status = $container.find('.acm-clear-filter-status');

                if (!$status.length) {
                    $status = $('<div class="acm-clear-filter-status" style="margin-top:10px;font-size:13px;color:#4a5568;"><div class="acm-clear-filter-progress" style="height:6px;background:#e5e7eb;border-radius:6px;overflow:hidden;"><span style="display:block;height:100%;width:0;background:#db9563;transition:width 0.2s ease;"></span></div><div class="acm-clear-filter-message" style="margin-top:6px;"></div></div>');
                    $btn.after($status);
                }

                var $progress = $status.find('.acm-clear-filter-progress span');
                var $message = $status.find('.acm-clear-filter-message');
                var progressValue = 8;
                var progressTimer = null;

                function updateStatus(message, color, percent) {
                    if (typeof percent === 'number') {
                        $progress.css('width', percent + '%');
                    }
                    if (color) {
                        $message.css('color', color);
                    }
                    $message.text(message);
                }

                function buildFallbackUrl() {
                    try {
                        var url = new URL(window.location.href);
                        url.searchParams.set('acm_clear_quiz_filter', '1');
                        url.searchParams.set('_acm_nonce', nonce);
                        url.searchParams.delete('acm_related_only');
                        return url.toString();
                    } catch (error) {
                        return window.location.href;
                    }
                }

                if (!nonce || !ajaxUrl) {
                    updateStatus('Unable to clear filter right now. Please refresh and try again.', '#b91c1c', 100);
                    return;
                }

                $btn.prop('disabled', true);
                updateStatus('Clearing quiz filter...', '#4a5568', progressValue);

                progressTimer = setInterval(function() {
                    if (progressValue < 90) {
                        progressValue += 8;
                        updateStatus('Clearing quiz filter...', '#4a5568', progressValue);
                    }
                }, 180);

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_clear_personalization_filter',
                        nonce: nonce,
                        current_url: window.location.href
                    },
                    success: function(response) {
                        if (response && response.success) {
                            var redirectUrl = response.data && response.data.redirect_url
                                ? response.data.redirect_url
                                : window.location.href;

                            if (progressTimer) {
                                clearInterval(progressTimer);
                            }

                            updateStatus('Quiz filter cleared. Redirecting...', '#166534', 100);

                            try {
                                var url = new URL(redirectUrl, window.location.origin);
                                url.searchParams.delete('acm_related_only');
                                url.searchParams.delete('acm_clear_quiz_filter');
                                url.searchParams.delete('_acm_nonce');
                                setTimeout(function() {
                                    window.location.href = url.toString();
                                }, 300);
                            } catch (error) {
                                setTimeout(function() {
                                    window.location.href = redirectUrl;
                                }, 300);
                            }
                            return;
                        }

                        if (progressTimer) {
                            clearInterval(progressTimer);
                        }

                        updateStatus('Could not clear via AJAX. Retrying...', '#b45309', 95);
                        setTimeout(function() {
                            window.location.href = buildFallbackUrl();
                        }, 400);
                        $btn.prop('disabled', false);
                    },
                    error: function() {
                        if (progressTimer) {
                            clearInterval(progressTimer);
                        }

                        updateStatus('Connection issue. Retrying...', '#b45309', 95);
                        setTimeout(function() {
                            window.location.href = buildFallbackUrl();
                        }, 400);
                        $btn.prop('disabled', false);
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ACM.init();
    });

})(jQuery);