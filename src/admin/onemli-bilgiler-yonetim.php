<?php
// Admin Önemli Bilgiler Yönetim Paneli
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

// Create table if not exists
$pdo->exec('CREATE TABLE IF NOT EXISTS onemli_bilgiler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(255) NOT NULL,
    aciklama TEXT NOT NULL,
    icerik TEXT NOT NULL,
    resim VARCHAR(255) DEFAULT NULL,
    tarih DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tarih (tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$bilgiler = $pdo->query('SELECT * FROM onemli_bilgiler ORDER BY tarih DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Önemli Bilgiler Yönetimi</h1>
      <a href="onemli-bilgi-ekle.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Yeni Bilgi Ekle</a>
      <table class="table table-striped admin-table">
        <thead class="thead-dark">
          <tr>
            <th>ID</th>
            <th>Başlık</th>
            <th>Tarih</th>
            <th>Resim</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
            <?php if(empty($bilgiler)): ?>
            <tr>
                <td colspan="5" class="text-center">Henüz bilgi eklenmemiş.</td>
            </tr>
            <?php else: ?>
            <?php foreach($bilgiler as $bilgi): ?>
            <tr>
                <td><?= htmlspecialchars($bilgi['id']) ?></td>
                <td><?= htmlspecialchars($bilgi['baslik']) ?></td>
                <td><?= htmlspecialchars($bilgi['tarih']) ?></td>
                <td>
                    <?php if(!empty($bilgi['resim'])): ?>
                        <img src="../uploads/onemli-bilgiler/<?= htmlspecialchars($bilgi['resim']) ?>" alt="Resim" style="max-width: 100px; max-height: 60px; object-fit: cover;">
                    <?php else: ?>
                        <span class="text-muted">Resim yok</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="onemli-bilgi-duzenle.php?id=<?= $bilgi['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Düzenle</a>
                    <a href="onemli-bilgi-sil.php?id=<?= $bilgi['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
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

