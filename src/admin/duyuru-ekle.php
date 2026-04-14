<?php
// Duyuru Ekleme
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
require_once '../includes/email_queue_helper.php';

// Ensure English columns exist
try {
    $columns = $pdo->query("SHOW COLUMNS FROM duyurular LIKE 'baslik_en'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE duyurular ADD COLUMN baslik_en VARCHAR(255) NULL AFTER baslik");
    }
    $columns = $pdo->query("SHOW COLUMNS FROM duyurular LIKE 'icerik_en'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE duyurular ADD COLUMN icerik_en LONGTEXT NULL AFTER icerik");
    }
    // STEP 1: Ensure photo column exists
    $columns = $pdo->query("SHOW COLUMNS FROM duyurular LIKE 'photo'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE duyurular ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER baslik");
    }
} catch (Exception $e) {
    // Columns might already exist
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'] ?? '';
    $baslik_en = $_POST['baslik_en'] ?? '';
    $icerik = $_POST['icerik'] ?? '';
    $icerik_en = $_POST['icerik_en'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    $link = $_POST['link'] ?? '';
    $photo = null;
    
    // STEP 2: Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/duyurular/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $file = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'duyuru_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $photo = $filename;
            }
        }
    }
    
    $stmt = $pdo->prepare('INSERT INTO duyurular (baslik, baslik_en, icerik, icerik_en, kategori, tarih, link, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $ok = $stmt->execute([$baslik, $baslik_en, $icerik, $icerik_en, $kategori, $tarih, $link, $photo]);
    if ($ok) {
        // Queue notification for new announcement (only for new insertions, not updates)
        $announcement_id = $pdo->lastInsertId();
        $announcement_title = $baslik;
        // Construct base URL
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base_path = dirname(dirname($_SERVER['PHP_SELF']));
        $announcement_url = $protocol . '://' . $host . $base_path . '/duyurular.php';
        queueAnnouncementNotification($pdo, $announcement_title, $announcement_url);
        $msg = 'Duyuru başarıyla eklendi!';
    } else {
        $msg = 'Hata oluştu!';
    }
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Duyuru Ekle</h1>
      <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
        <!-- Language Tabs -->
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

        <!-- Tab Content -->
        <div class="tab-content mb-4" id="langTabContent">
            <!-- Turkish Tab -->
            <div class="tab-pane fade show active" id="tr-content" role="tabpanel" aria-labelledby="tr-tab">
                <div class="form-group mb-3">
                    <label>Başlık (Türkçe) *</label>
                    <input type="text" name="baslik" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>İçerik (Türkçe)</label>
                    <textarea name="icerik" rows="5" class="form-control"></textarea>
                </div>
            </div>

            <!-- English Tab -->
            <div class="tab-pane fade" id="en-content" role="tabpanel" aria-labelledby="en-tab">
                <div class="form-group mb-3">
                    <label>Title (English)</label>
                    <input type="text" name="baslik_en" class="form-control" placeholder="Optional: English title">
                </div>
                <div class="form-group mb-3">
                    <label>Content (English)</label>
                    <textarea name="icerik_en" rows="5" class="form-control" placeholder="Optional: English content"></textarea>
                </div>
            </div>
        </div>
        <div class="form-group mb-3">
          <label>Kategori</label>
          <select name="kategori" class="form-control" required>
            <option value="Genel">Genel</option>
            <option value="Önemli">Önemli</option>
            <option value="Workshop">Workshop</option>
            <option value="Etkinlik">Etkinlik</option>
          </select>
        </div>
        <div class="form-group mb-3">
          <label>Tarih</label>
          <input type="date" name="tarih" class="form-control" required>
        </div>
        <div class="form-group mb-3">
          <label>Link (isteğe bağlı)</label>
          <input type="text" name="link" class="form-control">
        </div>
        <div class="form-group mb-4">
          <label>Fotoğraf (isteğe bağlı)</label>
          <input type="file" name="photo" accept="image/*" class="form-control">
          <small class="text-muted">Desteklenen formatlar: JPEG, PNG, GIF</small>
        </div>
        <button class="btn btn-primary px-5" type="submit">Kaydet</button>
      </form>
    </div>
  </div>
</main>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
.admin-form-container {
    max-width: 700px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(44,62,80,0.08);
    padding: 32px 32px 24px 32px;
    margin: 40px 0 40px 0;
}
.form-group {margin-bottom:1rem;}
label {font-weight:600;}
input, textarea, select {width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:6px;background:#f8fafc;font-size:1rem;margin-bottom:8px;}
input:focus, textarea:focus, select:focus {outline:none;border-color:#3498db;background:#fff;}
.btn {padding:10px 28px;background:#3498db;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:1rem;transition:background 0.2s;}
.btn:hover {background:#217dbb;}
.msg {margin-bottom:1rem; color:green;}
@media (max-width: 768px) {
    .admin-form-container {
        padding: 18px 4vw 18px 4vw;
        max-width: 99vw;
    }
}
</style>
</body>
</html>
