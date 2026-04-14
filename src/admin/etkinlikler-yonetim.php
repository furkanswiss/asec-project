<?php
// Admin Etkinlikler Yönetim Paneli
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$etkinlikler = $pdo->query('SELECT * FROM etkinlikler ORDER BY tarih DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
    <div class="centered-content-admin">
      <div class="centered-content-admin-inner">
        <h1>Etkinlikler Yönetimi</h1>
        <a href="etkinlik-ekle.php" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Yeni Etkinlik</a>
        <table class="table table-striped">
            <tr>
                <th>ID</th>
                <th>Başlık</th>
                <th>Tarih</th>
                <th>Yer</th>
                <th>İşlem</th>
            </tr>
            <?php foreach($etkinlikler as $etkinlik): ?>
            <tr>
                <td><?= htmlspecialchars($etkinlik['id']) ?></td>
                <td><?= htmlspecialchars($etkinlik['baslik']) ?></td>
                <td><?= htmlspecialchars($etkinlik['tarih']) ?></td>
                <td><?= htmlspecialchars($etkinlik['yer']) ?></td>
                <td>
                    <a href="etkinlik-duzenle.php?id=<?= $etkinlik['id'] ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Düzenle</a>
                    <a href="etkinlik-sil.php?id=<?= $etkinlik['id'] ?>" class="btn btn-delete" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
      </div>
    </div>
<style>
.centered-content-admin {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 100vh;
  background: transparent;
}
.centered-content-admin-inner {
  max-width: 900px;
  width: 100%;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(44,62,80,0.08);
  padding: 32px 32px 24px 32px;
  margin: 40px 0 40px 0;
}
@media (max-width: 1000px) {
  .centered-content-admin-inner {
    padding: 18px 4vw 18px 4vw;
    max-width: 99vw;
  }
}
</style>
</body>
</html>
