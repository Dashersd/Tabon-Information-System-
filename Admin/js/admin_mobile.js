/**
 * Barangay Tabon Admin Portal - Universal Mobile & Tablet UX/UI Controller
 * Provides off-canvas sidebar drawer, sticky mobile app bar, table touch-scrollers,
 * and mobile master-detail navigation for Feedback Messenger.
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    onReady(function () {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        if (!sidebar) return;

        // 1. Detect current page title for the mobile topbar subtitle
        let pageLabel = 'Control Panel';
        const titleEl = document.querySelector('.page-title, .topbar-title, .media-top-title, .admin-header-row h2, .content-header h2, .legend-header-title h2, .settings-banner-title h2, .officials-header-container h2, .about-header h2');
        if (titleEl) {
            pageLabel = titleEl.textContent.replace(/\s+/g, ' ').trim();
        } else if (document.title) {
            pageLabel = document.title.split('|')[0].split('-')[0].trim();
        }

        // 3. Inject Mobile Top App Bar (.admin-mobile-topbar)
        if (!document.querySelector('.admin-mobile-topbar')) {
            const topbar = document.createElement('header');
            topbar.className = 'admin-mobile-topbar';
            topbar.innerHTML = `
                <div class="admin-mobile-left">
                    <button type="button" class="admin-mobile-menu-btn" id="adminMobileMenuBtn" aria-label="Open Admin Navigation">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="admin-mobile-brand">
                        <img src="../Barangay Logo/Logo.png" alt="Brgy Tabon" onerror="this.style.display='none'">
                        <div class="admin-mobile-brand-text">
                            <span class="admin-mobile-brand-title">Brgy Tabon Admin</span>
                            <span class="admin-mobile-brand-sub">${pageLabel}</span>
                        </div>
                    </div>
                </div>
                <div class="admin-mobile-actions">
                    <a href="feedback.php" class="admin-mobile-action-link" title="Feedback Chat">
                        <i class="fa-solid fa-comments"></i>
                    </a>
                    <a href="../index.php" target="_blank" class="admin-mobile-action-link" title="View Public Website">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
            `;
            document.body.insertBefore(topbar, document.body.firstChild);
        }

        // 4. Inject Close Button inside Sidebar Header & Backdrop Overlay
        const sidebarHeader = sidebar.querySelector('.sidebar-header');
        if (sidebarHeader && !sidebarHeader.querySelector('.admin-sidebar-close-btn')) {
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'admin-sidebar-close-btn';
            closeBtn.setAttribute('aria-label', 'Close menu');
            closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            sidebarHeader.appendChild(closeBtn);
            closeBtn.addEventListener('click', closeDrawer);
        }

        let backdrop = document.querySelector('.admin-sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'admin-sidebar-backdrop';
            document.body.appendChild(backdrop);
        }

        const menuBtn = document.getElementById('adminMobileMenuBtn');

        function openDrawer() {
            sidebar.classList.add('mobile-open');
            backdrop.classList.add('active');
            document.body.classList.add('admin-drawer-lock');
        }

        function closeDrawer() {
            sidebar.classList.remove('mobile-open');
            backdrop.classList.remove('active');
            document.body.classList.remove('admin-drawer-lock');
        }

        if (menuBtn) {
            menuBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (sidebar.classList.contains('mobile-open')) {
                    closeDrawer();
                } else {
                    openDrawer();
                }
            });
        }

        backdrop.addEventListener('click', closeDrawer);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                closeDrawer();
            }
        });

        // 5. Auto-highlight active nav link & auto-expand parent dropdown in sidebar
        const currentPath = decodeURIComponent(window.location.pathname.split('/').pop() || 'controlpanel.php');
        const navLinks = sidebar.querySelectorAll('.sidebar-nav a[href]');
        navLinks.forEach(function (link) {
            const href = decodeURIComponent(link.getAttribute('href') || '');
            if (href && href === currentPath) {
                link.classList.add('active');
                const parentDropdown = link.closest('.dropdown-container');
                if (parentDropdown) {
                    parentDropdown.classList.add('active');
                    parentDropdown.style.display = 'block';
                    const btn = parentDropdown.previousElementSibling;
                    if (btn && btn.classList.contains('dropdown-btn')) {
                        btn.classList.add('active');
                    }
                }
            }

            // Close mobile drawer when clicking a real navigation link
            if (!link.classList.contains('dropdown-btn')) {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 992) {
                        closeDrawer();
                    }
                });
            }
        });

        // 6. Wrap any wide tables in a touch-friendly horizontal scroll wrapper
        if (mainContent) {
            const tables = mainContent.querySelectorAll('table');
            tables.forEach(function (tbl) {
                const parent = tbl.parentElement;
                if (parent && !parent.classList.contains('table-responsive-scroll') && !parent.classList.contains('table-container')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive-scroll';
                    parent.insertBefore(wrapper, tbl);
                    wrapper.appendChild(tbl);
                }
            });
        }

        // 7. Feedback Chat (feedback.php) Mobile Master-Detail Navigation
        const chatWrapper = document.getElementById('chatAppWrapper');
        const chatAreaHeader = document.querySelector('.chat-area-header');
        if (chatWrapper && chatAreaHeader) {
            // Inject "Back to Inbox" button for mobile screens
            if (!document.getElementById('btnMobileBackList')) {
                const backBtn = document.createElement('button');
                backBtn.type = 'button';
                backBtn.id = 'btnMobileBackList';
                backBtn.className = 'btn-mobile-back-list';
                backBtn.innerHTML = '<i class="fa-solid fa-arrow-left"></i> <span>Inbox</span>';
                chatAreaHeader.insertBefore(backBtn, chatAreaHeader.firstChild);

                backBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    chatWrapper.classList.remove('chat-open');
                });
            }

            // Open chat view on mobile when clicking a conversation or notification
            document.addEventListener('click', function (e) {
                const convItem = e.target.closest('.conversation-item, .notif-item, .btn-toast-reply');
                if (convItem) {
                    chatWrapper.classList.add('chat-open');
                }
            });

            // If URL has ?thread=..., open chat view immediately
            if (window.location.search.indexOf('thread=') !== -1) {
                chatWrapper.classList.add('chat-open');
            }
        }
    });
})();

