<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// İlan Ekleme
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
require_once '../includes/email_queue_helper.php';

// Ensure ilanlar table exists with all required columns
try {
    // Check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'ilanlar'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create table with all columns
        $pdo->exec('CREATE TABLE ilanlar (
            id INT AUTO_INCREMENT PRIMARY KEY,
            baslik VARCHAR(255) NOT NULL,
            icerik TEXT NOT NULL,
            kategori VARCHAR(100) NOT NULL,
            tarih DATE NOT NULL,
            link VARCHAR(500),
            sirket VARCHAR(255),
            lokasyon VARCHAR(255),
            son_basvuru DATE,
            tip VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } else {
        // Table exists, check and add missing columns
        $columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);
        
        // Required columns that must exist - handle NOT NULL columns separately
        $requiredColumns = [
            'baslik' => ['type' => 'VARCHAR(255)', 'nullable' => false],
            'icerik' => ['type' => 'TEXT', 'nullable' => false],
            'kategori' => ['type' => 'VARCHAR(100)', 'nullable' => false],
            'baslik_en' => ['type' => 'VARCHAR(255)', 'nullable' => true],
            'icerik_en' => ['type' => 'TEXT', 'nullable' => true],
            'nitelikler_en' => ['type' => 'TEXT', 'nullable' => true],
            'tarih' => ['type' => 'DATE', 'nullable' => false],
            'link' => ['type' => 'VARCHAR(500)', 'nullable' => true],
            'sirket' => ['type' => 'VARCHAR(255)', 'nullable' => true],
            'lokasyon' => ['type' => 'VARCHAR(255)', 'nullable' => true],
            'son_basvuru' => ['type' => 'DATE', 'nullable' => true],
            'tip' => ['type' => 'VARCHAR(50)', 'nullable' => true],
            'created_at' => ['type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 'nullable' => true],
            'updated_at' => ['type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'nullable' => true]
        ];
        
        // Check if table has any data
        $hasData = $pdo->query("SELECT COUNT(*) FROM ilanlar")->fetchColumn() > 0;
        
        foreach ($requiredColumns as $colName => $colInfo) {
            if (!in_array($colName, $columns)) {
                try {
                    $colDef = $colInfo['type'];
                    // For NOT NULL columns in existing table with data, add as nullable first or with default
                    if (!$colInfo['nullable'] && $hasData) {
                        // Add as nullable first, then we can update values and make it NOT NULL later if needed
                        $pdo->exec("ALTER TABLE ilanlar ADD COLUMN {$colName} {$colDef} NULL");
                        // Set default values for existing rows
                        if ($colName === 'kategori') {
                            $pdo->exec("UPDATE ilanlar SET kategori = 'Bireysel İlanlar' WHERE kategori IS NULL");
                        } elseif ($colName === 'tarih') {
                            $pdo->exec("UPDATE ilanlar SET tarih = CURDATE() WHERE tarih IS NULL");
                        }
                    } else {
                        $nullable = $colInfo['nullable'] ? 'NULL' : 'NOT NULL';
                        $pdo->exec("ALTER TABLE ilanlar ADD COLUMN {$colName} {$colDef} {$nullable}");
                    }
                } catch (PDOException $e) {
                    // Column might already exist or there's a syntax issue, continue
                    // Try alternative approach - just add as nullable
                    if (strpos($e->getMessage(), 'Duplicate column') === false) {
                        try {
                            $pdo->exec("ALTER TABLE ilanlar ADD COLUMN {$colName} {$colInfo['type']} NULL");
                        } catch (PDOException $e2) {
                            // Skip if still fails
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    // Table creation/alteration failed, but continue - might work if table is correct
}

$msg = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $baslik = trim($_POST['baslik'] ?? '');
        $baslik_en = trim($_POST['baslik_en'] ?? '');
        $icerik = trim($_POST['icerik'] ?? '');
        $icerik_en = trim($_POST['icerik_en'] ?? '');
        $nitelikler_en = trim($_POST['nitelikler_en'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $tarih = $_POST['tarih'] ?? date('Y-m-d');
        $link = trim($_POST['link'] ?? '');
        $sirket = trim($_POST['sirket'] ?? '');
        $lokasyon = trim($_POST['lokasyon'] ?? '');
        $son_basvuru = $_POST['son_basvuru'] ?? null;
        // HARDCODE tip to 'admin' for admin-created posts
        $tip = 'admin';
        
        // Validation
        if (empty($baslik)) {
            $error = 'Başlık alanı zorunludur!';
        } elseif (empty($icerik)) {
            $error = 'İçerik alanı zorunludur!';
        } elseif (empty($kategori)) {
            $error = 'Kategori seçimi zorunludur!';
        } elseif (empty($tarih)) {
            $error = 'Tarih alanı zorunludur!';
        } else {
            // Get current columns to build dynamic INSERT
            $columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);
            
            // Build column list and values based on what exists
            $insertColumns = [];
            $insertValues = [];
            $placeholders = [];
            
            // Always include required columns
            if (in_array('baslik', $columns)) {
                $insertColumns[] = 'baslik';
                $insertValues[] = $baslik;
                $placeholders[] = '?';
            }
            if (in_array('icerik', $columns)) {
                $insertColumns[] = 'icerik';
                $insertValues[] = $icerik;
                $placeholders[] = '?';
            }
            if (in_array('baslik_en', $columns)) {
                $insertColumns[] = 'baslik_en';
                $insertValues[] = $baslik_en ?? null;
                $placeholders[] = '?';
            }
            if (in_array('icerik_en', $columns)) {
                $insertColumns[] = 'icerik_en';
                $insertValues[] = $icerik_en ?? null;
                $placeholders[] = '?';
            }
            if (in_array('nitelikler_en', $columns)) {
                $insertColumns[] = 'nitelikler_en';
                $insertValues[] = $nitelikler_en ?? null;
                $placeholders[] = '?';
            }
            if (in_array('kategori', $columns)) {
                $insertColumns[] = 'kategori';
                $insertValues[] = $kategori;
                $placeholders[] = '?';
            }
            if (in_array('tarih', $columns)) {
                $insertColumns[] = 'tarih';
                $insertValues[] = $tarih;
                $placeholders[] = '?';
            }
            if (in_array('link', $columns)) {
                $insertColumns[] = 'link';
                $insertValues[] = $link ?: null;
                $placeholders[] = '?';
            }
            if (in_array('sirket', $columns)) {
                $insertColumns[] = 'sirket';
                $insertValues[] = $sirket ?: null;
                $placeholders[] = '?';
            }
            if (in_array('lokasyon', $columns)) {
                $insertColumns[] = 'lokasyon';
                $insertValues[] = $lokasyon ?: null;
                $placeholders[] = '?';
            }
            if (in_array('son_basvuru', $columns)) {
                $insertColumns[] = 'son_basvuru';
                $insertValues[] = $son_basvuru ?: null;
                $placeholders[] = '?';
            }
            // Always include tip column (hardcoded to 'admin')
            if (in_array('tip', $columns)) {
                $insertColumns[] = 'tip';
                $insertValues[] = $tip;
                $placeholders[] = '?';
            }
            // Ensure sirket is included (already handled above, but ensure it's there)
            // sirket is already added above if column exists
            
            if (empty($insertColumns)) {
                $error = 'Tablo yapısı hatası! Lütfen yöneticiye bildirin.';
            } else {
                $sql = 'INSERT INTO ilanlar (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute($insertValues);
                
                if ($ok) {
                    $success = true;
                    
                    // Queue Email Notification
                    if (function_exists('queueNewListingNotification')) {
                        $public_url = 'https://aybuasec.org/ilanlar';
                        queueNewListingNotification($pdo, $baslik, $public_url);
                    }
                    
                    // Redirect back to add page with success message
                    header('Location: ilan-ekle.php?success=1');
                    exit;
                } else {
                    $error = 'İlan eklenirken bir hata oluştu!';
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $msg = 'İlan başarıyla eklendi!';
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>İlan Ekle</h1>
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
                  <input type="text" name="baslik" class="form-control" required value="<?= htmlspecialchars($_POST['baslik'] ?? '') ?>">
                </div>
              </div>
            </div>
            <div class="form-group mb-3">
              <label>İçerik <span class="text-danger">*</span></label>
              <textarea name="icerik" rows="5" class="form-control" required placeholder="İlan detaylarını buraya yazın..."><?= htmlspecialchars($_POST['icerik'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="tab-pane fade" id="content-en" role="tabpanel" aria-labelledby="job-en-tab">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label>Başlık (EN)</label>
                  <input type="text" name="baslik_en" class="form-control" value="<?= htmlspecialchars($_POST['baslik_en'] ?? '') ?>">
                </div>
              </div>
            </div>
            <div class="form-group mb-3">
              <label>İçerik (EN)</label>
              <textarea name="icerik_en" rows="5" class="form-control" placeholder="Write job details in English..."><?= htmlspecialchars($_POST['icerik_en'] ?? '') ?></textarea>
            </div>
            <div class="form-group mb-3">
              <label>Nitelikler / Requirements (EN)</label>
              <textarea name="nitelikler_en" rows="4" class="form-control" placeholder="List requirements in English..."><?= htmlspecialchars($_POST['nitelikler_en'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>Şirket/Kurum Adı</label>
              <input type="text" name="sirket" class="form-control" placeholder="Örn: ABC Teknoloji A.Ş." value="<?= htmlspecialchars($_POST['sirket'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>Lokasyon</label>
              <input type="text" name="lokasyon" class="form-control" placeholder="Örn: İstanbul, Ankara" value="<?= htmlspecialchars($_POST['lokasyon'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>Kategori <span class="text-danger">*</span></label>
              <select name="kategori" class="form-control" required>
                <option value="">Seçiniz...</option>
                <option value="Staj İlanları" <?= (isset($_POST['kategori']) && $_POST['kategori'] == 'Staj İlanları') ? 'selected' : '' ?>>Staj İlanları</option>
                <option value="Burs İlanları" <?= (isset($_POST['kategori']) && $_POST['kategori'] == 'Burs İlanları') ? 'selected' : '' ?>>Burs İlanları</option>
                <option value="İş İlanı" <?= (isset($_POST['kategori']) && $_POST['kategori'] == 'İş İlanı') ? 'selected' : '' ?>>İş İlanı</option>
                <option value="Bireysel İlanlar" <?= (isset($_POST['kategori']) && $_POST['kategori'] == 'Bireysel İlanlar') ? 'selected' : '' ?>>Bireysel İlanlar</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label>İlan Tarihi <span class="text-danger">*</span></label>
              <input type="date" name="tarih" class="form-control" required value="<?= htmlspecialchars($_POST['tarih'] ?? date('Y-m-d')) ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label>Son Başvuru Tarihi</label>
              <input type="date" name="son_basvuru" class="form-control" value="<?= htmlspecialchars($_POST['son_basvuru'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label>Başvuru Linki</label>
              <input type="url" name="link" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($_POST['link'] ?? '') ?>">
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


