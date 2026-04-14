<?php
// Board Member Düzenle
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

// Ensure board_members table exists
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'board_members'")->rowCount() > 0;
    
    if (!$tableExists) {
        $pdo->exec('CREATE TABLE board_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            position VARCHAR(255) NOT NULL,
            profileImage VARCHAR(500),
            linkedinUrl VARCHAR(500),
            githubUrl VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
} catch (PDOException $e) {
    // Table creation failed, continue
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Geçersiz ID!');
$msg = '';
$error = '';
$stmt = $pdo->prepare('SELECT * FROM board_members WHERE id=?');
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) die('Üye bulunamadı!');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $linkedinUrl = trim($_POST['linkedinUrl'] ?? '');
        $githubUrl = trim($_POST['githubUrl'] ?? '');
        
        // Validation
        if (empty($name)) {
            $error = 'İsim alanı zorunludur!';
        } elseif (empty($position)) {
            $error = 'Pozisyon alanı zorunludur!';
        } else {
            // Handle image upload
            $profileImage = $member['profileImage']; // Keep existing image by default
            
            if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/board/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = $_FILES['profileImage']['name'];
                $file_tmp = $_FILES['profileImage']['tmp_name'];
                $file_size = $_FILES['profileImage']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($file_ext, $allowed_exts)) {
                    $error = 'Sadece JPG, JPEG, PNG, GIF ve WEBP dosyalarına izin verilmektedir.';
                } elseif ($file_size > 5242880) { // 5MB max
                    $error = 'Dosya boyutu 5MB\'dan küçük olmalıdır.';
                } else {
                    // Delete old image if exists
                    if (!empty($member['profileImage']) && file_exists(__DIR__ . '/../' . $member['profileImage'])) {
                        @unlink(__DIR__ . '/../' . $member['profileImage']);
                    }
                    
                    $new_file_name = uniqid('board_', true) . '.' . $file_ext;
                    $target_path = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $target_path)) {
                        $profileImage = 'uploads/board/' . $new_file_name;
                    } else {
                        $error = 'Dosya yüklenirken bir hata oluştu.';
                    }
                }
            }
            
            // Handle delete image checkbox
            if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                if (!empty($member['profileImage']) && file_exists(__DIR__ . '/../' . $member['profileImage'])) {
                    @unlink(__DIR__ . '/../' . $member['profileImage']);
                }
                $profileImage = null;
            }
            
            // Validate URLs if provided
            if (!empty($linkedinUrl) && !filter_var($linkedinUrl, FILTER_VALIDATE_URL)) {
                $error = 'Geçerli bir LinkedIn URL\'si giriniz.';
            } elseif (!empty($githubUrl) && !filter_var($githubUrl, FILTER_VALIDATE_URL)) {
                $error = 'Geçerli bir GitHub URL\'si giriniz.';
            } elseif (empty($error)) {
                // Update database
                $stmt2 = $pdo->prepare('UPDATE board_members SET name=?, position=?, profileImage=?, linkedinUrl=?, githubUrl=? WHERE id=?');
                $ok = $stmt2->execute([$name, $position, $profileImage, $linkedinUrl ?: null, $githubUrl ?: null, $id]);
                
                if ($ok) {
                    $msg = 'Üye güncellendi!';
                    // Refresh member data
                    $stmt->execute([$id]);
                    $member = $stmt->fetch();
                    header('Location: board-duzenle.php?id=' . $id . '&success=1');
                    exit;
                } else {
                    $error = 'Üye güncellenirken bir hata oluştu!';
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
    // Refresh member data after update attempt
    $stmt->execute([$id]);
    $member = $stmt->fetch();
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $msg = 'Üye güncellendi!';
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Yönetim Kurulu Üyesi Düzenle</h1>
      <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($msg) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>İsim <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($member['name'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>Pozisyon <span class="text-danger">*</span></label>
              <input type="text" name="position" class="form-control" required placeholder="Örn: Başkan, Başkan Yardımcısı" value="<?= htmlspecialchars($member['position'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-group mb-3">
          <label>Profil Fotoğrafı</label>
          <?php if(!empty($member['profileImage'])): ?>
            <div class="mb-2">
              <img src="../<?= htmlspecialchars($member['profileImage']) ?>" alt="Current Profile" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd;">
            </div>
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" name="delete_image" value="1" id="deleteImage">
              <label class="form-check-label" for="deleteImage">Mevcut fotoğrafı sil</label>
            </div>
          <?php endif; ?>
          <input type="file" name="profileImage" accept="image/*" class="form-control-file">
          <small class="form-text text-muted">JPG, PNG, GIF veya WEBP formatında. Maksimum 5MB. Yeni fotoğraf yüklemek için seçin.</small>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>LinkedIn URL</label>
              <input type="url" name="linkedinUrl" class="form-control" placeholder="https://linkedin.com/in/..." value="<?= htmlspecialchars($member['linkedinUrl'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label>GitHub URL</label>
              <input type="url" name="githubUrl" class="form-control" placeholder="https://github.com/..." value="<?= htmlspecialchars($member['githubUrl'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-group mb-4">
          <button class="btn btn-primary px-5" type="submit"><i class="fas fa-save"></i> Kaydet</button>
          <a href="board-yonetim.php" class="btn btn-secondary px-4"><i class="fas fa-times"></i> İptal</a>
        </div>
      </form>
    </div>
  </div>
</main>

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

