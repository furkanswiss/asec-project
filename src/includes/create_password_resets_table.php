<?php
/**
 * ASEC Kulübü - Şifre Sıfırlama Tablosu Oluşturma
 * Bu script, şifre sıfırlama token'larını saklamak için gerekli tabloyu oluşturur
 */

require_once '../db.php';

// Tablo var mı kontrol et
$tableExists = $pdo->query("SHOW TABLES LIKE 'password_resets'")->rowCount() > 0;

if (!$tableExists) {
    // Tablo yoksa oluştur
    $sql = "CREATE TABLE password_resets (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    try {
        $pdo->exec($sql);
        echo "password_resets tablosu başarıyla oluşturuldu.";
    } catch (PDOException $e) {
        echo "Tablo oluşturma hatası: " . $e->getMessage();
    }
} else {
    echo "password_resets tablosu zaten mevcut.";
}
?>
