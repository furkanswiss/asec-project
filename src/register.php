<?php
// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

require_once 'db.php';
require_once 'includes/validation.php';
require_once 'includes/lang.php';
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

// Individual requests tablosunu oluştur (yoksa) - Onay bekleyen bireysel kullanıcı istekleri
$pdo->exec('CREATE TABLE IF NOT EXISTS individual_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    university VARCHAR(150) NOT NULL,
    department VARCHAR(150) NOT NULL,
    class VARCHAR(50) NOT NULL,
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

// Determine active tab (default to individual)
$activeTab = $_GET['tab'] ?? ($_POST['user_type'] ?? 'individual');
if (!in_array($activeTab, ['individual', 'corporate'])) {
    $activeTab = 'individual';
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token doğrulama
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $user_type = $_POST['user_type'] ?? 'individual';
        
        if ($user_type === 'corporate') {
            // Corporate registration
            $company_name = trim($_POST['company_name'] ?? '');
            $contact_person = trim($_POST['contact_person'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $tax_number = trim($_POST['tax_number'] ?? '');
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';
            
            if (!validateEmail($email)) {
                $error = 'Geçersiz e-posta adresi!';
            } else if (!validatePhone($phone)) {
                $error = 'Geçersiz telefon numarası! Lütfen geçerli bir Türkiye telefon numarası girin.';
            } else if ($password !== $password2) {
                $error = 'Şifreler eşleşmiyor!';
            } else {
                $password_check = validatePassword($password);
                if (!$password_check['valid']) {
                    $error = $password_check['message'];
                } else if (!$company_name || !$contact_person || !$phone || !$email || !$password) {
                    $error = 'Lütfen zorunlu alanları doldurun!';
                } else {
                    $captcha_response = $_POST['g-recaptcha-response'] ?? '';
                    if (!validateCaptcha($captcha_response)) {
                        $error = 'Robot olmadığınızı doğrulayın!';
                    } else {
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
                                        $activeTab = 'corporate';
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
        } else {
            // Individual registration
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            // Handle university field - check if "other" is selected
            $university_raw = trim($_POST['universite'] ?? '');
            if ($university_raw === 'other') {
                $university = trim($_POST['university_custom'] ?? '');
            } else {
                $university = $university_raw;
            }
            
            // Handle department field - check if "other" is selected
            $department_raw = trim($_POST['bolum'] ?? '');
            if ($department_raw === 'other') {
                $department = trim($_POST['department_custom'] ?? '');
            } else {
                $department = $department_raw;
            }
            
            $class = trim($_POST['class'] ?? '');
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';
            
            if (!validateEmail($email)) {
                $error = 'Geçersiz e-posta adresi!';
            } else if (!validatePhone($phone)) {
                $error = 'Geçersiz telefon numarası! Lütfen geçerli bir Türkiye telefon numarası girin.';
            } else if ($password !== $password2) {
                $error = 'Şifreler eşleşmiyor!';
            } else {
                $password_check = validatePassword($password);
                if (!$password_check['valid']) {
                    $error = $password_check['message'];
                } else if (!$name || !$phone || !$email || !$university || !$department || !$class || !$password) {
                    $error = 'Lütfen tüm alanları doldurun!';
                } else if ($university_raw === 'other' && empty($university)) {
                    $error = 'Lütfen üniversite adını giriniz!';
                } else if ($department_raw === 'other' && empty($department)) {
                    $error = 'Lütfen bölüm adını giriniz!';
                } else {
                    $captcha_response = $_POST['g-recaptcha-response'] ?? '';
                    if (!validateCaptcha($captcha_response)) {
                        $error = 'Robot olmadığınızı doğrulayın!';
                    } else {
                        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = 'Bu e-posta adresi zaten kayıtlı!';
                        } else {
                            // Check for pending/approved individual requests
                            $stmt = $pdo->prepare('SELECT id FROM individual_requests WHERE email = ? AND status IN ("pending", "approved")');
                            $stmt->execute([$email]);
                            if ($stmt->fetch()) {
                                $error = 'Bu e-posta adresi için zaten bir onay bekleyen veya onaylanmış istek var!';
                            } else {
                                $hashed = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare('INSERT INTO individual_requests (name, phone, email, university, department, class, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, "pending")');
                                $ok = $stmt->execute([$name, $phone, $email, $university, $department, $class, $hashed]);
                                if ($ok) {
                                    // Set flash message for successful registration
                                    $_SESSION['success'] = 'Kayıt isteğiniz alındı! Hesabınız yönetici onayından sonra aktif olacaktır.';
                                    
                                    // Clean output buffer before redirect
                                    if (ob_get_level()) {
                                        ob_end_clean();
                                    }
                                    
                                    // Redirect to login page
                                    header('Location: login.php');
                                    exit;
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
    <title><?php echo __t('register.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/auth.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .auth-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            padding: 0;
            background: transparent;
        }
        .auth-tabs button {
            width: auto !important;
            margin: 0 !important;
        }
        .auth-tab {
            flex: 1;
            padding: 12px 20px;
            background: transparent !important;
            border: 2px solid #1c2444 !important;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #1c2444 !important;
            transition: all 0.3s ease;
            text-align: center;
            border-radius: 8px;
            box-shadow: none !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        }
        .auth-tab:hover {
            background: rgba(28, 36, 68, 0.05) !important;
        }
        .auth-tab.active,
        .auth-tab.corporate.active {
            color: #ffffff !important;
            border: 2px solid #1c2444 !important;
            background: #1c2444 !important;
            box-shadow: none !important;
        }
        .auth-form-container { display: none; }
        .auth-form-container.active { display: block; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="auth-page">
        <div class="auth-container">
            <div class="auth-tabs">
                <button class="auth-tab <?php echo $activeTab === 'individual' ? 'active' : ''; ?>" onclick="switchTab('individual', event)">
                    <?php echo __t('register.individual'); ?>
                </button>
                <button class="auth-tab corporate <?php echo $activeTab === 'corporate' ? 'active' : ''; ?>" onclick="switchTab('corporate', event)">
                    <?php echo __t('register.corporate'); ?>
                </button>
            </div>
            
            <div id="individual-form" class="auth-form-container <?php echo $activeTab === 'individual' ? 'active' : ''; ?>">
                <h2><?php echo __t('register.individual'); ?></h2>
                <form method="post">
                    <input type="hidden" name="user_type" value="individual">
                    <?php if (!empty($error) && $activeTab === 'individual') { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                    <?php if (!empty($success) && $activeTab === 'individual') { echo '<div class="alert-success">'.__t('register.success').'</div>'; } ?>
                    <div class="form-group">
                        <label for="name"><?php echo __t('register.name'); ?></label>
                        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone"><?php echo __t('register.phone'); ?></label>
                        <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email"><?php echo __t('register.email'); ?></label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="universite"><?php echo __t('register.university'); ?></label>
                        <select id="universite" name="universite" class="form-select" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--primary); background-color: var(--secondary); font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit; border-radius: 6px;">
                            <?php 
                            $selected_uni = $_POST['universite'] ?? 'Ankara Yıldırım Beyazıt Üniversitesi';
                            ?>
                            <option value="Ankara Yıldırım Beyazıt Üniversitesi" <?= $selected_uni === 'Ankara Yıldırım Beyazıt Üniversitesi' ? 'selected' : '' ?>>Ankara Yıldırım Beyazıt Üniversitesi</option>
                            <option value="other" <?= $selected_uni === 'other' ? 'selected' : '' ?>>Diğer</option>
                        </select>
                        <input type="text" id="university_custom" name="university_custom" class="form-input" style="width: 100%; padding: 12px 15px; border: 2px solid var(--primary); background-color: var(--secondary); font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit; border-radius: 6px; margin-top: 10px; display: none;" placeholder="Üniversite adını giriniz" value="<?php echo htmlspecialchars($_POST['university_custom'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="bolum"><?php echo __t('register.department'); ?></label>
                        <select id="bolum" name="bolum" class="form-select" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--primary); background-color: var(--secondary); font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit; border-radius: 6px;">
                            <?php 
                            $selected_dept = $_POST['bolum'] ?? 'Yazılım Mühendisliği';
                            ?>
                            <option value="Yazılım Mühendisliği" <?= $selected_dept === 'Yazılım Mühendisliği' ? 'selected' : '' ?>>Yazılım Mühendisliği</option>
                            <option value="other" <?= $selected_dept === 'other' ? 'selected' : '' ?>>Diğer</option>
                        </select>
                        <input type="text" id="department_custom" name="department_custom" class="form-input" style="width: 100%; padding: 12px 15px; border: 2px solid var(--primary); background-color: var(--secondary); font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit; border-radius: 6px; margin-top: 10px; display: none;" placeholder="Bölüm adını giriniz" value="<?php echo htmlspecialchars($_POST['department_custom'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="class"><?php echo __t('register.class'); ?></label>
                        <select id="class" name="class" class="form-select" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--primary); background-color: var(--secondary); font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit; border-radius: 6px;">
                            <?php 
                            $selected_class = $_POST['class'] ?? '';
                            ?>
                            <option value="" <?= $selected_class === '' ? 'selected' : '' ?>>Sınıf Seçiniz</option>
                            <option value="Hazırlık" <?= $selected_class === 'Hazırlık' ? 'selected' : '' ?>>Hazırlık</option>
                            <option value="1" <?= $selected_class === '1' ? 'selected' : '' ?>>1</option>
                            <option value="2" <?= $selected_class === '2' ? 'selected' : '' ?>>2</option>
                            <option value="3" <?= $selected_class === '3' ? 'selected' : '' ?>>3</option>
                            <option value="4" <?= $selected_class === '4' ? 'selected' : '' ?>>4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password"><?php echo __t('register.password'); ?></label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="password2"><?php echo __t('register.password2'); ?></label>
                        <input type="password" id="password2" name="password2" required>
                    </div>
                    <div class="form-group">
                        <div class="password-requirements">
                            <small><?php echo __t('register.requirements.title'); ?></small>
                            <ul>
                                <li id="length-check"><?php echo __t('register.requirements.length'); ?></li>
                                <li id="upper-check"><?php echo __t('register.requirements.upper'); ?></li>
                                <li id="lower-check"><?php echo __t('register.requirements.lower'); ?></li>
                                <li id="number-check"><?php echo __t('register.requirements.number'); ?></li>
                                <li id="special-check"><?php echo __t('register.requirements.special'); ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="6LeLMC8rAAAAAChTj8rlQ_zyjedV3VdnejoNAZy1"></div>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="cta-button"><?php echo __t('register.submit'); ?></button>
                </form>
                <p><?php echo __t('register.have_account'); ?> <a href="login.php"><?php echo __t('register.login'); ?></a></p>
            </div>
            
            <div id="corporate-form" class="auth-form-container <?php echo $activeTab === 'corporate' ? 'active' : ''; ?>">
                <h2><?php echo __t('register.corporate'); ?></h2>
                <form method="post">
                    <input type="hidden" name="user_type" value="corporate">
                    <?php if (!empty($error) && $activeTab === 'corporate') { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                    <?php if (!empty($success) && $activeTab === 'corporate') { 
                        echo '<div class="alert-success">'.($success_message ?? __t('register.corporate.success')).'</div>'; 
                    } ?>
                    <div class="form-group">
                        <label for="company_name"><?php echo __t('register.corporate.company_name'); ?> <span style="color: red;">*</span></label>
                        <input type="text" id="company_name" name="company_name" required value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_person"><?php echo __t('register.corporate.contact_person'); ?> <span style="color: red;">*</span></label>
                        <input type="text" id="contact_person" name="contact_person" required value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="corporate_phone"><?php echo __t('register.corporate.phone'); ?> <span style="color: red;">*</span></label>
                        <input type="tel" id="corporate_phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="corporate_email"><?php echo __t('register.corporate.email'); ?> <span style="color: red;">*</span></label>
                        <input type="email" id="corporate_email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address"><?php echo __t('register.corporate.address'); ?></label>
                        <textarea id="address" name="address" rows="3" style="width: 100%; padding: 12px 15px; border: 2px solid var(--primary); background-color: var(--secondary); font-size: 16px; outline: none; transition: all 0.3s; font-family: inherit;"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tax_number"><?php echo __t('register.corporate.tax_number'); ?></label>
                        <input type="text" id="tax_number" name="tax_number" value="<?php echo htmlspecialchars($_POST['tax_number'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="corporate_password"><?php echo __t('register.corporate.password'); ?> <span style="color: red;">*</span></label>
                        <input type="password" id="corporate_password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="corporate_password2"><?php echo __t('register.corporate.password2'); ?> <span style="color: red;">*</span></label>
                        <input type="password" id="corporate_password2" name="password2" required>
                    </div>
                    <div class="form-group">
                        <div class="password-requirements">
                            <small><?php echo __t('register.corporate.requirements.title'); ?></small>
                            <ul>
                                <li id="corporate-length-check"><?php echo __t('register.corporate.requirements.length'); ?></li>
                                <li id="corporate-upper-check"><?php echo __t('register.corporate.requirements.upper'); ?></li>
                                <li id="corporate-lower-check"><?php echo __t('register.corporate.requirements.lower'); ?></li>
                                <li id="corporate-number-check"><?php echo __t('register.corporate.requirements.number'); ?></li>
                                <li id="corporate-special-check"><?php echo __t('register.corporate.requirements.special'); ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="6LeLMC8rAAAAAChTj8rlQ_zyjedV3VdnejoNAZy1"></div>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="cta-button"><?php echo __t('register.corporate.submit'); ?></button>
                </form>
                <p><?php echo __t('register.corporate.have_account'); ?> <a href="login.php?tab=corporate"><?php echo __t('register.corporate.login'); ?></a></p>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/matrix-animation.js"></script>
    <script src="javascript/password-validator.js"></script>
    <script src="javascript/password-toggle.js"></script>
    <script>
        function switchTab(tab, event) {
            // Update active tab button
            const buttons = document.querySelectorAll('.auth-tab');
            buttons.forEach(btn => {
                btn.classList.remove('active');
            });
            // Find the clicked button and activate it
            if (event && event.target) {
                event.target.closest('.auth-tab')?.classList.add('active');
            } else {
                // Fallback: find button by tab name
                document.querySelector(`.auth-tab[onclick*="${tab}"]`)?.classList.add('active');
            }
            
            // Update active form
            document.getElementById('individual-form').classList.remove('active');
            document.getElementById('corporate-form').classList.remove('active');
            document.getElementById(tab + '-form').classList.add('active');
            
            // Update URL without reload (preserving language and other params)
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }
        
        // Initialize password validator for corporate form
        document.addEventListener('DOMContentLoaded', function() {
            const corporatePassword = document.getElementById('corporate_password');
            if (corporatePassword) {
                corporatePassword.addEventListener('input', function() {
                    validatePasswordCorporate(this.value);
                });
            }
        });
        
        function validatePasswordCorporate(password) {
            const lengthCheck = document.getElementById('corporate-length-check');
            const upperCheck = document.getElementById('corporate-upper-check');
            const lowerCheck = document.getElementById('corporate-lower-check');
            const numberCheck = document.getElementById('corporate-number-check');
            const specialCheck = document.getElementById('corporate-special-check');
            
            if (lengthCheck) lengthCheck.classList.toggle('valid', password.length >= 8);
            if (upperCheck) upperCheck.classList.toggle('valid', /[A-Z]/.test(password));
            if (lowerCheck) lowerCheck.classList.toggle('valid', /[a-z]/.test(password));
            if (numberCheck) numberCheck.classList.toggle('valid', /[0-9]/.test(password));
            if (specialCheck) specialCheck.classList.toggle('valid', /[^A-Za-z0-9]/.test(password));
        }
        
        // Handle "Other" option for University and Department fields
        document.addEventListener('DOMContentLoaded', function() {
            const universitySelect = document.getElementById('universite');
            const universityCustom = document.getElementById('university_custom');
            const departmentSelect = document.getElementById('bolum');
            const departmentCustom = document.getElementById('department_custom');
            
            // University field handler
            if (universitySelect && universityCustom) {
                function toggleUniversityCustom() {
                    if (universitySelect.value === 'other') {
                        universityCustom.style.display = 'block';
                        universityCustom.setAttribute('required', 'required');
                        universitySelect.removeAttribute('required');
                    } else {
                        universityCustom.style.display = 'none';
                        universityCustom.removeAttribute('required');
                        universitySelect.setAttribute('required', 'required');
                    }
                }
                
                universitySelect.addEventListener('change', toggleUniversityCustom);
                // Check initial state
                toggleUniversityCustom();
            }
            
            // Department field handler
            if (departmentSelect && departmentCustom) {
                function toggleDepartmentCustom() {
                    if (departmentSelect.value === 'other') {
                        departmentCustom.style.display = 'block';
                        departmentCustom.setAttribute('required', 'required');
                        departmentSelect.removeAttribute('required');
                    } else {
                        departmentCustom.style.display = 'none';
                        departmentCustom.removeAttribute('required');
                        departmentSelect.setAttribute('required', 'required');
                    }
                }
                
                departmentSelect.addEventListener('change', toggleDepartmentCustom);
                // Check initial state
                toggleDepartmentCustom();
            }
        });
    </script>
</body>
</html>
