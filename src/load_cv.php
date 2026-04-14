<?php
// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

require_once 'db.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['user'];
$user_type = $_SESSION['user_type'] ?? 'individual';

// Block corporate users from accessing CV pages
if ($user_type === 'corporate') {
    // Clean output buffer before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    $_SESSION['error'] = 'CV yükleme özelliği yalnızca bireysel kullanıcılar için kullanılabilir.';
    header('Location: index.php');
    exit;
}

// Fetch individual user data
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// CV profili tablosunu oluştur (yoksa)
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

// Var olan kayıt
$stmt = $pdo->prepare('SELECT * FROM user_cv_profiles WHERE user_id = ?');
$stmt->execute([$user['id']]);
$cvProfile = $stmt->fetch();

$errors = [];
$success = '';

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: Fetch registered department from database - ignore any form input
    // Re-fetch user data to ensure we have the latest department value
    $stmtDept = $pdo->prepare('SELECT department FROM users WHERE id = ?');
    $stmtDept->execute([$user['id']]);
    $userDept = $stmtDept->fetch();
    $registered_major = $userDept['department'] ?? '';
    
    // HARD OVERRIDE: Always use registered department, completely ignore POST input
    $major = $registered_major;
    
    $languages = $_POST['languages'] ?? [];
    $softwareFields = $_POST['software_fields'] ?? [];
    $companies = $_POST['companies'] ?? [];

    $languagesJson = json_encode(array_values($languages), JSON_UNESCAPED_UNICODE);
    $softwareFieldsJson = json_encode(array_values($softwareFields), JSON_UNESCAPED_UNICODE);
    // Temiz, boş olmayan şirket adları
    $companiesClean = array_values(array_filter(array_map('trim', $companies), function($c){ return $c !== ''; }));
    $companiesJson = json_encode($companiesClean, JSON_UNESCAPED_UNICODE);

    $cvFileName = $cvProfile['cv_filename'] ?? null;
    if (isset($_FILES['cv_pdf']) && $_FILES['cv_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['cv_pdf']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cv_pdf']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $errors[] = __t('cv.error.only_pdf');
            } else {
                $uploadDir = __DIR__ . '/uploads/cv/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                $cvFileName = 'cv_' . $user['id'] . '_' . time() . '.pdf';
                $targetPath = $uploadDir . $cvFileName;
                if (!move_uploaded_file($_FILES['cv_pdf']['tmp_name'], $targetPath)) {
                    $errors[] = __t('cv.error.upload_failed');
                }
            }
        } else {
            $errors[] = __t('cv.error.upload_error');
        }
    }

    if (empty($errors)) {
        // Upsert
        $stmt = $pdo->prepare('INSERT INTO user_cv_profiles (user_id, major, languages, software_fields, companies, cv_filename)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE major=VALUES(major), languages=VALUES(languages), software_fields=VALUES(software_fields), companies=VALUES(companies), cv_filename=VALUES(cv_filename)');
        $stmt->execute([$user['id'], $major, $languagesJson, $softwareFieldsJson, $companiesJson, $cvFileName]);

        // Redirect to profile page after successful save
        header('Location: profilim.php');
        exit;
    }
}

// Form seçenekleri - Fetch from database
// Ensure cv_options table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS cv_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM("language", "software_field") NOT NULL,
    name VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type_name (type, name),
    INDEX idx_type (type),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

// Fetch active options from database
$languageOptions = $pdo->query('SELECT name FROM cv_options WHERE type = "language" AND is_active = 1 ORDER BY display_order ASC, name ASC')->fetchAll(PDO::FETCH_COLUMN);
$softwareFieldOptions = $pdo->query('SELECT name FROM cv_options WHERE type = "software_field" AND is_active = 1 ORDER BY display_order ASC, name ASC')->fetchAll(PDO::FETCH_COLUMN);

// Fallback to default values if database is empty
if (empty($languageOptions)) {
    $languageOptions = [
        'C', 'C++', 'C#', 'Java', 'Python', 'JavaScript', 'TypeScript', 'PHP', 'Go', 'Rust', 'Kotlin', 'Swift', 'R', 'MATLAB', 'SQL'
    ];
}
if (empty($softwareFieldOptions)) {
    $softwareFieldOptions = [
        'Web Geliştirme', 'Mobil Geliştirme', 'Veri Bilimi', 'Makine Öğrenmesi', 'Yapay Zeka', 'DevOps', 'Siber Güvenlik', 'Oyun Geliştirme', 'Gömülü Sistemler', 'Bulut', 'Yazılım Testi', 'UI/UX', 'AR/VR'
    ];
}

// Mevcut değerleri ayrıştır
$existingLanguages = $cvProfile && $cvProfile['languages'] ? json_decode($cvProfile['languages'], true) : [];
$existingSoftwareFields = $cvProfile && $cvProfile['software_fields'] ? json_decode($cvProfile['software_fields'], true) : [];
$existingCompanies = $cvProfile && $cvProfile['companies'] ? json_decode($cvProfile['companies'], true) : [];

?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('cv.upload.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/profilim.css">
    <style>
    /* CV Form Container - White Background */
    .cv-form { 
        max-width: 900px; 
        margin: 40px auto; 
        background: #fff; 
        padding: 40px; 
        border-radius: 12px; 
        box-shadow: 0 10px 30px rgba(27, 31, 59, 0.15);
    }
    
    /* Main Background - White */
    .profile-main {
        background: #fff;
        min-height: 80vh;
        padding: 40px 0;
    }
    
    /* Title Styling */
    .cv-form h2 { 
        margin: 0 0 2rem 0; 
        font-size: 2rem;
        font-weight: 700;
        color: #1b1f3b;
        padding-bottom: 1rem;
        border-bottom: 2px solid #9370db;
    }
    
    /* Form Groups - Increased Spacing */
    .cv-row { display: flex; gap: 20px; flex-wrap: wrap; }
    .cv-col { flex: 1 1 260px; }
    .cv-group { 
        margin-bottom: 2rem; 
    }
    
    /* Labels */
    .cv-group label { 
        display: block; 
        margin-bottom: 12px; 
        font-weight: 600;
        color: #1b1f3b;
        font-size: 1rem;
    }
    
    /* Input Fields */
    .cv-group input[type="text"],
    .cv-group input[type="file"] {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid rgba(106, 13, 173, 0.2);
        border-radius: 8px;
        font-size: 1rem;
        color: #1b1f3b;
        background: #fff;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    
    .cv-group input[type="text"]:focus,
    .cv-group input[type="file"]:focus {
        border-color: #9370db;
        box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.1);
        outline: none;
    }
    
    /* Chip Container - More Spacing */
    .cv-chips { 
        display: flex; 
        gap: 12px; 
        flex-wrap: wrap; 
        margin-top: 8px;
    }
    
    /* Chips - Light Theme */
    .cv-chip { 
        display: inline-flex; 
        align-items: center; 
        gap: 8px; 
        padding: 10px 16px; 
        border-radius: 25px; 
        background: rgba(230, 230, 250, 0.5); 
        border: 1px solid rgba(147, 112, 219, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .cv-chip:hover {
        background: rgba(147, 112, 219, 0.15);
        border-color: #9370db;
        transform: translateY(-2px);
    }
    
    .cv-chip input[type="checkbox"] {
        margin: 0;
        cursor: pointer;
    }
    
    .cv-chip input[type="checkbox"]:checked + span {
        color: #9370db;
        font-weight: 600;
    }
    
    .cv-chip span {
        color: #1b1f3b;
        font-size: 0.95rem;
    }
    
    /* Companies Section */
    .companies { 
        margin-top: 12px; 
    }
    .companies input { 
        display: block; 
        width: 100%; 
        margin-bottom: 12px; 
    }
    
    /* Buttons */
    .btn-small { 
        padding: 10px 20px; 
        font-size: 0.9rem;
        margin-top: 8px;
    }
    
    .btn-danger { 
        background: #dc3545; 
        color: #fff; 
        border: 1px solid #dc3545; 
    }
    
    .btn-danger:hover { 
        background: #c82333; 
        border-color: #bd2130; 
    }
    
    /* Note Text */
    .note { 
        color: #555; 
        font-size: 0.9rem;
        margin-top: 8px;
        margin-bottom: 12px;
    }
    
    .note a {
        color: #9370db;
        text-decoration: none;
        font-weight: 500;
    }
    
    .note a:hover {
        text-decoration: underline;
    }
    
    /* Alerts - Light Theme */
    .alert { 
        margin: 1.5rem 0; 
        padding: 15px 20px; 
        border-radius: 8px;
        animation: fadeIn 0.3s ease;
    }
    
    .alert-success { 
        background-color: rgba(76, 175, 80, 0.1);
        color: #388e3c;
        border-left: 4px solid #4caf50;
    }
    
    .alert-error { 
        background-color: rgba(255, 0, 0, 0.1);
        color: #d32f2f;
        border-left: 4px solid #ff0000;
    }
    
    /* Button Group - More Spacing */
    .cv-group:last-of-type {
        margin-top: 2.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(106, 13, 173, 0.1);
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .cv-form {
            margin: 20px auto;
            padding: 25px 20px;
        }
        
        .cv-form h2 {
            font-size: 1.5rem;
        }
        
        .cv-group {
            margin-bottom: 1.5rem;
        }
        
        .cv-chips {
            gap: 10px;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main class="profile-main">
    <div class="cv-form">
        <h2><?php echo __t('cv.upload.title'); ?></h2>
        <?php if (isset($_SESSION['cv_required']) && $_SESSION['cv_required']): ?>
            <div class="alert alert-error" style="background-color: rgba(255, 193, 7, 0.1); color: #856404; border-left: 4px solid #ffc107; margin-bottom: 1.5rem;">
                <strong><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['cv_required_message'] ?? __t('cv.mandatory.message')); ?></strong>
            </div>
            <?php unset($_SESSION['cv_required'], $_SESSION['cv_required_message']); ?>
        <?php endif; ?>
        <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <div><?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="cv-group cv-col">
                <label><?php echo __t('register.department'); ?></label>
                <div style="padding: 12px 15px; background-color: #e9ecef; border: 1px solid rgba(106, 13, 173, 0.2); border-radius: 8px; color: #6c757d; font-size: 1rem;">
                    <?php echo htmlspecialchars($user['department'] ?? __t('profile.value.not_set')); ?>
                </div>
                <input type="hidden" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
            </div>
            <!-- <div class="cv-group cv-col">
                <label for="major"><?php echo __t('cv.form.major.label'); ?></label>
                <input type="text" id="major" name="major" value="<?php echo htmlspecialchars($cvProfile['major'] ?? ''); ?>" placeholder="<?php echo __t('cv.form.major.placeholder'); ?>">
            </div> -->

            <div class="cv-group">
                <label><?php echo __t('cv.form.languages.label'); ?></label>
                <div class="cv-chips">
                    <?php foreach ($languageOptions as $lang): $id = 'lang_' . md5($lang); $checked = in_array($lang, $existingLanguages ?? [], true) ? 'checked' : ''; ?>
                        <label class="cv-chip" for="<?php echo $id; ?>">
                            <input type="checkbox" id="<?php echo $id; ?>" name="languages[]" value="<?php echo htmlspecialchars($lang); ?>" <?php echo $checked; ?>>
                            <span><?php echo htmlspecialchars($lang); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cv-group">
                <label><?php echo __t('cv.form.fields.label'); ?></label>
                <div class="cv-chips">
                    <?php foreach ($softwareFieldOptions as $fld): $id = 'fld_' . md5($fld); $checked = in_array($fld, $existingSoftwareFields ?? [], true) ? 'checked' : ''; ?>
                        <label class="cv-chip" for="<?php echo $id; ?>">
                            <input type="checkbox" id="<?php echo $id; ?>" name="software_fields[]" value="<?php echo htmlspecialchars($fld); ?>" <?php echo $checked; ?>>
                            <span><?php echo htmlspecialchars($fld); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cv-group">
                <label><?php echo __t('cv.form.companies.label'); ?></label>
                <div class="note"><?php echo __t('cv.form.companies.note'); ?></div>
                <div id="companies" class="companies">
                    <?php if (!empty($existingCompanies)): ?>
                        <?php foreach ($existingCompanies as $c): ?>
                            <input type="text" name="companies[]" value="<?php echo htmlspecialchars($c); ?>" placeholder="<?php echo __t('cv.form.companies.placeholder'); ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="text" name="companies[]" placeholder="<?php echo __t('cv.form.companies.placeholder'); ?>">
                    <?php endif; ?>
                </div>
                <button type="button" class="cta-button btn-small" id="add-company"><?php echo __t('cv.form.add_row'); ?></button>
            </div>

            <div class="cv-group">
                <label for="cv_pdf"><i class="fas fa-file-pdf"></i> <?php echo __t('cv.form.upload.label'); ?></label>
                <input type="file" id="cv_pdf" name="cv_pdf" accept="application/pdf">
                <?php if (!empty($cvProfile['cv_filename']) && file_exists(__DIR__ . '/uploads/cv/' . $cvProfile['cv_filename'])): ?>
                    <div class="note"><?php echo __t('cv.form.upload.current'); ?> <a href="uploads/cv/<?php echo htmlspecialchars($cvProfile['cv_filename']); ?>" target="_blank"><?php echo __t('cv.form.upload.view'); ?></a></div>
                <?php endif; ?>
            </div>

            <div class="cv-group">
                <button type="submit" class="cta-button"><?php echo __t('cv.form.save'); ?></button>
                <button type="button" class="cta-button btn-small" onclick="window.location.href='profilim.php'"><?php echo __t('cv.view_profile'); ?></button>
                <?php if (!empty($cvProfile['cv_filename']) && file_exists(__DIR__ . '/uploads/cv/' . $cvProfile['cv_filename'])): ?>
                    <button type="button" class="cta-button btn-small btn-danger" onclick="deleteCV()"><?php echo __t('cv.delete'); ?></button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
document.getElementById('add-company').addEventListener('click', function(){
    var container = document.getElementById('companies');
    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'companies[]';
    input.placeholder = <?php echo json_encode(__t('cv.form.companies.placeholder')); ?>;
    container.appendChild(input);
});

function deleteCV() {
    if (confirm(<?php echo json_encode(__t('cv.delete.confirm')); ?>)) {
        window.location.href = 'cv-sil.php';
    }
}
</script>
</body>
</html>


