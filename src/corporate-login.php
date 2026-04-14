<?php
require_once 'db.php';
require_once 'includes/validation.php';
session_start();

// Corporate users tablosunu oluştur (yoksa)
$pdo->exec('CREATE TABLE IF NOT EXISTS corporate_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    tax_number VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

// CSRF token oluştur
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token doğrulama
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        // E-posta doğrulama
        if (!validateEmail($email)) {
            $error = 'Geçersiz e-posta adresi!';
        } else {
            // CAPTCHA doğrulama
            $captcha_response = $_POST['g-recaptcha-response'] ?? '';
            if (!validateCaptcha($captcha_response)) {
                $error = 'Robot olmadığınızı doğrulayın!';
            } else {
                // Giriş denemesi kontrolü
                $login_check = checkLoginAttempts($pdo, $email);
                if ($login_check['locked']) {
                    $error = $login_check['message'];
                } else {
                    $stmt = $pdo->prepare('SELECT * FROM corporate_users WHERE email = ?');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Başarılı giriş, denemeleri sıfırla
                        resetLoginAttempts($pdo, $email);
                        $_SESSION['user'] = $user['email'];
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['company_name'];
                        $_SESSION['user_type'] = 'corporate'; // Corporate user işareti
                        $_SESSION['contact_person'] = $user['contact_person'];
                        header('Location: corporate/dashboard.php');
                        exit;
                    } else {
                        $error = 'E-posta veya şifre hatalı!';
                    }
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('login.corporate'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/auth.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="auth-page">
        <div class="auth-container">
            <h2><?php echo __t('login.corporate'); ?></h2>
            <form method="post">
                <?php if (!empty($error)) { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                <div class="form-group">
                    <label for="email"><?php echo __t('login.email'); ?></label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password"><?php echo __t('login.password'); ?></label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-info">
                        <small><?php echo __t('login.forgot'); ?> <a href="sifremi-unuttum.php"><?php echo __t('login.reset'); ?></a></small>
                    </div>
                </div>
                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6LeLMC8rAAAAAChTj8rlQ_zyjedV3VdnejoNAZy1"></div>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <button type="submit" class="cta-button"><?php echo __t('login.submit'); ?></button>
            </form>
            <p><?php echo __t('login.no_account'); ?> <a href="corporate-register.php"><?php echo __t('login.register'); ?></a></p>
            <p style="margin-top: 10px; font-size: 0.9rem; color: #666;"><?php echo __t('login.individual_switch'); ?> <a href="login.php" style="color: #9370db; font-weight: 600;"><?php echo __t('login.individual_link'); ?></a></p>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/matrix-animation.js"></script>
    <script src="javascript/password-toggle.js"></script>
</body>
</html>

