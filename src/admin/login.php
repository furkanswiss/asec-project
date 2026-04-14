<?php
require_once 'includes/config.php';

// GEÇİCİ KOD BLOĞU - Admin şifre güncelleme
$create_table_sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";
mysqli_query($conn, $create_table_sql);

$username = "admin";
$new_password = "Adminasec145!";
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Mevcut admin kullanıcısını kontrol et
$check_sql = "SELECT id FROM admin_users WHERE username = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $username);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

// Eğer admin kullanıcısı yoksa oluştur, varsa şifreyi güncelle
if(mysqli_stmt_num_rows($check_stmt) == 0) {
    $insert_sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
    if($stmt = mysqli_prepare($conn, $insert_sql)){
        mysqli_stmt_bind_param($stmt, "ss", $username, $new_hashed_password);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
} else {
    // Mevcut admin kullanıcısının şifresini güncelle
    $update_sql = "UPDATE admin_users SET password = ? WHERE username = ?";
    if($stmt = mysqli_prepare($conn, $update_sql)){
        mysqli_stmt_bind_param($stmt, "ss", $new_hashed_password, $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
mysqli_stmt_close($check_stmt);
// GEÇİCİ KOD BLOĞU SONU

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Kullanıcı adını giriniz.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Şifrenizi giriniz.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password FROM admin_users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            header("location: dashboard.php");
                        } else{
                            $login_err = "Geçersiz kullanıcı adı veya şifre.";
                        }
                    }
                } else{
                    $login_err = "Geçersiz kullanıcı adı veya şifre.";
                }
            } else{
                echo "Bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>
 
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>ASEC Admin - Giriş</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="../images/gallery/try.png" type="image/png">
    <link rel="shortcut icon" href="../images/gallery/try.png" type="image/png">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .wrapper {
            width: 360px;
            padding: 20px;
            margin: 100px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            width: 100%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2 class="text-center mb-4">ASEC Admin</h2>
        <p class="text-center mb-4">Yönetim paneline giriş yapın</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Giriş Yap">
            </div>
        </form>
    </div>    
</body>
</html> 