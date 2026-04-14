<?php
require_once 'db.php';
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/lang.php';

// Access language arrays globally
global $translations, $langCode;

// Determine language
$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');

// Fetch all info tables
try {
    $stmt = $pdo->query('SELECT * FROM info_tables ORDER BY created_at DESC');
    $tables = $stmt->fetchAll();
} catch (PDOException $e) {
    $tables = [];
}

// Function to get rows for a table
function getTableRows($pdo, $tableId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM info_rows WHERE table_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$tableId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Bilgilendirmeler - ASEC Kulübü</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mobile-optimizations.css">
    <style>
        .bilgilendirmeler-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .page-title {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.5rem;
            font-weight: 700;
            color: #1B1F3B;
        }
        .info-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .info-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            border-color: #1B1F3B;
        }
        .info-card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1B1F3B;
            margin-bottom: 1rem;
            text-align: center;
        }
        .info-card-icon {
            text-align: center;
            font-size: 3rem;
            color: #1B1F3B;
            margin-bottom: 1rem;
        }
        .info-card-btn {
            width: 100%;
            margin-top: 1rem;
            background: #1B1F3B;
            color: #fff;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .info-card-btn:hover {
            background: #2a3a5c;
            color: #fff;
        }
        .modal-table {
            width: 100%;
        }
        .modal-table th {
            background-color: #1B1F3B;
            color: #fff;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
        }
        .modal-table td {
            padding: 0.75rem;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }
        .modal-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .no-tables {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .no-tables i {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }
        @media (max-width: 768px) {
            .info-cards-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="bilgilendirmeler-container">
            <h2 class="page-title">Bilgilendirmeler</h2>
            
            <?php if (empty($tables)): ?>
                <div class="no-tables">
                    <i class="fas fa-info-circle"></i>
                    <h3>Henüz bilgilendirme tablosu bulunmamaktadır.</h3>
                    <p>Yakında yeni bilgilendirmeler eklenecektir.</p>
                </div>
            <?php else: ?>
                <div class="info-cards-grid">
                    <?php foreach ($tables as $table): ?>
                        <?php
                        $rows = getTableRows($pdo, $table['id']);
                        $title = ($currentLang === 'en' && !empty($table['title_en'])) ? $table['title_en'] : $table['title_tr'];
                        ?>
                        <div class="info-card" data-table-id="<?= $table['id'] ?>" data-toggle="modal" data-target="#infoModal<?= $table['id'] ?>">
                            <div class="info-card-icon">
                                <i class="fas fa-table"></i>
                            </div>
                            <div class="info-card-title">
                                <?= htmlspecialchars($title) ?>
                            </div>
                            <button class="info-card-btn" type="button">
                                <i class="fas fa-eye"></i> İncele
                            </button>
                        </div>
                        
                        <!-- Bootstrap Modal for this table -->
                        <div class="modal fade" id="infoModal<?= $table['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel<?= $table['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header" style="background-color: #1B1F3B; color: #fff;">
                                        <h5 class="modal-title" id="infoModalLabel<?= $table['id'] ?>">
                                            <?= htmlspecialchars($title) ?>
                                        </h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if (empty($rows)): ?>
                                            <p class="text-center text-muted">Bu tabloda henüz veri bulunmamaktadır.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-bordered modal-table">
                                                    <thead>
                                                        <tr>
                                                            <th>
                                                                <?= $currentLang === 'en' ? 'Column 1' : 'Sütun 1' ?>
                                                            </th>
                                                            <th>
                                                                <?= $currentLang === 'en' ? 'Column 2' : 'Sütun 2' ?>
                                                            </th>
                                                            <th>
                                                                <?= $currentLang === 'en' ? 'Column 3' : 'Sütun 3' ?>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($rows as $row): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($currentLang === 'en' ? $row['col1_en'] : $row['col1_tr']) ?></td>
                                                                <td><?= htmlspecialchars($currentLang === 'en' ? $row['col2_en'] : $row['col2_tr']) ?></td>
                                                                <td><?= htmlspecialchars($currentLang === 'en' ? $row['col3_en'] : $row['col3_tr']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="javascript/script.js"></script>
</body>
</html>


