<?php
/**
 * ASEC Kulübü - Giriş Denemeleri Tablosu Oluşturma
 * Bu script, giriş denemelerini takip etmek için gerekli tabloyu oluşturur
 */

require_once '../db.php';

// Tablo var mı kontrol et
$tableExists = $pdo->query("SHOW TABLES LIKE 'login_attempts'")->rowCount() > 0;

if (!$tableExists) {
    // Tablo yoksa oluştur
    $sql = "CREATE TABLE login_attempts (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        attempts INT(11) NOT NULL DEFAULT 1,
        last_attempt INT(11) NOT NULL,
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    try {
        $pdo->exec($sql);
        echo "login_attempts tablosu başarıyla oluşturuldu.";
    } catch (PDOException $e) {
        echo "Tablo oluşturma hatası: " . $e->getMessage();
    }
} else {
    echo "login_attempts tablosu zaten mevcut.";
}
?>
