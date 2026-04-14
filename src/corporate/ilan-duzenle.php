<?php
// Corporate İlan Düzenle - Sadece kendi ilanlarını düzenleyebilir
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

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ilanlar-yonetim.php');
    exit;
}

// First try to get from requests table
$stmt = $pdo->prepare('SELECT * FROM corporate_ilan_requests WHERE id = ? AND corporate_user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$ilan = $stmt->fetch();

// If not found in requests, try ilanlar table (for approved announcements)
if (!$ilan) {
    $stmt = $pdo->prepare('SELECT * FROM ilanlar WHERE id = ? AND corporate_user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    $ilan = $stmt->fetch();
    
    if ($ilan) {
        // This is an approved announcement - editing creates a new request
        $is_approved = true;
    }
}

if (!$ilan) {
    header('Location: ilanlar-yonetim.php');
    exit;
}

// Verify it's a staj or burs announcement
if (!in_array($ilan['kategori'], ['Staj İlanları', 'Burs İlanları'])) {
    header('Location: ilanlar-yonetim.php');
    exit;
}

// If it's an approved announcement, we can't edit directly - need to create new request
// For now, we'll disable editing of approved announcements
$is_approved_announcement = isset($is_approved) && $is_approved;

$msg = '';
$error = '';

// If editing an approved announcement or non-pending request, show message
if ($is_approved_announcement) {
    $msg = 'Bu ilan onaylanmış ve yayında. Değişiklik yapmak için yeni bir ilan oluşturun.';
} elseif (isset($ilan['status']) && $ilan['status'] !== 'pending') {
    $msg = 'Bu ilan ' . ($ilan['status'] === 'rejected' ? 'reddedilmiştir' : 'onaylanmıştır') . '. Sadece bekleyen ilanlar düzenlenebilir.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_approved_announcement && (isset($ilan['status']) && $ilan['status'] === 'pending')) {
    try {
        $baslik = trim($_POST['baslik'] ?? '');
        $icerik = trim($_POST['icerik'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $tarih = $_POST['tarih'] ?? '';
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
        } elseif (!in_array($kategori, ['Staj İlanları', 'Burs İlanları'])) {
            $error = 'Sadece Staj İlanları ve Burs İlanları düzenleyebilirsiniz!';
        } elseif (empty($tarih)) {
            $error = 'Tarih alanı zorunludur!';
        } else {
            // Update request (only if it's still pending)
            if (isset($ilan['status']) && $ilan['status'] === 'pending') {
                $updateColumns = ['baslik = ?', 'icerik = ?', 'kategori = ?', 'tarih = ?', 'link = ?', 'sirket = ?', 'lokasyon = ?', 'son_basvuru = ?'];
                $updateValues = [$baslik, $icerik, $kategori, $tarih, $link ?: null, $sirket ?: null, $lokasyon ?: null, $son_basvuru ?: null];
                $updateValues[] = $id;
                $updateValues[] = $_SESSION['user_id'];
                
                $sql = 'UPDATE corporate_ilan_requests SET ' . implode(', ', $updateColumns) . ' WHERE id = ? AND corporate_user_id = ?';
                $stmt2 = $pdo->prepare($sql);
                $ok = $stmt2->execute($updateValues);
                
                if ($ok) {
                    $msg = 'İlan isteği güncellendi!';
                    // Refresh the request data
                    $stmt = $pdo->prepare('SELECT * FROM corporate_ilan_requests WHERE id = ? AND corporate_user_id = ?');
                    $stmt->execute([$id, $_SESSION['user_id']]);
                    $ilan = $stmt->fetch();
                } else {
                    $error = 'İlan isteği güncellenirken bir hata oluştu!';
                }
            } else {
                $error = 'Sadece bekleyen ilan istekleri düzenlenebilir!';
            }
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $msg = 'İlan isteği güncellendi!';
}
?>
<?php include 'corporate-header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include 'corporate-sidebar.php'; ?>
    <main class="main-content col-md-9 ml-sm-auto col-lg-10">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h3 mb-3 mb-md-0">İlan Düzenle</h1>
      </div>
      <?php if($msg): ?><div class="alert alert-<?= $is_approved_announcement ? 'info' : 'success' ?> alert-dismissible fade show" role="alert"><?= htmlspecialchars($msg) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <?php if(isset($ilan['status'])): ?>
        <div class="alert alert-<?= $ilan['status'] === 'pending' ? 'warning' : ($ilan['status'] === 'approved' ? 'success' : 'danger') ?>">
          Durum: <strong><?= $ilan['status'] === 'pending' ? 'Onay Bekliyor' : ($ilan['status'] === 'approved' ? 'Onaylandı' : 'Reddedildi') ?></strong>
          <?php if($ilan['status'] === 'rejected' && $ilan['admin_notes']): ?>
            <br>Red Nedeni: <?= htmlspecialchars($ilan['admin_notes']) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <form method="post" class="bg-white p-3 p-md-4 rounded shadow-sm" <?= ($is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending')) ? 'onsubmit="return false;"' : '' ?>>
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Başlık <span class="text-danger">*</span></label>
              <input type="text" name="baslik" class="form-control" value="<?= htmlspecialchars($ilan['baslik'] ?? '') ?>" required <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'readonly' : '' ?>>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Kategori <span class="text-danger">*</span></label>
              <select name="kategori" class="form-control form-control-lg" required <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'disabled' : '' ?>>
                <option value="">Seçiniz...</option>
                <option value="Staj İlanları"<?= (isset($ilan['kategori']) && $ilan['kategori']=='Staj İlanları')?' selected':'' ?>>Staj İlanları</option>
                <option value="Burs İlanları"<?= (isset($ilan['kategori']) && $ilan['kategori']=='Burs İlanları')?' selected':'' ?>>Burs İlanları</option>
              </select>
              <?php if($is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending')): ?>
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($ilan['kategori'] ?? '') ?>">
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Şirket/Kurum Adı</label>
              <input type="text" name="sirket" class="form-control form-control-lg" placeholder="Örn: <?= htmlspecialchars($_SESSION['user_name'] ?? 'ABC Teknoloji A.Ş.') ?>" value="<?= htmlspecialchars($ilan['sirket'] ?? $_SESSION['user_name'] ?? '') ?>" <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'readonly' : '' ?>>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-group mb-3">
              <label>Lokasyon</label>
              <input type="text" name="lokasyon" class="form-control form-control-lg" placeholder="Örn: İstanbul, Ankara" value="<?= htmlspecialchars($ilan['lokasyon'] ?? '') ?>" <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'readonly' : '' ?>>
            </div>
          </div>
        </div>
        <div class="form-group mb-3">
          <label>İçerik <span class="text-danger">*</span></label>
          <textarea name="icerik" rows="8" class="form-control form-control-lg" required placeholder="İlan detaylarını buraya yazın..." <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'readonly' : '' ?>><?= htmlspecialchars($ilan['icerik'] ?? '') ?></textarea>
        </div>
        <div class="row">
          <div class="col-12 col-md-4 mb-3">
            <div class="form-group mb-3">
              <label>İlan Tarihi <span class="text-danger">*</span></label>
              <input type="date" name="tarih" class="form-control form-control-lg" value="<?= htmlspecialchars($ilan['tarih'] ?? '') ?>" required <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'readonly' : '' ?>>
            </div>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <div class="form-group mb-3">
              <label>Son Başvuru Tarihi</label>
              <input type="date" name="son_basvuru" class="form-control form-control-lg" value="<?= htmlspecialchars($ilan['son_basvuru'] ?? '') ?>" <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'readonly' : '' ?>>
            </div>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <div class="form-group mb-3">
              <label>Başvuru Linki</label>
              <input type="url" name="link" class="form-control form-control-lg" placeholder="https://..." value="<?= htmlspecialchars($ilan['link'] ?? '') ?>" <?= $is_approved_announcement || (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'readonly' : '' ?>>
            </div>
          </div>
        </div>
        <?php if($is_approved_announcement): ?>
          <div class="alert alert-info w-100 mb-3">
            <i class="fas fa-info-circle mr-2"></i>Bu ilan onaylanmış ve yayında. Değişiklik yapmak için yeni bir ilan oluşturun.
          </div>
        <?php elseif(isset($ilan['status']) && $ilan['status'] !== 'pending'): ?>
          <div class="alert alert-<?= $ilan['status'] === 'rejected' ? 'danger' : 'info' ?> w-100 mb-3">
            <i class="fas fa-info-circle mr-2"></i>Bu ilan <?= $ilan['status'] === 'rejected' ? 'reddedilmiştir' : 'onaylanmıştır' ?>. Sadece bekleyen ilanlar düzenlenebilir.
          </div>
        <?php endif; ?>
        <div class="form-group mb-4 d-flex flex-column flex-md-row gap-3 justify-content-center justify-content-md-start">
          <?php if($is_approved_announcement): ?>
            <a href="ilan-ekle.php" class="btn btn-primary btn-lg corporate-action-btn">
              <i class="fas fa-plus mr-2"></i>Yeni İlan Oluştur
            </a>
          <?php else: ?>
            <button class="btn btn-primary btn-lg corporate-action-btn" type="submit" <?= (isset($ilan['status']) && $ilan['status'] !== 'pending') ? 'disabled' : '' ?>>
              <i class="fas fa-save mr-2"></i>Kaydet
            </button>
          <?php endif; ?>
          <a href="ilanlar-yonetim.php" class="btn btn-secondary btn-lg corporate-action-btn">
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

/* Standardized Corporate Action Buttons */
.corporate-action-btn {
    min-width: 200px;
    min-height: 50px;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.corporate-action-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.corporate-action-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .admin-form-container {
        padding: 18px 4vw 18px 4vw;
        max-width: 99vw;
    }
    .corporate-action-btn {
        width: 100%;
        min-width: 100%;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

