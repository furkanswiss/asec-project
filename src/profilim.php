<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$email = $_SESSION['user'];
$user_type = $_SESSION['user_type'] ?? 'individual';

// Check user type and fetch from appropriate table
if ($user_type === 'corporate') {
    $stmt = $pdo->prepare('SELECT * FROM corporate_users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        session_destroy();
        header('Location: corporate-login.php');
        exit;
    }
} else {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// Profil güncelleme işlemi
if (isset($_POST['update_profile'])) {
    if ($user_type === 'corporate') {
        // Corporate user update
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $tax_number = trim($_POST['tax_number'] ?? '');
        
        $stmt = $pdo->prepare('UPDATE corporate_users SET company_name=?, contact_person=?, phone=?, address=?, tax_number=? WHERE id=?');
        $stmt->execute([$company_name, $contact_person, $phone, $address, $tax_number, $user['id']]);
        $_SESSION['user_name'] = $company_name; // Update session
    } else {
        // Individual user update
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $class = trim($_POST['class'] ?? '');
        // Fix: Convert empty birthdate string to NULL for DATE column
        $birthdate = !empty(trim($_POST['birthdate'] ?? '')) ? $_POST['birthdate'] : null;
        $address = trim($_POST['address'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $achievements = trim($_POST['achievements'] ?? '');
        $avatarFile = $user['avatar'] ?? '';

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/avatar/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                $avatarFile = $filename;
            }
        }

        // Update users table
        $stmt = $pdo->prepare('UPDATE users SET name=?, phone=?, university=?, department=?, class=?, birthdate=?, address=?, bio=?, instagram=?, linkedin=?, achievements=?, avatar=? WHERE id=?');
        $stmt->execute([$name, $phone, $university, $department, $class, $birthdate, $address, $bio, $instagram, $linkedin, $achievements, $avatarFile, $user['id']]);
        
        // SYNC: Update CV major column to match the new department
        $stmtCv = $pdo->prepare('UPDATE user_cv_profiles SET major = ? WHERE user_id = ?');
        $stmtCv->execute([$department, $user['id']]);
        
        $_SESSION['user_name'] = $name; // Update session
    }
    header('Location: profilim.php');
    exit;
}

// Re-fetch user data (already done above, but keeping for consistency)
if ($user_type === 'corporate') {
    $stmt = $pdo->prepare('SELECT * FROM corporate_users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} else {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
}
if (!$user) {
    session_destroy();
    header('Location: ' . ($user_type === 'corporate' ? 'corporate-login.php' : 'login.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('profile.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/profilim.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="profile-main">
    <div class="profil-container">
        <div class="profil-header">
            <div class="profil-avatar" id="profil-avatar">
                <?php if (!empty($user['avatar']) && file_exists('uploads/avatar/' . $user['avatar'])): ?>
                    <img src="uploads/avatar/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Profil Fotoğrafı" style="width:72px;height:72px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profil-header-info">
                <h2><?php echo htmlspecialchars($user_type === 'corporate' ? ($user['company_name'] ?? 'Kurumsal Kullanıcı') : ($user['name'] ?? 'Kullanıcı')); ?></h2>
                <?php if ($user_type === 'corporate'): ?>
                    <div class="profil-email"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['contact_person'] ?? ''); ?></div>
                <?php endif; ?>
                <div class="profil-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                <button class="cta-button" id="profil-guncelle-btn" style="margin-top:0.3rem;"><?php echo __t('profile.edit'); ?></button>
            </div>
        </div>

        <div id="profil-modal" class="profil-modal" style="display:none;">
            <div class="profil-modal-content">
                <span class="profil-modal-close" id="profil-modal-close">&times;</span>
                <h3><?php echo __t('profile.edit'); ?></h3>
                <form id="profil-update-form" method="post" enctype="multipart/form-data">
                    <?php if ($user_type === 'corporate'): ?>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label>Şirket Adı</label>
                                <input type="text" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>" required>
                            </div>
                            <div class="profil-form-group">
                                <label>İletişim Kişisi</label>
                                <input type="text" name="contact_person" value="<?php echo htmlspecialchars($user['contact_person'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.phone'); ?></label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="profil-form-group">
                                <label>Vergi Numarası</label>
                                <input type="text" name="tax_number" value="<?php echo htmlspecialchars($user['tax_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="profil-form-row">
                            <div class="profil-form-group" style="width: 100%;">
                                <label><?php echo __t('profile.labels.address'); ?></label>
                                <textarea name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label><?php echo __t('register.name'); ?></label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                            </div>
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.phone'); ?></label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.university'); ?></label>
                                <input type="text" name="university" value="<?php echo htmlspecialchars($user['university'] ?? ''); ?>">
                            </div>
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.department'); ?></label>
                                <input type="text" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" style="width: 100%; padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 6px; font-size: 1rem;">
                            </div>
                        </div>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.class'); ?></label>
                                <select name="class" style="width: 100%; padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 6px; font-size: 1rem; background-color: #fff;">
                                    <?php 
                                    $currentClass = $user['class'] ?? '';
                                    $classOptions = ['Hazırlık', '1. Sınıf', '2. Sınıf', '3. Sınıf', '4. Sınıf'];
                                    ?>
                                    <option value="">Sınıf Seçiniz</option>
                                    <?php foreach ($classOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($currentClass === $option) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.birthdate'); ?></label>
                                <input type="date" name="birthdate" value="<?php echo htmlspecialchars($user['birthdate'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="profil-form-row">
                            <div class="profil-form-group" style="width: 100%;">
                                <label><?php echo __t('profile.labels.address'); ?></label>
                                <textarea name="address" rows="3" style="width: 100%; padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 6px; font-size: 1rem;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.bio'); ?></label>
                                <textarea name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.instagram'); ?></label>
                                <input type="text" name="instagram" value="<?php echo htmlspecialchars($user['instagram'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.linkedin'); ?></label>
                                <input type="text" name="linkedin" value="<?php echo htmlspecialchars($user['linkedin'] ?? ''); ?>">
                            </div>
                            <div class="profil-form-group">
                                <label><?php echo __t('profile.labels.achievements'); ?></label>
                                <textarea name="achievements"><?php echo htmlspecialchars($user['achievements'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="profil-form-row">
                            <div class="profil-form-group">
                                <label>Profil Fotoğrafı:</label>
                                <input type="file" name="avatar" accept="image/*">
                            </div>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="update_profile" class="cta-button"><?php echo __t('profile.save'); ?></button>
                </form>
            </div>
        </div>

        <div class="profil-info-list">
            <?php if ($user_type === 'corporate'): ?>
                <div class="profil-info-item"><span class="profil-label">İletişim Kişisi</span><span class="profil-value"><?php echo htmlspecialchars($user['contact_person'] ?? __t('profile.value.not_set')); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.phone'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['phone'] ?? __t('profile.value.not_set')); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.address'); ?></span><span class="profil-value"><?php echo isset($user['address']) && trim($user['address']) !== '' ? htmlspecialchars($user['address']) : __t('profile.value.not_set'); ?></span></div>
                <div class="profil-info-item"><span class="profil-label">Vergi Numarası</span><span class="profil-value"><?php echo htmlspecialchars($user['tax_number'] ?? __t('profile.value.not_set')); ?></span></div>
            <?php else: ?>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.phone'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['phone']); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.university'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['university']); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.department'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['department']); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.class'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['class']); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.birthdate'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['birthdate'] ?? __t('profile.value.not_set')); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.address'); ?></span><span class="profil-value"><?php echo isset($user['address']) && trim($user['address']) !== '' ? htmlspecialchars($user['address']) : __t('profile.value.not_set'); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.bio'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['bio'] ?? __t('profile.value.not_set')); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.instagram'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['instagram'] ?? __t('profile.value.not_set')); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.linkedin'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['linkedin'] ?? __t('profile.value.not_set')); ?></span></div>
                <div class="profil-info-item"><span class="profil-label"><?php echo __t('profile.labels.achievements'); ?></span><span class="profil-value"><?php echo htmlspecialchars($user['achievements'] ?? __t('profile.value.not_set')); ?></span></div>
            <?php endif; ?>
        </div>

        <?php
        // CV section only for individual users
        if ($user_type === 'individual'):
            // ✅ CV KONTROLÜ
            $cvProfileStmt = $pdo->prepare('SELECT cv_filename FROM user_cv_profiles WHERE user_id = ? LIMIT 1');
            $cvProfileStmt->execute([$user['id']]);
            $cvProfileRow = $cvProfileStmt->fetch(PDO::FETCH_ASSOC);

            $hasCv = false;
            $cvFilePath = '';

            // Yalnızca veritabanında bir kayıt varsa ve bu kayıttaki dosya adı boş değilse CV var demektir.
            // Ek olarak, güvenlik için dosyanın fiziksel olarak var olup olmadığını da kontrol edebiliriz.
            if ($cvProfileRow && !empty($cvProfileRow['cv_filename'])) {
                $cvFilePath = 'uploads/cv/' . $cvProfileRow['cv_filename'];
                
                // Veritabanında kayıt var VE dosya sisteminde de mevcutsa
                if (file_exists($cvFilePath)) {
                    $hasCv = true;
                }
                // NOT: Eğer dosya veritabanında kayıtlı olduğu halde silinmişse, $hasCv yine false kalır.
                // Bu, daha doğru bir kontrol mantığıdır.
            }
        ?>
            <div class="profil-cv-section" style="margin-top:1rem;">
                <?php if ($hasCv): ?>
                    <button class="cta-button" onclick="window.location.href='cv-goruntule.php'">
                        <i class="fas fa-file-pdf"></i> <?php echo __t('profile.cv.view'); ?>
                    </button>
                <?php else: ?>
                    <button class="cta-button" onclick="window.location.href='load-cv.php'">
                        <i class="fas fa-file-upload"></i> <?php echo __t('profile.cv.add'); ?>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'footer.php'; ?>
<script src="js/profilim.js"></script>
</body>
</html>