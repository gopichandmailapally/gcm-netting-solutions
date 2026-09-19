    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <!-- Company Info -->
                    <div class="footer-column">
                        <div class="footer-logo">
                            <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="<?php echo COMPANY_NAME; ?>">
                            <h3><?php echo COMPANY_NAME; ?></h3>
                        </div>
                        <p><?php echo SITE_TAGLINE; ?></p>
                        <div class="footer-social">
                            <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                            <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul class="footer-links">
                            <li><a href="<?php echo SITE_URL; ?>/"><i class="fas fa-angle-right"></i> Home</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/about.php"><i class="fas fa-angle-right"></i> About Us</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/faqs.php"><i class="fas fa-angle-right"></i> FAQ's</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/estimation.php"><i class="fas fa-angle-right"></i> Price Estimation</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/gallery.php"><i class="fas fa-angle-right"></i> Gallery</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/reviews.php"><i class="fas fa-angle-right"></i> Reviews</a></li>
                        </ul>
                    </div>
                    
                    <!-- Services -->
                    <div class="footer-column">
                        <h4>Our Services</h4>
                        <ul class="footer-links">
                            <?php
                            $footer_services = [];
                            if (isset($conn)) {
                                $result = $conn->query("SELECT service_slug, service_name FROM services WHERE is_active = 1 ORDER BY display_order LIMIT 6");
                                if ($result) {
                                    $footer_services = $result->fetch_all(MYSQLI_ASSOC);
                                }
                            }
                            
                            foreach ($footer_services as $service):
                            ?>
                                <li>
                                    <a href="/<?php echo $service['service_slug']; ?>.php">
                                        <i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($service['service_name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="footer-column">
                        <h4>Contact Us</h4>
                        <ul class="footer-contact">
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo COMPANY_ADDRESS; ?></span>
                            </li>
                            <li>
                                <i class="fas fa-phone"></i>
                                <a href="tel:<?php echo COMPANY_PHONE; ?>">+91-<?php echo COMPANY_PHONE; ?></a>
                            </li>
                            <li>
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:<?php echo COMPANY_EMAIL; ?>"><?php echo COMPANY_EMAIL; ?></a>
                            </li>
                            <li>
                                <i class="fas fa-clock"></i>
                                <span>Mon - Sun: 8:00 AM - 8:00 PM</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All Rights Reserved.</p>
                    <div class="footer-bottom-links">
                        <a href="<?php echo SITE_URL; ?>/privacy-policy.php">Privacy Policy</a>
                        <a href="<?php echo SITE_URL; ?>/terms-conditions.php">Terms & Conditions</a>
                        <a href="<?php echo SITE_URL; ?>/sitemap.xml">Sitemap</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/mega-menu.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/slider.js"></script>
    
    <!-- Visitor Tracking Script - ADDED FOR LIVE VISITORS FEATURE -->
    <script src="<?php echo SITE_URL; ?>/assets/js/visitor-tracking.js"></script>
    
    <!-- Contact Form Handler with Success Popup - UNIVERSAL FOR ALL FORMS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/contact-form-handler.js"></script>
    
    <!-- Additional JS if specified -->
    <?php if (isset($additional_js) && is_array($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo SITE_URL; ?>/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Google Analytics (Add your tracking ID) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script>
</body>
</html>
