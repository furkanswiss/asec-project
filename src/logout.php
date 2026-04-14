<?php
// 1. Önce db.php'yi çağırıyoruz ki doğru session yolunu (sessions klasörünü) bulsun.
require_once 'db.php';

// 2. Çıktı tamponlamayı başlat (Header hatası almamak için)
ob_start();

// 3. Tüm Session değişkenlerini hafızadan sil (RAM'i temizle)
$_SESSION = array();

// 4. Tarayıcıdaki Çerezi (Cookie) de sil (Tam temizlik)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Sunucudaki Session dosyasını fiziksel olarak yok et
session_destroy();

// 6. Ana sayfaya yönlendir
header("Location: index.php");
exit;
?>