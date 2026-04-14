<?php
// Admin Bilgilendirme Yönetim Paneli
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

// Database Auto-Creation: Create tables if they don't exist
try {
    // Create info_tables table
    $pdo->exec('CREATE TABLE IF NOT EXISTS info_tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title_tr VARCHAR(255) NOT NULL,
        title_en VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    
    // Create info_rows table
    $pdo->exec('CREATE TABLE IF NOT EXISTS info_rows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_id INT NOT NULL,
        col1_tr VARCHAR(500) NOT NULL,
        col1_en VARCHAR(500) NOT NULL,
        col2_tr VARCHAR(500) NOT NULL,
        col2_en VARCHAR(500) NOT NULL,
        col3_tr VARCHAR(500) NOT NULL,
        col3_en VARCHAR(500) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_table_id (table_id),
        INDEX idx_sort_order (sort_order),
        FOREIGN KEY (table_id) REFERENCES info_tables(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
} catch (PDOException $e) {
    // Tables might already exist, continue
}

// Fetch all info tables
$tables = $pdo->query('SELECT * FROM info_tables ORDER BY created_at DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Bilgilendirme Yönetimi</h1>
      <a href="bilgi-ekle.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Yeni Bilgilendirme Tablosu</a>
      <table class="table table-striped admin-table">
        <thead class="thead-dark">
          <tr>
            <th>ID</th>
            <th>Başlık (TR)</th>
            <th>Başlık (EN)</th>
            <th>Oluşturulma Tarihi</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
            <?php if(empty($tables)): ?>
            <tr>
                <td colspan="5" class="text-center">Henüz bilgilendirme tablosu eklenmemiş.</td>
            </tr>
            <?php else: ?>
            <?php foreach($tables as $table): ?>
            <tr>
                <td><?= htmlspecialchars($table['id']) ?></td>
                <td><?= htmlspecialchars($table['title_tr']) ?></td>
                <td><?= htmlspecialchars($table['title_en']) ?></td>
                <td><?= htmlspecialchars($table['created_at']) ?></td>
                <td>
                    <a href="bilgi-ekle.php?id=<?= $table['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Düzenle</a>
                    <a href="bilgi-sil.php?id=<?= $table['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
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


