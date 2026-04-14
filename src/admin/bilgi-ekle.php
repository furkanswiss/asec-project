<?php
// Bilgilendirme Tablosu Ekle/Düzenle
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';

// Ensure tables exist
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS info_tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title_tr VARCHAR(255) NOT NULL,
        title_en VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    
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

$msg = '';
$error = '';
$success = false;
$isEdit = isset($_GET['id']);
$tableId = $isEdit ? intval($_GET['id']) : 0;
$tableData = null;
$rowsData = [];

// Load existing data if editing
if ($isEdit && $tableId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM info_tables WHERE id = ?');
    $stmt->execute([$tableId]);
    $tableData = $stmt->fetch();
    
    if ($tableData) {
        $stmt = $pdo->prepare('SELECT * FROM info_rows WHERE table_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$tableId]);
        $rowsData = $stmt->fetchAll();
    } else {
        $error = 'Tablo bulunamadı!';
        $isEdit = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title_tr = trim($_POST['title_tr'] ?? '');
        $title_en = trim($_POST['title_en'] ?? '');
        
        // Validation
        if (empty($title_tr)) {
            $error = 'Başlık (TR) alanı zorunludur!';
        } elseif (empty($title_en)) {
            $error = 'Başlık (EN) alanı zorunludur!';
        } else {
            $pdo->beginTransaction();
            
            try {
                if ($isEdit && $tableId > 0) {
                    // Update existing table
                    $stmt = $pdo->prepare('UPDATE info_tables SET title_tr = ?, title_en = ? WHERE id = ?');
                    $stmt->execute([$title_tr, $title_en, $tableId]);
                    
                    // Delete existing rows
                    $pdo->prepare('DELETE FROM info_rows WHERE table_id = ?')->execute([$tableId]);
                } else {
                    // Insert new table
                    $stmt = $pdo->prepare('INSERT INTO info_tables (title_tr, title_en) VALUES (?, ?)');
                    $stmt->execute([$title_tr, $title_en]);
                    $tableId = $pdo->lastInsertId();
                }
                
                // Process rows
                $rowCount = 0;
                if (isset($_POST['rows']) && is_array($_POST['rows'])) {
                    foreach ($_POST['rows'] as $rowIndex => $row) {
                        $col1_tr = trim($row['col1_tr'] ?? '');
                        $col1_en = trim($row['col1_en'] ?? '');
                        $col2_tr = trim($row['col2_tr'] ?? '');
                        $col2_en = trim($row['col2_en'] ?? '');
                        $col3_tr = trim($row['col3_tr'] ?? '');
                        $col3_en = trim($row['col3_en'] ?? '');
                        
                        // Skip empty rows
                        if (empty($col1_tr) && empty($col1_en) && empty($col2_tr) && empty($col2_en) && empty($col3_tr) && empty($col3_en)) {
                            continue;
                        }
                        
                        $stmt = $pdo->prepare('INSERT INTO info_rows (table_id, col1_tr, col1_en, col2_tr, col2_en, col3_tr, col3_en, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$tableId, $col1_tr, $col1_en, $col2_tr, $col2_en, $col3_tr, $col3_en, $rowCount]);
                        $rowCount++;
                    }
                }
                
                $pdo->commit();
                $success = true;
                header('Location: bilgi-yonetim.php?success=1');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1><?= $isEdit ? 'Bilgilendirme Tablosu Düzenle' : 'Yeni Bilgilendirme Tablosu' ?></h1>
      <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($msg) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><?php endif; ?>
      <form method="post" class="bg-white p-4 rounded shadow-sm" id="bilgiForm">
        <div class="form-group mb-3">
          <label>Başlık (Türkçe) <span class="text-danger">*</span></label>
          <input type="text" name="title_tr" class="form-control" required value="<?= htmlspecialchars($tableData['title_tr'] ?? ($_POST['title_tr'] ?? '')) ?>">
        </div>
        <div class="form-group mb-3">
          <label>Başlık (English) <span class="text-danger">*</span></label>
          <input type="text" name="title_en" class="form-control" required value="<?= htmlspecialchars($tableData['title_en'] ?? ($_POST['title_en'] ?? '')) ?>">
        </div>
        
        <hr class="my-4">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4>Satırlar</h4>
          <button type="button" class="btn btn-primary" id="addRowBtn">
            <i class="fas fa-plus"></i> Satır Ekle
          </button>
        </div>
        
        <div id="rowsContainer">
          <?php if (!empty($rowsData)): ?>
            <?php foreach ($rowsData as $rowIndex => $row): ?>
              <div class="row-item mb-3 p-3 border rounded" data-row-index="<?= $rowIndex ?>">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>Satır <?= $rowIndex + 1 ?></strong>
                  <button type="button" class="btn btn-sm btn-danger remove-row-btn">
                    <i class="fas fa-trash"></i> Sil
                  </button>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="small">Şirket Adı (TR)</label>
                      <input type="text" name="rows[<?= $rowIndex ?>][col1_tr]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['col1_tr']) ?>">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small">Şartlar (TR)</label>
                      <input type="text" name="rows[<?= $rowIndex ?>][col2_tr]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['col2_tr']) ?>">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small">Link (TR)</label>
                      <input type="text" name="rows[<?= $rowIndex ?>][col3_tr]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['col3_tr']) ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="small">Şirket Adı (EN)</label>
                      <input type="text" name="rows[<?= $rowIndex ?>][col1_en]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['col1_en']) ?>">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small">Şartlar (EN)</label>
                      <input type="text" name="rows[<?= $rowIndex ?>][col2_en]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['col2_en']) ?>">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small">Link (EN)</label>
                      <input type="text" name="rows[<?= $rowIndex ?>][col3_en]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['col3_en']) ?>">
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <div class="form-group mt-4">
          <button class="btn btn-primary px-5" type="submit"><i class="fas fa-save"></i> Kaydet</button>
          <a href="bilgi-yonetim.php" class="btn btn-secondary px-4"><i class="fas fa-times"></i> İptal</a>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
let rowIndex = <?= count($rowsData) ?>;
document.addEventListener('DOMContentLoaded', function() {
    const addRowBtn = document.getElementById('addRowBtn');
    const rowsContainer = document.getElementById('rowsContainer');
    
    // Add new row
    addRowBtn.addEventListener('click', function() {
        const rowHtml = `
            <div class="row-item mb-3 p-3 border rounded" data-row-index="${rowIndex}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Satır ${rowIndex + 1}</strong>
                    <button type="button" class="btn btn-sm btn-danger remove-row-btn">
                        <i class="fas fa-trash"></i> Sil
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-2">
                            <label class="small">Şirket Adı (TR)</label>
                            <input type="text" name="rows[${rowIndex}][col1_tr]" class="form-control form-control-sm">
                        </div>
                        <div class="form-group mb-2">
                            <label class="small">Şartlar (TR)</label>
                            <input type="text" name="rows[${rowIndex}][col2_tr]" class="form-control form-control-sm">
                        </div>
                        <div class="form-group mb-2">
                            <label class="small">Link (TR)</label>
                            <input type="text" name="rows[${rowIndex}][col3_tr]" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-2">
                            <label class="small">Şirket Adı (EN)</label>
                            <input type="text" name="rows[${rowIndex}][col1_en]" class="form-control form-control-sm">
                        </div>
                        <div class="form-group mb-2">
                            <label class="small">Şartlar (EN)</label>
                            <input type="text" name="rows[${rowIndex}][col2_en]" class="form-control form-control-sm">
                        </div>
                        <div class="form-group mb-2">
                            <label class="small">Link (EN)</label>
                            <input type="text" name="rows[${rowIndex}][col3_en]" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
        `;
        rowsContainer.insertAdjacentHTML('beforeend', rowHtml);
        rowIndex++;
    });
    
    // Remove row
    rowsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row-btn')) {
            e.target.closest('.row-item').remove();
            // Update row numbers
            updateRowNumbers();
        }
    });
    
    function updateRowNumbers() {
        const rowItems = rowsContainer.querySelectorAll('.row-item');
        rowItems.forEach((item, index) => {
            const strong = item.querySelector('strong');
            if (strong) {
                strong.textContent = 'Satır ' + (index + 1);
            }
        });
    }
});
</script>

<style>
.row-item {
    background-color: #f8f9fa;
}
.row-item:hover {
    background-color: #e9ecef;
}
</style>
</body>
</html>


