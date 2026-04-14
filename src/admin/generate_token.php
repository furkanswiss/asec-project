<?php
// Token oluşturma yardımcısı
$security_key = "asec_secure_key_2025";

// Varsayılan değerler
$username = "admin2";
$password = "123456";

// URL'den parametreleri al (eğer varsa)
if(isset($_GET['username'])) {
    $username = $_GET['username'];
}
if(isset($_GET['password'])) {
    $password = $_GET['password'];
}

// Token oluştur
$token = md5($username . $password . $security_key);

// URL oluştur
$base_url = "http://localhost:8000/admin/create_admin_url.php";
$full_url = $base_url . "?key=" . urlencode($security_key) . "&username=" . urlencode($username) . "&password=" . urlencode($password) . "&token=" . urlencode($token);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Oluşturucu - ASEC Kulübü</title>
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
            max-width: 800px;
        }
        .header {
            margin-bottom: 2rem;
            text-align: center;
        }
        .header h1 {
            color: #1B1F3B;
            margin-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
        }
        .btn {
            display: inline-block;
            background-color: #6A0DAD;
            color: #fff;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-family: inherit;
        }
        .btn:hover {
            background-color: #4B0082;
        }
        .result {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .result h3 {
            margin-top: 0;
            color: #1B1F3B;
        }
        .url-box {
            background-color: #f1f1f1;
            padding: 1rem;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 1rem 0;
        }
        .copy-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .copy-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ASEC Kulübü</h1>
            <p>Admin Oluşturma Token Üreteci</p>
        </div>
        
        <form method="GET" action="">
            <div class="form-group">
                <label for="username">Kullanıcı Adı:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>" required>
            </div>
            
            <button type="submit" class="btn">Token Oluştur</button>
        </form>
        
        <div class="result">
            <h3>Oluşturulan Token ve URL</h3>
            <p><strong>Kullanıcı Adı:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>Şifre:</strong> <?php echo htmlspecialchars($password); ?></p>
            <p><strong>Token:</strong> <?php echo $token; ?></p>
            
            <h4>Admin Oluşturma URL'si:</h4>
            <div class="url-box" id="urlBox">
                <?php echo htmlspecialchars($full_url); ?>
            </div>
            
            <button class="copy-btn" onclick="copyToClipboard()">URL'yi Kopyala</button>
            
            <p style="margin-top: 1.5rem;">
                <a href="<?php echo htmlspecialchars($full_url); ?>" class="btn" target="_blank">Admin Oluştur</a>
            </p>
        </div>
    </div>
    
    <script>
        function copyToClipboard() {
            const urlBox = document.getElementById('urlBox');
            const textArea = document.createElement('textarea');
            textArea.value = urlBox.textContent.trim();
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            const copyBtn = document.querySelector('.copy-btn');
            const originalText = copyBtn.textContent;
            copyBtn.textContent = 'Kopyalandı!';
            setTimeout(() => {
                copyBtn.textContent = originalText;
            }, 2000);
        }
    </script>
</body>
</html>
