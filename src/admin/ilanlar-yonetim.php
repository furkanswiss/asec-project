<?php
// Admin İlanlar Yönetim Paneli
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$ilanlar = $pdo->query('SELECT * FROM ilanlar ORDER BY tarih DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>İlanlar Yönetimi</h1>
      <a href="ilan-ekle.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Yeni İlan</a>
      <table class="table table-striped admin-table">
        <thead class="thead-dark">
          <tr>
            <th>ID</th>
            <th>Başlık</th>
            <th>Tarih</th>
            <th>Kategori</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
            <?php if(empty($ilanlar)): ?>
            <tr>
                <td colspan="5" class="text-center">Henüz ilan eklenmemiş.</td>
            </tr>
            <?php else: ?>
            <?php foreach($ilanlar as $ilan): ?>
            <tr>
                <td><?= htmlspecialchars($ilan['id']) ?></td>
                <td><?= htmlspecialchars($ilan['baslik']) ?></td>
                <td><?= htmlspecialchars($ilan['tarih']) ?></td>
                <td><?= htmlspecialchars($ilan['kategori']) ?></td>
                <td>
                    <a href="ilan-duzenle.php?id=<?= $ilan['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Düzenle</a>
                    <a href="ilan-sil.php?id=<?= $ilan['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>


