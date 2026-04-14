<?php
// Admin Corporate Requests Management
require_once 'includes/config.php';
require_once '../db.php'; // Use PDO for corporate_requests table

// Ensure corporate_requests table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS corporate_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    tax_number VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    status ENUM("pending", "approved", "rejected") DEFAULT "pending",
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

// Handle approve/reject action
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = intval($_POST['request_id'] ?? 0);
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    if ($request_id > 0 && in_array($action, ['approve', 'reject'])) {
        // Get request details
        $stmt = $pdo->prepare('SELECT * FROM corporate_requests WHERE id = ?');
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if ($request) {
            if ($action === 'approve') {
                // Check if email already exists in corporate_users
                $check_stmt = $pdo->prepare('SELECT id FROM corporate_users WHERE email = ?');
                $check_stmt->execute([$request['email']]);
                if ($check_stmt->fetch()) {
                    $error = 'Bu e-posta adresi zaten kurumsal kullanıcı olarak kayıtlı!';
                } else {
                    // Create corporate user account
                    $insert_stmt = $pdo->prepare('INSERT INTO corporate_users (company_name, contact_person, email, phone, address, tax_number, password) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $insert_ok = $insert_stmt->execute([
                        $request['company_name'],
                        $request['contact_person'],
                        $request['email'],
                        $request['phone'],
                        $request['address'],
                        $request['tax_number'],
                        $request['password']
                    ]);
                    
                    if ($insert_ok) {
                        // Update request status
                        $update_stmt = $pdo->prepare('UPDATE corporate_requests SET status = "approved", admin_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
                        $update_stmt->execute([$admin_notes, $admin_user_id, $request_id]);
                        $msg = 'Kurumsal kullanıcı başarıyla onaylandı ve hesap oluşturuldu!';
                    } else {
                        $error = 'Hesap oluşturulurken bir hata oluştu!';
                    }
                }
            } elseif ($action === 'reject') {
                // Update request status to rejected
                $update_stmt = $pdo->prepare('UPDATE corporate_requests SET status = "rejected", admin_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
                $update_stmt->execute([$admin_notes, $admin_user_id, $request_id]);
                $msg = 'İstek reddedildi.';
            }
        } else {
            $error = 'İstek bulunamadı!';
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';
if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'])) {
    $filter = 'pending';
}

// Build query
$where_clause = '';
$params = [];
if ($filter !== 'all') {
    $where_clause = 'WHERE status = ?';
    $params[] = $filter;
}

$sql = "SELECT cr.* 
        FROM corporate_requests cr 
        $where_clause 
        ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get counts
$pending_count = $pdo->query("SELECT COUNT(*) FROM corporate_requests WHERE status = 'pending'")->fetchColumn();
$approved_count = $pdo->query("SELECT COUNT(*) FROM corporate_requests WHERE status = 'approved'")->fetchColumn();
$rejected_count = $pdo->query("SELECT COUNT(*) FROM corporate_requests WHERE status = 'rejected'")->fetchColumn();

// Get admin user ID from session (adjust based on your admin session structure)
$admin_user_id = $_SESSION['user_id'] ?? null;
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
    <div class="row">
        <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Kurumsal Kullanıcı İstekleri</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="mb-3">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>" href="?filter=pending">
                            Bekleyen <span class="badge badge-warning"><?= $pending_count ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $filter === 'approved' ? 'active' : '' ?>" href="?filter=approved">
                            Onaylanan <span class="badge badge-success"><?= $approved_count ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $filter === 'rejected' ? 'active' : '' ?>" href="?filter=rejected">
                            Reddedilen <span class="badge badge-danger"><?= $rejected_count ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="?filter=all">
                            Tümü
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Requests Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if(empty($requests)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <?= $filter === 'pending' ? 'Bekleyen istek bulunmamaktadır.' : 'İstek bulunmamaktadır.' ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Şirket Adı</th>
                                        <th>İletişim Kişisi</th>
                                        <th>E-posta</th>
                                        <th>Telefon</th>
                                        <th>Vergi No</th>
                                        <th>Durum</th>
                                        <th>İstek Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($requests as $req): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($req['id']) ?></td>
                                            <td><?= htmlspecialchars($req['company_name']) ?></td>
                                            <td><?= htmlspecialchars($req['contact_person']) ?></td>
                                            <td><?= htmlspecialchars($req['email']) ?></td>
                                            <td><?= htmlspecialchars($req['phone']) ?></td>
                                            <td><?= htmlspecialchars($req['tax_number'] ?? '-') ?></td>
                                            <td>
                                                <?php if($req['status'] === 'pending'): ?>
                                                    <span class="badge badge-warning">Bekliyor</span>
                                                <?php elseif($req['status'] === 'approved'): ?>
                                                    <span class="badge badge-success">Onaylandı</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Reddedildi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#detailModal<?= $req['id'] ?>">
                                                    <i class="fas fa-eye"></i> Detay
                                                </button>
                                                <?php if($req['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#approveModal<?= $req['id'] ?>">
                                                        <i class="fas fa-check"></i> Onayla
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal<?= $req['id'] ?>">
                                                        <i class="fas fa-times"></i> Reddet
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Detail Modal -->
                                        <div class="modal fade" id="detailModal<?= $req['id'] ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">İstek Detayları</h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Şirket Adı:</strong> <?= htmlspecialchars($req['company_name']) ?></p>
                                                                <p><strong>İletişim Kişisi:</strong> <?= htmlspecialchars($req['contact_person']) ?></p>
                                                                <p><strong>E-posta:</strong> <?= htmlspecialchars($req['email']) ?></p>
                                                                <p><strong>Telefon:</strong> <?= htmlspecialchars($req['phone']) ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Vergi Numarası:</strong> <?= htmlspecialchars($req['tax_number'] ?? '-') ?></p>
                                                                <p><strong>Adres:</strong> <?= htmlspecialchars($req['address'] ?? '-') ?></p>
                                                                <p><strong>İstek Tarihi:</strong> <?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></p>
                                                                <?php if($req['reviewed_at']): ?>
                                                                    <p><strong>İnceleme Tarihi:</strong> <?= date('d.m.Y H:i', strtotime($req['reviewed_at'])) ?></p>
                                                                    <?php if($req['reviewed_by']): ?>
                                                                        <p><strong>İnceleyen ID:</strong> <?= htmlspecialchars($req['reviewed_by']) ?></p>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                                <?php if($req['admin_notes']): ?>
                                                                    <p><strong>Notlar:</strong> <?= htmlspecialchars($req['admin_notes']) ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Approve Modal -->
                                        <div class="modal fade" id="approveModal<?= $req['id'] ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">İsteği Onayla</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Bu kurumsal kullanıcı isteğini onaylamak istediğinize emin misiniz?</p>
                                                            <p><strong>Şirket:</strong> <?= htmlspecialchars($req['company_name']) ?></p>
                                                            <p><strong>E-posta:</strong> <?= htmlspecialchars($req['email']) ?></p>
                                                            <div class="form-group">
                                                                <label for="approve_notes<?= $req['id'] ?>">Notlar (Opsiyonel)</label>
                                                                <textarea class="form-control" id="approve_notes<?= $req['id'] ?>" name="admin_notes" rows="3"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                                                            <button type="submit" class="btn btn-success">Onayla</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal<?= $req['id'] ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">İsteği Reddet</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Bu kurumsal kullanıcı isteğini reddetmek istediğinize emin misiniz?</p>
                                                            <p><strong>Şirket:</strong> <?= htmlspecialchars($req['company_name']) ?></p>
                                                            <p><strong>E-posta:</strong> <?= htmlspecialchars($req['email']) ?></p>
                                                            <div class="form-group">
                                                                <label for="reject_notes<?= $req['id'] ?>">Red Nedeni (Opsiyonel)</label>
                                                                <textarea class="form-control" id="reject_notes<?= $req['id'] ?>" name="admin_notes" rows="3"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                                                            <button type="submit" class="btn btn-danger">Reddet</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

