<?php
// Admin Sponsorlar Yönetim Paneli
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

// Database Automation: Create sponsors table if not exists
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS sponsors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firma_adi VARCHAR(255) NOT NULL,
        logo_yolu VARCHAR(255),
        aciklama_tr TEXT,
        aciklama_en TEXT,
        web_site VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
} catch (PDOException $e) {
    // Table might already exist, continue
}

// Database Migration: Add kategori column if it doesn't exist
try {
    $columns = $pdo->query("SHOW COLUMNS FROM sponsors LIKE 'kategori'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE sponsors ADD COLUMN kategori ENUM('surekli', 'etkinlik') DEFAULT 'etkinlik'");
    }
} catch (PDOException $e) {
    // Column might already exist, continue
}

$msg = '';
$error = '';
$success = false;

// Handle POST requests (Add/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $firma_adi = trim($_POST['firma_adi'] ?? '');
        $aciklama_tr = trim($_POST['aciklama_tr'] ?? '');
        $aciklama_en = trim($_POST['aciklama_en'] ?? '');
        $web_site = trim($_POST['web_site'] ?? '');
        $kategori = isset($_POST['kategori']) && in_array($_POST['kategori'], ['surekli', 'etkinlik']) ? $_POST['kategori'] : 'etkinlik';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Validation
        if (empty($firma_adi)) {
            $error = 'Firma adı zorunludur!';
        } else {
            // Handle logo upload
            $logo_yolu = null;
            
            // If updating, keep existing logo by default
            if ($id > 0) {
                $stmt_check = $pdo->prepare('SELECT logo_yolu FROM sponsors WHERE id=?');
                $stmt_check->execute([$id]);
                $existing = $stmt_check->fetch();
                $logo_yolu = $existing['logo_yolu'] ?? null;
            }
            
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/sponsors/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = $_FILES['logo']['name'];
                $file_tmp = $_FILES['logo']['tmp_name'];
                $file_size = $_FILES['logo']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                
                if (!in_array($file_ext, $allowed_exts)) {
                    $error = 'Sadece JPG, JPEG, PNG, GIF, WEBP ve SVG dosyalarına izin verilmektedir.';
                } elseif ($file_size > 5242880) { // 5MB max
                    $error = 'Dosya boyutu 5MB\'dan küçük olmalıdır.';
                } else {
                    // Delete old logo if updating
                    if ($id > 0 && !empty($logo_yolu) && file_exists(__DIR__ . '/../' . $logo_yolu)) {
                        @unlink(__DIR__ . '/../' . $logo_yolu);
                    }
                    
                    $new_file_name = uniqid('sponsor_', true) . '.' . $file_ext;
                    $target_path = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $target_path)) {
                        $logo_yolu = 'uploads/sponsors/' . $new_file_name;
                    } else {
                        $error = 'Dosya yüklenirken bir hata oluştu.';
                    }
                }
            }
            
            // Handle delete logo checkbox
            if (isset($_POST['delete_logo']) && $_POST['delete_logo'] == '1') {
                if (!empty($logo_yolu) && file_exists(__DIR__ . '/../' . $logo_yolu)) {
                    @unlink(__DIR__ . '/../' . $logo_yolu);
                }
                $logo_yolu = null;
            }
            
            // Validate website URL if provided
            if (!empty($web_site) && !filter_var($web_site, FILTER_VALIDATE_URL)) {
                $error = 'Geçerli bir web sitesi URL\'si giriniz.';
            } elseif (empty($error)) {
                if ($id > 0) {
                    // Update existing sponsor
                    $stmt = $pdo->prepare('UPDATE sponsors SET firma_adi=?, logo_yolu=?, aciklama_tr=?, aciklama_en=?, web_site=?, kategori=? WHERE id=?');
                    $ok = $stmt->execute([$firma_adi, $logo_yolu, $aciklama_tr ?: null, $aciklama_en ?: null, $web_site ?: null, $kategori, $id]);
                    
                    if ($ok) {
                        $success = true;
                        header('Location: sponsor-yonetim.php?success=1');
                        exit;
                    } else {
                        $error = 'Sponsor güncellenirken bir hata oluştu!';
                    }
                } else {
                    // Insert new sponsor
                    $stmt = $pdo->prepare('INSERT INTO sponsors (firma_adi, logo_yolu, aciklama_tr, aciklama_en, web_site, kategori) VALUES (?, ?, ?, ?, ?, ?)');
                    $ok = $stmt->execute([$firma_adi, $logo_yolu, $aciklama_tr ?: null, $aciklama_en ?: null, $web_site ?: null, $kategori]);
                    
                    if ($ok) {
                        $success = true;
                        header('Location: sponsor-yonetim.php?success=1');
                        exit;
                    } else {
                        $error = 'Sponsor eklenirken bir hata oluştu!';
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Handle delete action
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $delete_id = intval($_GET['delete']);
    try {
        // Get sponsor data to delete logo
        $stmt = $pdo->prepare('SELECT logo_yolu FROM sponsors WHERE id=?');
        $stmt->execute([$delete_id]);
        $sponsor = $stmt->fetch();
        
        if ($sponsor) {
            // Delete logo file if exists
            if (!empty($sponsor['logo_yolu']) && file_exists(__DIR__ . '/../' . $sponsor['logo_yolu'])) {
                @unlink(__DIR__ . '/../' . $sponsor['logo_yolu']);
            }
            
            // Delete from database
            $pdo->prepare('DELETE FROM sponsors WHERE id=?')->execute([$delete_id]);
            header('Location: sponsor-yonetim.php?success=1');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage();
    }
}

// Check for success message
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $msg = 'İşlem başarıyla tamamlandı!';
}

// Get sponsor for editing if ID is provided
$edit_sponsor = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM sponsors WHERE id=?');
    $stmt->execute([$edit_id]);
    $edit_sponsor = $stmt->fetch();
}

// Fetch all sponsors
$sponsors = $pdo->query('SELECT * FROM sponsors ORDER BY created_at DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Sponsorlar Yönetimi</h1>
      <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($msg) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      
      <!-- Add/Edit Form -->
      <div class="card mb-4">
        <div class="card-header">
          <h5><?= $edit_sponsor ? 'Sponsor Düzenle' : 'Yeni Sponsor Ekle' ?></h5>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data">
            <?php if($edit_sponsor): ?>
              <input type="hidden" name="id" value="<?= $edit_sponsor['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group mb-3">
              <label>Firma Adı <span class="text-danger">*</span></label>
              <input type="text" name="firma_adi" class="form-control" required value="<?= htmlspecialchars($edit_sponsor['firma_adi'] ?? '') ?>">
            </div>
            
            <div class="form-group mb-3">
              <label>Kategori <span class="text-danger">*</span></label>
              <select name="kategori" class="form-control" required>
                <option value="surekli" <?= (isset($edit_sponsor['kategori']) && $edit_sponsor['kategori'] == 'surekli') ? 'selected' : '' ?>>Sürekli Sponsor (Ana Sponsor)</option>
                <option value="etkinlik" <?= (!isset($edit_sponsor['kategori']) || $edit_sponsor['kategori'] == 'etkinlik') ? 'selected' : '' ?>>Etkinlik Sponsoru</option>
              </select>
            </div>
            
            <div class="form-group mb-3">
              <label>Web Sitesi</label>
              <input type="url" name="web_site" class="form-control" placeholder="https://example.com" value="<?= htmlspecialchars($edit_sponsor['web_site'] ?? '') ?>">
            </div>
            
            <div class="form-group mb-3">
              <label>Açıklama (Türkçe)</label>
              <textarea name="aciklama_tr" class="form-control" rows="4" placeholder="Sponsor hakkında Türkçe açıklama..."><?= htmlspecialchars($edit_sponsor['aciklama_tr'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group mb-3">
              <label>Açıklama (English)</label>
              <textarea name="aciklama_en" class="form-control" rows="4" placeholder="Sponsor description in English..."><?= htmlspecialchars($edit_sponsor['aciklama_en'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group mb-3">
              <label>Logo</label>
              <?php if($edit_sponsor && !empty($edit_sponsor['logo_yolu'])): ?>
                <div class="mb-2">
                  <img src="../<?= htmlspecialchars($edit_sponsor['logo_yolu']) ?>" alt="Current Logo" style="max-width: 200px; max-height: 100px; object-fit: contain; border: 1px solid #ddd; padding: 5px;">
                </div>
                <div class="form-check mb-2">
                  <input type="checkbox" class="form-check-input" name="delete_logo" value="1" id="deleteLogo">
                  <label class="form-check-label" for="deleteLogo">Mevcut logoyu sil</label>
                </div>
              <?php endif; ?>
              <input type="file" name="logo" accept="image/*" class="form-control-file">
              <small class="form-text text-muted">JPG, PNG, GIF, WEBP veya SVG formatında. Maksimum 5MB.</small>
            </div>
            
            <div class="form-group mb-4">
              <button class="btn btn-primary px-5" type="submit"><i class="fas fa-save"></i> <?= $edit_sponsor ? 'Güncelle' : 'Kaydet' ?></button>
              <?php if($edit_sponsor): ?>
                <a href="sponsor-yonetim.php" class="btn btn-secondary px-4"><i class="fas fa-times"></i> İptal</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Sponsors List -->
      <div class="card">
        <div class="card-header">
          <h5>Sponsor Listesi</h5>
        </div>
        <div class="card-body">
          <?php if(empty($sponsors)): ?>
            <p class="text-muted">Henüz sponsor eklenmemiş.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped admin-table">
                <thead class="thead-dark">
                  <tr>
                    <th>ID</th>
                    <th>Logo</th>
                    <th>Firma Adı</th>
                    <th>Kategori</th>
                    <th>Web Sitesi</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>İşlem</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($sponsors as $sponsor): ?>
                    <tr>
                      <td><?= htmlspecialchars($sponsor['id']) ?></td>
                      <td>
                        <?php if(!empty($sponsor['logo_yolu'])): ?>
                          <img src="../<?= htmlspecialchars($sponsor['logo_yolu']) ?>" alt="Logo" style="max-width: 80px; max-height: 50px; object-fit: contain;">
                        <?php else: ?>
                          <span class="text-muted">Logo yok</span>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($sponsor['firma_adi']) ?></td>
                      <td>
                        <?php 
                        $kategori_label = (isset($sponsor['kategori']) && $sponsor['kategori'] == 'surekli') ? 'Sürekli' : 'Etkinlik';
                        $kategori_badge = (isset($sponsor['kategori']) && $sponsor['kategori'] == 'surekli') ? 'badge-primary' : 'badge-info';
                        ?>
                        <span class="badge <?= $kategori_badge ?>"><?= htmlspecialchars($kategori_label) ?></span>
                      </td>
                      <td>
                        <?php if(!empty($sponsor['web_site'])): ?>
                          <a href="<?= htmlspecialchars($sponsor['web_site']) ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-external-link-alt"></i> Web Sitesi
                          </a>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($sponsor['created_at']) ?></td>
                      <td>
                        <a href="sponsor-yonetim.php?edit=<?= $sponsor['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Düzenle</a>
                        <a href="sponsor-yonetim.php?delete=<?= $sponsor['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu sponsoru silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
</body>
</html>

