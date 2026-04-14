<?php
/**
 * ASEC Kulübü - Görsel Optimizasyon Sistemi
 * Bu dosya, görsel optimizasyonu için gerekli fonksiyonları içerir.
 */

// Hata raporlamayı kapat
error_reporting(0);

// URL parametrelerini al
$image_path = $_SERVER['QUERY_STRING'];
$optimize = isset($_GET['optimize']) ? $_GET['optimize'] : false;
$width = isset($_GET['width']) ? intval($_GET['width']) : null;
$height = isset($_GET['height']) ? intval($_GET['height']) : null;
$quality = isset($_GET['quality']) ? intval($_GET['quality']) : 80;

// Optimize parametresi yoksa veya false ise, doğrudan orijinal görseli göster
if (!$optimize || $optimize !== 'true') {
    header("Location: $image_path");
    exit;
}

// Görsel yolunu temizle
$image_path = explode('?', $image_path)[0];

// Görsel var mı kontrol et
if (!file_exists($image_path)) {
    header("HTTP/1.0 404 Not Found");
    exit("Görsel bulunamadı: $image_path");
}

// Görsel türünü belirle
$image_info = getimagesize($image_path);
$mime_type = $image_info['mime'];

// Desteklenen görsel formatları
switch ($mime_type) {
    case 'image/jpeg':
        $source_image = imagecreatefromjpeg($image_path);
        break;
    case 'image/png':
        $source_image = imagecreatefrompng($image_path);
        break;
    case 'image/gif':
        $source_image = imagecreatefromgif($image_path);
        break;
    default:
        // Desteklenmeyen format, doğrudan orijinal görseli göster
        header("Content-Type: $mime_type");
        readfile($image_path);
        exit;
}

// Orijinal boyutları al
$original_width = imagesx($source_image);
$original_height = imagesy($source_image);

// Yeni boyutları hesapla
if ($width && !$height) {
    // Sadece genişlik belirtilmişse, oranı koru
    $new_width = $width;
    $new_height = ($original_height / $original_width) * $new_width;
} elseif (!$width && $height) {
    // Sadece yükseklik belirtilmişse, oranı koru
    $new_height = $height;
    $new_width = ($original_width / $original_height) * $new_height;
} elseif ($width && $height) {
    // Her ikisi de belirtilmişse, tam boyut
    $new_width = $width;
    $new_height = $height;
} else {
    // Hiçbiri belirtilmemişse, orijinal boyut
    $new_width = $original_width;
    $new_height = $original_height;
}

// Yeni görsel oluştur
$new_image = imagecreatetruecolor($new_width, $new_height);

// PNG için şeffaflık koruması
if ($mime_type === 'image/png') {
    imagealphablending($new_image, false);
    imagesavealpha($new_image, true);
    $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
    imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
}

// Görseli yeniden boyutlandır
imagecopyresampled(
    $new_image,
    $source_image,
    0, 0, 0, 0,
    $new_width, $new_height,
    $original_width, $original_height
);

// Görseli çıktıla
header("Content-Type: $mime_type");
switch ($mime_type) {
    case 'image/jpeg':
        imagejpeg($new_image, null, $quality);
        break;
    case 'image/png':
        // PNG için kalite 0-9 arası (9 en iyi sıkıştırma)
        $png_quality = ($quality - 100) / 11.111111;
        $png_quality = round(abs($png_quality));
        imagepng($new_image, null, $png_quality);
        break;
    case 'image/gif':
        imagegif($new_image);
        break;
}

// Belleği temizle
imagedestroy($source_image);
imagedestroy($new_image);
