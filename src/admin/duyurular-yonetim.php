<?php
// Admin Duyurular Yönetim Paneli
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$duyurular = $pdo->query('SELECT * FROM duyurular ORDER BY tarih DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Duyurular Yönetimi</h1>
      <a href="duyuru-ekle.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Yeni Duyuru</a>
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
            <?php if(empty($duyurular)): ?>
            <tr>
                <td colspan="5" class="text-center">Henüz duyuru eklenmemiş.</td>
            </tr>
            <?php else: ?>
            <?php foreach($duyurular as $duyuru): ?>
            <tr>
                <td><?= htmlspecialchars($duyuru['id']) ?></td>
                <td><?= htmlspecialchars($duyuru['baslik']) ?></td>
                <td><?= htmlspecialchars($duyuru['tarih']) ?></td>
                <td><?= htmlspecialchars($duyuru['kategori']) ?></td>
                <td>
                    <a href="duyuru-duzenle.php?id=<?= $duyuru['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Düzenle</a>
                    <a href="duyuru-sil.php?id=<?= $duyuru['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
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
