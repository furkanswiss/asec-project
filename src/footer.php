<!-- DEBUG: footer.php LOADED -->
<footer>
    <div class="footer-container">
        <div class="footer-section">
            <div class="logo-text">
                <img src="images/gallery/try.png" alt="ASEC Logo" class="footer-logo">
                <p><?php echo __t('footer.tagline'); ?></p>
            </div>
            <div class="footer-links">
                <a href="index"><i class="fas fa-chevron-right"></i> <?php echo __t('footer.links.home'); ?></a>
                <a href="hakkimizda"><i class="fas fa-chevron-right"></i> <?php echo __t('footer.links.about'); ?></a>
                <a href="takimlar"><i class="fas fa-chevron-right"></i> <?php echo __t('footer.links.teams'); ?></a>
                <a href="galeri"><i class="fas fa-chevron-right"></i> <?php echo __t('footer.links.gallery'); ?></a>
                <a href="duyurular"><i class="fas fa-chevron-right"></i> <?php echo __t('footer.links.announcements'); ?></a>
                <a href="etkinlikler"><i class="fas fa-chevron-right"></i> <?php echo __t('footer.links.events'); ?></a>
                <a href="blog"><i class="fas fa-chevron-right"></i> <?php echo __t('footer.links.blog'); ?></a>
            </div>
        </div>
        
        <div class="footer-section">
            <h3><?php echo __t('footer.contact'); ?></h3>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <p> Ayvalı Mah. 150 Sk. Etlik-Keçiören</p>
                        <p>Ankara, Türkiye</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <p>yonetim@aybuasec.org</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <p>+90 551 553 6339</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-section">
            <h3><?php echo __t('footer.social'); ?></h3>
            <p><?php echo __t('footer.follow'); ?></p>
            <div class="social-links">
                <a href="https://www.instagram.com/asecaybu?igsh=MXdya2IxMnZ6ejQyeg==" class="social-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                <a href="https://www.linkedin.com/company/aybu-software-engineering-club/" class="social-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-linkedin"></i></a>
                <a href="https://youtube.com/@asecaybu?si=P7D6UUyN6jX_oiYO" class="social-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-youtube"></i></a>
            </div>
        </div>

    </div>

     <div class="copyright">
        &copy; <?php echo date('Y'); ?> <?php echo __t('footer.copy'); ?>
    </div> 

     <!-- Yukarı Çık Butonu -->  
    <button id="scrollToTopBtn" title="<?php echo __t('footer.scroll_top'); ?>">
        <img src="images/arrow-up.png" alt="<?php echo __t('footer.scroll_top'); ?>" />
    </button>
    <script src="js/scroll-top.js"></script>
    
</footer>

<!-- DEBUG: About to include cookie-banner.php -->
<?php
// Cookie Banner - Include before closing body tag
if (file_exists(__DIR__ . '/includes/cookie-banner.php')) {
    include_once __DIR__ . '/includes/cookie-banner.php';
} else {
    echo '<!-- DEBUG: cookie-banner.php NOT FOUND at: ' . __DIR__ . '/includes/cookie-banner.php -->';
}
?>
