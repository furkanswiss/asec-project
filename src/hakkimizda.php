<?php 
require_once 'db.php';
ob_start(); // Docker için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu yakalamak için şart
}
 // Veritabanı bağlantısı

// ... Buradan sonra sayfanın kendi kodları başlar ...


require_once 'includes/lang.php'; 
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('about.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/hakkimizda.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <section class="about-header">
            <h2><?php echo __t('about.title'); ?></h2>
            <p>
                <?php echo __t('about.intro'); ?>
            </p>
        </section>
        
        <section class="about-hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h2 class="animate-fade-in"><?php echo __t('about.title'); ?></h2>
                    <p class="animate-slide-up"><?php echo __t('about.description'); ?></p>
                    <div class="hero-stats animate-scale-up">
                        <div class="stat-item">
                            <span class="stat-number">7</span>
                            <span class="stat-label"><?php echo __t('about.stats.departments'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">180+</span>
                            <span class="stat-label"><?php echo __t('about.stats.members'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">20+</span>
                            <span class="stat-label"><?php echo __t('about.stats.projects'); ?></span>
                        </div>
                    </div>
                </div>
                <!-- Carousel Başlangıç -->
                <div class="hero-carousel animate-fade-in">
                    <div class="carousel-slide">
                        <img src="images/gallery/galeri_6821cd1f764311.33979100.jpg" alt="Galeri 1">
                    </div>
                    <div class="carousel-slide">
                        <img src="images/gallery/galeri_6821cd1f749576.68545737.jpg" alt="Galeri 2">
                    </div>
                    <div class="carousel-slide">
                        <img src="images/gallery/gameEvent.png" alt="Galeri 3">
                    </div>
                </div>
        <!-- Carousel Bitiş -->
            </div>
        </section>
        
        <section class="vision-mission-section">
            <div class="container">
                <div class="section-header animate-fade-in">
                    <h2><?php echo __t('about.vision_mission.title'); ?></h2>
                    <div class="divider"></div>
                </div>
                <div class="vision-mission-grid">
                    <div class="vision-card animate-slide-up">
                        <div class="icon-container">
                            <!-- Lottie animasyonu -->
                            <lottie-player src="images/animations/vision.json" background="transparent" speed="1" loop autoplay></lottie-player>
                        </div>
                        <h3><?php echo __t('about.vision.title'); ?></h3>
                        <p><?php echo __t('about.vision.description'); ?></p>
                    </div>
                    <div class="mission-card animate-slide-up">
                        <div class="icon-container">
                            <!-- Lottie animasyonu -->
                    <lottie-player src="images/animations/mission.json" background="transparent" speed="1" loop autoplay></lottie-player>
                        </div>
                        <h3><?php echo __t('about.mission.title'); ?></h3>
                        <p><?php echo __t('about.mission.description'); ?></p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Board Members Section -->
        <section class="board-members-section">
            <div class="container">
                <div class="section-header animate-fade-in">
                    <h2>Yönetim Kurulu</h2>
                    <div class="divider"></div>
                </div>
                <?php
                require_once 'db.php';
                try {
                    $boardMembers = $pdo->query('SELECT * FROM board_members ORDER BY created_at DESC')->fetchAll();
                } catch (PDOException $e) {
                    $boardMembers = [];
                }
                ?>
                <?php if(!empty($boardMembers)): ?>
                <div class="board-members-grid">
                    <?php foreach($boardMembers as $member): ?>
                    <div class="board-member-card animate-slide-up">
                        <div class="member-image-wrapper">
                            <?php if(!empty($member['profileImage'])): ?>
                                <img src="<?= htmlspecialchars($member['profileImage']) ?>" alt="<?= htmlspecialchars($member['name']) ?>" class="member-image">
                            <?php else: ?>
                                <div class="member-image-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="member-name"><?= htmlspecialchars($member['name']) ?></h3>
                        <p class="member-position"><?= htmlspecialchars($member['position']) ?></p>
                        <div class="member-social">
                            <?php if(!empty($member['linkedinUrl'])): ?>
                                <a href="<?= htmlspecialchars($member['linkedinUrl']) ?>" target="_blank" rel="noopener noreferrer" class="social-link linkedin" aria-label="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            <?php endif; ?>
                            <?php if(!empty($member['githubUrl'])): ?>
                                <a href="<?= htmlspecialchars($member['githubUrl']) ?>" target="_blank" rel="noopener noreferrer" class="social-link github" aria-label="GitHub">
                                    <i class="fab fa-github"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="view-all-section" style="text-align: center; margin-top: 3rem;">
                    <a href="yonetim-kurulu" class="view-all-btn">
                        <i class="fas fa-arrow-right"></i> <?php echo __t('board.title'); ?> Sayfasını Görüntüle
                    </a>
                </div>
                <?php else: ?>
                <div class="empty-state" style="text-align: center; padding: 3rem 2rem; color: #666;">
                    <i class="fas fa-users" style="font-size: 4rem; color: #9370db; margin-bottom: 1.5rem; opacity: 0.6;"></i>
                    <h3 style="font-size: 1.8rem; color: #1b1f3b; margin-bottom: 1rem;"><?php echo __t('board.empty.title'); ?></h3>
                    <p style="font-size: 1.1rem; max-width: 600px; margin: 0 auto; line-height: 1.6;"><?php echo __t('board.empty.message'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/script.js"></script>
    <!-- Lottie Player Script -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <script>
        const slides = document.querySelectorAll('.carousel-slide');
        let currentIndex = 0;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
        }

        // İlk slide'ı göster
        showSlide(currentIndex);

        // Otomatik değişim her 3 saniye
        setInterval(() => {
            currentIndex = (currentIndex + 1) % slides.length;
            showSlide(currentIndex);
        }, 3000);
    </script>
</body>
</html>
