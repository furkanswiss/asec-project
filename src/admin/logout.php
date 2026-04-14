<?php
session_start();
 
// Tüm session değişkenlerini temizle
$_SESSION = array();
 
// Session'ı sonlandır
session_destroy();
 
// Login sayfasına yönlendir
header("location: login.php");
exit;
?> 