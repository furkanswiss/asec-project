<?php
// 1. ÖNEMLİ: db.php'yi çağırıyoruz ki doğru session ayarlarını yüklesin.
// Eğer bunu yapmazsanız PHP varsayılan session'ı siler, asıl kullanıcı kalır.
require_once '../db.php';

// 2. Çıktı tamponlamayı başlat
ob_start();

// 3. Eğer db.php session'ı başlatmadıysa biz başlatalım
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4. Tüm Session değişkenlerini temizle
$_SESSION = array();

// 5. Tarayıcıdaki Çerezi (Cookie) sil (Bireysel logout'taki ile aynı mantık)
// Bu adım, tarayıcının hala eski session ID'sini tutmasını engeller.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 6. Sunucudaki Session dosyasını yok et
session_destroy();

// 7. Login sayfasına yönlendir
header("location: ../login.php");
exit;
?>