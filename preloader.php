<style>
    /* Instant zero-delay scrollbar removal */
    html.ml-preloader-active,
    body.ml-preloader-active {
        overflow: hidden !important;
        height: 100% !important;
        max-height: 100vh !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    html.ml-preloader-active::-webkit-scrollbar,
    body.ml-preloader-active::-webkit-scrollbar,
    #ml-preloader::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        background: transparent !important;
    }
</style>
<script>
    var isReload = false;
    if (window.performance && window.performance.getEntriesByType) {
        var navEntries = window.performance.getEntriesByType("navigation");
        if (navEntries.length > 0 && navEntries[0].type === "reload") {
            isReload = true;
        }
    } else if (window.performance && window.performance.navigation) {
        if (window.performance.navigation.type === 1) {
            isReload = true;
        }
    }

    var shouldShowPreloader = !sessionStorage.getItem('preloaderShown') || isReload;
    window.mlPreloaderEnabled = shouldShowPreloader;

    if (shouldShowPreloader) {
        sessionStorage.setItem('preloaderShown', 'true');
        document.documentElement.classList.add('ml-preloader-active');
        if (document.body) {
            document.body.classList.add('ml-preloader-active');
        }
        window.scrollTo(0, 0);
    } else {
        document.write('<style>#ml-preloader { display: none !important; }</style>');
        document.documentElement.classList.remove('ml-preloader-active');
        if (document.body) {
            document.body.classList.remove('ml-preloader-active');
        }
    }
</script>
<!-- Mobile Legends: Bang Bang (MLBB) Inspired Preloader (HTML Only) -->
<div id="ml-preloader" class="ml-preloader-container" data-timeout="4500" aria-label="Loading page..." role="status">
    <!-- Preloader Audio Track -->
    <audio id="ml-preloader-audio" preload="auto" playsinline>
        <source src="Audio/Loading%20Audio.mp3" type="audio/mpeg">
        <source src="Audio/Loading Audio.mp3" type="audio/mpeg">
    </audio>

    <!-- Immediate Audio Trigger (Plays automatically when Phase 1 starts) -->
    <script>
        (function() {
            if (!window.mlPreloaderEnabled) return;
            var audio = document.getElementById('ml-preloader-audio');
            if (!audio) return;
            audio.currentTime = 0;
            audio.volume = 1.0;

            function autoPlayAudio() {
                var p = audio.play();
                if (p !== undefined) {
                    p.catch(function() {
                        // If browser autoplay policy held unmuted sound, resume automatically on any initial movement/interaction
                        var unlock = function() {
                            if (audio && audio.paused) {
                                audio.play().catch(function() {});
                            }
                            ['pointermove', 'mousemove', 'wheel', 'touchstart', 'pointerdown', 'keydown'].forEach(function(evt) {
                                window.removeEventListener(evt, unlock, true);
                            });
                        };
                        ['pointermove', 'mousemove', 'wheel', 'touchstart', 'pointerdown', 'keydown'].forEach(function(evt) {
                            window.addEventListener(evt, unlock, { once: true, passive: true, capture: true });
                        });
                    });
                }
            }
            autoPlayAudio();
        })();
    </script>

    <!-- PHASE 1: LOGO INTRO (0.0s - 1.6s) -->
    <div class="ml-preloader-intro">
        <div class="ml-preloader-intro-lockup">
            <div class="ml-preloader-intro-mark-wrap">
                <div class="ml-preloader-intro-glow"></div>
                <img 
                    src="Barangay Logo/Logo.png" 
                    alt="Barangay Tabon Logo" 
                    class="ml-preloader-intro-mark"
                >
            </div>
            <div class="ml-preloader-intro-text-group">
                <div class="ml-preloader-intro-title">
                    Barangay Tabon
                </div>
                <div class="ml-preloader-intro-subtitle">
                    Information System
                </div>
            </div>
        </div>
    </div>

    <!-- PHASE 2: MLBB LOADING SCREEN STAGE -->
    <div class="ml-preloader-stage">
        <!-- Atmospheric background and vignette -->
        <div class="ml-preloader-bg"></div>
        <div class="ml-preloader-overlay"></div>


        <!-- Center Stage: Ring, Focal Image & Accent Text -->
        <div class="ml-preloader-center">
            <div class="ml-preloader-ring-wrapper">
                <!-- Staggered accent lettering inside/behind ring -->
                <div class="ml-preloader-accent-wrap" aria-hidden="true">
                    <span class="ml-preloader-accent-letter" style="animation-delay: 1.7s;">T</span>
                    <span class="ml-preloader-accent-letter" style="animation-delay: 1.8s;">A</span>
                    <span class="ml-preloader-accent-letter" style="animation-delay: 1.9s;">B</span>
                    <span class="ml-preloader-accent-letter" style="animation-delay: 2.0s;">O</span>
                    <span class="ml-preloader-accent-letter" style="animation-delay: 2.1s;">N</span>
                </div>

                <!-- Central focal seal/portrait -->
                <img 
                    src="Barangay Logo/Logo.png" 
                    alt="Barangay Seal" 
                    class="ml-preloader-focal-img"
                >

                <!-- Rotating & pulsing glowing ring -->
                <div class="ml-preloader-ring"></div>
            </div>

            <!-- Status message -->
            <div class="ml-preloader-status">
                Loading Resources...
            </div>

            <!-- Indeterminate diamond slider -->
            <div class="ml-preloader-track-wrap">
                <div class="ml-preloader-rail"></div>
                <div class="ml-preloader-node ml-preloader-node-left"></div>
                <div class="ml-preloader-node ml-preloader-node-right"></div>
                <div class="ml-preloader-diamond"></div>
            </div>
        </div>
    </div>
</div>
