<?php
// Etkinlik Ekleme
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once '../db.php';
require_once '../includes/email_queue_helper.php';

// Ensure English columns exist
try {
    $columns = $pdo->query("SHOW COLUMNS FROM etkinlikler LIKE 'baslik_en'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE etkinlikler ADD COLUMN baslik_en VARCHAR(255) NULL AFTER baslik");
    }
    $columns = $pdo->query("SHOW COLUMNS FROM etkinlikler LIKE 'aciklama_en'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE etkinlikler ADD COLUMN aciklama_en LONGTEXT NULL AFTER aciklama");
    }
} catch (Exception $e) {
    // Columns might already exist
}

$msg = '';
$msgClass = 'alert-success'; // Uyarı mesajının rengi için varsayılan sınıf eklendi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'] ?? '';
    $baslik_en = $_POST['baslik_en'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';
    $aciklama_en = $_POST['aciklama_en'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    $saat = $_POST['saat'] ?? '';
    $yer = $_POST['yer'] ?? '';
    $kayit_link = $_POST['kayit_link'] ?? '';

    // ÇÖZÜM 2: Tarih boş mu diye kontrol ediyoruz
    if (empty($tarih)) {
        $msg = 'Lütfen geçerli bir tarih seçiniz!';
        $msgClass = 'alert-danger'; // Hata olduğu için kırmızı (danger) uyarı göster
    } else {
        // Etkinlik kaydı
        $stmt = $pdo->prepare('INSERT INTO etkinlikler (baslik, baslik_en, aciklama, aciklama_en, tarih, saat, yer, kayit_link, foto_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)');
        $ok = $stmt->execute([$baslik, $baslik_en, $aciklama, $aciklama_en, $tarih, $saat, $yer, $kayit_link]);
        
        if ($ok) {
            $etkinlik_id = $pdo->lastInsertId();
            
            // Queue notification for new event (only for new insertions, not updates)
            $events_title = $baslik;
            // Construct base URL
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base_path = dirname(dirname($_SERVER['PHP_SELF']));
            $events_url = $protocol . '://' . $host . $base_path . '/etkinlik-detay.php?id=' . $etkinlik_id;
            queueEventNotification($pdo, $events_title, $events_url);
            
            $msg = 'Etkinlik başarıyla eklendi!';
            $msgClass = 'alert-success'; // Başarılı olduğu için yeşil göster
            
            // Fotoğraf yükleme işlemi
            $upload_dir = __DIR__ . '/../uploads/etkinlikler/' . $etkinlik_id . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $dosya_sayisi = isset($_FILES['fotolar']['name']) ? count($_FILES['fotolar']['name']) : 0;
            if ($dosya_sayisi > 10) {
                $msg = 'Etkinlik eklendi ancak en fazla 10 fotoğraf yükleyebilirsiniz!';
                $msgClass = 'alert-warning'; // Uyarı rengi
            } else {
                for ($i = 0; $i < $dosya_sayisi; $i++) {
                    $tmp_name = $_FILES['fotolar']['tmp_name'][$i];
                    $name = basename($_FILES['fotolar']['name'][$i]);
                    $type = $_FILES['fotolar']['type'][$i];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                        $new_name = uniqid('foto_', true) . '.' . $ext;
                        $target_path = $upload_dir . $new_name;
                        if (move_uploaded_file($tmp_name, $target_path)) {
                            $rel_path = 'uploads/etkinlikler/' . $etkinlik_id . '/' . $new_name;
                            $stmt2 = $pdo->prepare('INSERT INTO etkinlik_fotolar (etkinlik_id, dosya_yolu) VALUES (?, ?)');
                            $stmt2->execute([$etkinlik_id, $rel_path]);
                        }
                    }
                }
            }
        } else {
            $msg = 'Kayıt sırasında bir hata oluştu!';
            $msgClass = 'alert-danger';
        }
    }
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Etkinlik Ekle</h1>
      
      <?php if($msg): ?><div class="alert <?= $msgClass ?>"><?= $msg ?></div><?php endif; ?>
      
      <form method="post" enctype="multipart/form-data">
        <ul class="nav nav-tabs mb-4" id="langTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="tr-tab" data-toggle="tab" href="#tr-content" role="tab" aria-controls="tr-content" aria-selected="true">
                    <i class="fas fa-flag"></i> Türkçe
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="en-tab" data-toggle="tab" href="#en-content" role="tab" aria-controls="en-content" aria-selected="false">
                    <i class="fas fa-flag"></i> English
                </a>
            </li>
        </ul>

        <div class="tab-content mb-4" id="langTabContent">
            <div class="tab-pane fade show active" id="tr-content" role="tabpanel" aria-labelledby="tr-tab">
                <div class="form-group">
                    <label>Başlık (Türkçe) *</label>
                    <input type="text" name="baslik" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Açıklama (Türkçe)</label>
                    <textarea name="aciklama" rows="5" class="form-control"></textarea>
                </div>
            </div>

            <div class="tab-pane fade" id="en-content" role="tabpanel" aria-labelledby="en-tab">
                <div class="form-group">
                    <label>Title (English)</label>
                    <input type="text" name="baslik_en" class="form-control" placeholder="Optional: English title">
                </div>
                <div class="form-group">
                    <label>Description (English)</label>
                    <textarea name="aciklama_en" rows="5" class="form-control" placeholder="Optional: English description"></textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Tarih *</label>
            <input type="date" name="tarih" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Saat</label>
            <input type="text" name="saat" class="form-control" placeholder="Örn: 14:00 - 17:00">
        </div>
        <div class="form-group">
            <label>Yer</label>
            <input type="text" name="yer" class="form-control">
        </div>
        <div class="form-group">
            <label>Kayıt Linki</label>
            <input type="text" name="kayit_link" class="form-control">
        </div>
        <div class="form-group">
            <label>Etkinlik Fotoğrafları (En fazla 10 adet)</label>
            <input type="file" name="fotolar[]" accept="image/*" multiple class="form-control" required>
            <small>JPG, PNG veya GIF. Maksimum 10 dosya.</small>
        </div>
        <button class="btn btn-primary" type="submit">Kaydet</button>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>