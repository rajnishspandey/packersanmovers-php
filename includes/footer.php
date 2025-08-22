</main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-truck-moving me-2"></i><?php echo get_setting('company_name', 'PackersAnMovers'); ?></h5>
                    <p>Professional moving and packing services to make your move stress-free.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white">Home</a></li>
                        <li><a href="/services" class="text-white">Services</a></li>
                        <li><a href="/about" class="text-white">About Us</a></li>
                        <li><a href="/contact" class="text-white">Contact</a></li>
                        <li><a href="/quote" class="text-white">Get a Quote</a></li>
                        <li><a href="/privacy-policy" class="text-white">Privacy Policy</a></li>
                        <li><a href="/terms-of-service" class="text-white">Terms of Service</a></li>
                        <li><a href="/disclaimer" class="text-white">Disclaimer</a></li>
                        <li><a href="/cookie-policy" class="text-white">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <address>
                        <i class="fas fa-map-marker-alt me-2"></i><?php echo get_setting('company_address', 'Address not configured'); ?><br>
                        <i class="fas fa-phone me-2"></i><?php echo get_setting('company_phone1', 'Phone not configured'); ?><?php if(get_setting('company_phone2')): ?> / <?php echo get_setting('company_phone2'); ?><?php endif; ?><br>
                        <i class="fas fa-envelope me-2"></i><?php echo get_setting('company_email', 'Email not configured'); ?>
                    </address>
                    <div class="social-icons">
                        <?php $facebook_url = get_setting('facebook_url'); if($facebook_url && $facebook_url != '#'): ?>
                        <a href="<?php echo $facebook_url; ?>" class="text-white me-2" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php $twitter_url = get_setting('twitter_url'); if($twitter_url && $twitter_url != '#'): ?>
                        <a href="<?php echo $twitter_url; ?>" class="text-white me-2" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php $instagram_url = get_setting('instagram_url'); if($instagram_url && $instagram_url != '#'): ?>
                        <a href="<?php echo $instagram_url; ?>" class="text-white me-2" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php $linkedin_url = get_setting('linkedin_url'); if($linkedin_url && $linkedin_url != '#'): ?>
                        <a href="<?php echo $linkedin_url; ?>" class="text-white" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2025 <?php echo get_setting('company_name', 'PackersAnMovers'); ?><?php echo get_setting('company_tagline') ? ' - ' . get_setting('company_tagline') : ''; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <div class="whatsapp-float">
        <a href="https://wa.me/<?php echo str_replace(['+', ' ', '-'], '', get_setting('company_phone1', '917710020974')); ?>?text=Hi, I need help with packers and movers service" target="_blank">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>

    <!-- Floating Call Button (Mobile/Tablet only) -->
    <div class="call-float">
        <a href="tel:<?php echo get_setting('company_phone1', '+917710020974'); ?>">
            <i class="fas fa-phone"></i>
        </a>
    </div>

    <!-- ======== Consent Info Banner (No Buttons) ======== -->
    <div id="consent-banner" style="display:none; position:fixed; bottom:0; left:0; width:100%; background:#222; color:#fff; padding:14px 10px; z-index:9999; text-align:center; font-size:15px;">
    We use cookies and similar technologies to enhance your experience and analyze our traffic. 
    By continuing to browse, you agree to our 
    <a href="/privacy-policy" style="color:#ffd700;text-decoration:underline;">Privacy Policy</a>.
    </div>

    <script>
    function setConsentCookie() {
        var d = new Date();
        d.setFullYear(d.getFullYear() + 1);
        document.cookie = "site_consent=1; expires=" + d.toUTCString() + "; path=/";
    }
    function getConsentCookie() {
        return document.cookie.split(';').some(c => c.trim().startsWith('site_consent=1'));
    }
    function hideBanner() {
        document.getElementById('consent-banner').style.display = 'none';
    }

    window.addEventListener('DOMContentLoaded', function() {
        if (!getConsentCookie()) {
            document.getElementById('consent-banner').style.display = 'block';
            setConsentCookie();
            setTimeout(hideBanner, 5000);
        }
    });
    </script>
    <!-- ======== End Consent Info Banner ======== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>