<?php
require_once 'db.php';
require_once 'includes/validation.php';
session_start();

// CSRF token oluştur
$csrf_token = generateCSRFToken();

// Token kontrolü
$token = $_GET['token'] ?? '';
$valid_token = false;
$email = '';

if (!empty($token)) {
    // Token geçerli mi kontrol et
    $stmt = $pdo->prepare('SELECT email, expires FROM password_resets WHERE token = ?');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if ($reset && $reset['expires'] > time()) {
        $valid_token = true;
        $email = $reset['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token doğrulama
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        
        // Token geçerli mi kontrol et
        $stmt = $pdo->prepare('SELECT email, expires FROM password_resets WHERE token = ?');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset || $reset['expires'] <= time()) {
            $error = __t('auth.invalid_token');
        } else {
            // Şifre güçlülük kontrolü
            $password_check = validatePassword($password);
            if (!$password_check['valid']) {
                $error = $password_check['message'];
            } 
            // Şifre eşleşme kontrolü
            else if ($password !== $password2) {
                $error = __t('auth.pass_mismatch');
            } else {
                // Identify user type: Check which table contains this email
                $target_table = null;
                
                // First check users table (individual users)
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$reset['email']]);
                if ($stmt->fetch()) {
                    $target_table = 'users';
                } else {
                    // If not found in users, check corporate_users table
                    $stmt = $pdo->prepare('SELECT id FROM corporate_users WHERE email = ?');
                    $stmt->execute([$reset['email']]);
                    if ($stmt->fetch()) {
                        $target_table = 'corporate_users';
                    }
                }
                
                if (!$target_table) {
                    // Email not found in either table (shouldn't happen with valid token, but safety check)
                    $error = 'Kullanıcı bulunamadı. Lütfen yeni bir şifre sıfırlama bağlantısı talep edin.';
                } else {
                    // Şifreyi hashle ve güncelle
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE {$target_table} SET password = ? WHERE email = ?");
                    $ok = $stmt->execute([$hashed, $reset['email']]);
                    
                    if ($ok) {
                        // Kullanılan token'ı sil
                        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE token = ?');
                        $stmt->execute([$token]);
                        
                        $success = true;
                    } else {
                        $error = 'Şifre güncelleme sırasında bir hata oluştu!';
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
    <title><?php echo __t('auth.reset_title'); ?> - ASEC Kulübü</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="auth-page">
        <div class="auth-container">
            <h2><?php echo __t('auth.reset_title'); ?></h2>
            
            <?php if (!empty($success)): ?>
                <div class="alert-success">
                    <p><?php echo __t('auth.pass_updated'); ?></p>
                    <p>Artık yeni şifrenizle <a href="login.php"><?php echo __t('auth.back_login'); ?></a> yapabilirsiniz.</p>
                </div>
            <?php elseif (!$valid_token && empty($_POST)): ?>
                <div class="alert-error">
                    <p><?php echo __t('auth.invalid_token'); ?></p>
                    <p>Lütfen <a href="sifremi-unuttum.php"><?php echo __t('auth.forgot_title'); ?></a> sayfasından yeni bir sıfırlama bağlantısı talep edin.</p>
                </div>
            <?php else: ?>
                <p class="auth-intro">Lütfen yeni şifrenizi belirleyin.</p>
                <form method="post">
                    <?php if (!empty($error)) { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                    <div class="form-group">
                        <label for="password"><?php echo __t('auth.new_pass'); ?>:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="password2"><?php echo __t('auth.new_pass_confirm'); ?>:</label>
                        <input type="password" id="password2" name="password2" required>
                    </div>
                    <div class="form-group">
                        <div class="password-requirements">
                            <small>Şifreniz en az:</small>
                            <ul>
                                <li id="length-check">8 karakter uzunluğunda</li>
                                <li id="upper-check">Bir büyük harf</li>
                                <li id="lower-check">Bir küçük harf</li>
                                <li id="number-check">Bir rakam</li>
                                <li id="special-check">Bir özel karakter içermelidir</li>
                            </ul>
                        </div>
                    </div>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="cta-button"><?php echo __t('auth.update_btn'); ?></button>
                </form>
            <?php endif; ?>
            
            <p><?php echo __t('auth.remembered'); ?> <a href="login.php"><?php echo __t('auth.back_login'); ?></a></p>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/matrix-animation.js"></script>
    <script src="javascript/password-validator.js"></script>
    <script src="javascript/password-toggle.js"></script>
</body>
</html>
