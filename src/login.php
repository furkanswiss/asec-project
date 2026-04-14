<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once 'includes/validation.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Determine active tab (default to individual)
$activeTab = $_GET['tab'] ?? ($_POST['user_type'] ?? 'individual');
if (!in_array($activeTab, ['individual', 'corporate'])) {
    $activeTab = 'individual';
}

$error = '';
$success = '';

// Check for flash success message from registration
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); // Clear the flash message after displaying
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token doğrulama
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $user_type = $_POST['user_type'] ?? 'individual';
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
                    if ($user_type === 'corporate') {
                        $stmt = $pdo->prepare('SELECT * FROM corporate_users WHERE email = ?');
                        $stmt->execute([$email]);
                        $user = $stmt->fetch();
                        
                        if ($user && password_verify($password, $user['password'])) {
                            resetLoginAttempts($pdo, $email);
                            $_SESSION["loggedin"] = true;
                            $_SESSION['user'] = $user['email'];
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['company_name'];
                            $_SESSION['user_type'] = 'corporate';
                            $_SESSION['contact_person'] = $user['contact_person'];
                            header('Location: corporate/dashboard.php');
                            exit;
                        } else {
                            $error = 'E-posta veya şifre hatalı!';
                            $activeTab = 'corporate';
                        }
                    } else {
                        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
                        $stmt->execute([$email]);
                        $user = $stmt->fetch();
                        
                        if ($user && password_verify($password, $user['password'])) {
                            resetLoginAttempts($pdo, $email);
                            $_SESSION['user'] = $user['email'];
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_type'] = 'individual';
                            header('Location: index.php');
                            exit;
                        } else {
                            // Check for pending request
                            $stmt = $pdo->prepare('SELECT status FROM individual_requests WHERE email = ?');
                            $stmt->execute([$email]);
                            $request = $stmt->fetch();
                            
                            if ($request && $request['status'] === 'pending') {
                                $error = 'Hesabınız henüz yönetici tarafından onaylanmamıştır. Lütfen onay bekleyiniz.';
                            } elseif ($request && $request['status'] === 'rejected') {
                                $error = 'Üyelik isteğiniz reddedilmiştir.';
                            } else {
                                $error = 'E-posta veya şifre hatalı!';
                            }
                            $activeTab = 'individual';
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
    <title><?php echo __t('login.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/auth.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
     <style>
        /* TAB BUTONLARI STİLİ */
        .auth-tabs { display: flex; gap: 10px; margin-bottom: 30px; }
        .auth-tabs button { width: auto !important; margin: 0 !important; }
        
        .auth-tab {
            flex: 1; 
            padding: 12px 20px; 
            background: transparent !important; 
            color: #1c2444 !important; 
            border: 2px solid #1c2444 !important; 
            cursor: pointer;
            font-weight: 600; 
            border-radius: 8px;
            box-shadow: none !important;
            transition: all 0.3s ease;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        }
        
        .auth-tab:hover {
            background: rgba(28, 36, 68, 0.05) !important;
        }

        .auth-tab.active {
            background: #1c2444 !important;
            color: #ffffff !important;
            border-color: #1c2444 !important;
            box-shadow: none !important;
        }

        .auth-form-container { display: none; }
        .auth-form-container.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="auth-page">
        <div class="auth-container">
            <div class="auth-tabs">
                <button class="auth-tab <?php echo $activeTab === 'individual' ? 'active' : ''; ?>" onclick="switchTab('individual')">
                    <?php echo __t('login.individual'); ?>
                </button>
                <button class="auth-tab corporate <?php echo $activeTab === 'corporate' ? 'active' : ''; ?>" onclick="switchTab('corporate')">
                    <?php echo __t('login.corporate'); ?>
                </button>
            </div>
            
            <div id="individual-form" class="auth-form-container <?php echo $activeTab === 'individual' ? 'active' : ''; ?>">
                <h2><?php echo __t('login.individual'); ?></h2>
                <form method="post">
                    <input type="hidden" name="user_type" value="individual">
                    <?php if (!empty($success) && $activeTab === 'individual') { echo '<div class="alert-success">'.$success.'</div>'; } ?>
                    <?php if (!empty($error) && $activeTab === 'individual') { echo '<div class="alert-error">'.$error.'</div>'; } ?>
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
                <p><?php echo __t('login.no_account'); ?> <a href="register.php"><?php echo __t('login.register'); ?></a></p>
            </div>
            
            <div id="corporate-form" class="auth-form-container <?php echo $activeTab === 'corporate' ? 'active' : ''; ?>">
                <h2><?php echo __t('login.corporate'); ?></h2>
                <form method="post">
                    <input type="hidden" name="user_type" value="corporate">
                    <?php if (!empty($error) && $activeTab === 'corporate') { echo '<div class="alert-error">'.$error.'</div>'; } ?>
                    <div class="form-group">
                        <label for="corporate_email"><?php echo __t('login.email'); ?></label>
                        <input type="email" id="corporate_email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="corporate_password"><?php echo __t('login.password'); ?></label>
                        <input type="password" id="corporate_password" name="password" required>
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
                <p><?php echo __t('login.no_account'); ?> <a href="register.php?tab=corporate"><?php echo __t('login.register'); ?></a></p>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/matrix-animation.js"></script>
    <script src="javascript/password-toggle.js"></script>
    <script>
        function switchTab(tab) {
            // Update active tab button
            document.querySelectorAll('.auth-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update active form
            document.getElementById('individual-form').classList.remove('active');
            document.getElementById('corporate-form').classList.remove('active');
            document.getElementById(tab + '-form').classList.add('active');
            
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }
    </script>
</body>
</html>
