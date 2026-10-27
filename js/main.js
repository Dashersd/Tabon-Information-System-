document.addEventListener('DOMContentLoaded', () => {
    // Select all navigation links and back buttons
    const navLinks = document.querySelectorAll('nav a, .back-to-home, .btn-login');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetUrl = link.getAttribute('href');
            
            // Ignore links that don't go to another internal page (like # or external URLs)
            if(!targetUrl || targetUrl === '#' || targetUrl.startsWith('http')) {
                return;
            }
            
            // Prevent instant navigation
            e.preventDefault();
            
            // Add the fade-out class to trigger CSS transition
            document.body.classList.add('fade-out');
            
            // Wait for the animation to finish (400ms) before actually navigating
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 400);
        });
    });
});

// ----------------------------------------------------
// 3D Infinite Scroll Image Carousel
// ----------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const track = document.querySelector('.track');
    const carousel = document.querySelector('.carousel');
    
    if (!track || !carousel) return;

    // --- Configuration Constants ---
    const MAX_ANGLE = 45; // max rotation degrees at the edges
    const DEPTH = 120; // max translateZ push (px) at the edges
    const SPEED = 70; // pixels per second (increased for a tiny bit faster scroll)
    const GAP = 20; // Match gap from CSS

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

    function init() {
        // Find original cards
        const originalCards = Array.from(track.querySelectorAll('.card'));
        originalCardCount = originalCards.length;
        if(originalCardCount === 0) return;

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
        containerWidth = carousel.offsetWidth;
        containerCenter = containerWidth / 2;

        const cards = Array.from(track.querySelectorAll('.card'));
        if (cards.length > 0) {
            const cardWidth = cards[0].offsetWidth;
            // Width of exactly one original set (cards + gaps)
            singleSetWidth = originalCardCount * (cardWidth + GAP);
        }
    }

    function loop(now) {
        const deltaTime = (now - lastTime) / 1000; // in seconds
        lastTime = now;

        if (!prefersReducedMotion.matches && !isPaused) {
            offset += SPEED * deltaTime;

            // Wrap seamlessly
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
            // Position of this card in the track, factoring in the current scroll offset
            const initialLeft = index * (cardWidth + GAP);
            const cardLeft = initialLeft - offset;
            const cardCenter = cardLeft + cardWidth / 2;

            // Compute ratio from center (-1 to 1 across the container width)
            // If it's outside the container, it's > 1 or < -1, but they are faded/clipped anyway
            const ratio = (cardCenter - containerCenter) / (containerWidth / 2);

            // Compute the curve
            // Ratio is 0 at center, -1 at left, 1 at right
            // We want it to bow TOWARD the viewer (concave).
            // A concave wall: center is flat, left card faces right, right card faces left.
            // Left card (ratio < 0): rotateY should be positive to face right (assuming CSS 3D).
            // Right card (ratio > 0): rotateY should be negative to face left.
            // Thus, angle = -ratio * MAX_ANGLE.
            const angle = -ratio * MAX_ANGLE; 
            
            // Depth pushes cards forward (positive Z) as they move away from center
            // This pulls the edges toward the viewer.
            const depth = Math.abs(ratio) * DEPTH;

            // Apply transform
            card.style.transform = `translateX(${-offset}px) rotateY(${angle}deg) translateZ(${depth}px)`;
        });
    }

    // --- Events ---
    window.addEventListener('resize', () => {
        measure();
        render(); // Force render immediately on resize
    });

    carousel.addEventListener('mouseenter', () => {
        // Only pause if lightbox is not active
        if (!lightbox.classList.contains('active')) {
            isPaused = true;
        }
    });

    carousel.addEventListener('mouseleave', () => {
        // Only unpause if lightbox is not active
        if (!lightbox.classList.contains('active')) {
            isPaused = false;
            lastTime = performance.now(); // reset time so it doesn't jump
        }
    });

    // --- Lightbox Feature ---
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

    // Event delegation on the track to catch all clicks (including cloned cards)
    track.addEventListener('click', (e) => {
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
            isPaused = true; // Pause carousel when expanded
        }
    });

    const closeLightbox = () => {
        lightbox.classList.remove('active');
        isPaused = false;
        lastTime = performance.now(); // Reset timer so scroll resumes smoothly
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
document.addEventListener('DOMContentLoaded', () => {
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
// Lenis Smooth Scroll Initialization
// ----------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    // Check if Lenis is loaded (only on pages where it's included)
    if (typeof Lenis !== 'undefined') {
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
    }
});
