<?php
// src/admin/includes/config.php – TAM DOCKER UYUMLU (PDO + MySQLi)

// Oturum başlatma kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ayarları Docker Ortamından Al
$db_host = getenv('DB_HOST') ?: 'database';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'root';
$db_name = getenv('DB_NAME') ?: 'db_asec';

// 2. Sabitleri Tanımla
if (!defined('DB_SERVER')) define('DB_SERVER', $db_host);
if (!defined('DB_USERNAME')) define('DB_USERNAME', $db_user);
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $db_pass);
if (!defined('DB_NAME')) define('DB_NAME', $db_name);

// 3. Hata Raporlama
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // A. PDO Bağlantısı (Admin Paneli Genelde Bunu Kullanır) - EKSİK OLAN BUYDU!
    $dbh = new PDO("mysql:host=".DB_SERVER.";dbname=".DB_NAME, DB_USERNAME, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // B. MySQLi Bağlantısı (Eski Kodlar İçin Yedek)
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    mysqli_set_charset($conn, "utf8");

} catch (Exception $e) {
    // Hata durumunda ekrana net bilgi basalım
    die('<div style="background-color: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb;">
        <h3>❌ Admin Paneli Veritabanı Hatası</h3>
        <p><strong>Host:</strong> ' . DB_SERVER . '</p>
        <p><strong>Hata Mesajı:</strong> ' . $e->getMessage() . '</p>
        </div>');
}
?>