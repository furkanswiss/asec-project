<?php
// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

// Individual İlan Sil - Individual users can delete their own approved ads
require_once 'db.php';
require_once 'includes/lang.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an individual user
if (!isset($_SESSION['user']) || !isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'individual') {
    // Clean output buffer before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: login.php');
    exit;
}

// Ensure ilanlar table has user_id column
try {
    $columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('user_id', $columns)) {
        $pdo->exec("ALTER TABLE ilanlar ADD COLUMN user_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE ilanlar ADD INDEX idx_user_id (user_id)");
    }
} catch (PDOException $e) {
    // Column might already exist, continue
}

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        // Verify ownership - only individual users can delete their own approved ads
        $stmt = $pdo->prepare('SELECT * FROM ilanlar WHERE id = ? AND user_id = ? AND kategori = "Bireysel İlanlar"');
        $stmt->execute([$id, $_SESSION['user_id']]);
        $ilan = $stmt->fetch();
        
        if ($ilan) {
            // Delete from ilanlar table
            $delete_stmt = $pdo->prepare('DELETE FROM ilanlar WHERE id = ? AND user_id = ?');
            $delete_stmt->execute([$id, $_SESSION['user_id']]);
            
            // Also update the individual_ilan_requests status if linked
            if (isset($ilan['individual_ilan_request_id']) && $ilan['individual_ilan_request_id']) {
                $update_stmt = $pdo->prepare('UPDATE individual_ilan_requests SET status = "rejected", admin_notes = CONCAT(COALESCE(admin_notes, ""), "\n[Deleted by user]") WHERE id = ? AND user_id = ?');
                $update_stmt->execute([$ilan['individual_ilan_request_id'], $_SESSION['user_id']]);
            }
        }
    } catch (PDOException $e) {
        // Log error but continue to redirect
        error_log('Error deleting individual listing: ' . $e->getMessage());
    }
}

// Clean output buffer before redirect
if (ob_get_level()) {
    ob_end_clean();
}
header('Location: ilanlar.php');
exit;
