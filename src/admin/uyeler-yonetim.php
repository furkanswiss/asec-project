<?php
require_once 'includes/config.php';
require_once '../db.php'; // For corporate_users table (PDO)

// Oturum kontrolü
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'individual', 'corporate'])) {
    $filter = 'all';
}

// Initialize arrays
$individual_users = [];
$corporate_users = [];

// Fetch individual users
if ($filter === 'all' || $filter === 'individual') {
    $sql = "SELECT id, name, phone, email, university, department, class, created_at FROM users ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['user_type'] = 'individual';
            $individual_users[] = $row;
        }
    }
}

// Fetch corporate users
if ($filter === 'all' || $filter === 'corporate') {
    $corporate_sql = "SELECT id, company_name, contact_person, email, phone, address, tax_number, created_at FROM corporate_users ORDER BY created_at DESC";
    $corporate_stmt = $pdo->query($corporate_sql);
    if ($corporate_stmt) {
        $corporate_users = $corporate_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($corporate_users as &$user) {
            $user['user_type'] = 'corporate';
        }
    }
}

// Combine results for "all" view
$all_users = array_merge($individual_users, $corporate_users);

// Export to Excel (respect current filter)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $export_rows = [];

    if ($filter === 'all' || $filter === 'individual') {
        $all_individual_sql = "SELECT id, name, email, created_at FROM users ORDER BY created_at DESC";
        $all_individual_result = mysqli_query($conn, $all_individual_sql);
        if ($all_individual_result) {
            while ($row = mysqli_fetch_assoc($all_individual_result)) {
                $row['user_type'] = 'Bireysel';
                $row['display_name'] = $row['name'];
                $export_rows[] = $row;
            }
        }
    }

    if ($filter === 'all' || $filter === 'corporate') {
        $all_corporate_sql = "SELECT id, company_name, email, created_at FROM corporate_users ORDER BY created_at DESC";
        $all_corporate_stmt = $pdo->query($all_corporate_sql);
        if ($all_corporate_stmt) {
            $corporate_rows = $all_corporate_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($corporate_rows as $row) {
                $row['user_type'] = 'Kurumsal';
                $row['display_name'] = $row['company_name'];
                $export_rows[] = $row;
            }
        }
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="uyeler.xls"');
    // BOM for Turkish characters in Excel
    echo "\xEF\xBB\xBF";

    echo "<table border='1'>";
    echo "<tr>";
    echo "<th>ID</th><th>Ad / Şirket</th><th>Email</th><th>Üye Tipi</th><th>Kayıt Tarihi</th>";
    echo "</tr>";

    foreach ($export_rows as $row) {
        $name = $row['display_name'] ?? ($row['company_name'] ?? ($row['name'] ?? ''));
        echo "<tr>";
        echo "<td>".htmlspecialchars($row['id'])."</td>";
        echo "<td>".htmlspecialchars($name)."</td>";
        echo "<td>".htmlspecialchars($row['email'])."</td>";
        echo "<td>".htmlspecialchars($row['user_type'])."</td>";
        echo "<td>".htmlspecialchars($row['created_at'])."</td>";
        echo "</tr>";
    }

    echo "</table>";
    exit;
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
    <div class="row">
        <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <h1 class="mt-4 mb-4">Üyeler</h1>
            
            <!-- Filter Buttons + Export -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="btn-group" role="group" aria-label="User type filter">
                    <a href="?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="fas fa-users"></i> Tümü
                    </a>
                    <a href="?filter=individual" class="btn <?= $filter === 'individual' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="fas fa-user"></i> Bireysel
                    </a>
                    <a href="?filter=corporate" class="btn <?= $filter === 'corporate' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="fas fa-building"></i> Kurumsal
                    </a>
                </div>
                <a href="?export=excel&filter=<?= urlencode($filter) ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Excel'e Aktar
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Tip</th>
                            <?php if ($filter === 'all'): ?>
                                <th>Ad/Şirket</th>
                                <th>İletişim Kişisi</th>
                            <?php elseif ($filter === 'individual'): ?>
                                <th>Ad Soyad</th>
                            <?php else: ?>
                                <th>Şirket Adı</th>
                                <th>İletişim Kişisi</th>
                            <?php endif; ?>
                            <th>Email</th>
                            <th>Telefon</th>
                            <?php if ($filter === 'all' || $filter === 'individual'): ?>
                                <th>Üniversite</th>
                                <th>Bölüm</th>
                                <th>Sınıf</th>
                            <?php endif; ?>
                            <?php if ($filter === 'corporate'): ?>
                                <th>Vergi No</th>
                            <?php endif; ?>
                            <th>Kayıt Tarihi</th>
                            <th>Detay</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $users_to_display = ($filter === 'all') ? $all_users : (($filter === 'individual') ? $individual_users : $corporate_users);
                    if (!empty($users_to_display)): 
                        $i = 1; 
                        foreach ($users_to_display as $uye): 
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <?php if ($uye['user_type'] === 'corporate'): ?>
                                    <span class="badge badge-secondary">Kurumsal</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Bireysel</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($filter === 'all'): ?>
                                <td>
                                    <?php if ($uye['user_type'] === 'corporate'): ?>
                                        <?= htmlspecialchars($uye['company_name'] ?? '-') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($uye['name'] ?? '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($uye['user_type'] === 'corporate'): ?>
                                        <?= htmlspecialchars($uye['contact_person'] ?? '-') ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php elseif ($filter === 'individual'): ?>
                                <td><?= htmlspecialchars($uye['name'] ?? '-') ?></td>
                            <?php else: ?>
                                <td><?= htmlspecialchars($uye['company_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($uye['contact_person'] ?? '-') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($uye['email']) ?></td>
                            <td><?= htmlspecialchars($uye['phone']) ?></td>
                            <?php if ($filter === 'all' || $filter === 'individual'): ?>
                                <td>
                                    <?php if ($uye['user_type'] === 'corporate'): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($uye['university'] ?? '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($uye['user_type'] === 'corporate'): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($uye['department'] ?? '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($uye['user_type'] === 'corporate'): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($uye['class'] ?? '-') ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($filter === 'corporate'): ?>
                                <td><?= htmlspecialchars($uye['tax_number'] ?? '-') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($uye['created_at']) ?></td>
                            <td>
                                <?php if ($uye['user_type'] === 'individual'): ?>
                                    <a href="uye-detay.php?id=<?= $uye['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Görüntüle</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    else: 
                        $colspan = ($filter === 'all') ? '11' : (($filter === 'corporate') ? '8' : '9');
                    ?>
                        <tr>
                            <td colspan="<?= $colspan ?>" class="text-center">
                                <?php if ($filter === 'individual'): ?>
                                    Hiç bireysel üye bulunamadı.
                                <?php elseif ($filter === 'corporate'): ?>
                                    Hiç kurumsal üye bulunamadı.
                                <?php else: ?>
                                    Hiç üye bulunamadı.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
