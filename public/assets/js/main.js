// Uma Shakti Dham Website JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Mobile navigation toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('mainNav');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            // toggle page-level nav-open class to prevent background scroll
            document.documentElement.classList.toggle('nav-open');
            
            // Update aria-expanded attribute
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', (!expanded).toString());
            
            // Update button text
            this.textContent = navMenu.classList.contains('active') ? '✕ Close' : '☰ Menu';
        });
        
        // Dropdown toggles: clicking the main dropbtn should open/close the submenu
        // (prevents navigation on items that have children). Works on desktop and mobile.
        const dropdownItems = document.querySelectorAll('.dropdown .dropbtn');
        dropdownItems.forEach(dropbtn => {
            dropbtn.addEventListener('click', function(e) {
                // Prevent navigation and toggle the dropdown
                e.preventDefault();
                // Prevent other click handlers from closing the nav/menu
                e.stopPropagation();
                const dropdown = this.closest('.dropdown');

                // Close all other dropdowns
                document.querySelectorAll('.dropdown.active').forEach(d => {
                    if (d !== dropdown) d.classList.remove('active');
                });

                // Toggle current dropdown
                dropdown.classList.toggle('active');

                // Update aria-expanded on the trigger for accessibility
                const expanded = dropdown.classList.contains('active');
                try { this.setAttribute('aria-expanded', expanded.toString()); } catch (err) {}
                // If opening a dropdown inside the sidebar, ensure the sidebar stays open
                if (expanded && navMenu && !navMenu.classList.contains('active')) {
                    navMenu.classList.add('active');
                    document.documentElement.classList.add('nav-open');
                    if (navToggle) {
                        navToggle.setAttribute('aria-expanded', 'true');
                        navToggle.textContent = '✕ Close';
                    }
                }
            });

            // Keyboard support: Enter or Space toggles the dropdown
            dropbtn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
        
        // Close mobile menu when clicking on a non-dropdown link
        const navLinks = navMenu.querySelectorAll('a:not(.dropbtn)');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                // ensure nav-open removed and toggle updated
                document.documentElement.classList.remove('nav-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.textContent = '☰ Menu';
                
                // Close all dropdowns
                document.querySelectorAll('.dropdown.active').forEach(d => {
                    d.classList.remove('active');
                });
            });
        });

        // Close button inside mobile nav (if present)
        const navClose = document.getElementById('navClose');
        if (navClose) {
            navClose.addEventListener('click', function() {
                navMenu.classList.remove('active');
                document.documentElement.classList.remove('nav-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.textContent = '☰ Menu';
            });
        }

        // Close nav when clicking outside the menu (mobile)
        document.addEventListener('click', function(e) {
            const isClickInsideNav = navMenu.contains(e.target) || navToggle.contains(e.target) || (navClose && navClose.contains(e.target));
            if (!isClickInsideNav && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                document.documentElement.classList.remove('nav-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.textContent = '☰ Menu';
            }
        });
    }

    // Smooth scrolling for internal anchor links
    const internalLinks = document.querySelectorAll('a[href^="#"]');
    internalLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#f44336';
                } else {
                    field.style.borderColor = '#ccc';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });

    // Donation amount validation
    const donationForm = document.querySelector('#donation-form');
    if (donationForm) {
        const amountInput = donationForm.querySelector('#donation-amount');
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                const value = parseFloat(this.value);
                if (value <= 0 || isNaN(value)) {
                    this.style.borderColor = '#f44336';
                } else {
                    this.style.borderColor = '#4CAF50';
                }
            });
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                const dropdownContent = dropdown.querySelector('.dropdown-content');
                if (dropdownContent && window.innerWidth <= 768) {
                    dropdown.classList.remove('active');
                }
            }
        });
    });

    // Enhanced dropdown behavior for desktop
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        
        if (dropdownContent) {
            // Desktop hover behavior
            dropdown.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768) {
                    this.classList.add('hover');
                }
            });
            
            dropdown.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768) {
                    this.classList.remove('hover');
                }
            });
        }
    });

    // Scroll behavior: hide top-contact-bar, keep main-header sticky, shrink site-title on scroll
    (function() {
        const root = document.documentElement;
        const mainHeader = document.querySelector('.main-header');
        // const mainNav = document.querySelector('.main-navigation');
        const topContact = document.querySelector('.top-contact-bar');
        const SCROLL_THRESHOLD = 60; // pixels
        let isScrolled = false;

        function updateNavTop() {
            // if (!mainHeader || !mainNav) return;
            // const h = Math.ceil(mainHeader.getBoundingClientRect().height);
            // mainNav.style.top = h + 'px';
        }

        function onScroll() {
            const st = window.pageYOffset || document.documentElement.scrollTop || 0;

            if (st > SCROLL_THRESHOLD && !isScrolled) {
                // entering scrolled state
                if (root) root.classList.add('scrolled');
                if (mainHeader) mainHeader.classList.add('shrunk');
                isScrolled = true;
                updateNavTop();
            } else if (st <= SCROLL_THRESHOLD && isScrolled) {
                // back to top (BAU)
                if (root) root.classList.remove('scrolled');
                if (mainHeader) mainHeader.classList.remove('shrunk');
                isScrolled = false;
                updateNavTop();
            }
        }

        // Init
        window.addEventListener('load', updateNavTop);
        window.addEventListener('resize', updateNavTop);
        window.addEventListener('scroll', onScroll, { passive: true });

        // If header transitions (shrink/expand) finish, ensure nav top is correct
        if (mainHeader) {
            mainHeader.addEventListener('transitionend', function(e) {
                if (/height|padding|transform|margin/.test(e.propertyName)) {
                    updateNavTop();
                }
            });
        }

        // Run once to set initial state
        updateNavTop();
        onScroll();
    })();
});