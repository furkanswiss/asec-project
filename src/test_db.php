<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Veritabanı Bağlantı Testi<br>";

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'db_asec';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Bağlantı hatası: " . $conn->connect_error);
    }
    
    echo "Veritabanına başarıyla bağlandı!<br>";
    
    // Blog tablosunu kontrol et
    $result = $conn->query("SELECT * FROM blog_posts LIMIT 1");
    if ($result) {
        echo "Blog tablosu mevcut ve erişilebilir.<br>";
        $row = $result->fetch_assoc();
        if ($row) {
            echo "İlk blog yazısı başlığı: " . htmlspecialchars($row['title']);
        } else {
            echo "Blog tablosu boş.";
        }
    } else {
        echo "Blog tablosu bulunamadı veya erişilemiyor: " . $conn->error;
    }
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage();
}
?>
