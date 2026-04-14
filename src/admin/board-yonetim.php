<?php
// Admin Board Members Yönetim Paneli
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
    } else {
        // Check and add missing columns
        $columns = $pdo->query("SHOW COLUMNS FROM board_members")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = [
            'name' => 'VARCHAR(255) NOT NULL',
            'position' => 'VARCHAR(255) NOT NULL',
            'profileImage' => 'VARCHAR(500)',
            'linkedinUrl' => 'VARCHAR(500)',
            'githubUrl' => 'VARCHAR(500)',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        foreach ($requiredColumns as $colName => $colDef) {
            if (!in_array($colName, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE board_members ADD COLUMN {$colName} {$colDef}");
                } catch (PDOException $e) {
                    // Column might already exist, continue
                }
            }
        }
    }
} catch (PDOException $e) {
    // Table creation/alteration failed, continue
}

$boardMembers = $pdo->query('SELECT * FROM board_members ORDER BY created_at DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Yönetim Kurulu Yönetimi</h1>
      <a href="board-ekle.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Yeni Üye Ekle</a>
      <table class="table table-striped admin-table">
        <thead class="thead-dark">
          <tr>
            <th>ID</th>
            <th>Fotoğraf</th>
            <th>İsim</th>
            <th>Pozisyon</th>
            <th>LinkedIn</th>
            <th>GitHub</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
            <?php if(empty($boardMembers)): ?>
            <tr>
                <td colspan="7" class="text-center">Henüz üye eklenmemiş.</td>
            </tr>
            <?php else: ?>
            <?php foreach($boardMembers as $member): ?>
            <tr>
                <td><?= htmlspecialchars($member['id']) ?></td>
                <td>
                    <?php if(!empty($member['profileImage'])): ?>
                        <img src="../<?= htmlspecialchars($member['profileImage']) ?>" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <span class="text-muted">Fotoğraf Yok</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($member['name']) ?></td>
                <td><?= htmlspecialchars($member['position']) ?></td>
                <td>
                    <?php if(!empty($member['linkedinUrl'])): ?>
                        <a href="<?= htmlspecialchars($member['linkedinUrl']) ?>" target="_blank" class="btn btn-sm btn-info"><i class="fab fa-linkedin"></i></a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!empty($member['githubUrl'])): ?>
                        <a href="<?= htmlspecialchars($member['githubUrl']) ?>" target="_blank" class="btn btn-sm btn-dark"><i class="fab fa-github"></i></a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="board-duzenle.php?id=<?= $member['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Düzenle</a>
                    <a href="board-sil.php?id=<?= $member['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
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

