<?php
require_once 'includes/config.php';

// Güvenlik anahtarı - bu değeri değiştirerek güvenliği artırabilirsiniz
$security_key = "asec_secure_key_2025";

// URL'den parametreleri al ve güvenli hale getir
$key = isset($_GET['key']) ? trim($_GET['key']) : '';
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$password = isset($_GET['password']) ? trim($_GET['password']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Debug bilgisi (geliştirme aşamasında yardımcı olabilir)
$debug_info = "";
$debug_info .= "Key: " . htmlspecialchars($key) . "<br>";
$debug_info .= "Username: " . htmlspecialchars($username) . "<br>";
$debug_info .= "Password: " . htmlspecialchars($password) . "<br>";
$debug_info .= "Token: " . htmlspecialchars($token) . "<br>";

// Güvenlik token'ı oluştur (username + password + security_key)
$expected_token = md5($username . $password . $security_key);
$debug_info .= "Expected Token: " . $expected_token . "<br>";
$debug_info .= "Token Eşleşmesi: " . ($token === $expected_token ? "Evet" : "Hayır") . "<br>";
$debug_info .= "Key Eşleşmesi: " . ($key === $security_key ? "Evet" : "Hayır") . "<br>";
$debug_info .= "Username Boş mu: " . (empty($username) ? "Evet" : "Hayır") . "<br>";
$debug_info .= "Password Boş mu: " . (empty($password) ? "Evet" : "Hayır") . "<br>";

// Çıktı için HTML başlangıcı
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Oluştur - ASEC Kulübü</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        .header {
            margin-bottom: 2rem;
        }
        .header h1 {
            color: #1B1F3B;
            margin-bottom: 0.5rem;
        }
        .content {
            margin-bottom: 2rem;
        }
        .success {
            color: #28a745;
            padding: 1rem;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .error {
            color: #dc3545;
            padding: 1rem;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .btn {
            display: inline-block;
            background-color: #6A0DAD;
            color: #fff;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        .btn:hover {
            background-color: #4B0082;
        }
        .info {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: left;
        }
        .info p {
            margin: 0.5rem 0;
        }
        .code {
            font-family: monospace;
            background-color: #f1f1f1;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ASEC Kulübü</h1>
            <p>Admin Oluşturma Sistemi</p>
        </div>
        <div class="content">
<?php
// Güvenlik kontrolü
if ($key !== $security_key || $token !== $expected_token || empty($username) || empty($password)) {
    echo '<div class="error">';
    echo '<i class="fas fa-exclamation-triangle"></i> Hata: Geçersiz güvenlik parametreleri.';
    echo '</div>';
    echo '<p>Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>';
    
    // Hata ayıklama bilgilerini göster
    echo '<div class="debug-info" style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; text-align: left;">';
    echo '<h3>Hata Ayıklama Bilgileri:</h3>';
    echo $debug_info;
    echo '</div>';
} else {
    // Şifreyi hashle
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Kullanıcı adının veritabanında olup olmadığını kontrol et
    $check_sql = "SELECT id FROM admin_users WHERE username = ?";
    $user_exists = false;
    
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $user_exists = true;
        }
        mysqli_stmt_close($stmt);
    }
    
    if ($user_exists) {
        // Kullanıcı adı zaten varsa güncelle
        $sql = "UPDATE admin_users SET password = ? WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $username);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="success">';
                echo '<i class="fas fa-check-circle"></i> Admin kullanıcısı başarıyla güncellendi!';
                echo '</div>';
                echo '<div class="info">';
                echo '<p><strong>Kullanıcı adı:</strong> ' . htmlspecialchars($username) . '</p>';
                echo '<p><strong>Şifre:</strong> ' . htmlspecialchars($password) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<i class="fas fa-exclamation-triangle"></i> Hata oluştu: ' . mysqli_error($conn);
                echo '</div>';
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Yeni admin kullanıcısı ekle
        $sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="success">';
                echo '<i class="fas fa-check-circle"></i> Admin kullanıcısı başarıyla oluşturuldu!';
                echo '</div>';
                echo '<div class="info">';
                echo '<p><strong>Kullanıcı adı:</strong> ' . htmlspecialchars($username) . '</p>';
                echo '<p><strong>Şifre:</strong> ' . htmlspecialchars($password) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<i class="fas fa-exclamation-triangle"></i> Hata oluştu: ' . mysqli_error($conn);
                echo '</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    echo '<a href="login.php" class="btn">Admin Giriş Sayfasına Git</a>';
    
    // Kullanım bilgisi
    echo '<div class="info" style="margin-top: 2rem;">';
    echo '<h3>URL Kullanım Bilgisi:</h3>';
    echo '<p>Admin oluşturmak için aşağıdaki URL formatını kullanabilirsiniz:</p>';
    echo '<p class="code">create_admin_url.php?key=' . $security_key . '&username=KULLANICI_ADI&password=ŞİFRE&token=TOKEN</p>';
    echo '<p>Token değeri şu şekilde oluşturulur: <span class="code">md5(username + password + security_key)</span></p>';
    echo '<p><strong>Örnek Token Oluşturma (PHP):</strong></p>';
    echo '<p class="code">$token = md5($username . $password . "' . $security_key . '");</p>';
    echo '<p><a href="generate_token.php" class="btn">Token Oluşturma Aracını Kullan</a></p>';
    echo '</div>';
}

mysqli_close($conn);
?>
        </div>
    </div>
</body>
</html>
