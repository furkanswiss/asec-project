<?php
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
    $_SESSION['error'] = 'CV görüntüleme özelliği yalnızca bireysel kullanıcılar için kullanılabilir.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// CV profili çek
$cvStmt = $pdo->prepare('SELECT major, languages, software_fields, companies, cv_filename FROM user_cv_profiles WHERE user_id = ?');
$cvStmt->execute([$user['id']]);
$cvProfile = $cvStmt->fetch();

// Kullanılacak CV dosya adını belirle (DB öncelikli, yoksa FS üzerinden ara)
$cvFilename = $cvProfile && !empty($cvProfile['cv_filename']) ? $cvProfile['cv_filename'] : null;
if ($cvFilename === null) {
    $patternAbs = __DIR__ . '/uploads/cv/' . 'cv_' . $user['id'] . '_*.pdf';
    $matches = function_exists('glob') ? glob($patternAbs) : [];
    if (empty($matches) && function_exists('glob')) {
        $patternRel = 'uploads/cv/' . 'cv_' . $user['id'] . '_*.pdf';
        $matches = glob($patternRel);
        if (!empty($matches)) { $cvFilename = basename($matches[0]); }
    } else if (!empty($matches)) {
        $cvFilename = basename($matches[0]);
    }
    if ($cvFilename === null) {
        // glob devre dışıysa scandir ile ara
        $dir = __DIR__ . '/uploads/cv';
        if (is_dir($dir)) {
            $files = scandir($dir);
            if ($files) {
                $prefix = 'cv_' . $user['id'] . '_';
                foreach ($files as $f) {
                    if (strpos($f, $prefix) === 0 && substr($f, -4) === '.pdf') { $cvFilename = $f; break; }
                }
            }
        }
    }
}

// CV yoksa düzenleme/yükleme sayfasına yönlendir
if ($cvFilename === null || !file_exists(__DIR__ . '/uploads/cv/' . $cvFilename)) {
    header('Location: load-cv.php');
    exit;
}

$languages = $cvProfile && $cvProfile['languages'] ? json_decode($cvProfile['languages'], true) : [];
$softwareFields = $cvProfile && $cvProfile['software_fields'] ? json_decode($cvProfile['software_fields'], true) : [];
$companies = $cvProfile && $cvProfile['companies'] ? json_decode($cvProfile['companies'], true) : [];
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('cv.page.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/profilim.css">
    <style>
    .cv-form { 
        max-width: 1200px; 
        margin: 40px auto; 
        background: #fff; 
        padding: 40px; 
        border-radius: 12px; 
        box-shadow: 0 10px 30px rgba(27, 31, 59, 0.15);
    }
    .profile-main { background: #fff; min-height: 80vh; padding: 40px 0; }
    .cv-form h2 { 
        margin: 0 0 1.25rem 0; 
        font-size: 2rem; font-weight: 700; color: #1b1f3b; 
        padding-bottom: 1rem; border-bottom: 2px solid #9370db; 
    }
    .cv-section { margin-bottom: 1.25rem; }
    .cv-label { display:block; font-weight:600; color:#1b1f3b; margin-bottom:6px; }
    .cv-value { color:#333; }
    .cv-chiplist { display:flex; flex-wrap:wrap; gap:10px; margin-top:6px; }
    .cv-chip { padding:8px 14px; border-radius:20px; background: rgba(230,230,250,0.6); border:1px solid rgba(147,112,219,0.3); font-size:0.95rem; color:#1b1f3b; }
    .cv-pdf { margin-top: 20px; border:1px solid rgba(147,112,219,0.25); border-radius:8px; overflow:hidden; }
    .cv-actions { margin-top: 20px; display:flex; gap:12px; flex-wrap:wrap; }
    .btn-danger { background: #dc3545; color: #fff; border: 1px solid #dc3545; }
    .btn-danger:hover { background: #c82333; border-color: #bd2130; }
    .note { color:#555; font-size:0.9rem; margin-top:8px; }
    @media (max-width: 768px) { .cv-form { margin: 20px auto; padding: 25px 20px; } }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main class="profile-main">
    <div class="cv-form">
        <h2><?php echo __t('cv.page.title'); ?></h2>

        <div class="cv-section">
            <span class="cv-label"><?php echo __t('cv.name'); ?></span>
            <div class="cv-value"><?php echo htmlspecialchars($user['name']); ?></div>
        </div>

        <?php if (!empty($cvProfile['major'])): ?>
        <div class="cv-section">
            <span class="cv-label"><?php echo __t('cv.major'); ?></span>
            <div class="cv-value"><?php echo htmlspecialchars($cvProfile['major']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($languages)): ?>
        <div class="cv-section">
            <span class="cv-label"><?php echo __t('cv.programming_languages'); ?></span>
            <div class="cv-chiplist">
                <?php foreach ($languages as $lang): ?>
                    <span class="cv-chip"><?php echo htmlspecialchars($lang); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($softwareFields)): ?>
        <div class="cv-section">
            <span class="cv-label"><?php echo __t('cv.software_fields'); ?></span>
            <div class="cv-chiplist">
                <?php foreach ($softwareFields as $fld): ?>
                    <span class="cv-chip"><?php echo htmlspecialchars($fld); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($companies)): ?>
        <div class="cv-section">
            <span class="cv-label"><?php echo __t('cv.worked_companies'); ?></span>
            <div class="cv-value">
                <?php foreach ($companies as $c): ?>
                    <div>- <?php echo htmlspecialchars($c); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="cv-section">
            <span class="cv-label"><?php echo __t('cv.pdf'); ?></span>
            <div class="note"><?php echo __t('cv.pdf_note'); ?></div>
            <div class="cv-pdf">
                <iframe src="uploads/cv/<?php echo htmlspecialchars($cvProfile['cv_filename']); ?>" style="width:100%; height:720px; border:0;" title="CV"></iframe>
            </div>
        </div>

        <div class="cv-actions">
            <button class="cta-button" onclick="window.location.href='load-cv.php'"><?php echo __t('cv.edit'); ?></button>
            <button class="cta-button btn-small" onclick="window.location.href='profilim.php'"><?php echo __t('cv.view_profile'); ?></button>
            <button class="cta-button btn-danger" onclick="deleteCV()"><?php echo __t('cv.delete'); ?></button>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
function deleteCV() {
    if (confirm(<?php echo json_encode(__t('cv.delete.confirm')); ?>)) {
        window.location.href = 'cv-sil.php';
    }
}
</script>
</body>
</html>


