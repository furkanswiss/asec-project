<?php
// Gallery Folders Database Setup
// Run this file once to set up the folder system
require_once '../db.php';
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

try {
    // Create gallery_folders table
    $pdo->exec('CREATE TABLE IF NOT EXISTS gallery_folders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        baslik VARCHAR(255) NOT NULL,
        kategori VARCHAR(100) NOT NULL DEFAULT "events",
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_kategori (kategori)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    
    // Add folder_id column to galeri table if it doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM galeri LIKE 'folder_id'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec('ALTER TABLE galeri ADD COLUMN folder_id INT NULL AFTER id, ADD INDEX idx_folder_id (folder_id), ADD FOREIGN KEY (folder_id) REFERENCES gallery_folders(id) ON DELETE SET NULL');
    }
    
    echo '<div style="padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;">';
    echo '<h2>✅ Database Setup Successful!</h2>';
    echo '<p>Gallery folders table created and galeri table updated successfully.</p>';
    echo '<p><a href="galeri-yonetim.php">Go to Gallery Management</a></p>';
    echo '</div>';
} catch (PDOException $e) {
    echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">';
    echo '<h2>❌ Error</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>

