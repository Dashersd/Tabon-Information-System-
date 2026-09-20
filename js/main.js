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
        </div>
    `;
    document.body.appendChild(lightbox);
    
    const lightboxImg = lightbox.querySelector('img');
    const lightboxClose = lightbox.querySelector('.lightbox-close');

    // Event delegation on the track to catch all clicks (including cloned cards)
    track.addEventListener('click', (e) => {
        const card = e.target.closest('.card');
        if (card) {
            const img = card.querySelector('img');
            lightboxImg.src = img.src;
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
        // Close if clicking the background overlay
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    init();
});
