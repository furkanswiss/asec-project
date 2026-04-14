<?php
require_once 'db.php';
require_once 'includes/validation.php';
require_once 'includes/email_queue_helper.php';
session_start();

// CSRF token oluştur
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token doğrulama
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        // E-posta doğrulama
        if (!validateEmail($email)) {
            $error = 'Geçersiz e-posta adresi!';
        } else {
            // CAPTCHA doğrulama
            $captcha_response = $_POST['g-recaptcha-response'] ?? '';
            if (!validateCaptcha($captcha_response)) {
                $error = 'Robot olmadığınızı doğrulayın!';
            } else {
                // E-posta adresinin kayıtlı olup olmadığını kontrol et
                // First check users table (individual users)
                $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                $user_name = null;
                
                // If not found in users, check corporate_users table
                if (!$user) {
                    $stmt = $pdo->prepare('SELECT id, company_name FROM corporate_users WHERE email = ?');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    if ($user) {
                        $user_name = $user['company_name']; // Use company_name for corporate users
                    }
                } else {
                    $user_name = $user['name']; // Use name for individual users
                }
                
                if (!$user) {
                    $error = __t('auth.user_not_found');
                } else {
                    // Şifre sıfırlama token'ı oluştur
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + 3600; // 1 saat geçerli
                    
                    // Eski token'ları temizle
                    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
                    $stmt->execute([$email]);
                    
                    // Yeni token'ı kaydet
                    $stmt = $pdo->prepare('INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?)');
                    $ok = $stmt->execute([$email, $token, $expires]);
                    
                    if ($ok) {
                        // Construct reset link
                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $base_path = dirname($_SERVER['PHP_SELF']);
                        $reset_link = $protocol . '://' . $host . $base_path . '/sifre-sifirla.php?token=' . $token;
                        
                        // Construct HTML email body
                        $email_body = "
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                        </head>
                        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #9370db;'>Şifre Sıfırlama İsteği</h2>
                                <p>Merhaba " . htmlspecialchars($user_name) . ",</p>
                                <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
                                <p style='text-align: center; margin: 30px 0;'>
                                    <a href='" . htmlspecialchars($reset_link) . "' 
                                       style='background-color: #9370db; color: white; padding: 12px 30px; 
                                              text-decoration: none; border-radius: 5px; display: inline-block;'>
                                        Şifremi Sıfırla
                                    </a>
                                </p>
                                <p style='color: #666; font-size: 0.9em;'>
                                    Bu bağlantı 1 saat süreyle geçerlidir. Eğer bu isteği siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.
                                </p>
                                <p style='color: #666; font-size: 0.9em;'>
                                    Bu e-posta ASEC Kulübü tarafından gönderilmiştir.
                                </p>
                            </div>
                        </body>
                        </html>
                        ";
                        
                        // Queue the email with high priority (10)
                        $queue_result = queueEmail($pdo, $email, $user_name, "Şifre Sıfırlama İsteği", $email_body, 10);
                        
                        if ($queue_result !== false) {
                            $success = true;
                            
                            // Trigger background email sender (if exec is available)
                            if (function_exists('exec')) {
                                $phpBinary = PHP_BINARY; // Gets the exact path, e.g. /usr/bin/php
                                $scriptPath = __DIR__ . '/cron_sender.php';
                                
                                // Get cron key from environment
                                $cron_key = $_ENV['CRON_KEY'] ?? getenv('CRON_KEY');
                                
                                if (!empty($cron_key)) {
                                    // Construct the URL for the cron sender (since it expects HTTP GET)
                                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                    $base_path = dirname($_SERVER['PHP_SELF']);
                                    $cron_url = $protocol . '://' . $host . $base_path . '/cron_sender.php?key=' . urlencode($cron_key);
                                    
                                    // Use PHP_BINARY with inline code to make HTTP request
                                    // Ensure paths are quoted safely
                                    $command = $phpBinary . ' -r "file_get_contents(' . escapeshellarg($cron_url) . ');" > /dev/null 2>&1 &';
                                    
                                    // Execute
                                    exec($command);
                                    
                                    // Optional: Log for debugging
                                    error_log("Attempted to trigger background email: " . $command);
                                } else {
                                    error_log("Warning: CRON_KEY not found. Cannot trigger background email sender.");
                                }
                            } else {
                                error_log("Warning: exec() function is disabled on this server. Email will wait for cron.");
                            }
                        } else {
                            $error = 'E-posta kuyruğa eklenirken bir hata oluştu. Lütfen tekrar deneyin.';
                        }
                    } else {
                        $error = 'Şifre sıfırlama işlemi sırasında bir hata oluştu!';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('auth.forgot_title'); ?> - ASEC Kulübü</title>
    <link rel="stylesheet" href="css/auth.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="auth-page">
        <div class="auth-container">
            <h2><?php echo __t('auth.forgot_title'); ?></h2>
            <?php if (!empty($success)): ?>
                <div class="alert-success">
                    <p><?php echo __t('auth.link_sent'); ?></p>
                    <p><?php echo __t('auth.check_inbox'); ?></p>
                    <p style="margin-top: 10px; font-weight: 600; color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                        <strong>ℹ️ <?php echo __t('auth.link_sent_note'); ?></strong>
                    </p>
                </div>
            <?php else: ?>
                <p class="auth-intro"><?php echo (isset($langCode) && $langCode === 'en') ? 'Enter your email address and we will send you a password reset link.' : 'E-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.'; ?></p>
                <form method="post">
                    <?php if (!empty($error)) { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                    <div class="form-group">
                        <label for="email"><?php echo __t('auth.email_label'); ?>:</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="6LeLMC8rAAAAAChTj8rlQ_zyjedV3VdnejoNAZy1"></div>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="cta-button"><?php echo __t('auth.send_btn'); ?></button>
                </form>
            <?php endif; ?>
            <p><?php echo __t('auth.remembered'); ?> <a href="login.php"><?php echo __t('auth.back_login'); ?></a></p>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/matrix-animation.js"></script>
</body>
</html>
