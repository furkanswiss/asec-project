<?php
require_once 'db.php';
ob_start(); // Docker'da hata almamak için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu başlat
}
 // Veritabanını dahil et
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Önemli Bilgilendirmeler - ASEC Kulübü</title>
    <link rel="stylesheet" href="css/onemli-bilgilendirmeler.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="bilgilendirmeler-container">
            <h2 class="page-title">Önemli Bilgilendirmeler</h2>
            <?php
            require_once 'db.php';
            require_once 'includes/lang.php';
            
            // Determine language (use cookie from lang.php, fallback to 'tr')
            $currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');
            
            // Create table if not exists
            $pdo->exec('CREATE TABLE IF NOT EXISTS onemli_bilgiler (
                id INT AUTO_INCREMENT PRIMARY KEY,
                baslik VARCHAR(255) NOT NULL,
                aciklama TEXT NOT NULL,
                icerik TEXT NOT NULL,
                resim VARCHAR(255) DEFAULT NULL,
                tarih DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tarih (tarih)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            
            $bilgiler = $pdo->query('SELECT * FROM onemli_bilgiler ORDER BY tarih DESC')->fetchAll();
            ?>
            
            <!-- Card Grid View -->
            <div id="card-grid" class="bilgiler-grid">
                <?php if(empty($bilgiler)): ?>
                    <div class="no-bilgiler">
                        <i class="fas fa-info-circle"></i>
                        <h3>Henüz bilgi eklenmemiş</h3>
                        <p>Yakında önemli bilgilendirmeler burada yer alacaktır.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($bilgiler as $bilgi): 
                        // Select title, description, and content based on language
                        if ($currentLang == 'en' && !empty($bilgi['baslik_en']) && !empty($bilgi['aciklama_en'])) {
                            $display_baslik = $bilgi['baslik_en'];
                            $display_aciklama = $bilgi['aciklama_en'];
                        } else {
                            // Default to Turkish
                            $display_baslik = $bilgi['baslik'];
                            $display_aciklama = $bilgi['aciklama'];
                        }
                    ?>
                        <div class="bilgi-card" data-id="<?= $bilgi['id'] ?>">
                            <?php if(!empty($bilgi['resim'])): ?>
                                <div class="card-header-image">
                                    <img src="uploads/onemli-bilgiler/<?= htmlspecialchars($bilgi['resim']) ?>" alt="<?= htmlspecialchars($display_baslik) ?>">
                                </div>
                            <?php else: ?>
                                <div class="card-header-image placeholder">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-content">
                                <h3 class="card-title"><?= htmlspecialchars($display_baslik) ?></h3>
                                <p class="card-description"><?= htmlspecialchars($display_aciklama) ?></p>
                                <div class="card-footer">
                                    <span class="card-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= date('d M Y', strtotime($bilgi['tarih'])) ?>
                                    </span>
                                    <button class="devami-btn" data-id="<?= $bilgi['id'] ?>">
                                        Devamı <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Detail View -->
            <div id="detail-view" class="detail-view" style="display: none;">
                <button class="back-btn" id="back-to-grid">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </button>
                <div id="detail-content"></div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="js/onemli-bilgilendirmeler.js"></script>
    <script src="javascript/script.js"></script>
</body>
</html>

