<?php
// Corporate İlan Ekleme - Staj, Burs ve İş İlanları
// Start output buffering to prevent any output issues with redirects
if (!ob_get_level()) {
    ob_start();
}
require_once 'includes/config.php';

// Ensure corporate_ilan_requests table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS corporate_ilan_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    corporate_user_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    icerik TEXT NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    tarih DATE NOT NULL,
    link VARCHAR(500),
    sirket VARCHAR(255),
    lokasyon VARCHAR(255),
    son_basvuru DATE,
    status ENUM("pending", "approved", "rejected") DEFAULT "pending",
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_corporate_user_id (corporate_user_id),
    INDEX idx_status (status),
    INDEX idx_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$msg = '';
$error = '';
$success = false;

// Get default category from URL parameter
$default_kategori = $_GET['kategori'] ?? '';
if (!in_array($default_kategori, ['Staj İlanları', 'Burs İlanları', 'İş İlanı'])) {
    $default_kategori = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $baslik = trim($_POST['baslik'] ?? '');
        $icerik = trim($_POST['icerik'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $tarih = $_POST['tarih'] ?? date('Y-m-d');
        $link = trim($_POST['link'] ?? '');
        $sirket = trim($_POST['sirket'] ?? '');
        $lokasyon = trim($_POST['lokasyon'] ?? '');
        $son_basvuru = $_POST['son_basvuru'] ?? null;
        
        // Validation
        if (empty($baslik)) {
            $error = 'Başlık alanı zorunludur!';
        } elseif (empty($icerik)) {
            $error = 'İçerik alanı zorunludur!';
        } elseif (empty($kategori)) {
            $error = 'Kategori seçimi zorunludur!';
        } elseif (!in_array($kategori, ['Staj İlanları', 'Burs İlanları', 'İş İlanı'])) {
            $error = 'Sadece Staj İlanları, Burs İlanları ve İş İlanı oluşturabilirsiniz!';
        } elseif (empty($tarih)) {
            $error = 'Tarih alanı zorunludur!';
        } else {
            // Create request instead of direct announcement
            $insertColumns = ['corporate_user_id', 'baslik', 'icerik', 'kategori', 'tarih', 'link', 'sirket', 'lokasyon', 'son_basvuru', 'status'];
            $insertValues = [$_SESSION['user_id'], $baslik, $icerik, $kategori, $tarih, $link ?: null, $sirket ?: null, $lokasyon ?: null, $son_basvuru ?: null, 'pending'];
            $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?'];
            
            $sql = 'INSERT INTO corporate_ilan_requests (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute($insertValues);
            
            if ($ok) {
                // Clean output buffer before redirect
                if (ob_get_level()) {
                    ob_end_clean();
                }
                // Redirect to listing management page
                header('Location: ilanlar-yonetim.php?kategori=' . urlencode($kategori));
                exit;
            } else {
                $error = 'İlan isteği oluşturulurken bir hata oluştu!';
            }
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Success message is no longer needed as we redirect directly to management page
?>
<?php include 'corporate-header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include 'corporate-sidebar.php'; ?>
    <main class="main-content col-md-9 ml-sm-auto col-lg-10">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h3 mb-3 mb-md-0">İlan Ekle</h1>
      </div>
      <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($msg) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <form method="post" class="bg-white p-3 p-md-4 rounded shadow-sm">
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Başlık <span class="text-danger">*</span></label>
              <input type="text" name="baslik" class="form-control" required value="<?= htmlspecialchars($_POST['baslik'] ?? '') ?>">
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Kategori <span class="text-danger">*</span></label>
              <select name="kategori" class="form-control form-control-lg" required>
                <option value="">Seçiniz...</option>
                <option value="Staj İlanları" <?= ((isset($_POST['kategori']) && $_POST['kategori'] == 'Staj İlanları') || $default_kategori == 'Staj İlanları') ? 'selected' : '' ?>>Staj İlanları</option>
                <option value="Burs İlanları" <?= ((isset($_POST['kategori']) && $_POST['kategori'] == 'Burs İlanları') || $default_kategori == 'Burs İlanları') ? 'selected' : '' ?>>Burs İlanları</option>
                <option value="İş İlanı" <?= ((isset($_POST['kategori']) && $_POST['kategori'] == 'İş İlanı') || $default_kategori == 'İş İlanı') ? 'selected' : '' ?>>İş İlanı</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Şirket/Kurum Adı</label>
              <input type="text" name="sirket" class="form-control form-control-lg" placeholder="Örn: <?= htmlspecialchars($_SESSION['user_name'] ?? 'ABC Teknoloji A.Ş.') ?>" value="<?= htmlspecialchars($_POST['sirket'] ?? $_SESSION['user_name'] ?? '') ?>">
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Lokasyon</label>
              <input type="text" name="lokasyon" class="form-control form-control-lg" placeholder="Örn: İstanbul, Ankara" value="<?= htmlspecialchars($_POST['lokasyon'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-group mb-3">
          <label>İçerik <span class="text-danger">*</span></label>
          <textarea name="icerik" rows="8" class="form-control form-control-lg" required placeholder="İlan detaylarını buraya yazın..."><?= htmlspecialchars($_POST['icerik'] ?? '') ?></textarea>
        </div>
        <div class="row">
          <div class="col-12 col-md-4 mb-3">
            <div class="form-group mb-3">
              <label>İlan Tarihi <span class="text-danger">*</span></label>
              <input type="date" name="tarih" class="form-control form-control-lg" required value="<?= htmlspecialchars($_POST['tarih'] ?? date('Y-m-d')) ?>">
            </div>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <div class="form-group mb-3">
              <label>Son Başvuru Tarihi</label>
              <input type="date" name="son_basvuru" class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['son_basvuru'] ?? '') ?>">
            </div>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <div class="form-group mb-3">
              <label>Başvuru Linki</label>
              <input type="url" name="link" class="form-control form-control-lg" placeholder="https://..." value="<?= htmlspecialchars($_POST['link'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-group mb-4 d-flex flex-column flex-md-row justify-content-center align-items-center" style="gap: 16px;">
          <button class="btn btn-primary btn-lg" type="submit" style="min-width: 180px; min-height: 54px;">
            <i class="fas fa-save mr-2"></i>Kaydet
          </button>
          <a href="ilanlar-yonetim.php" class="btn btn-secondary btn-lg" style="min-width: 180px; min-height: 54px;">
            <i class="fas fa-times mr-2"></i>İptal
          </a>
        </div>
      </form>
    </main>
  </div>
</div>

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
input:focus, textarea:focus, select:focus {outline:none;border-color:#9370db;background:#fff;}
.btn {padding:10px 28px;background:#9370db;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:1rem;transition:background 0.2s;}
.btn:hover {background:#7a5fb8;}
.msg {margin-bottom:1rem; color:green;}

/* Save and Cancel button styling */
.form-group.d-flex.flex-column.flex-md-row .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .admin-form-container {
        padding: 18px 4vw 18px 4vw;
        max-width: 99vw;
    }
    
    .form-group.d-flex.flex-column .btn {
        width: 100%;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

