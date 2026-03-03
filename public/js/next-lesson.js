(function($) {
    'use strict';

    var SESSION_KEY = 'acmViewFullCourse';

    function isViewFullOn() {
        return sessionStorage.getItem(SESSION_KEY) === '1';
    }

    function setViewFullState(enabled) {
        sessionStorage.setItem(SESSION_KEY, enabled ? '1' : '0');

        var $roots = $('.acm-filter-root');
        if (enabled) {
            $roots.addClass('acm-view-full');
        } else {
            $roots.removeClass('acm-view-full');
        }

        $('[data-acm-toggle-full-course]').each(function() {
            var $button = $(this);
            $button.attr('data-acm-toggle-full-course', enabled ? 'on' : 'off');
            $button.find('.acm-toggle-label').text(enabled ? 'View My Course' : 'View Full Course');
        });
    }

    function normalizeUrl(url) {
        var anchor = document.createElement('a');
        anchor.href = url;
        return anchor.pathname + anchor.search + anchor.hash;
    }

    function getSequenceItems() {
        if (typeof acmLessonData !== 'undefined' && Array.isArray(acmLessonData.lessonSequence)) {
            return acmLessonData.lessonSequence;
        }

        return [];
    }

    function findNextVisibleLessonUrl(currentHref) {
        var sequence = getSequenceItems();
        if (!sequence.length) {
            return null;
        }

        var currentNormalized = normalizeUrl(currentHref);
        var currentIndex = -1;

        for (var i = 0; i < sequence.length; i++) {
            if (normalizeUrl(sequence[i].url) === currentNormalized) {
                currentIndex = i;
                break;
            }
        }

        if (currentIndex === -1) {
            return null;
        }

        for (var j = currentIndex + 1; j < sequence.length; j++) {
            if (!sequence[j].isHidden) {
                return sequence[j].url;
            }
        }

        return null;
    }

    $(document).ready(function() {
        setViewFullState(isViewFullOn());

        $(document).on('click', '[data-acm-toggle-full-course]', function(e) {
            e.preventDefault();
            setViewFullState(!isViewFullOn());
        });

        $(document).on('click', '.acm-next-lesson-btn', function(e) {
            if (isViewFullOn()) {
                return;
            }

            var currentHref = $(this).attr('href');
            if (!currentHref) {
                return;
            }

            var targetUrl = findNextVisibleLessonUrl(currentHref);
            if (!targetUrl) {
                return;
            }

            if (normalizeUrl(targetUrl) === normalizeUrl(currentHref)) {
                return;
            }

            e.preventDefault();
            window.location.href = targetUrl;
        });

        window.addEventListener('acmQuizSaved', function() {
            sessionStorage.setItem(SESSION_KEY, '0');
        });
    });
})(jQuery);
