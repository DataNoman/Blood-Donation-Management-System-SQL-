// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize all components
    initHamburgerMenu();
    initSmoothScroll();
    initScrollAnimations();
    initBloodGroupCards();
    initContactForm();
    initNavbarScroll();
}

// ==================== HAMBURGER MENU ====================
function initHamburgerMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');
    
    if (!hamburger || !navMenu) return;

    // Toggle menu on hamburger click
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu();
    });

    // Close menu when clicking nav links
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (navMenu.classList.contains('active')) {
                closeMenu();
            }
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (navMenu.classList.contains('active') && 
            !e.target.closest('.nav-container')) {
            closeMenu();
        }
    });

    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && navMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    function toggleMenu() {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
        document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
    }

    function closeMenu() {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ==================== SMOOTH SCROLL ====================
function initSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Skip if it's just "#"
            if (href === '#') return;
            
            const target = document.querySelector(href);
            
            if (target) {
                e.preventDefault();
                
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.offsetTop - navbarHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// ==================== NAVBAR SCROLL EFFECT ====================
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        // Add shadow on scroll
        if (currentScroll > 50) {
            navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        } else {
            navbar.style.boxShadow = '0 1px 3px rgba(0,0,0,0.12)';
        }
        
        lastScroll = currentScroll;
    });
}

// ==================== SCROLL ANIMATIONS ====================
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(card);
    });

    // Observe feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(card);
    });

    // Observe blood group cards
    const bloodCards = document.querySelectorAll('.blood-group-card');
    bloodCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.05}s`;
        observer.observe(card);
    });
}

// ==================== BLOOD GROUP CARDS ====================
function initBloodGroupCards() {
    const bloodCards = document.querySelectorAll('.blood-group-card');
    
    bloodCards.forEach(card => {
        card.addEventListener('click', function() {
            const bloodGroup = this.dataset.group;
            showToast(`Searching for ${bloodGroup} donors...`, 'info');
            
            // Redirect to donors page with blood group filter
            setTimeout(() => {
                window.location.href = `donors/index.php?blood_group=${encodeURIComponent(bloodGroup)}`;
            }, 1000);
        });
    });
}

// ==================== CONTACT FORM ====================
function initContactForm() {
    const contactForm = document.querySelector('.contact-form');
    
    if (!contactForm) return;

    const inputs = contactForm.querySelectorAll('input, textarea');
    
    // Real-time validation
    inputs.forEach(input => {
        // Validate on blur
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        // Remove error on input
        input.addEventListener('input', function() {
            this.style.borderColor = '';
            removeErrorMessage(this);
        });
    });

    // Form submission
    contactForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            showToast('Please fill all fields correctly', 'error');
        }
    });
}

// ==================== FORM VALIDATION ====================
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';

    // Remove previous error
    removeErrorMessage(field);
    field.style.borderColor = '';

    // Required field check
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    else if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    // Textarea minimum length
    else if (field.tagName === 'TEXTAREA' && value && value.length < 10) {
        isValid = false;
        errorMessage = 'Message must be at least 10 characters';
    }

    // Name minimum length
    else if (field.name === 'name' && value && value.length < 2) {
        isValid = false;
        errorMessage = 'Name must be at least 2 characters';
    }

    if (!isValid) {
        field.style.borderColor = '#e53e3e';
        showErrorMessage(field, errorMessage);
    }

    return isValid;
}

function showErrorMessage(field, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#e53e3e';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '5px';
    
    field.parentNode.appendChild(errorDiv);
}

function removeErrorMessage(field) {
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// ==================== TOAST NOTIFICATION ====================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = toast.querySelector('i');
    
    if (!toast || !toastMessage) return;

    // Set message
    toastMessage.textContent = message;
    
    // Set icon based on type
    let iconClass = 'fas fa-check-circle';
    let bgColor = '#38a169';
    
    switch(type) {
        case 'error':
            iconClass = 'fas fa-times-circle';
            bgColor = '#e53e3e';
            break;
        case 'warning':
            iconClass = 'fas fa-exclamation-triangle';
            bgColor = '#d69e2e';
            break;
        case 'info':
            iconClass = 'fas fa-info-circle';
            bgColor = '#3182ce';
            break;
    }
    
    toastIcon.className = iconClass;
    toast.style.borderLeftColor = bgColor;
    
    // Show toast
    toast.classList.add('show');
    
    // Hide after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// ==================== UTILITY FUNCTIONS ====================

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// ==================== ACCESSIBILITY ====================
// Add keyboard navigation support
document.addEventListener('keydown', function(e) {
    // Tab trap in mobile menu when open
    const navMenu = document.querySelector('.nav-menu');
    if (navMenu && navMenu.classList.contains('active')) {
        const focusableElements = navMenu.querySelectorAll(
            'a[href], button:not([disabled])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.key === 'Tab') {
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    lastElement.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastElement) {
                    firstElement.focus();
                    e.preventDefault();
                }
            }
        }
    }
});

// ==================== PERFORMANCE ====================
// Lazy load images
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ==================== BROWSER COMPATIBILITY ====================
// Polyfill for smooth scroll on older browsers
if (!('scrollBehavior' in document.documentElement.style)) {
    const smoothScrollPolyfill = document.createElement('script');
    smoothScrollPolyfill.src = 'https://cdnjs.cloudflare.com/ajax/libs/smooth-scroll/16.1.3/smooth-scroll.polyfills.min.js';
    document.head.appendChild(smoothScrollPolyfill);
}

// Console welcome message
console.log('%cBloodConnect', 'color: #e53e3e; font-size: 24px; font-weight: bold;');
console.log('%cSaving lives, one donation at a time.', 'color: #718096; font-size: 14px;');
console.log('%cMade with ❤️ for Bangladesh', 'color: #38a169; font-size: 12px;');