/**
 * Main Interactive Application Script - Barangay Tabon Information System
 * Fully compatible with Localhost, InfinityFree, and Mobile Browsers.
 */

// Universal Helper: Runs callback whether DOM is loading, interactive, or complete
function onDOMReady(fn) {
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        setTimeout(fn, 1);
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

// ----------------------------------------------------
// Smooth Page Transition Handler & bfcache recovery
// ----------------------------------------------------
onDOMReady(() => {
    const navLinks = document.querySelectorAll('nav a, .back-to-home, .btn-login');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetUrl = link.getAttribute('href');

            // Ignore links that don't go to another internal page (like #, empty, or external URLs)
            if (!targetUrl || targetUrl === '#' || targetUrl.startsWith('http') || targetUrl.startsWith('javascript:')) {
                return;
            }

            // Prevent instant navigation
            e.preventDefault();

            // Add the fade-out class to trigger CSS transition
            document.body.classList.add('fade-out');

            // Wait for transition before navigating
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 250);
        });
    });

    // Mobile bfcache recovery (ensures page is never blank when returning with back button)
    window.addEventListener('pageshow', () => {
        document.body.classList.remove('fade-out');
        document.body.classList.remove('menu-open-lock');
        if (document.documentElement) {
            document.documentElement.classList.remove('menu-open-lock');
        }
    });
});

// ----------------------------------------------------
// 3D Infinite Scroll Image Carousel
// ----------------------------------------------------
onDOMReady(() => {
    const track = document.querySelector('.track');
    const carousel = document.querySelector('.carousel');

    if (!track || !carousel) return;

    // --- Configuration Constants & Responsive Defaults ---
    const SPEED = 65; // pixels per second
    let currentGap = 20;
    let currentMaxAngle = 28;
    let currentDepth = 60;
    let isMobile = false;

    // --- State ---
    let offset = 0;
    let isPaused = false;
    let originalCardCount = 0;
    let singleSetWidth = 0;

    let containerWidth = 0;
    let containerCenter = 0;

    let lastTime = performance.now();
    let animationFrameId;

    // Check prefers-reduced-motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Pre-create Lightbox element to avoid temporal dead zone
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <button class="lightbox-close" aria-label="Close lightbox">&times;</button>
            <img src="" alt="Expanded View">
            <div class="carousel-lightbox-caption" style="color:#ffffff; font-size:1.05rem; margin-top:12px; font-weight:600; text-align:center; font-family:'Montserrat',sans-serif; text-shadow:0 2px 4px rgba(0,0,0,0.6);"></div>
        </div>
    `;
    document.body.appendChild(lightbox);

    const lightboxImg = lightbox.querySelector('img');
    const lightboxCaption = lightbox.querySelector('.carousel-lightbox-caption');
    const lightboxClose = lightbox.querySelector('.lightbox-close');

    function init() {
        // Find original cards
        const originalCards = Array.from(track.querySelectorAll('.card'));
        originalCardCount = originalCards.length;
        if (originalCardCount === 0) return;

        // Duplicate the full card list twice in JS by cloning for seamless loop
        for (let i = 0; i < 2; i++) {
            originalCards.forEach(card => {
                const clone = card.cloneNode(true);
                track.appendChild(clone);
            });
        }

        measure();

        // Start loop
        lastTime = performance.now();
        loop(lastTime);
    }

    function measure() {
        containerWidth = carousel.offsetWidth || window.innerWidth;
        containerCenter = containerWidth / 2;

        const viewportWidth = window.innerWidth;
        isMobile = viewportWidth <= 768;

        if (viewportWidth <= 480) {
            currentMaxAngle = 0;
            currentDepth = 0;
            currentGap = 12;
        } else if (isMobile) {
            currentMaxAngle = 0;
            currentDepth = 0;
            currentGap = 14;
        } else if (viewportWidth <= 1024) {
            currentMaxAngle = 18;
            currentDepth = 35;
            currentGap = 18;
        } else {
            currentMaxAngle = 28;
            currentDepth = 60;
            currentGap = 20;
        }

        try {
            const computedGap = parseFloat(window.getComputedStyle(track).gap);
            if (!isNaN(computedGap) && computedGap > 0) {
                currentGap = computedGap;
            }
        } catch (e) { }

        const cards = Array.from(track.querySelectorAll('.card'));
        if (cards.length > 0) {
            const cardWidth = cards[0].offsetWidth;
            singleSetWidth = originalCardCount * (cardWidth + currentGap);
        }
    }

    function loop(now) {
        const deltaTime = (now - lastTime) / 1000;
        lastTime = now;

        if (!prefersReducedMotion.matches && !isPaused && !isDragging) {
            offset += SPEED * deltaTime;

            if (offset >= singleSetWidth) {
                offset -= singleSetWidth;
            }
        }

        render();
        animationFrameId = requestAnimationFrame(loop);
    }

    function render() {
        const cards = track.querySelectorAll('.card');
        if (cards.length === 0) return;

        const cardWidth = cards[0].offsetWidth;

        cards.forEach((card, index) => {
            const initialLeft = index * (cardWidth + currentGap);
            const cardLeft = initialLeft - offset;
            const cardCenter = cardLeft + cardWidth / 2;
            const ratio = (cardCenter - containerCenter) / (containerWidth / 2);
            const absRatio = Math.abs(ratio);

            // Center-focused z-index: closest to viewport center has highest z-index
            const zIndex = Math.round((1 - Math.min(1, absRatio)) * 50);
            card.style.zIndex = zIndex;

            if (isMobile) {
                // On mobile: completely flat (no rotateY / translateZ collision)
                // Subtle spotlight scale for the center focused card
                const scale = Math.max(0.93, 1 - Math.min(1, absRatio) * 0.07);
                card.style.transform = `translateX(${-offset}px) scale(${scale.toFixed(3)})`;
            } else {
                // On desktop: elegant coverflow with edge cards receding into background
                const clampedRatio = Math.max(-1.5, Math.min(1.5, ratio));
                const angle = -clampedRatio * currentMaxAngle;
                // Negative translateZ pushes edge cards BACK, keeping center prominent & preventing overlap
                const depth = -Math.min(1.2, absRatio) * currentDepth;
                card.style.transform = `translateX(${-offset}px) rotateY(${angle.toFixed(1)}deg) translateZ(${depth.toFixed(1)}px)`;
            }
        });
    }

    // --- Touch / Mouse Drag Handling ---
    let isDragging = false;
    let startX = 0;
    let startOffset = 0;
    let dragDistance = 0;
    let resumeTimeout = null;

    function onPointerDown(e) {
        if (lightbox.classList.contains('active')) return;
        isDragging = true;
        isPaused = true;
        if (resumeTimeout) clearTimeout(resumeTimeout);
        dragDistance = 0;
        startX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
        startOffset = offset;
    }

    function onPointerMove(e) {
        if (!isDragging) return;
        const currentX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
        const deltaX = currentX - startX;
        dragDistance = Math.abs(deltaX);

        offset = startOffset - deltaX;

        // Wrap offset seamlessly
        if (singleSetWidth > 0) {
            while (offset >= singleSetWidth) offset -= singleSetWidth;
            while (offset < 0) offset += singleSetWidth;
        }

        render();
    }

    function onPointerUp() {
        if (!isDragging) return;
        isDragging = false;

        if (resumeTimeout) clearTimeout(resumeTimeout);
        resumeTimeout = setTimeout(() => {
            if (!lightbox.classList.contains('active')) {
                isPaused = false;
                lastTime = performance.now();
            }
        }, 1200);
    }

    // Attach touch gestures
    carousel.addEventListener('touchstart', onPointerDown, { passive: true });
    window.addEventListener('touchmove', onPointerMove, { passive: true });
    window.addEventListener('touchend', onPointerUp, { passive: true });
    window.addEventListener('touchcancel', onPointerUp, { passive: true });

    // Attach mouse drag gestures
    carousel.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        onPointerDown(e);
    });
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('mouseup', onPointerUp);

    // --- Hover Pause (Desktop) ---
    carousel.addEventListener('mouseenter', () => {
        if (!lightbox.classList.contains('active') && !isDragging) {
            isPaused = true;
        }
    });

    carousel.addEventListener('mouseleave', () => {
        if (!lightbox.classList.contains('active') && !isDragging) {
            isPaused = false;
            lastTime = performance.now();
        }
    });

    // Window resize
    window.addEventListener('resize', () => {
        measure();
        render();
    });

    // Event delegation on the track to catch all clicks (including cloned cards)
    track.addEventListener('click', (e) => {
        if (dragDistance > 8) {
            dragDistance = 0;
            return; // Swiped, do not trigger lightbox
        }
        const card = e.target.closest('.card');
        if (card) {
            const img = card.querySelector('img');
            const dataImg = card.getAttribute('data-img') || (img ? img.src : '');
            lightboxImg.src = dataImg;

            const title = card.getAttribute('data-title') || (img ? img.getAttribute('alt') : '') || '';
            const folder = card.getAttribute('data-folder') || '';
            const date = card.getAttribute('data-date') || '';

            if (lightboxCaption) {
                let captionHtml = `<div style="font-size:1.25rem; font-weight:700; color:#ffffff; margin-bottom:8px; font-family:'Montserrat',sans-serif; text-shadow:0 2px 5px rgba(0,0,0,0.85);">${title}</div>`;
                if (folder || date) {
                    captionHtml += `<div style="display:inline-flex; align-items:center; gap:10px; justify-content:center; flex-wrap:wrap;">`;
                    if (folder) {
                        captionHtml += `<span style="display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:50px; border:1.5px solid #f59e0b; background:rgba(245,158,11,0.18); color:#fbbf24; font-size:0.75rem; font-weight:800; letter-spacing:0.5px; text-transform:uppercase;"><i class="fa-solid fa-folder"></i> ${folder}</span>`;
                    }
                    if (date) {
                        captionHtml += `<span style="display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:50px; background:rgba(15,23,42,0.88); border:1px solid rgba(255,255,255,0.15); color:#f8fafc; font-size:0.75rem; font-weight:600;"><i class="fa-regular fa-clock" style="color:#fbbf24;"></i> ${date}</span>`;
                    }
                    captionHtml += `</div>`;
                }
                lightboxCaption.innerHTML = captionHtml;
            }
            lightbox.classList.add('active');
            isPaused = true;
        }
    });

    const closeLightbox = () => {
        lightbox.classList.remove('active');
        isPaused = false;
        lastTime = performance.now();
    };

    lightboxClose.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    init();
});

// ----------------------------------------------------
// Scroll Animations Trigger
// ----------------------------------------------------
onDOMReady(() => {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.2
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    animatedElements.forEach(el => observer.observe(el));
});

// ----------------------------------------------------
// Lenis Smooth Scroll Initialization (Safe try/catch)
// ----------------------------------------------------
onDOMReady(() => {
    if (typeof Lenis !== 'undefined') {
        try {
            const lenis = new Lenis({
                duration: 1.2,
                easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
                direction: 'vertical',
                gestureDirection: 'vertical',
                smooth: true,
                mouseMultiplier: 1,
                smoothTouch: false,
                touchMultiplier: 2,
                infinite: false,
            });

            function raf(time) {
                lenis.raf(time);
                requestAnimationFrame(raf);
            }

            requestAnimationFrame(raf);
        } catch (e) {
            console.warn('Lenis scroll init bypassed:', e);
        }
    }
});

// ----------------------------------------------------
// Universal Mobile Navigation Drawer Controller
// ----------------------------------------------------
function initUniversalMobileNav() {
    const header = document.querySelector('header');
    const nav = document.querySelector('nav');
    if (!header || !nav) return;

    if (window.__tabonMobileNavInitialized) return;
    window.__tabonMobileNavInitialized = true;

    // Anchor placeholder to keep exact desktop position inside header
    let navPlaceholder = document.getElementById('navDesktopPlaceholder');
    if (!navPlaceholder) {
        navPlaceholder = document.createElement('div');
        navPlaceholder.id = 'navDesktopPlaceholder';
        navPlaceholder.style.display = 'none';
        header.insertBefore(navPlaceholder, nav);
    }

    // Locate or create hamburger toggle button
    let toggleBtn = header.querySelector('.mobile-menu-toggle') || document.querySelector('.mobile-menu-toggle');
    if (!toggleBtn) {
        toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-menu-toggle';
        toggleBtn.id = 'mobileMenuToggle';
        toggleBtn.setAttribute('aria-label', 'Toggle navigation menu');
        toggleBtn.setAttribute('type', 'button');
        toggleBtn.innerHTML = `
            <span class="bar bar-1"></span>
            <span class="bar bar-2"></span>
            <span class="bar bar-3"></span>
        `;
        header.appendChild(toggleBtn);
    }

    // Locate or create backdrop
    let backdrop = document.querySelector('.mobile-nav-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'mobile-nav-backdrop';
        document.body.appendChild(backdrop);
    }

    // Locate or create close button inside nav
    let closeBtn = nav.querySelector('.mobile-nav-close');
    if (!closeBtn) {
        closeBtn = document.createElement('button');
        closeBtn.className = 'mobile-nav-close';
        closeBtn.setAttribute('aria-label', 'Close menu');
        closeBtn.setAttribute('type', 'button');
        closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        nav.insertBefore(closeBtn, nav.firstChild);
    }

    // Locate or create mobile brand header inside nav drawer
    let drawerBrand = nav.querySelector('.mobile-drawer-brand');
    if (!drawerBrand) {
        drawerBrand = document.createElement('div');
        drawerBrand.className = 'mobile-drawer-brand';
        const brandSubEl = document.querySelector('.brand-sub');
        const brandSubText = (brandSubEl && brandSubEl.textContent.trim()) || 'Together for a Stronger Community';
        drawerBrand.innerHTML = `
            <div class="drawer-brand-text">
                <div class="drawer-brand-title">Barangay Tabon</div>
                <div class="drawer-brand-sub">${brandSubText}</div>
            </div>
        `;
        if (closeBtn.nextSibling) {
            nav.insertBefore(drawerBrand, closeBtn.nextSibling);
        } else {
            nav.appendChild(drawerBrand);
        }
    }

    function openMobileMenu() {
        if (nav.parentElement !== document.body) {
            document.body.appendChild(nav);
        }
        nav.classList.add('mobile-open');
        backdrop.classList.add('active');
        toggleBtn.classList.add('open');
        header.classList.add('header-menu-active');
        document.body.classList.add('menu-open-lock');
        if (document.documentElement) {
            document.documentElement.classList.add('menu-open-lock');
        }
    }

    function closeMobileMenu() {
        nav.classList.remove('mobile-open');
        backdrop.classList.remove('active');
        toggleBtn.classList.remove('open');
        header.classList.remove('header-menu-active');
        document.body.classList.remove('menu-open-lock');
        if (document.documentElement) {
            document.documentElement.classList.remove('menu-open-lock');
        }
    }

    // Dynamic placement: body on mobile (escapes sticky header constraints), header on desktop
    function syncNavPlacement() {
        if (window.innerWidth <= 992) {
            if (nav.parentElement !== document.body) {
                document.body.appendChild(nav);
            }
        } else {
            if (nav.parentElement !== header) {
                if (navPlaceholder && navPlaceholder.parentNode) {
                    navPlaceholder.parentNode.insertBefore(nav, navPlaceholder.nextSibling);
                } else {
                    header.appendChild(nav);
                }
            }
            closeMobileMenu();
        }
    }
    syncNavPlacement();
    window.addEventListener('resize', syncNavPlacement);

    // Toggle button click / touch
    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (nav.classList.contains('mobile-open')) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    });

    // Close button click
    closeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeMobileMenu();
    });

    // Backdrop click / touch to dismiss
    backdrop.addEventListener('click', (e) => {
        e.preventDefault();
        closeMobileMenu();
    });
    backdrop.addEventListener('touchstart', (e) => {
        e.preventDefault();
        closeMobileMenu();
    }, { passive: false });

    // Mobile dropdown accordion handling (About Us & Officials)
    const dropdownToggles = nav.querySelectorAll('.nav-item.dropdown > .dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            if (window.innerWidth <= 992) {
                e.preventDefault();
                e.stopPropagation();
                const parent = toggle.closest('.nav-item.dropdown');
                if (parent) {
                    parent.classList.toggle('expanded');
                }
            }
        });
    });

    // Close mobile nav when clicking a direct navigation link
    const directLinks = nav.querySelectorAll('a:not(.dropdown-toggle)');
    directLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                closeMobileMenu();
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('mobile-open')) {
            closeMobileMenu();
        }
    });

    // Handle bfcache on mobile browsers
    window.addEventListener('pageshow', () => {
        closeMobileMenu();
    });
}

// Initialize Universal Mobile Navigation
onDOMReady(initUniversalMobileNav);
