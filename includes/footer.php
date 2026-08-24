</main>
<footer class="sn-footer py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Brand & Tagline -->
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="sn-brand footer-brand mb-3">
                    <span class="brand-mark"><i class="fas fa-home"></i></span>
                    <span class="brand-text text-white"><?= e(APP_NAME) ?></span>
                </div>
                <p class="footer-desc mb-4 small" style="line-height: 1.6">Find and book unique local homestays. Connect with verified hosts who treat you like family, and experience destination travel authentic to local cultures.</p>
                <!-- Social Links -->
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/sikkim_treks/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Destinations Columns -->
            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="footer-title text-white fw-bold mb-3 small text-uppercase" style="letter-spacing: 0.05em">Destinations to Visit</h6>
                <ul class="footer-links list-unstyled d-flex flex-column gap-2 small">
                    <li><a href="<?= BASE_URL ?>pages/search.php?location=Khechuperi" class="text-decoration-none hover-teal transition">Khechuperi Lake</a></li>
                    <!-- <li><a href="<?= BASE_URL ?>pages/search.php?location=Pelling" class="text-decoration-none hover-teal transition">Pelling Stays</a></li>
                    <li><a href="<?= BASE_URL ?>pages/search.php?location=Yuksom" class="text-decoration-none hover-teal transition">Yuksom Stays</a></li>
                    <li><a href="<?= BASE_URL ?>pages/search.php?location=Geyzing" class="text-decoration-none hover-teal transition">Geyzing Stays</a></li> -->
                </ul>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="footer-title text-white fw-bold mb-3 small text-uppercase" style="letter-spacing: 0.05em">Quick Links</h6>
                <ul class="footer-links list-unstyled d-flex flex-column gap-2 small">
                    <li><a href="<?= BASE_URL ?>" class="text-decoration-none hover-teal transition">Home</a></li>
                    <li><a href="<?= BASE_URL ?>pages/search.php" class="text-decoration-none hover-teal transition">Explore Stays</a></li>
                    <li><a href="<?= BASE_URL ?>pages/gallery.php" class="text-decoration-none hover-teal transition">Photo Gallery</a></li>
                    <li><a href="<?= BASE_URL ?>#why-us" class="text-decoration-none hover-teal transition">Why Sonam Homestay</a></li>
                </ul>
            </div>

            <!-- Contact & Newsletter -->
            <div class="col-lg-4 col-md-12 col-sm-12">
                <h6 class="footer-title text-white fw-bold mb-3 small text-uppercase" style="letter-spacing: 0.05em">Support & Contact</h6>
                <p class="small mb-2"><a href="mailto:support@sonamhomestay.local" class="text-decoration-none hover-teal transition"><i class="far fa-envelope text-teal me-2"></i>sonamhomestay01@gmail.com</a></p>
                <p class="small mb-3"><a href="tel:+15552345678" class="text-decoration-none hover-teal transition"><i class="fas fa-phone-alt text-teal me-2"></i>+91-9735589678</a></p>
                <!-- Newsletter Subscription Form -->
                <form class="newsletter-form input-group input-group-sm mt-3" style="max-width: 320px">
                    <input type="email" class="form-control" placeholder="Email address" aria-label="Subscribe to newsletter" required>
                    <button class="btn btn-teal btn-sm" type="button" onclick="if(this.form.checkValidity()){ alert('Demo: Thank you for subscribing!'); this.form.reset(); } else { alert('Please enter a valid email address.'); }">Subscribe</button>
                </form>
            </div>
        </div>

        <!-- Divider line -->
        <hr class="text-muted my-4 opacity-25">

        <!-- Copyright section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 footer-bottom small">
            <!-- <div>
                &copy; <?= date('Y') ?> <strong class="text-white"><?= e(APP_NAME) ?></strong>. All rights reserved.
            </div>
            <div class="d-flex gap-3">
                <a href="#" class="text-decoration-none hover-teal">Privacy Policy</a>
                <span>&bull;</span>
                <a href="#" class="text-decoration-none hover-teal">Terms of Service</a>
                <span>&bull;</span>
                <a href="#" class="text-decoration-none hover-teal">Sitemap</a>
            </div>
        </div> -->
    </div>
</footer>

<!-- Floating Scroll to Top Button -->
<button type="button" id="scrollToTop" class="scroll-to-top-btn">
    <i class="fas fa-arrow-up"></i>
    <span>Go to top</span>
</button>

<script>
    var BASE_URL = '<?= BASE_URL ?>';

    // Scroll to Top dynamic behavior
    window.addEventListener('scroll', function() {
        var btn = document.getElementById('scrollToTop');
        if (!btn) return;
        if (window.scrollY > 400) {
            btn.style.opacity = '1';
            btn.style.visibility = 'visible';
            btn.style.transform = 'translateY(0)';
        } else {
            btn.style.opacity = '0';
            btn.style.visibility = 'hidden';
            btn.style.transform = 'translateY(10px)';
        }
    });
    var scrollToTopBtn = document.getElementById('scrollToTop');
    if (scrollToTopBtn) {
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // SweetAlert Confirmation Global Event Listener
    document.addEventListener('click', function(e) {
        var confirmBtn = e.target.closest('.btn-confirm');
        if (confirmBtn) {
            e.preventDefault();
            e.stopPropagation();

            var form = confirmBtn.closest('form');
            if (form && !form.checkValidity()) {
                // Trigger standard validation alert by dispatching submit event
                var submitEvent = new Event('submit', { cancelable: true, bubbles: true });
                form.dispatchEvent(submitEvent);
                return;
            }

            var text = confirmBtn.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            var actionUrl = confirmBtn.getAttribute('href');

            Swal.fire({
                title: 'Are you sure?',
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0f766e',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-4 shadow-sm'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (form) {
                        // Submit form programmatically
                        // If the button has a name and value, append it to form before submitting
                        if (confirmBtn.name) {
                            var hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = confirmBtn.name;
                            hiddenInput.value = confirmBtn.value;
                            form.appendChild(hiddenInput);
                        }
                        form.submit();
                    } else if (actionUrl) {
                        window.location.href = actionUrl;
                    }
                }
            });
        }
    });

    // SweetAlert Validation Global Event Listener (Intercepts native browser validation bubbles)
    window.addEventListener('invalid', function(e) {
        var invalidEl = e.target;
        var form = invalidEl.closest('form');

        // Skip validation check if the form is marked to skip
        if (form && form.classList.contains('no-swal-validation')) return;

        // Prevent the browser's default validation bubble tooltip
        e.preventDefault();

        // Prevent multiple SweetAlert popups for multiple invalid inputs in the same form submission
        if (window.swalValidating) return;
        window.swalValidating = true;

        var fieldLabel = '';
        var labelEl = form ? form.querySelector('label[for="' + invalidEl.id + '"]') : null;
        if (labelEl) {
            fieldLabel = labelEl.textContent.replace('*', '').trim();
        } else {
            var siblingLabel = invalidEl.previousElementSibling;
            if (siblingLabel && siblingLabel.tagName === 'LABEL') {
                fieldLabel = siblingLabel.textContent.replace('*', '').trim();
            } else {
                fieldLabel = invalidEl.getAttribute('placeholder') || invalidEl.getAttribute('name') || 'Required field';
            }
        }

        Swal.fire({
            title: 'Required Field',
            text: 'Please fill in or correct: ' + fieldLabel,
            icon: 'error',
            confirmButtonColor: '#0f766e',
            customClass: {
                popup: 'rounded-4 shadow-sm'
            }
        }).then(() => {
            setTimeout(() => {
                invalidEl.focus();
                window.swalValidating = false;
            }, 150);
        });
    }, true);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
