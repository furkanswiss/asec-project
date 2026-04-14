<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// CSS artık doğrudan sayfalarda yükleniyor, burada bir şey yapmaya gerek yok
if (!defined('HEADER_CSS_LOADED')) {
    define('HEADER_CSS_LOADED', true);
}
	// Dil Dosyasını Güvenli Çağır
if (file_exists(__DIR__ . '/includes/lang.php')) {
    require_once __DIR__ . '/includes/lang.php';
}
// Dil fonksiyonu yoksa hata vermesin diye boş tanımla
if (!function_exists('__t')) {
    function __t($key) { return $key; }
}
// Messages helper functions
if (file_exists(__DIR__ . '/includes/messages.php')) {
    require_once __DIR__ . '/includes/messages.php';
}
// Initialize unread count (only if user is logged in)
$unread_count = 0;
if (isset($_SESSION['user'])) {
    // Ensure db connection exists
    if (!isset($pdo)) {
        require_once __DIR__ . '/db.php';
    }
    // Ensure messages table exists
    try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            sender_type ENUM(\'individual\', \'corporate\') NOT NULL,
            receiver_id INT NOT NULL,
            receiver_type ENUM(\'individual\', \'corporate\') NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message_body TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_receiver (receiver_id, receiver_type, is_read),
            INDEX idx_sender (sender_id, sender_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        error_log("Error creating messages table in header: " . $e->getMessage());
    }
    $unread_count = getUnreadMessageCount($pdo);
}

// MANDATORY CV ENFORCEMENT FOR INDIVIDUAL USERS
// This check runs on all pages that include header.php
if (isset($_SESSION['user']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'individual') {
    // Get current script name to prevent infinite loops
    $currentScript = basename($_SERVER['PHP_SELF']);
    
    // Pages that should be excluded from CV check (to prevent infinite redirect loops)
    $excludedPages = [
        'load-cv.php',
        'load_cv.php',
        'cv-goruntule.php',
        'cv-sil.php',
        'login.php',
        'register.php',
        'logout.php',
        'corporate-login.php',
        'corporate-register.php',
        'sifremi-unuttum.php',
        'sifre-sifirla.php'
    ];
    
    // Only check if not on excluded pages
    if (!in_array($currentScript, $excludedPages)) {
        // Check if we're in admin or corporate directories (exclude those)
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        $isAdminPath = strpos($currentPath, '/admin/') !== false;
        $isCorporatePath = strpos($currentPath, '/corporate/') !== false;
        
        if (!$isAdminPath && !$isCorporatePath) {
            // Require database connection
            if (!isset($pdo)) {
                require_once __DIR__ . '/db.php';
            }
            
            // Get user ID from session or fetch from database
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                // Fetch user ID from database
                $email = $_SESSION['user'];
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if ($user) {
                    $user_id = $user['id'];
                    $_SESSION['user_id'] = $user_id; // Cache in session
                }
            }
            
            // Check if user has a CV
            if ($user_id) {
                // Ensure user_cv_profiles table exists
                $pdo->exec('CREATE TABLE IF NOT EXISTS user_cv_profiles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    major VARCHAR(255) DEFAULT NULL,
                    languages TEXT DEFAULT NULL,
                    software_fields TEXT DEFAULT NULL,
                    companies TEXT DEFAULT NULL,
                    cv_filename VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                
                // Check if CV exists
                $stmt = $pdo->prepare('SELECT cv_filename FROM user_cv_profiles WHERE user_id = ? LIMIT 1');
                $stmt->execute([$user_id]);
                $cvProfile = $stmt->fetch();
                
                $hasCv = false;
                if ($cvProfile && !empty($cvProfile['cv_filename'])) {
                    // Also verify file exists
                    $cvFilePath = __DIR__ . '/uploads/cv/' . $cvProfile['cv_filename'];
                    if (file_exists($cvFilePath)) {
                        $hasCv = true;
                    }
                }
                
                // If no CV, redirect to upload page
                if (!$hasCv) {
                    // Clean output buffer before redirect
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Set flash message
                    $_SESSION['cv_required'] = true;
                    $_SESSION['cv_required_message'] = __t('cv.mandatory.message');
                    
                    // Redirect to CV upload page
                    header('Location: load-cv.php');
                    exit;
                }
            }
        }
    }
}
?>
<header>
    <div class="header-container">
        <div class="mobile-menu">
            <i class="fas fa-bars"></i>
            <i class="fas fa-times" style="display: none;"></i>
        </div>
        <div class="logo">
            <a href="index.php">
                <img src="images/gallery/try.png" alt="ASEC Logo" id="site-logo">
            </a>
            <link rel="icon" href="images/gallery/try.png" type="image/png">
        </div>
        <nav>
            <ul class="nav-links">
				<li><a href="index"><i class="fas fa-home"></i> <?php echo __t('nav.home'); ?></a></li>
                <li class="dropdown">
					<a href="#"><i class="fas fa-info-circle"></i> <?php echo __t('nav.about'); ?> <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
						<li><a href="hakkimizda"><?php echo __t('nav.club'); ?></a></li>
						<li><a href="yonetim-kurulu"><i class="fas fa-users-cog"></i> <?php echo __t('board.title'); ?></a></li>
						<li><a href="takimlar"><i class="fas fa-users"></i> <?php echo __t('nav.teams'); ?></a></li>
						<li><a href="galeri"><i class="fas fa-images"></i> <?php echo __t('nav.gallery'); ?></a></li>
                    </ul>
                </li>
                <li class="dropdown">
					<a href="#"><i class="fas fa-bullhorn"></i> <?php echo __t('nav.announcements_jobs'); ?> <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
						<li><a href="duyurular"><i class="fas fa-bullhorn"></i> <?php echo __t('nav.announcements'); ?></a></li>
						<li><a href="ilanlar"><i class="fas fa-briefcase"></i> <?php echo __t('nav.jobs'); ?></a></li>
                    </ul>
                </li>
				<li><a href="etkinlikler"><i class="fas fa-calendar-alt"></i> <?php echo __t('nav.events'); ?></a></li>
				<li><a href="sponsorlar"><i class="fas fa-handshake"></i> <?php echo __t('nav.sponsors'); ?></a></li>
				<li><a href="blog"><i class="fas fa-blog"></i> <?php echo __t('nav.blog'); ?></a></li>
				<li><a href="iletisim"><i class="fas fa-envelope"></i> <?php echo __t('nav.contact'); ?></a></li>
            </ul>
        </nav>
		<?php
			// Language toggle (TR <-> EN) button
			$currentUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
			$baseUrl = strtok($currentUrl, '?');
			$query = $_GET;
			$targetLang = (isset($langCode) && $langCode === 'en') ? 'tr' : 'en';
			$query['lang'] = $targetLang;
			$toggleUrl = $baseUrl . '?' . http_build_query($query);
		?>
		<div class="lang-toggle">
			<a href="<?php echo htmlspecialchars($toggleUrl); ?>" class="btn-lang" title="Language / Dil">
				<i class="fas fa-globe"></i>
				<span><?php echo __t('lang.label'); ?></span>
			</a>
		</div>
        <?php
            if (session_status() === PHP_SESSION_NONE) session_start();
            $user_type = $_SESSION['user_type'] ?? 'individual';
            if (isset($_SESSION['user'])) {
        ?>
            <div class="auth-buttons">
                <?php if ($user_type === 'corporate'): ?>
                    <a href="corporate/dashboard.php" class="btn-login corporate-panel-btn" style="background: rgba(147, 112, 219, 0.2); border: 1px solid rgba(147, 112, 219, 0.5); border-radius: 5px; padding: 0.45rem 0.8rem; font-size: 0.85rem;">
                        <i class="fas fa-building"></i> <span class="corporate-panel-text">Kurumsal Panel</span>
                    </a>
                <?php else: ?>
                    <!-- Messages link only for individual users -->
                    <a href="mailbox.php" class="btn-login mailbox-icon-btn" style="position: relative;">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="mailbox-badge"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
				<a href="profilim" class="btn-login"><i class="fas fa-user"></i> <?php echo __t('auth.profile'); ?></a>
				<a href="logout" class="btn-register"><i class="fas fa-sign-out-alt"></i> <?php echo __t('auth.logout'); ?></a>
            </div>
        <?php } else { ?>
            <div class="auth-buttons">
                <button onclick="window.location.href='login.php'" class="btn liquid">
                    <span><?php echo __t('auth.login'); ?></span>
                </button>
                <button onclick="window.location.href='register.php'" class="btn liquid" id="btn-register">
                    <span><?php echo __t('auth.register'); ?></span>
                </button>
            </div>
        <?php } ?>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure <html lang> reflects current language on all pages
        try { document.documentElement.setAttribute('lang', <?php echo json_encode(isset($langCode) ? $langCode : 'tr'); ?>); } catch (e) {}
        // Mobil menü açma/kapama
        const mobileMenu = document.querySelector('.mobile-menu');
        const navLinks = document.querySelector('.nav-links');
        const menuBars = document.querySelector('.fa-bars');
        const menuClose = document.querySelector('.fa-times');
        
        mobileMenu.addEventListener('click', function() {
            const nav = document.querySelector('nav');
            nav.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            
            if (mobileMenu.classList.contains('active')) {
                menuBars.style.display = 'none';
                menuClose.style.display = 'block';
            } else {
                menuBars.style.display = 'block';
                menuClose.style.display = 'none';
            }
        });
        
        // Dropdown menüleri mobil görünümde açma/kapama
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const dropdownLink = dropdown.querySelector('a');
            
            dropdownLink.addEventListener('click', function(e) {
                if (window.innerWidth <= 968) {
                    e.preventDefault();
                    dropdown.classList.toggle('active');
                }
            });
        });

        // Language button touch feedback for mobile
        const langButtons = document.querySelectorAll('.btn-lang');
        langButtons.forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.98)';
            });
            
            btn.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.opacity = '1';
                    this.style.transform = 'scale(1)';
                }, 100);
            });
        });
    });
</script>
