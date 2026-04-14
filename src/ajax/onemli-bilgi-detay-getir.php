<?php
// Önemli Bilgi Detay Getir (AJAX)
header('Content-Type: application/json');
require_once '../db.php';
require_once '../includes/lang.php';

// Determine language (use cookie from lang.php, fallback to 'tr')
$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ID']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM onemli_bilgiler WHERE id = ?');
    $stmt->execute([$id]);
    $bilgi = $stmt->fetch();
    
    if ($bilgi) {
        // Select title, description, and content based on language
        if ($currentLang == 'en' && !empty($bilgi['baslik_en']) && !empty($bilgi['aciklama_en']) && !empty($bilgi['icerik_en'])) {
            $bilgi['display_baslik'] = $bilgi['baslik_en'];
            $bilgi['display_aciklama'] = $bilgi['aciklama_en'];
            $bilgi['display_icerik'] = $bilgi['icerik_en'];
        } else {
            // Default to Turkish
            $bilgi['display_baslik'] = $bilgi['baslik'];
            $bilgi['display_aciklama'] = $bilgi['aciklama'];
            $bilgi['display_icerik'] = $bilgi['icerik'];
        }
        
        echo json_encode([
            'success' => true,
            'bilgi' => $bilgi
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Bilgi bulunamadı'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>

