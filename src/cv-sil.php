<?php
// CV Sil - Individual users can delete their own CV
// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

require_once 'db.php';
require_once 'includes/lang.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Clean output buffer before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
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
    $_SESSION['error'] = 'CV silme özelliği yalnızca bireysel kullanıcılar için kullanılabilir.';
    header('Location: index.php');
    exit;
}

// Check user type and fetch from appropriate table
if ($user_type === 'corporate') {
    $stmt = $pdo->prepare('SELECT * FROM corporate_users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        session_destroy();
        header('Location: corporate-login.php');
        exit;
    }
} else {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// Ensure user_cv_profiles table exists
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

// Get CV profile to verify ownership and get filename
$stmt = $pdo->prepare('SELECT * FROM user_cv_profiles WHERE user_id = ?');
$stmt->execute([$user['id']]);
$cvProfile = $stmt->fetch();

if ($cvProfile) {
    // Verify ownership - user can only delete their own CV
    if ($cvProfile['user_id'] == $user['id']) {
        // Get the CV filename before deleting
        $cvFilename = $cvProfile['cv_filename'];
        
        // Delete the record from database
        $delete_stmt = $pdo->prepare('DELETE FROM user_cv_profiles WHERE user_id = ?');
        $delete_stmt->execute([$user['id']]);
        
        // Delete the physical file if it exists
        if (!empty($cvFilename)) {
            $filePath = __DIR__ . '/uploads/cv/' . $cvFilename;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        // Also check for any old CV files with the user's ID pattern (cleanup)
        $uploadDir = __DIR__ . '/uploads/cv/';
        if (is_dir($uploadDir)) {
            $pattern = $uploadDir . 'cv_' . $user['id'] . '_*.pdf';
            $files = glob($pattern);
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }
}

// Clean output buffer before redirect
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to profile page after deletion
header('Location: profilim.php');
exit;
?>

