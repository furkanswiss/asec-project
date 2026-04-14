<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Corporate İlanlar Yönetim Paneli
require_once 'includes/config.php';

// Ensure corporate_ilan_requests table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS corporate_ilan_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    corporate_user_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    icerik TEXT NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    tarih DATE NOT NULL,
    link VARCHAR(500),
    sirket VARCHAR(255),
    lokasyon VARCHAR(255),
    son_basvuru DATE,
    status ENUM("pending", "approved", "rejected") DEFAULT "pending",
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_corporate_user_id (corporate_user_id),
    INDEX idx_status (status),
    INDEX idx_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

// Also check for approved announcements in ilanlar table
try {
    $columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('corporate_user_id', $columns)) {
        $pdo->exec("ALTER TABLE ilanlar ADD COLUMN corporate_user_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE ilanlar ADD INDEX idx_corporate_user_id (corporate_user_id)");
    }
} catch (PDOException $e) {
    // Column might already exist, continue
}

// Get filter for category if provided
$kategori_filter = $_GET['kategori'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

// Get requests from corporate_ilan_requests table
$where_clause = "WHERE corporate_user_id = ?";
$params = [$_SESSION['user_id']];

// Allow Staj İlanları, Burs İlanları, and İş İlanı
if ($kategori_filter && in_array($kategori_filter, ['Staj İlanları', 'Burs İlanları', 'İş İlanı'])) {
    $where_clause .= " AND kategori = ?";
    $params[] = $kategori_filter;
} else {
    // Show staj, burs, and job announcements
    $where_clause .= " AND (kategori = 'Staj İlanları' OR kategori = 'Burs İlanları' OR kategori = 'İş İlanı')";
}

// Status filter for requests
if ($status_filter !== 'all' && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $where_clause .= " AND status = ?";
    $params[] = $status_filter;
}

$sql = "SELECT * FROM corporate_ilan_requests $where_clause ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get published announcements from ilanlar table
$ilan_where = "WHERE corporate_user_id = ?";
$ilan_params = [$_SESSION['user_id']];
if ($kategori_filter && in_array($kategori_filter, ['Staj İlanları', 'Burs İlanları', 'İş İlanı'])) {
    $ilan_where .= " AND kategori = ?";
    $ilan_params[] = $kategori_filter;
} else {
    $ilan_where .= " AND (kategori = 'Staj İlanları' OR kategori = 'Burs İlanları' OR kategori = 'İş İlanı')";
}

// Check if columns exist for filtering
try {
    $ilan_columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);
    
    // Add filter for "Yayında" (Published) - must be active and not expired
    if ($status_filter === 'yayinda') {
        // Check if durum column exists (for active/inactive status)
        if (in_array('durum', $ilan_columns)) {
            $ilan_where .= " AND durum = 1";
        }
        // Check if bitis_tarihi column exists (for expiration date)
        if (in_array('bitis_tarihi', $ilan_columns)) {
            $ilan_where .= " AND (bitis_tarihi IS NULL OR bitis_tarihi >= CURDATE())";
        } elseif (in_array('son_basvuru', $ilan_columns)) {
            // Use son_basvuru as fallback if bitis_tarihi doesn't exist
            $ilan_where .= " AND (son_basvuru IS NULL OR son_basvuru >= CURDATE())";
        }
    }
    
    // Filter for "Onaylanan" (Approved) - all approved items regardless of status
    if ($status_filter === 'approved') {
        // Include all items from ilanlar table (they are approved and published)
        // Also include approved requests that may not be published yet
    }
} catch (PDOException $e) {
    // Columns might not exist, continue without those filters
}

$ilan_sql = "SELECT *, 'approved' as request_status, 'published' as is_published FROM ilanlar $ilan_where ORDER BY tarih DESC";
$ilan_stmt = $pdo->prepare($ilan_sql);
$ilan_stmt->execute($ilan_params);
$published_ilanlar = $ilan_stmt->fetchAll();

// Combine requests and published announcements based on filter
$ilanlar = [];

if ($status_filter === 'all') {
    // Show all: requests + published
    $ilanlar = array_merge($requests, $published_ilanlar);
} elseif ($status_filter === 'pending') {
    // Only pending requests
    $ilanlar = $requests;
} elseif ($status_filter === 'rejected') {
    // Only rejected requests
    $ilanlar = $requests;
} elseif ($status_filter === 'approved') {
    // All approved: approved requests + all published items
    $approved_requests = array_filter($requests, function($r) {
        return isset($r['status']) && $r['status'] === 'approved';
    });
    $ilanlar = array_merge($approved_requests, $published_ilanlar);
} elseif ($status_filter === 'yayinda') {
    // Only published and active items (from ilanlar table)
    $ilanlar = $published_ilanlar;
} else {
    // Default: show all
    $ilanlar = array_merge($requests, $published_ilanlar);
}

// Sort by date
usort($ilanlar, function($a, $b) {
    $dateA = $a['created_at'] ?? $a['tarih'] ?? '';
    $dateB = $b['created_at'] ?? $b['tarih'] ?? '';
    return strtotime($dateB) - strtotime($dateA);
});
?>
<?php include 'corporate-header.php'; ?>
<style>
    /* Minimalist styling - remove heavy shadows and colors */
    .table {
        box-shadow: none;
        border: 1px solid #e9ecef;
    }
    
    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
    }
    
    .table tbody tr {
        transition: background-color 0.15s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    /* Add button styling - outline variants */
    .mb-4.d-flex.flex-column.flex-md-row .btn {
        min-height: 50px;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        font-weight: 500;
        min-width: 280px;
        border-width: 1.5px;
        transition: all 0.2s ease;
    }
    
    .mb-4.d-flex.flex-column.flex-md-row .btn:hover {
        transform: translateY(-1px);
    }
    
    /* Filter button groups - clean tab bar look */
    .btn-group[role="group"] {
        box-shadow: none;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #dee2e6;
    }
    
    .btn-group[role="group"] .btn {
        min-height: 44px;
        padding: 10px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        border: none;
        border-right: 1px solid #dee2e6;
        transition: all 0.2s ease;
    }
    
    .btn-group[role="group"] .btn:last-child {
        border-right: none;
    }
    
    .btn-group[role="group"] .btn.btn-primary {
        background-color: #007bff;
        color: #fff;
        font-weight: 600;
        border-color: #007bff;
    }
    
    .btn-group[role="group"] .btn.btn-outline-secondary {
        background-color: #fff;
        color: #6c757d;
        border-color: #dee2e6;
    }
    
    .btn-group[role="group"] .btn.btn-outline-secondary:hover {
        background-color: #f8f9fa;
        color: #495057;
        border-color: #adb5bd;
    }
    
    /* Table action buttons - light with colored icons */
    .table td .btn-light {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #495057;
        padding: 6px 12px;
        font-size: 0.875rem;
        transition: all 0.15s ease;
        box-shadow: none;
    }
    
    .table td .btn-light:hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
        transform: translateY(-1px);
    }
    
    .table td .btn-light:active,
    .table td .btn-light:focus {
        box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.1);
        outline: none;
    }
    
    .table td .btn-light i {
        font-size: 0.9rem;
    }
    
    .table td .btn-light[disabled] {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Gap utility for spacing */
    .gap-2 {
        gap: 0.5rem;
    }
    
    .gap-3 {
        gap: 1rem;
    }
    
    /* Mobile responsive */
    @media (max-width: 767px) {
        .mb-4.d-flex.flex-column .btn {
            width: 100%;
        }
        
        .btn-group[role="group"] {
            flex-wrap: wrap;
            border: none;
        }
        
        .btn-group[role="group"] .btn {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin: 2px;
            flex: 1 1 calc(50% - 4px);
            min-width: calc(50% - 4px);
        }
        
        .btn-group[role="group"] .btn:last-child {
            border-right: 1px solid #dee2e6;
        }
        
        .table td .d-flex {
            flex-direction: column;
        }
        
        .table td .btn-light {
            width: 100%;
            margin-bottom: 4px;
        }
    }
</style>
<div class="container-fluid">
  <div class="row">
    <?php include 'corporate-sidebar.php'; ?>
    <main class="main-content col-md-9 ml-sm-auto col-lg-10">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h3 mb-3 mb-md-0">İlanlarım</h1>
      </div>
      
      <div class="mb-4 d-flex flex-column flex-md-row justify-content-center align-items-stretch gap-2">
        <a href="ilan-ekle.php?kategori=Staj İlanları" class="btn btn-outline-primary" style="min-width: 280px;">
          <i class="fas fa-plus mr-2"></i>Staj İlanı Ekle
        </a>
        <a href="ilan-ekle.php?kategori=Burs İlanları" class="btn btn-outline-success" style="min-width: 280px;">
          <i class="fas fa-plus mr-2"></i>Burs İlanı Ekle
        </a>
        <a href="ilan-ekle.php?kategori=İş İlanı" class="btn btn-outline-info" style="min-width: 280px;">
          <i class="fas fa-plus mr-2"></i>İş İlanı Ekle
        </a>
      </div>
      
      <div class="mb-4 d-flex flex-column align-items-center gap-3">
        <div class="btn-group d-flex justify-content-center" role="group" style="width: 100%; max-width: 900px;">
          <a href="ilanlar-yonetim.php" class="btn <?php echo $kategori_filter === '' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 180px;">Tümü</a>
          <a href="ilanlar-yonetim.php?kategori=Staj İlanları" class="btn <?php echo $kategori_filter === 'Staj İlanları' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 180px;">Staj</a>
          <a href="ilanlar-yonetim.php?kategori=Burs İlanları" class="btn <?php echo $kategori_filter === 'Burs İlanları' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 180px;">Burs</a>
          <a href="ilanlar-yonetim.php?kategori=İş İlanı" class="btn <?php echo $kategori_filter === 'İş İlanı' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 180px;">İş İlanı</a>
        </div>
        <div class="btn-group d-flex justify-content-center" role="group" style="width: 100%; max-width: 1000px;">
          <a href="ilanlar-yonetim.php<?php echo $kategori_filter ? '?kategori=' . urlencode($kategori_filter) : ''; ?>" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 150px;">Tümü</a>
          <a href="ilanlar-yonetim.php?status=pending<?php echo $kategori_filter ? '&kategori=' . urlencode($kategori_filter) : ''; ?>" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 150px;">Bekleyen</a>
          <a href="ilanlar-yonetim.php?status=approved<?php echo $kategori_filter ? '&kategori=' . urlencode($kategori_filter) : ''; ?>" class="btn <?php echo $status_filter === 'approved' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 150px;">Onaylanan</a>
          <a href="ilanlar-yonetim.php?status=yayinda<?php echo $kategori_filter ? '&kategori=' . urlencode($kategori_filter) : ''; ?>" class="btn <?php echo $status_filter === 'yayinda' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 150px;">Yayında</a>
          <a href="ilanlar-yonetim.php?status=rejected<?php echo $kategori_filter ? '&kategori=' . urlencode($kategori_filter) : ''; ?>" class="btn <?php echo $status_filter === 'rejected' ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill" style="min-width: 150px;">Reddedilen</a>
        </div>
      </div>
      
      <div class="table-responsive">
      <table class="table table-striped table-hover admin-table">
        <thead class="thead-dark">
          <tr>
            <th class="d-none d-md-table-cell">ID</th>
            <th>Başlık</th>
            <th class="d-none d-lg-table-cell">Tarih</th>
            <th>Kategori</th>
            <th>Durum</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
            <?php if(empty($ilanlar)): ?>
            <tr>
                <td colspan="6" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted">Henüz ilan eklenmemiş.</p>
                    <a href="ilan-ekle.php?kategori=Staj İlanları" class="btn btn-outline-primary mt-2">
                        <i class="fas fa-plus mr-2"></i>İlan Ekle
                    </a>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach($ilanlar as $ilan): 
                $is_request = isset($ilan['status']) && !isset($ilan['request_status']);
                $is_approved = (isset($ilan['request_status']) && $ilan['request_status'] === 'approved') || (isset($ilan['status']) && $ilan['status'] === 'approved');
                $display_date = $ilan['created_at'] ?? $ilan['tarih'] ?? '';
            ?>
            <tr>
                <td class="d-none d-md-table-cell"><?= htmlspecialchars($ilan['id']) ?></td>
                <td>
                    <strong><?= htmlspecialchars(mb_substr($ilan['baslik'], 0, 50)) ?><?= mb_strlen($ilan['baslik']) > 50 ? '...' : '' ?></strong>
                    <small class="d-block d-lg-none text-muted"><?= htmlspecialchars(date('d.m.Y', strtotime($display_date))) ?></small>
                </td>
                <td class="d-none d-lg-table-cell"><?= htmlspecialchars($display_date) ?></td>
                <td>
                    <?php
                    $badge_class = 'secondary';
                    $badge_text = htmlspecialchars($ilan['kategori']);
                    if ($ilan['kategori'] == 'Staj İlanları') {
                        $badge_class = 'primary';
                        $badge_text = 'Staj';
                    } elseif ($ilan['kategori'] == 'Burs İlanları') {
                        $badge_class = 'success';
                        $badge_text = 'Burs';
                    } elseif ($ilan['kategori'] == 'İş İlanı') {
                        $badge_class = 'info';
                        $badge_text = 'İş İlanı';
                    }
                    ?>
                    <span class="badge badge-<?= $badge_class ?>"><?= $badge_text ?></span>
                </td>
                <td>
                    <?php if($is_request): ?>
                        <?php if($ilan['status'] === 'pending'): ?>
                            <span class="badge badge-warning">Bekliyor</span>
                        <?php elseif($ilan['status'] === 'approved'): ?>
                            <span class="badge badge-success">Onaylandı</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Reddedildi</span>
                            <?php if($ilan['admin_notes']): ?>
                                <small class="text-muted d-block">Not: <?= htmlspecialchars($ilan['admin_notes']) ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-success">Yayında</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if($is_request && $ilan['status'] === 'pending'): ?>
                            <a href="ilan-duzenle.php?id=<?= $ilan['id'] ?>" class="btn btn-light btn-sm" title="Düzenle">
                                <i class="fas fa-edit text-warning"></i>
                                <span class="d-none d-md-inline ml-1">Düzenle</span>
                            </a>
                            <a href="ilan-sil.php?id=<?= $ilan['id'] ?>&type=request" class="btn btn-light btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')" title="Sil">
                                <i class="fas fa-trash text-danger"></i>
                                <span class="d-none d-md-inline ml-1">Sil</span>
                            </a>
                        <?php elseif($is_request && $ilan['status'] === 'rejected'): ?>
                            <button class="btn btn-light btn-sm" disabled title="Reddedildi">
                                <i class="fas fa-times text-muted"></i>
                                <span class="d-none d-md-inline ml-1">Reddedildi</span>
                            </button>
                        <?php elseif($is_approved || (isset($ilan['request_status']) && $ilan['request_status'] === 'approved')): ?>
                            <a href="ilan-sil.php?id=<?= $ilan['id'] ?>&type=published" class="btn btn-light btn-sm" onclick="return confirm('Yayındaki bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')" title="Sil">
                                <i class="fas fa-trash text-danger"></i>
                                <span class="d-none d-md-inline ml-1">Sil</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
      </table>
      </div>
    </main>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

