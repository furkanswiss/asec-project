<?php
/**
 * ASEC Kulübü - Favicon Oluşturucu
 * Bu script, logo dosyasını kullanarak favicon oluşturur.
 */

// Hata raporlamayı kapat
error_reporting(0);

// Logo dosyasının yolu
$logo_path = '../images/gallery/logo3.png';

// Favicon boyutları
$favicon_sizes = [
    16, 32, 48, 64, 128, 192, 256
];

// Favicon klasörü
$favicon_dir = '../images/favicon/';

// Favicon klasörü yoksa oluştur
if (!file_exists($favicon_dir)) {
    mkdir($favicon_dir, 0755, true);
}

// Logo dosyası var mı kontrol et
if (!file_exists($logo_path)) {
    die("Logo dosyası bulunamadı: $logo_path");
}

// Logo dosyasını yükle
$source_image = imagecreatefrompng($logo_path);
if (!$source_image) {
    die("Logo dosyası yüklenemedi.");
}

// Orijinal boyutları al
$original_width = imagesx($source_image);
$original_height = imagesy($source_image);

// Her boyut için favicon oluştur
foreach ($favicon_sizes as $size) {
    // Yeni görsel oluştur
    $favicon = imagecreatetruecolor($size, $size);
    
    // PNG için şeffaflık koruması
    imagealphablending($favicon, false);
    imagesavealpha($favicon, true);
    $transparent = imagecolorallocatealpha($favicon, 0, 0, 0, 127);
    imagefilledrectangle($favicon, 0, 0, $size, $size, $transparent);
    
    // Logoyu yeniden boyutlandır
    imagecopyresampled(
        $favicon,
        $source_image,
        0, 0, 0, 0,
        $size, $size,
        $original_width, $original_height
    );
    
    // Favicon dosyasını kaydet
    $favicon_path = $favicon_dir . "favicon-{$size}x{$size}.png";
    imagepng($favicon, $favicon_path, 9); // En yüksek sıkıştırma
    
    echo "Favicon oluşturuldu: $favicon_path<br>";
    
    // Belleği temizle
    imagedestroy($favicon);
}

// ICO dosyası oluştur (16x16 ve 32x32 boyutlarında)
$favicon_ico_path = '../favicon.ico';
$favicon_16 = $favicon_dir . "favicon-16x16.png";
$favicon_32 = $favicon_dir . "favicon-32x32.png";

// Belleği temizle
imagedestroy($source_image);

echo "<br>Favicon dosyaları başarıyla oluşturuldu.";
echo "<br>Şimdi bu dosyaları HTML head bölümüne ekleyin.";
?>
