<?php
// debug_files.php
echo "<h2>Docker İçerik Kontrolü</h2>";
echo "Çalışılan Dizin: " . __DIR__ . "<br><hr>";

$hedef_klasor = __DIR__ . '/PHPMailer';

if (is_dir($hedef_klasor)) {
    echo "<p style='color:green'>✅ PHPMailer Klasörü Bulundu!</p>";
    echo "<h3>Klasör İçeriği:</h3><pre>";
    // Klasör içindeki dosyaları listele
    $dosyalar = scandir($hedef_klasor);
    print_r($dosyalar);
    echo "</pre>";
} else {
    echo "<p style='color:red'>❌ HATA: Docker bu dizinde 'PHPMailer' adında bir klasör GÖRMÜYOR.</p>";
    echo "<p>Mevcut dizindeki her şey şunlar:</p><pre>";
    print_r(scandir(__DIR__));
    echo "</pre>";
}
?>