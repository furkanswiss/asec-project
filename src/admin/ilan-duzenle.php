<?php
// İlan Düzenle
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

// Ensure ilanlar table has all required columns
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'ilanlar'")->rowCount() > 0;
    
    if ($tableExists) {
        $columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = [
            'baslik' => ['type' => 'VARCHAR(255)', 'nullable' => false],
            'icerik' => ['type' => 'TEXT', 'nullable' => false],
            'kategori' => ['type' => 'VARCHAR(100)', 'nullable' => false],
            'tarih' => ['type' => 'DATE', 'nullable' => false],
            'link' => ['type' => 'VARCHAR(500)', 'nullable' => true],
            'sirket' => ['type' => 'VARCHAR(255)', 'nullable' => true],
            'lokasyon' => ['type' => 'VARCHAR(255)', 'nullable' => true],
            'son_basvuru' => ['type' => 'DATE', 'nullable' => true],
            'baslik_en' => ['type' => 'VARCHAR(255)', 'nullable' => true],
            'icerik_en' => ['type' => 'TEXT', 'nullable' => true],
            'nitelikler_en' => ['type' => 'TEXT', 'nullable' => true],
        ];
        
        $hasData = $pdo->query("SELECT COUNT(*) FROM ilanlar")->fetchColumn() > 0;
        
        foreach ($requiredColumns as $colName => $colInfo) {
            if (!in_array($colName, $columns)) {
                try {
                    if (!$colInfo['nullable'] && $hasData) {
                        $pdo->exec("ALTER TABLE ilanlar ADD COLUMN {$colName} {$colInfo['type']} NULL");
                        if ($colName === 'kategori') {
                            $pdo->exec("UPDATE ilanlar SET kategori = 'Bireysel İlanlar' WHERE kategori IS NULL");
                        } elseif ($colName === 'tarih') {
                            $pdo->exec("UPDATE ilanlar SET tarih = CURDATE() WHERE tarih IS NULL");
                        }
                    } else {
                        $nullable = $colInfo['nullable'] ? 'NULL' : 'NOT NULL';
                        $pdo->exec("ALTER TABLE ilanlar ADD COLUMN {$colName} {$colInfo['type']} {$nullable}");
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') === false) {
                        try {
                            $pdo->exec("ALTER TABLE ilanlar ADD COLUMN {$colName} {$colInfo['type']} NULL");
                        } catch (PDOException $e2) {
                            // Skip
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    // Continue
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Geçersiz ID!');
$msg = '';
$error = '';
$stmt = $pdo->prepare('SELECT * FROM ilanlar WHERE id=?');
$stmt->execute([$id]);
$ilan = $stmt->fetch();
if (!$ilan) die('İlan bulunamadı!');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $baslik = trim($_POST['baslik'] ?? '');
        $baslik_en = trim($_POST['baslik_en'] ?? '');
        $icerik = trim($_POST['icerik'] ?? '');
        $icerik_en = trim($_POST['icerik_en'] ?? '');
        $nitelikler_en = trim($_POST['nitelikler_en'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $tarih = $_POST['tarih'] ?? '';
        $link = trim($_POST['link'] ?? '');
        $sirket = trim($_POST['sirket'] ?? '');
        $lokasyon = trim($_POST['lokasyon'] ?? '');
        $son_basvuru = $_POST['son_basvuru'] ?? null;
        
        // Get current columns to build dynamic UPDATE
        $columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);
        
        // Build SET clause based on what columns exist
        $updateColumns = [];
        $updateValues = [];
        
        if (in_array('baslik', $columns)) {
            $updateColumns[] = 'baslik = ?';
            $updateValues[] = $baslik;
        }
        if (in_array('icerik', $columns)) {
            $updateColumns[] = 'icerik = ?';
            $updateValues[] = $icerik;
        }
        if (in_array('baslik_en', $columns)) {
            $updateColumns[] = 'baslik_en = ?';
            $updateValues[] = $baslik_en ?: null;
        }
        if (in_array('icerik_en', $columns)) {
            $updateColumns[] = 'icerik_en = ?';
            $updateValues[] = $icerik_en ?: null;
        }
        if (in_array('nitelikler_en', $columns)) {
            $updateColumns[] = 'nitelikler_en = ?';
            $updateValues[] = $nitelikler_en ?: null;
        }
        if (in_array('kategori', $columns)) {
            $updateColumns[] = 'kategori = ?';
            $updateValues[] = $kategori;
        }
        // Locked fields: tarih, link, sirket, lokasyon, son_basvuru are intentionally excluded from updates
        
        if (empty($updateColumns)) {
            $error = 'Tablo yapısı hatası! Lütfen yöneticiye bildirin.';
        } else {
            $updateValues[] = $id; // Add ID for WHERE clause
            $sql = 'UPDATE ilanlar SET ' . implode(', ', $updateColumns) . ' WHERE id = ?';
            $stmt2 = $pdo->prepare($sql);
            $ok = $stmt2->execute($updateValues);
        }
        
        if ($ok) {
            $msg = 'İlan güncellendi!';
            header('Location: ilan-duzenle.php?id=' . $id . '&success=1');
            exit;
        } else {
            $error = 'İlan güncellenirken bir hata oluştu!';
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
    $stmt->execute([$id]);
    $ilan = $stmt->fetch();
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $msg = 'İlan güncellendi!';
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>İlan Düzenle</h1>
      <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($msg) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <form method="post" class="bg-white p-4 rounded shadow-sm">
        <!-- Language Tabs -->
        <ul class="nav nav-tabs mb-4" id="jobLangTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link active" id="job-tr-tab" data-toggle="tab" href="#content-tr" role="tab" aria-controls="content-tr" aria-selected="true">🇹🇷 Türkçe</a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link" id="job-en-tab" data-toggle="tab" href="#content-en" role="tab" aria-controls="content-en" aria-selected="false">🇬🇧 English</a>
          </li>
        </ul>

        <div class="tab-content mb-4" id="jobLangTabContent">
          <div class="tab-pane fade show active" id="content-tr" role="tabpanel" aria-labelledby="job-tr-tab">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label>Başlık <span class="text-danger">*</span></label>
                  <input type="text" name="baslik" class="form-control" value="<?= htmlspecialchars($ilan['baslik'] ?? '') ?>" required>
                </div>
              </div>
            </div>
            <div class="form-group mb-3">
              <label>İçerik <span class="text-danger">*</span></label>
              <textarea name="icerik" rows="5" class="form-control" required placeholder="İlan detaylarını buraya yazın..."><?= htmlspecialchars($ilan['icerik'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="tab-pane fade" id="content-en" role="tabpanel" aria-labelledby="job-en-tab">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label>Başlık (EN)</label>
                  <input type="text" name="baslik_en" class="form-control" value="<?= htmlspecialchars($ilan['baslik_en'] ?? '') ?>">
                </div>
              </div>
            </div>
            <div class="form-group mb-3">
              <label>İçerik (EN)</label>
              <textarea name="icerik_en" rows="5" class="form-control" placeholder="Write job details in English..."><?= htmlspecialchars($ilan['icerik_en'] ?? '') ?></textarea>
            </div>
            <div class="form-group mb-3">
              <label>Nitelikler / Requirements (EN)</label>
              <textarea name="nitelikler_en" rows="4" class="form-control" placeholder="List requirements in English..."><?= htmlspecialchars($ilan['nitelikler_en'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>Şirket/Kurum Adı</label>
              <input type="text" name="sirket" class="form-control bg-light text-muted" placeholder="Örn: ABC Teknoloji A.Ş." value="<?= htmlspecialchars($ilan['sirket'] ?? '') ?>" readonly>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>Lokasyon</label>
              <input type="text" name="lokasyon" class="form-control bg-light text-muted" placeholder="Örn: İstanbul, Ankara" value="<?= htmlspecialchars($ilan['lokasyon'] ?? '') ?>" readonly>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>Kategori <span class="text-danger">*</span></label>
              <select name="kategori" class="form-control" required>
                <option value="">Seçiniz...</option>
                <option value="Staj İlanları"<?= (isset($ilan['kategori']) && $ilan['kategori']=='Staj İlanları')?' selected':'' ?>>Staj İlanları</option>
                <option value="Burs İlanları"<?= (isset($ilan['kategori']) && $ilan['kategori']=='Burs İlanları')?' selected':'' ?>>Burs İlanları</option>
                <option value="İş İlanı"<?= (isset($ilan['kategori']) && $ilan['kategori']=='İş İlanı')?' selected':'' ?>>İş İlanı</option>
                <option value="Bireysel İlanlar"<?= (isset($ilan['kategori']) && $ilan['kategori']=='Bireysel İlanlar')?' selected':'' ?>>Bireysel İlanlar</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label>İlan Tarihi <span class="text-danger">*</span></label>
              <input type="date" name="tarih" class="form-control bg-light text-muted" value="<?= htmlspecialchars($ilan['tarih'] ?? '') ?>" readonly>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label>Son Başvuru Tarihi</label>
              <input type="date" name="son_basvuru" class="form-control bg-light text-muted" value="<?= htmlspecialchars($ilan['son_basvuru'] ?? '') ?>" readonly>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label>Başvuru Linki</label>
              <input type="url" name="link" class="form-control bg-light text-muted" placeholder="https://..." value="<?= htmlspecialchars($ilan['link'] ?? '') ?>" readonly>
            </div>
          </div>
        </div>
        <div class="form-group mb-4">
          <button class="btn btn-primary px-5" type="submit"><i class="fas fa-save"></i> Kaydet</button>
          <a href="ilanlar-yonetim.php" class="btn btn-secondary px-4"><i class="fas fa-times"></i> İptal</a>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
  // Simple tab toggle for TR/EN blocks (Bootstrap tabs also work)
  document.addEventListener('DOMContentLoaded', function() {
    var trTab = document.getElementById('job-tr-tab');
    var enTab = document.getElementById('job-en-tab');
    var trContent = document.getElementById('content-tr');
    var enContent = document.getElementById('content-en');

    function showTR() {
      trTab.classList.add('active');
      enTab.classList.remove('active');
      trContent.classList.add('show', 'active');
      enContent.classList.remove('show', 'active');
    }

    function showEN() {
      enTab.classList.add('active');
      trTab.classList.remove('active');
      enContent.classList.add('show', 'active');
      trContent.classList.remove('show', 'active');
    }

    if (trTab && enTab && trContent && enContent) {
      trTab.addEventListener('click', function(e) {
        e.preventDefault();
        showTR();
      });
      enTab.addEventListener('click', function(e) {
        e.preventDefault();
        showEN();
      });
      showTR(); // default
    }
  });
</script>

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


