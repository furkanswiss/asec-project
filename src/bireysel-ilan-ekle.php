<?php
require_once 'db.php';
require_once 'includes/lang.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get the logged-in user's ID and validate it
$logged_in_user_id = intval($_SESSION['user_id']);
if ($logged_in_user_id <= 0) {
    header('Location: login.php');
    exit;
}

// Ensure individual_ilan_requests table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS individual_ilan_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    icerik TEXT NOT NULL,
    kategori VARCHAR(100) NOT NULL DEFAULT "Bireysel İlanlar",
    tarih DATE NOT NULL,
    link VARCHAR(500),
    sirket VARCHAR(255),
    lokasyon VARCHAR(255),
    son_basvuru DATE,
    iletisim_bilgisi VARCHAR(255),
    status ENUM("pending", "approved", "rejected") DEFAULT "pending",
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$msg = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $baslik = trim($_POST['baslik'] ?? '');
        $icerik = trim($_POST['icerik'] ?? '');
        $tarih = $_POST['tarih'] ?? date('Y-m-d');
        $link = trim($_POST['link'] ?? '');
        $sirket = trim($_POST['sirket'] ?? '');
        $lokasyon = trim($_POST['lokasyon'] ?? '');
        $son_basvuru = $_POST['son_basvuru'] ?? null;
        $iletisim_bilgisi = trim($_POST['iletisim_bilgisi'] ?? '');
        
        // Validation
        if (empty($baslik)) {
            $error = 'Başlık alanı zorunludur!';
        } elseif (empty($icerik)) {
            $error = 'İçerik alanı zorunludur!';
        } elseif (empty($tarih)) {
            $error = 'Tarih alanı zorunludur!';
        } else {
            // Create request - automatically set owner to the currently logged-in user
            // The user_id is taken from the session (cannot be overridden via POST/GET)
            $insertColumns = ['user_id', 'baslik', 'icerik', 'kategori', 'tarih', 'link', 'sirket', 'lokasyon', 'son_basvuru', 'iletisim_bilgisi', 'status'];
            $insertValues = [
                $logged_in_user_id, // Automatically set to the currently logged-in user
                $baslik, 
                $icerik, 
                'Bireysel İlanlar', 
                $tarih, 
                $link ?: null, 
                $sirket ?: null, 
                $lokasyon ?: null, 
                $son_basvuru ?: null,
                $iletisim_bilgisi ?: null,
                'pending'
            ];
            $placeholders = array_fill(0, count($insertValues), '?');
            
            $sql = 'INSERT INTO individual_ilan_requests (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute($insertValues);
            
            if ($ok) {
                $success = true;
            } else {
                $error = 'İlan isteği oluşturulurken bir hata oluştu!';
            }
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo $langCode === 'en' ? 'Post an Ad' : 'İlan Ver'; ?> - ASEC</title>
    <link rel="stylesheet" href="css/ilanlar.css">
    <style>
        .ilan-form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .btn-submit {
            background: linear-gradient(135deg, #9370db, #6A0DAD);
            color: #fff;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(147, 112, 219, 0.4);
            color: #fff;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="ilan-form-container">
            <h2><?php echo $langCode === 'en' ? 'Post an Individual Ad' : 'Bireysel İlan Ver'; ?></h2>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <?php echo $langCode === 'en' ? 'Your ad request has been submitted. Once approved, you will be able to see your ad on the individual ads page.' : 'İlan isteğiniz başarıyla gönderildi. Onaylandıktan sonra ilanınız bireysel ilanlar sayfasında görünecektir.'; ?>
                </div>
                <a href="ilanlar.php" class="btn-submit">
                    <?php echo $langCode === 'en' ? 'Back to Ads' : 'İlanlara Dön'; ?>
                </a>
            <?php else: ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="baslik"><?php echo $langCode === 'en' ? 'Title' : 'Başlık'; ?> *</label>
                        <input type="text" id="baslik" name="baslik" required value="<?= htmlspecialchars($_POST['baslik'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="icerik"><?php echo $langCode === 'en' ? 'Description' : 'Açıklama'; ?> *</label>
                        <textarea id="icerik" name="icerik" required><?= htmlspecialchars($_POST['icerik'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="sirket"><?php echo $langCode === 'en' ? 'Ad Owner' : 'İlan Sahibi'; ?></label>
                        <input type="text" id="sirket" name="sirket" value="<?= htmlspecialchars($_POST['sirket'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasyon"><?php echo $langCode === 'en' ? 'Location' : 'Lokasyon'; ?></label>
                        <input type="text" id="lokasyon" name="lokasyon" value="<?= htmlspecialchars($_POST['lokasyon'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="iletisim_bilgisi"><?php echo $langCode === 'en' ? 'Contact Information' : 'İletişim Bilgisi'; ?></label>
                        <input type="text" id="iletisim_bilgisi" name="iletisim_bilgisi" placeholder="<?php echo $langCode === 'en' ? 'Email or Phone' : 'E-posta veya Telefon'; ?>" value="<?= htmlspecialchars($_POST['iletisim_bilgisi'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="link"><?php echo $langCode === 'en' ? 'Link (Optional)' : 'Link (Opsiyonel)'; ?></label>
                        <input type="url" id="link" name="link" value="<?= htmlspecialchars($_POST['link'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tarih"><?php echo $langCode === 'en' ? 'Date' : 'Tarih'; ?> *</label>
                        <input type="date" id="tarih" name="tarih" required value="<?= htmlspecialchars($_POST['tarih'] ?? date('Y-m-d')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="son_basvuru"><?php echo $langCode === 'en' ? 'Application Deadline (Optional)' : 'Son Başvuru Tarihi (Opsiyonel)'; ?></label>
                        <input type="date" id="son_basvuru" name="son_basvuru" value="<?= htmlspecialchars($_POST['son_basvuru'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <?php echo $langCode === 'en' ? 'Submit Ad Request' : 'İlan İsteğini Gönder'; ?>
                    </button>
                    <a href="ilanlar.php" style="margin-left: 1rem; color: #666; text-decoration: none;">
                        <?php echo $langCode === 'en' ? 'Cancel' : 'İptal'; ?>
                    </a>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>

