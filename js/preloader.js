/**
 * ==============================================================================
 * Mobile Legends: Bang Bang (MLBB) Inspired Animated Preloader Controller
 * Project: Barangay Tabon Information System
 * ==============================================================================
 */

(function () {
    'use strict';

    if (window.__mlPreloaderInitialized) return;
    window.__mlPreloaderInitialized = true;

    if (window.mlPreloaderEnabled === false) {
        if (document.documentElement) document.documentElement.classList.remove('ml-preloader-active');
        if (document.body) document.body.classList.remove('ml-preloader-active');
        var p = document.getElementById('ml-preloader');
        if (p && p.parentNode) p.parentNode.removeChild(p);
        // Dispatch resize so underlying carousels and observers recalculate cleanly
        window.dispatchEvent(new Event('resize'));
        return;
    }

    // Immediately lock scroll and hide page content
    if (document.documentElement) {
        document.documentElement.classList.add('ml-preloader-active');
    }
    if (document.body) {
        document.body.classList.add('ml-preloader-active');
    }

    function initPreloader() {
        var preloader = document.getElementById('ml-preloader');
        if (!preloader) {
            if (document.documentElement) document.documentElement.classList.remove('ml-preloader-active');
            if (document.body) document.body.classList.remove('ml-preloader-active');
            return;
        }

        // Ensure page is pinned to top and locked
        document.documentElement.classList.add('ml-preloader-active');
        if (document.body) {
            document.body.classList.add('ml-preloader-active');
        }
        window.scrollTo(0, 0);

        var isReloadNav = false;
        try {
            if (window.performance && window.performance.getEntriesByType) {
                var navEntries = window.performance.getEntriesByType("navigation");
                if (navEntries.length > 0 && navEntries[0].type === "reload") {
                    isReloadNav = true;
                }
            } else if (window.performance && window.performance.navigation) {
                if (window.performance.navigation.type === 1) {
                    isReloadNav = true;
                }
            }
        } catch (e) {}

        var startTime = (window.performance && window.performance.now) ? performance.now() : Date.now();
        // Allow both Phase 1 (0-1.6s intro) AND Phase 2 (1.6-3.8s MLBB loading screen stage) to play completely
        var minDisplayTime = 3800;
        var maxTimeout = parseInt(preloader.getAttribute('data-timeout'), 10) || 4500;
        var isDismissed = false;

        // Preloader Audio fallback check
        var audio = document.getElementById('ml-preloader-audio');
        if (audio && audio.paused && !isDismissed) {
            audio.play().catch(function () { });
        }

        function dismissPreloader() {
            if (isDismissed) return;
            isDismissed = true;
            window.dispatchEvent(new Event('mlPreloaderDismissed'));

            // Fade out audio smoothly over 300ms if playing
            if (audio && !audio.paused) {
                var fadeAudio = setInterval(function () {
                    if (audio.volume > 0.15) {
                        audio.volume = Math.max(0, audio.volume - 0.15);
                    } else {
                        clearInterval(fadeAudio);
                        audio.pause();
                    }
                }, 40);
            }

            // Unlock html/body and restore underlying content visibility
            document.documentElement.classList.remove('ml-preloader-active');
            if (document.body) {
                document.body.classList.remove('ml-preloader-active');
            }
            window.scrollTo(0, 0);

            // Add dismissed class for smooth 0.5s fade out
            preloader.classList.add('ml-preloader-dismissed');

            // Dispatch resize so underlying carousels and observers recalculate cleanly
            window.dispatchEvent(new Event('resize'));

            var cleanedUp = false;
            function cleanUp() {
                if (cleanedUp) return;
                cleanedUp = true;
                if (preloader && preloader.parentNode) {
                    preloader.parentNode.removeChild(preloader);
                }
            }

            // Remove from DOM upon transition end or fallback timer
            preloader.addEventListener('transitionend', cleanUp);
            setTimeout(cleanUp, 600);
        }

        function scheduleDismissal() {
            var now = (window.performance && window.performance.now) ? performance.now() : Date.now();
            var elapsed = now - startTime;
            var remaining = minDisplayTime - elapsed;

            if (remaining <= 0) {
                dismissPreloader();
            } else {
                setTimeout(dismissPreloader, remaining);
            }
        }

        // Dismiss when all assets are loaded or immediately if already complete
        if (document.readyState === 'complete') {
            scheduleDismissal();
        } else {
            window.addEventListener('load', scheduleDismissal);
        }

        // Hard safety timeout fallback
        setTimeout(dismissPreloader, maxTimeout);
    }

    // Initialize immediately if DOM is ready, otherwise on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPreloader);
    } else {
        initPreloader();
    }
})();
