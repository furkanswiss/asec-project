<?php
/**
 * ASEC Kulübü - Head Bölümü Güncelleme Scripti
 * Bu script, tüm PHP dosyalarının head bölümünü güncelleyerek favicon ekler.
 */

// Ana dizin
$baseDir = __DIR__;

// İşlenecek dosyalar
$files = [
    'blog.php',
    'blog-detay.php',
    'duyurular.php',
    'etkinlikler.php',
    'etkinlik-detay.php',
    'galeri.php',
    'hakkimizda.php',
    'iletisim.php',
    'login.php',
    'register.php',
    'profilim.php',
    'sifremi-unuttum.php',
    'sifre-sifirla.php',
    'takimlar.php',
    '403.php',
    '404.php',
    '500.php'
];

// İşlenecek dosya sayısı
$totalFiles = count($files);
$processedFiles = 0;

echo "Head bölümü güncelleme işlemi başlatılıyor...\n";

// Her dosyayı işle
foreach ($files as $file) {
    $filePath = $baseDir . '/' . $file;
    
    // Dosya var mı kontrol et
    if (!file_exists($filePath)) {
        echo "Dosya bulunamadı: $file\n";
        continue;
    }
    
    // Dosya içeriğini oku
    $content = file_get_contents($filePath);
    
    // Head bölümünü bul
    if (preg_match('/<head>(.*?)<\/head>/s', $content, $matches)) {
        $headContent = $matches[1];
        
        // Head-meta.php zaten include edilmiş mi kontrol et
        if (strpos($headContent, "includes/head-meta.php") !== false) {
            echo "Dosya zaten güncellenmiş: $file\n";
            $processedFiles++;
            continue;
        }
        
        // Eski head içeriğini temizle ve sadece title'ı koru
        if (preg_match('/<title>(.*?)<\/title>/s', $headContent, $titleMatches)) {
            $title = $titleMatches[1];
            
            // Yeni head içeriği oluştur
            $newHeadContent = '
    <?php include \'includes/head-meta.php\'; ?>
    <title>' . $title . '</title>';
            
            // Sayfa özel CSS dosyalarını bul
            if (preg_match_all('/<link rel="stylesheet" href="css\/(.*?)\.css">/s', $headContent, $cssMatches)) {
                foreach ($cssMatches[1] as $cssFile) {
                    // Header ve footer CSS dosyalarını atla, çünkü head-meta.php içinde zaten var
                    if ($cssFile != 'reset' && $cssFile != 'header' && $cssFile != 'footer') {
                        $newHeadContent .= '
    <link rel="stylesheet" href="css/' . $cssFile . '.css">';
                    }
                }
            }
            
            // Yeni head içeriğini eski head içeriğiyle değiştir
            $newContent = str_replace($matches[0], '<head>' . $newHeadContent . '
</head>', $content);
            
            // Dosyayı güncelle
            file_put_contents($filePath, $newContent);
            echo "Dosya güncellendi: $file\n";
            $processedFiles++;
        } else {
            echo "Title bulunamadı: $file\n";
        }
    } else {
        echo "Head bölümü bulunamadı: $file\n";
    }
}

echo "İşlem tamamlandı. Toplam $totalFiles dosyadan $processedFiles dosya güncellendi.\n";
?>
