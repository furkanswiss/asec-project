<?php
// Etkinlik Düzenle
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

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

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Geçersiz ID!');
$msg = '';
$stmt = $pdo->prepare('SELECT * FROM etkinlikler WHERE id=?');
$stmt->execute([$id]);
$etkinlik = $stmt->fetch();
if (!$etkinlik) die('Etkinlik bulunamadı!');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'] ?? '';
    $baslik_en = $_POST['baslik_en'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';
    $aciklama_en = $_POST['aciklama_en'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    $saat = $_POST['saat'] ?? '';
    $yer = $_POST['yer'] ?? '';
    $kayit_link = $_POST['kayit_link'] ?? '';
    $stmt2 = $pdo->prepare('UPDATE etkinlikler SET baslik=?, baslik_en=?, aciklama=?, aciklama_en=?, tarih=?, saat=?, yer=?, kayit_link=?, foto_link=NULL WHERE id=?');
    $ok = $stmt2->execute([$baslik, $baslik_en, $aciklama, $aciklama_en, $tarih, $saat, $yer, $kayit_link, $id]);
    if ($ok) {
        // Fotoğraf yükleme işlemi
        $upload_dir = __DIR__ . '/../uploads/etkinlikler/' . $id . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $dosya_sayisi = isset($_FILES['fotolar']['name']) ? count($_FILES['fotolar']['name']) : 0;
        // Var olan fotoğraf sayısını kontrol et
        $foto_count = $pdo->query("SELECT COUNT(*) FROM etkinlik_fotolar WHERE etkinlik_id=" . intval($id))->fetchColumn();
        if ($dosya_sayisi + $foto_count > 10) {
            $msg = 'Toplamda en fazla 10 fotoğrafınız olabilir!';
        } else {
            for ($i = 0; $i < $dosya_sayisi; $i++) {
                $tmp_name = $_FILES['fotolar']['tmp_name'][$i];
                $name = basename($_FILES['fotolar']['name'][$i]);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                    $new_name = uniqid('foto_', true) . '.' . $ext;
                    $target_path = $upload_dir . $new_name;
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $rel_path = 'uploads/etkinlikler/' . $id . '/' . $new_name;
                        $stmt2 = $pdo->prepare('INSERT INTO etkinlik_fotolar (etkinlik_id, dosya_yolu) VALUES (?, ?)');
                        $stmt2->execute([$id, $rel_path]);
                    }
                }
            }
        }
    }
    $msg = $ok ? 'Etkinlik güncellendi!' : 'Hata oluştu!';
    $stmt->execute([$id]);
    $etkinlik = $stmt->fetch();
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<div class="etkinlik-form-center">
  <div class="container etkinlik-form-container">
    <h1>Etkinlik Düzenle</h1>
    <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mb-4">
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
                    <input type="text" name="baslik" class="form-control" value="<?= htmlspecialchars($etkinlik['baslik']) ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label>Açıklama (Türkçe)</label>
                    <textarea name="aciklama" rows="5" class="form-control"><?= htmlspecialchars($etkinlik['aciklama']) ?></textarea>
                </div>
            </div>

            <!-- English Tab -->
            <div class="tab-pane fade" id="en-content" role="tabpanel" aria-labelledby="en-tab">
                <div class="form-group mb-3">
                    <label>Title (English)</label>
                    <input type="text" name="baslik_en" class="form-control" value="<?= htmlspecialchars($etkinlik['baslik_en'] ?? '') ?>" placeholder="Optional: English title">
                </div>
                <div class="form-group mb-3">
                    <label>Description (English)</label>
                    <textarea name="aciklama_en" rows="5" class="form-control" placeholder="Optional: English description"><?= htmlspecialchars($etkinlik['aciklama_en'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Tarih</label>
            <input type="date" name="tarih" class="form-control" value="<?= htmlspecialchars($etkinlik['tarih']) ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Saat</label>
            <input type="text" name="saat" class="form-control" value="<?= htmlspecialchars($etkinlik['saat']) ?>" placeholder="Örn: 14:00 - 17:00">
          </div>
        </div>
        <div class="form-group mb-3">
            <label>Yer</label>
            <input type="text" name="yer" class="form-control" value="<?= htmlspecialchars($etkinlik['yer']) ?>">
        </div>
        <div class="form-group mb-3">
            <label>Kayıt Linki</label>
            <input type="text" name="kayit_link" class="form-control" value="<?= htmlspecialchars($etkinlik['kayit_link']) ?>">
        </div>
        <div class="form-group mb-4">
            <label>Etkinlik Fotoğrafları <span class="text-muted">(En fazla 10 adet)</span></label>
            <input type="file" name="fotolar[]" accept="image/*" multiple class="form-control">
            <small class="form-text text-muted">JPG, PNG veya GIF. Maksimum 10 dosya. Eklemek için seçin.</small>
        </div>
        <button class="btn btn-primary px-5" type="submit">Kaydet</button>
    </form>
    <hr>
    <h4 class="mt-4 mb-3">Yüklü Fotoğraflar</h4>
    <div class="photo-gallery mb-4">
        <?php
        $fotolar = $pdo->prepare('SELECT * FROM etkinlik_fotolar WHERE etkinlik_id=?');
        $fotolar->execute([$id]);
        foreach($fotolar as $foto): ?>
            <div style="position:relative;display:inline-block;">
                <img src="/asec/<?= htmlspecialchars($foto['dosya_yolu']) ?>" style="width:110px;height:80px;object-fit:cover;border:2px solid #f3f3f3;background:#fafbfc;border-radius:8px;box-shadow:0 2px 8px rgba(44,62,80,0.10);">
                <form method="post" action="etkinlik-foto-sil.php" style="position:absolute;top:2px;right:2px;">
                    <input type="hidden" name="foto_id" value="<?= $foto['id'] ?>">
                    <input type="hidden" name="etkinlik_id" value="<?= $id ?>">
                    <button type="submit" style="background:red;color:white;border:none;border-radius:50%;width:22px;height:22px;cursor:pointer;">&times;</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</div>
<style>
.etkinlik-form-center {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    background: transparent;
}
.etkinlik-form-container {
    max-width: 700px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(44,62,80,0.08);
    padding: 32px 32px 24px 32px;
    margin: 40px 0 40px 0;
}
.etkinlik-form-container h1, .etkinlik-form-container h4 {
    font-weight: 700;
    color: #222;
}
.form-group label {
    font-weight: 600;
    margin-bottom: 6px;
}
.form-control, textarea.form-control {
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background: #f8fafc;
    font-size: 1rem;
    padding: 10px 14px;
    margin-bottom: 8px;
}
.form-control:focus, textarea.form-control:focus {
    outline: none;
    border-color: #3498db;
    background: #fff;
}
.btn-primary {
    background: #3498db;
    border: none;
    border-radius: 6px;
    padding: 10px 28px;
    font-weight: 600;
    font-size: 1rem;
    transition: background 0.2s;
}
.btn-primary:hover {
    background: #217dbb;
}
hr {
    margin: 36px 0 24px 0;
    border-top: 1px solid #e1e4e8;
}
.photo-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 12px;
}
.photo-gallery img {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(44,62,80,0.10);
    width: 110px;
    height: 80px;
    object-fit: cover;
    border: 2px solid #f3f3f3;
    background: #fafbfc;
}
@media (max-width: 768px) {
    .etkinlik-form-container {
        padding: 18px 4vw 18px 4vw;
        max-width: 99vw;
    }
    .photo-gallery img {
        width: 90px;
        height: 65px;
    }
}
</style>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
