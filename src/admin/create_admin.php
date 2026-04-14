<?php
require_once 'includes/config.php';

// Yeni şifre oluştur
$password = "admin123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Önce tabloyu temizle
mysqli_query($conn, "TRUNCATE TABLE admin_users");

// Yeni admin kullanıcısı ekle
$sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
    $username = "admin";
    
    if(mysqli_stmt_execute($stmt)){
        echo "Admin kullanıcısı başarıyla oluşturuldu!<br>";
        echo "Kullanıcı adı: admin<br>";
        echo "Şifre: admin123<br>";
        echo "<a href='login.php'>Giriş sayfasına git</a>";
    } else{
        echo "Hata oluştu: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?> 