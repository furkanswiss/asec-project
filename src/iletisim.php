<?php
// Veritabanı bağlantısını db.php'den al
require_once 'db.php';
require_once 'includes/validation.php';

ob_start(); // Docker için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu yakalamak için şart
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // CAPTCHA doğrulama
    $captcha_response = $_POST['g-recaptcha-response'] ?? '';
    if (!validateCaptcha($captcha_response)) {
        $error = 'Lütfen robot olmadığınızı doğrulayın.';
    } elseif ($name && $email && $subject && $message) {
        $stmt = $pdo->prepare('INSERT INTO mesajlar (ad, email, konu, mesaj, ip, tarih) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $email, $subject, $message, $ip]);
        $success = true;
    } else {
        $error = __t('contact.form.error');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('contact.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/iletisim.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="iletisim-page">
    <?php include 'header.php'; ?>

    <main>
        <section class="contact-container">
            <!-- Sol: Bilgi ve Sosyal Medya -->
            <div class="contact-info">
                <div>
                    <h2><?php echo __t('contact.info'); ?></h2>
                    <div class="contact-details">
                        <div class="contact-item"><i class="fas fa-map-marker-alt"></i> Ayvalı Mah. 150 Sk. Etlik-Keçiören Ankara</div>
                        <div class="contact-item"><i class="fas fa-phone"></i> +90 551 553 6339</div>
                        <div class="contact-item"><i class="fas fa-envelope"></i> ASECAybu@outlook.com</div>
                    </div>
                </div>
                <div class="social-media">
                    <h3><?php echo __t('contact.social.title'); ?></h3>
                    <div class="social-links">
                        <a href="https://www.instagram.com/asecaybu?igsh=MXdya2IxMnZ6ejQyeg==" class="social-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/company/aybu-software-engineering-club/" class="social-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-linkedin"></i></a>
                        <a href="https://youtube.com/@asecaybu?si=P7D6UUyN6jX_oiYO" class="social-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="whatsapp-group">
                        <div class="whatsapp-card">
                            <div class="wa-card-header">
                                <i class="fab fa-whatsapp"></i>
                                <span><?php echo __t('contact.whatsapp.title'); ?></span>
                            </div>
                            <div class="wa-card-body"><?php echo __t('contact.whatsapp.desc'); ?></div>
                            <a href="https://chat.whatsapp.com/DuZcHLL1ybg63MHOQKrkav" target="_blank" class="wa-join-btn">
                                <i class="fab fa-whatsapp"></i> <?php echo __t('contact.whatsapp.join'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sağ: Form -->
            <div class="contact-form">
                <h3><?php echo __t('contact.form.title'); ?></h3>
                <?php if (!empty($success)) { echo '<div class="alert-success">'.__t('contact.form.success').'</div>'; } ?>
                <?php if (!empty($error)) { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="name"><?php echo __t('contact.form.name'); ?></label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><?php echo __t('contact.form.email'); ?></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject"><?php echo __t('contact.form.subject'); ?></label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message"><?php echo __t('contact.form.message'); ?></label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="6LeLMC8rAAAAAChTj8rlQ_zyjedV3VdnejoNAZy1"></div>
                    </div>
                    <button type="submit" class="cta-button"><?php echo __t('contact.form.send'); ?></button>
                </form>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/script.js"></script>
</body>
</html>
