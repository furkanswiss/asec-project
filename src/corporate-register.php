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

// Corporate requests tablosunu oluştur (yoksa) - Onay bekleyen kurumsal kullanıcı istekleri
$pdo->exec('CREATE TABLE IF NOT EXISTS corporate_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    tax_number VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    status ENUM("pending", "approved", "rejected") DEFAULT "pending",
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

// CSRF token oluştur
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token doğrulama
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $tax_number = trim($_POST['tax_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        
        // E-posta doğrulama
        if (!validateEmail($email)) {
            $error = 'Geçersiz e-posta adresi!';
        } 
        // Telefon doğrulama
        else if (!validatePhone($phone)) {
            $error = 'Geçersiz telefon numarası! Lütfen geçerli bir Türkiye telefon numarası girin.';
        }
        // Şifre eşleşme kontrolü
        else if ($password !== $password2) {
            $error = 'Şifreler eşleşmiyor!';
        } 
        // Şifre güçlülük kontrolü
        else {
            $password_check = validatePassword($password);
            if (!$password_check['valid']) {
                $error = $password_check['message'];
            }
            // Zorunlu alanların doldurulduğunu kontrol et
            else if (!$company_name || !$contact_person || !$phone || !$email || !$password) {
                $error = 'Lütfen zorunlu alanları doldurun!';
            } 
            // CAPTCHA doğrulama
            else {
                $captcha_response = $_POST['g-recaptcha-response'] ?? '';
                if (!validateCaptcha($captcha_response)) {
                    $error = 'Robot olmadığınızı doğrulayın!';
                } else {
                    // E-posta daha önce kayıtlı mı? (hem users hem corporate_users tablolarında kontrol et)
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'Bu e-posta adresi zaten bireysel kullanıcı olarak kayıtlı!';
                    } else {
                        // Check if already exists in corporate_users
                        $stmt = $pdo->prepare('SELECT id FROM corporate_users WHERE email = ?');
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = 'Bu e-posta adresi zaten kurumsal kullanıcı olarak kayıtlı!';
                        } else {
                            // Check if there's a pending or approved request (not rejected)
                            $stmt = $pdo->prepare('SELECT id FROM corporate_requests WHERE email = ? AND status IN ("pending", "approved")');
                            $stmt->execute([$email]);
                            if ($stmt->fetch()) {
                                $error = 'Bu e-posta adresi için zaten bir onay bekleyen veya onaylanmış istek var!';
                            } else {
                                // Şifreyi hashle ve istek olarak kaydet
                                $hashed = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare('INSERT INTO corporate_requests (company_name, contact_person, email, phone, address, tax_number, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, "pending")');
                                $ok = $stmt->execute([$company_name, $contact_person, $email, $phone, $address, $tax_number, $hashed]);
                                if ($ok) {
                                    $success = true;
                                    $success_message = 'Kayıt isteğiniz başarıyla oluşturuldu! Hesabınız yönetici onayından sonra aktif olacaktır.';
                                } else {
                                    $error = 'Kayıt sırasında bir hata oluştu!';
                                }
                            }
                        }
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
    <title>Kurumsal Kayıt - ASEC</title>
    <link rel="stylesheet" href="css/auth.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="auth-page">
        <div class="auth-container">
            <h2>Kurumsal Kayıt</h2>
            <form method="post">
                <?php if (!empty($error)) { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                <?php if (!empty($success)) { 
                    echo '<div class="alert-success">'.($success_message ?? 'Kayıt isteğiniz başarıyla oluşturuldu! Hesabınız yönetici onayından sonra aktif olacaktır.').'</div>'; 
                } ?>
                <div class="form-group">
                    <label for="company_name">Şirket Adı <span style="color: red;">*</span></label>
                    <input type="text" id="company_name" name="company_name" required value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="contact_person">İletişim Kişisi <span style="color: red;">*</span></label>
                    <input type="text" id="contact_person" name="contact_person" required value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Telefon <span style="color: red;">*</span></label>
                    <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">E-posta <span style="color: red;">*</span></label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="address">Adres</label>
                    <textarea id="address" name="address" rows="3" style="width: 100%; padding: 12px 15px; border: 2px solid var(--primary); background-color: var(--secondary); font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit;"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="tax_number">Vergi Numarası</label>
                    <input type="text" id="tax_number" name="tax_number" value="<?php echo htmlspecialchars($_POST['tax_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Şifre <span style="color: red;">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="password2">Şifre Tekrar <span style="color: red;">*</span></label>
                    <input type="password" id="password2" name="password2" required>
                </div>
                <div class="form-group">
                    <div class="password-requirements">
                        <small>Şifre Gereksinimleri:</small>
                        <ul>
                            <li id="length-check">En az 8 karakter</li>
                            <li id="upper-check">En az bir büyük harf</li>
                            <li id="lower-check">En az bir küçük harf</li>
                            <li id="number-check">En az bir rakam</li>
                            <li id="special-check">En az bir özel karakter</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6LeLMC8rAAAAAChTj8rlQ_zyjedV3VdnejoNAZy1"></div>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <button type="submit" class="cta-button">Kayıt Ol</button>
            </form>
            <p>Zaten hesabınız var mı? <a href="corporate-login.php">Giriş Yap</a></p>
            <p style="margin-top: 10px; font-size: 0.9rem; color: #666;">Bireysel kullanıcı mısınız? <a href="register.php" style="color: #9370db; font-weight: 600;">Bireysel Kayıt</a></p>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/matrix-animation.js"></script>
    <script src="javascript/password-validator.js"></script>
    <script src="javascript/password-toggle.js"></script>
</body>
</html>

