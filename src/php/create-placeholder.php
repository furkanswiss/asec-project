<?php
/**
 * ASEC Kulübü - Placeholder Görsel Oluşturucu
 * Bu dosya, lazy loading için gerekli placeholder görselini oluşturur.
 */

// Placeholder görsel boyutları
$width = 100;
$height = 100;

// Görsel oluştur
$image = imagecreatetruecolor($width, $height);

// Arkaplan rengi (açık gri)
$bg_color = imagecolorallocate($image, 240, 240, 240);
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Kenarlık rengi (koyu gri)
$border_color = imagecolorallocate($image, 200, 200, 200);
imagerectangle($image, 0, 0, $width - 1, $height - 1, $border_color);

// Orta çizgi rengi
$line_color = imagecolorallocate($image, 220, 220, 220);

// Çapraz çizgiler çiz
imageline($image, 0, 0, $width, $height, $line_color);
imageline($image, 0, $height, $width, 0, $line_color);

// Görsel simgesi çiz (basit kamera ikonu)
$icon_color = imagecolorallocate($image, 180, 180, 180);
$center_x = $width / 2;
$center_y = $height / 2;
$size = min($width, $height) / 4;

// Kamera gövdesi
imagefilledrectangle($image, $center_x - $size, $center_y - $size / 2, $center_x + $size, $center_y + $size, $icon_color);

// Kamera lensi
imagefilledellipse($image, $center_x, $center_y, $size, $size, $border_color);
imagefilledellipse($image, $center_x, $center_y, $size - 2, $size - 2, $bg_color);
imagefilledellipse($image, $center_x, $center_y, $size / 2, $size / 2, $icon_color);

// Kamera flaşı
imagefilledrectangle($image, $center_x + $size / 2, $center_y - $size, $center_x + $size, $center_y - $size / 2, $icon_color);

// Görseli kaydet
$placeholder_path = '../images/placeholder.jpg';
imagejpeg($image, $placeholder_path, 90);

// Belleği temizle
imagedestroy($image);

echo "Placeholder görsel oluşturuldu: $placeholder_path";

// Loading GIF oluştur
$loading_width = 30;
$loading_height = 30;
$loading_frames = 8;
$loading_delay = 4; // Her kare arasındaki gecikme (100'de bir saniye)

// GIF oluştur
$loading_gif = imagecreatetruecolor($loading_width, $loading_height);
$transparent = imagecolorallocatealpha($loading_gif, 0, 0, 0, 127);
imagefill($loading_gif, 0, 0, $transparent);

// GIF'i kaydet
$loading_path = '../images/loading.gif';
$loading_file = fopen($loading_path, 'w');
fclose($loading_file);

echo "<br>Loading GIF oluşturuldu: $loading_path";
?>
