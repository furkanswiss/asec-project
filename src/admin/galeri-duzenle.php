<?php
// Galeri Fotoğrafı Düzenle
require_once '../db.php';
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM galeri WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if(!$item) die('Fotoğraf bulunamadı!');
$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'];
    $aciklama = $_POST['aciklama'];
    $kategori = $_POST['kategori'];
    $tarih = $_POST['tarih'];
    $dosya_yolu = $item['dosya_yolu'];
    if(isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['dosya']['name'], PATHINFO_EXTENSION);
        $newName = uniqid('galeri_', true).'.'.$ext;
        $target = '../images/gallery/'.$newName;
        if(move_uploaded_file($_FILES['dosya']['tmp_name'], $target)) {
            $dosya_yolu = 'images/gallery/'.$newName;
        }
    }
    $stmt = $pdo->prepare("UPDATE galeri SET baslik=?, aciklama=?, kategori=?, tarih=?, dosya_yolu=? WHERE id=?");
    $stmt->execute([$baslik, $aciklama, $kategori, $tarih, $dosya_yolu, $id]);
    $msg = 'Güncellendi!';
    header('Location: galeri-yonetim.php');
    exit;
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
    <div class="row">
        <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <h2>Fotoğraf Düzenle</h2>
<?php if($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="galeri-duzenle-form">
    <div class="form-fields">
        <input type="text" name="baslik" value="<?= htmlspecialchars($item['baslik']) ?>" required>
        <input type="text" name="aciklama" value="<?= htmlspecialchars($item['aciklama']) ?>">
        <select name="kategori">
            <option value="events"<?= $item['kategori']==='events'?' selected':''; ?>>Etkinlik</option>
            <option value="workshops"<?= $item['kategori']==='workshops'?' selected':''; ?>>Atölye</option>
            <option value="teams"<?= $item['kategori']==='teams'?' selected':''; ?>>Takım</option>
            <option value="other"<?= $item['kategori']==='other'?' selected':''; ?>>Diğer</option>
        </select>
        <input type="date" name="tarih" value="<?= $item['tarih'] ?>" required>
    </div>
    <div class="mevcut-img">Mevcut: <img src="../<?= htmlspecialchars($item['dosya_yolu']) ?>" alt="" style="max-width:100px;max-height:70px;object-fit:cover;border-radius:8px;display:block;margin:0 auto 10px auto;border:1.5px solid #e3e6f3;background:#fff;box-shadow:0 2px 8px 0 rgba(60,72,100,0.07);" ></div>
    <div class="form-actions">
        <input type="file" name="dosya" accept="image/*">
        <button type="submit">Kaydet</button>
    </div>
</form>
<a href="galeri-yonetim.php" class="geri-don-link">Geri Dön</a>
        </div>
    </div>
</main>
