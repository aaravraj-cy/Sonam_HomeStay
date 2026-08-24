/* Sonam Homestay - Simple vanilla JS (no jQuery) */

document.addEventListener('DOMContentLoaded', function () {
    document.documentElement.classList.add('js-animations-ready');

    // AOS animation
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 600, once: true });
    }

    // Dark mode
    var root = document.documentElement;
    var saved = localStorage.getItem('sn-theme') || 'light';
    root.setAttribute('data-theme', saved);
    updateThemeIcon(saved);

    var themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('sn-theme', next);
            updateThemeIcon(next);
        });
    }

    function updateThemeIcon(theme) {
        var icon = document.querySelector('#themeToggle i');
        if (!icon) return;
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    // JS-rendered text reveal animations
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var autoRevealSelectors = [
        '.main-content .section-kicker',
        '.main-content .page-header-bar p',
        '.main-content .page-header-bar',
        '.main-content .hero-lead',
        '.main-content .gallery-page-hero p',
        '.main-content .gallery-showcase-copy p',
        '.main-content .gallery-collection-head p',
        '.main-content .rooms-hero p',
        '.main-content .rooms-scroll-cue',
        '.main-content .rooms-filter-panel p',
        '.main-content .rooms-section-head p',
        '.main-content .stat-card',
        '.main-content .dash-card',
        '.main-content .card',
        '.main-content .alert',
        '.main-content form',
        '.main-content table',
        '.main-content .table-responsive',
        '.main-content .empty-state',
        '.main-content .feature-block',
        '.main-content .homestay-card',
        '.main-content .gallery-card-item',
        '.main-content .guest-review-card',
        '.main-content .review-card',
        '.main-content .booking-widget',
        '.main-content .search-bar',
        '.main-content .experience-item',
        '.main-content .amenity-item',
        '.main-content .room-detail-card',
        '.main-content .rooms-card'
    ].join(',');
    var autoWordSelectors = [
        '.main-content h1.display-font',
        '.main-content h2.display-font',
        '.main-content .hero-title',
        '.main-content .gallery-page-hero h1',
        '.main-content .rooms-hero h1'
    ].join(',');

    function hasSimpleTextOnly(el) {
        return Array.prototype.every.call(el.childNodes, function (node) {
            return node.nodeType === Node.TEXT_NODE;
        });
    }

    document.querySelectorAll(autoWordSelectors).forEach(function (el) {
        if (el.classList.contains('js-word-reveal')) return;
        if (!hasSimpleTextOnly(el)) return;
        el.classList.add('js-word-reveal');
    });

    document.querySelectorAll(autoRevealSelectors).forEach(function (el, index) {
        if (el.classList.contains('js-word-reveal') || el.classList.contains('js-text-reveal')) return;
        el.classList.add('js-text-reveal');
        el.style.setProperty('--reveal-delay', Math.min(index * 28, 180) + 'ms');
    });

    document.querySelectorAll('.js-word-reveal').forEach(function (el) {
        if (el.getAttribute('data-words-ready') === 'true') return;
        var text = el.textContent.trim();
        if (!text) return;
        var words = text.split(/\s+/);

        el.textContent = '';
        words.forEach(function (word, index) {
            var span = document.createElement('span');
            span.className = 'word';
            span.style.setProperty('--word-index', index);
            span.textContent = word;
            el.appendChild(span);
            if (index < words.length - 1) {
                el.appendChild(document.createTextNode(' '));
            }
        });
        el.setAttribute('data-words-ready', 'true');
    });

    document.querySelectorAll('.rooms-card').forEach(function (card, index) {
        card.classList.add('js-text-reveal');
        card.style.setProperty('--reveal-delay', Math.min(index * 45, 240) + 'ms');
    });

    if (reduceMotion) {
        document.querySelectorAll('.js-text-reveal, .js-word-reveal').forEach(function (el) {
            el.classList.add('is-visible');
        });
    } else if ('IntersectionObserver' in window) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.18, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.js-text-reveal, .js-word-reveal').forEach(function (el, index) {
            if (!el.style.getPropertyValue('--reveal-delay')) {
                el.style.setProperty('--reveal-delay', Math.min(index * 55, 220) + 'ms');
            }
            revealObserver.observe(el);
        });
    } else {
        document.querySelectorAll('.js-text-reveal, .js-word-reveal').forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    // Password show/hide
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = this.parentElement.querySelector('input');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            var i = this.querySelector('i');
            if (i) i.classList.toggle('fa-eye');
            if (i) i.classList.toggle('fa-eye-slash');
        });
    });

    // Mobile sidebar
    document.querySelectorAll('.mobile-sidebar-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var side = document.getElementById('snSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (side) side.classList.add('open');
            if (overlay) overlay.classList.add('show');
        });
    });
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', function () {
            var side = document.getElementById('snSidebar');
            if (side) side.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // Date min = today
    var today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(function (el) {
        if (!el.getAttribute('min')) el.setAttribute('min', today);
    });

    // Swiper
    if (typeof Swiper !== 'undefined') {
        if (document.querySelector('.featured-swiper')) {
            new Swiper('.featured-swiper', {
                slidesPerView: 1,
                spaceBetween: 20,
                breakpoints: {
                    576: { slidesPerView: 2 },
                    992: { slidesPerView: 3 }
                }
            });
        }
        if (document.querySelector('.detail-swiper')) {
            new Swiper('.detail-swiper', {
                slidesPerView: 1,
                navigation: {
                    nextEl: '.detail-next',
                    prevEl: '.detail-prev'
                }
            });
        }
    }

    // Simple booking price calc
    var roomSelect = document.getElementById('roomSelect');
    var checkIn = document.getElementById('book_check_in');
    var checkOut = document.getElementById('book_check_out');
    function calcPrice() {
        if (!roomSelect) return;
        var opt = roomSelect.options[roomSelect.selectedIndex];
        if (!opt) return;
        var price = parseFloat(opt.getAttribute('data-price') || 0);
        var cleaning = parseFloat(opt.getAttribute('data-cleaning') || 0);
        var nights = 1;
        if (checkIn && checkOut && checkIn.value && checkOut.value) {
            var d1 = new Date(checkIn.value);
            var d2 = new Date(checkOut.value);
            nights = Math.max(1, Math.round((d2 - d1) / 86400000));
        }
        var subtotal = price * nights;
        var service = Math.round(subtotal * 0.05 * 100) / 100;
        var total = subtotal + cleaning + service;
        var elN = document.getElementById('calcNights');
        var elS = document.getElementById('calcSubtotal');
        var elC = document.getElementById('calcCleaning');
        var elV = document.getElementById('calcService');
        var elT = document.getElementById('calcTotal');
        if (elN) elN.textContent = nights;
        if (elS) elS.textContent = '₹' + subtotal.toFixed(2);
        if (elC) elC.textContent = '₹' + cleaning.toFixed(2);
        if (elV) elV.textContent = '₹' + service.toFixed(2);
        if (elT) elT.textContent = '₹' + total.toFixed(2);
    }
    if (roomSelect) roomSelect.addEventListener('change', calcPrice);
    if (checkIn) checkIn.addEventListener('change', calcPrice);
    if (checkOut) checkOut.addEventListener('change', calcPrice);
});
