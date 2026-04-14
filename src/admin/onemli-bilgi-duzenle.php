<?php
// Önemli Bilgi Düzenle
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

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

// Ensure English columns exist
try {
    $columns = $pdo->query("SHOW COLUMNS FROM onemli_bilgiler LIKE 'baslik_en'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE onemli_bilgiler ADD COLUMN baslik_en VARCHAR(255) NULL AFTER baslik");
    }
    $columns = $pdo->query("SHOW COLUMNS FROM onemli_bilgiler LIKE 'aciklama_en'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE onemli_bilgiler ADD COLUMN aciklama_en TEXT NULL AFTER aciklama");
    }
    $columns = $pdo->query("SHOW COLUMNS FROM onemli_bilgiler LIKE 'icerik_en'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE onemli_bilgiler ADD COLUMN icerik_en LONGTEXT NULL AFTER icerik");
    }
} catch (Exception $e) {
    // Columns might already exist
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Geçersiz ID!');
$msg = '';
$stmt = $pdo->prepare('SELECT * FROM onemli_bilgiler WHERE id=?');
$stmt->execute([$id]);
$bilgi = $stmt->fetch();
if (!$bilgi) die('Bilgi bulunamadı!');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'] ?? '';
    $baslik_en = $_POST['baslik_en'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';
    $aciklama_en = $_POST['aciklama_en'] ?? '';
    $icerik = $_POST['icerik'] ?? '';
    $icerik_en = $_POST['icerik_en'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    
    $resim = $bilgi['resim']; // Keep existing image
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/onemli-bilgiler/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Handle new image upload
    if (isset($_FILES['resim']) && $_FILES['resim']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($resim && file_exists($uploadDir . $resim)) {
            unlink($uploadDir . $resim);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExt, $allowedExts)) {
            $resim = time() . '_' . basename($_FILES['resim']['name']);
            $targetFile = $uploadDir . $resim;
            move_uploaded_file($_FILES['resim']['tmp_name'], $targetFile);
        }
    }
    
    $stmt2 = $pdo->prepare('UPDATE onemli_bilgiler SET baslik=?, baslik_en=?, aciklama=?, aciklama_en=?, icerik=?, icerik_en=?, resim=?, tarih=? WHERE id=?');
    $ok = $stmt2->execute([$baslik, $baslik_en, $aciklama, $aciklama_en, $icerik, $icerik_en, $resim, $tarih, $id]);
    $msg = $ok ? 'Bilgi güncellendi!' : 'Hata oluştu!';
    if ($ok) {
        header('Location: onemli-bilgiler-yonetim.php');
        exit;
    }
    $stmt->execute([$id]);
    $bilgi = $stmt->fetch();
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Önemli Bilgi Düzenle</h1>
      <?php if($msg): ?><div class="alert alert-<?= strpos($msg, 'güncellendi') !== false ? 'success' : 'danger' ?>"><?= $msg ?></div><?php endif; ?>
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
                    <input type="text" name="baslik" class="form-control" value="<?= htmlspecialchars($bilgi['baslik']) ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label>Kısa Açıklama (Türkçe) *</label>
                    <textarea name="aciklama" rows="3" class="form-control" required><?= htmlspecialchars($bilgi['aciklama']) ?></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>İçerik (Detaylı - Türkçe) *</label>
                    <textarea name="icerik" rows="10" class="form-control" required><?= htmlspecialchars($bilgi['icerik']) ?></textarea>
                </div>
            </div>

            <!-- English Tab -->
            <div class="tab-pane fade" id="en-content" role="tabpanel" aria-labelledby="en-tab">
                <div class="form-group mb-3">
                    <label>Title (English)</label>
                    <input type="text" name="baslik_en" class="form-control" value="<?= htmlspecialchars($bilgi['baslik_en'] ?? '') ?>" placeholder="Optional: English title">
                </div>
                <div class="form-group mb-3">
                    <label>Short Description (English)</label>
                    <textarea name="aciklama_en" rows="3" class="form-control" placeholder="Optional: English short description"><?= htmlspecialchars($bilgi['aciklama_en'] ?? '') ?></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>Content (Detailed - English)</label>
                    <textarea name="icerik_en" rows="10" class="form-control" placeholder="Optional: English detailed content"><?= htmlspecialchars($bilgi['icerik_en'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="form-group mb-3">
          <label>Resim (Dikdörtgen header resmi)</label>
          <?php if(!empty($bilgi['resim'])): ?>
            <div class="mb-2">
              <img src="../uploads/onemli-bilgiler/<?= htmlspecialchars($bilgi['resim']) ?>" alt="Mevcut resim" style="max-width: 300px; max-height: 150px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
            </div>
          <?php endif; ?>
          <input type="file" name="resim" class="form-control" accept="image/*">
          <small class="form-text text-muted">Yeni resim yüklerseniz mevcut resim değiştirilir. Önerilen boyut: 800x400px</small>
        </div>
        <div class="form-group mb-3">
          <label>Tarih</label>
          <input type="date" name="tarih" class="form-control" value="<?= htmlspecialchars($bilgi['tarih']) ?>" required>
        </div>
        <button class="btn btn-primary px-5" type="submit">Kaydet</button>
        <a href="onemli-bilgiler-yonetim.php" class="btn btn-secondary px-5">İptal</a>
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
.btn {padding:10px 28px;background:#3498db;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:1rem;transition:background 0.2s;margin-right:10px;}
.btn:hover {background:#217dbb;}
.btn-secondary {background:#6c757d;}
.btn-secondary:hover {background:#5a6268;}
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

