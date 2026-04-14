<?php
// Veritabanı bağlantısı
require_once '../db.php';
require_once '../includes/lang.php';

// Determine language from GET parameter, cookie, or default to 'tr'
$currentLang = isset($_GET['lang']) && in_array($_GET['lang'], ['tr', 'en']) 
    ? $_GET['lang'] 
    : (isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr'));

// AJAX isteğinden etkinlik ID'sini al
$etkinlik_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($etkinlik_id <= 0) {
    echo '<div class="error">Geçersiz etkinlik ID\'si.</div>';
    exit;
}

try {
    // Etkinlik detaylarını getir
    $stmt = $pdo->prepare("SELECT * FROM etkinlikler WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $etkinlik_id]);
    $etkinlik = $stmt->fetch();

    if (!$etkinlik) {
        echo '<div class="error">Etkinlik bulunamadı.</div>';
        exit;
    }

    // Select title and description based on language
    if ($currentLang == 'en' && !empty($etkinlik['baslik_en']) && !empty($etkinlik['aciklama_en'])) {
        $display_baslik = $etkinlik['baslik_en'];
        $display_aciklama = $etkinlik['aciklama_en'];
    } else {
        // Default to Turkish
        $display_baslik = $etkinlik['baslik'];
        $display_aciklama = $etkinlik['aciklama'];
    }

    // Tarih ve saat formatını düzenle
    $tarih = new DateTime($etkinlik['tarih']);
    $tarih_formati = $tarih->format('d.m.Y');
    
    // HTML çıktısı oluştur
    ?>
    <div class="etkinlik-detay-modal">
        <h2><?= htmlspecialchars($display_baslik) ?></h2>
        
        <div class="etkinlik-meta">
            <div class="meta-item">
                <i class="fas fa-calendar-alt"></i> 
                <span><?= $tarih_formati ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-clock"></i> 
                <span><?= htmlspecialchars($etkinlik['saat']) ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-map-marker-alt"></i> 
                <span><?= htmlspecialchars($etkinlik['yer']) ?></span>
            </div>
        </div>
        
        <div class="etkinlik-aciklama">
            <p><?= nl2br(htmlspecialchars($display_aciklama)) ?></p>
        </div>
        
        <?php if (!empty($etkinlik['kayit_link'])): ?>
        <div class="etkinlik-kayit">
            <a href="<?= htmlspecialchars($etkinlik['kayit_link']) ?>" class="kayit-btn" target="_blank">
                <i class="fas fa-user-plus"></i> Kayıt Ol
            </a>
        </div>
        <?php endif; ?>
        
        <div class="etkinlik-footer">
            <a href="etkinlik-detay.php?id=<?= $etkinlik['id'] ?>" class="detay-btn">
                <i class="fas fa-external-link-alt"></i> Etkinlik Sayfasına Git
            </a>
        </div>
    </div>
    <?php
} catch (PDOException $e) {
    echo '<div class="error">Veritabanı hatası: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
