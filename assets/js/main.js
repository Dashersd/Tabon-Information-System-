/**
 * The Art of Detail - Vanilla JavaScript Controller
 * Encapsulated in a single IIFE with no external dependencies.
 */
(function () {
    'use strict';

    function initArtOfDetail() {
        var section = document.querySelector('.art-of-detail-section');
        if (!section) return;

        var items = section.querySelectorAll('.art-accordion-item');
        var triggers = section.querySelectorAll('.art-accordion-trigger');
        var slides = section.querySelectorAll('.art-image-slide');
        var captionLeft = section.querySelector('.art-caption-left');

        if (!triggers.length || !slides.length) return;

        var currentIndex = 0;
        var captionTimeout = null;

        /**
         * Activates an accordion item and swaps corresponding image slide & caption.
         * @param {number} targetIndex - Index of item to activate
         * @param {boolean} setFocus - Whether to set keyboard focus on the trigger
         */
        function activateItem(targetIndex, setFocus) {
            if (targetIndex < 0 || targetIndex >= triggers.length) return;
            if (targetIndex === currentIndex && items[targetIndex].classList.contains('is-active')) {
                // Exactly one item is open at all times; clicking active item keeps it open
                if (setFocus) triggers[targetIndex].focus();
                return;
            }

            currentIndex = targetIndex;

            // 1. Update Accordion items & ARIA states
            triggers.forEach(function (trigger, idx) {
                var item = items[idx];
                var isActive = (idx === targetIndex);

                trigger.setAttribute('aria-expanded', isActive ? 'true' : 'false');
                if (isActive) {
                    item.classList.add('is-active');
                    if (setFocus) {
                        trigger.focus();
                    }
                } else {
                    item.classList.remove('is-active');
                }
            });

            // 2. Synchronize Image Stack
            slides.forEach(function (slide, idx) {
                if (idx === targetIndex) {
                    slide.classList.add('is-active');
                } else {
                    slide.classList.remove('is-active');
                }
            });

            // 3. Fade out, change caption text, and fade back in (~250ms total)
            if (captionLeft) {
                var newCaption = triggers[targetIndex].getAttribute('data-caption') || '';
                if (captionTimeout) {
                    clearTimeout(captionTimeout);
                }

                // Check for prefers-reduced-motion
                var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (prefersReducedMotion) {
                    captionLeft.textContent = newCaption;
                } else {
                    captionLeft.classList.add('is-fading');
                    captionTimeout = setTimeout(function () {
                        captionLeft.textContent = newCaption;
                        captionLeft.classList.remove('is-fading');
                    }, 125);
                }
            }
        }

        // Attach click and keyboard handlers
        triggers.forEach(function (trigger, idx) {
            // Click handler
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                activateItem(idx, false);
            });

            // Keyboard navigation (ArrowUp, ArrowDown, Home, End)
            trigger.addEventListener('keydown', function (e) {
                var nextIndex = null;

                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        nextIndex = (idx + 1) % triggers.length;
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        nextIndex = (idx - 1 + triggers.length) % triggers.length;
                        break;
                    case 'Home':
                        e.preventDefault();
                        nextIndex = 0;
                        break;
                    case 'End':
                        e.preventDefault();
                        nextIndex = triggers.length - 1;
                        break;
                    default:
                        break;
                }

                if (nextIndex !== null) {
                    activateItem(nextIndex, true);
                }
            });
        });

        // Initialize state (first item open on load)
        activateItem(0, false);
    }

    // Run on DOMContentLoaded or immediately if already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initArtOfDetail);
    } else {
        initArtOfDetail();
    }
})();

