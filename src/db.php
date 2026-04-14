<?php
// db.php – TEMİZ VE DOCKER UYUMLU SÜRÜM

ob_start();

// 1. SESSION AYARLARI (Docker'da varsayılan yol en iyisidir)
// Özel klasör oluşturma kodlarını sildik, Docker'ın kendi /tmp klasörünü kullanacak.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. VERİTABANI AYARLARI
$charset = 'utf8mb4';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Docker Environment Değişkenlerini Kullan (Yoksa varsayılanları al)
// Bu sayede .env dosyasında ne yazıyorsa ona bağlanır.
$host = getenv('DB_HOST') ?: 'database'; 
$db   = getenv('DB_NAME') ?: 'db_asec';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Hata detayını ekrana bas (Sorunu görmek için)
    die('<div style="background-color: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; font-family: sans-serif;">
        <h3>❌ Veritabanı Bağlantı Hatası</h3>
        <p><strong>Hedef Host:</strong> ' . $host . '</p>
        <p><strong>Hedef DB:</strong> ' . $db . '</p>
        <p><strong>Hata Mesajı:</strong> ' . $e->getMessage() . '</p>
        </div>');
}
?>