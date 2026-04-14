<?php
// Admin CV Options Management
require_once 'includes/config.php';
require_once '../db.php'; // Use PDO for cv_options table

// Ensure user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Create cv_options table if it doesn't exist
$pdo->exec('CREATE TABLE IF NOT EXISTS cv_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM("language", "software_field") NOT NULL,
    name VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type_name (type, name),
    INDEX idx_type (type),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

// Initialize default options if table is empty
$existingCount = $pdo->query("SELECT COUNT(*) FROM cv_options")->fetchColumn();
if ($existingCount == 0) {
    // Default programming languages
    $defaultLanguages = [
        'C', 'C++', 'C#', 'Java', 'Python', 'JavaScript', 'TypeScript', 'PHP', 
        'Go', 'Rust', 'Kotlin', 'Swift', 'R', 'MATLAB', 'SQL'
    ];
    
    // Default software fields
    $defaultSoftwareFields = [
        'Web Geliştirme', 'Mobil Geliştirme', 'Veri Bilimi', 'Makine Öğrenmesi', 
        'Yapay Zeka', 'DevOps', 'Siber Güvenlik', 'Oyun Geliştirme', 
        'Gömülü Sistemler', 'Bulut', 'Yazılım Testi', 'UI/UX', 'AR/VR'
    ];
    
    $stmt = $pdo->prepare('INSERT INTO cv_options (type, name, display_order) VALUES (?, ?, ?)');
    
    $order = 0;
    foreach ($defaultLanguages as $lang) {
        $stmt->execute(['language', $lang, $order++]);
    }
    
    $order = 0;
    foreach ($defaultSoftwareFields as $field) {
        $stmt->execute(['software_field', $field, $order++]);
    }
}

$msg = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $type = $_POST['type'] ?? '';
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            $error = 'İsim alanı boş olamaz.';
        } elseif (!in_array($type, ['language', 'software_field'])) {
            $error = 'Geçersiz tip.';
        } else {
            // Get max display_order for this type
            $maxOrder = $pdo->prepare('SELECT COALESCE(MAX(display_order), -1) FROM cv_options WHERE type = ?');
            $maxOrder->execute([$type]);
            $nextOrder = $maxOrder->fetchColumn() + 1;
            
            try {
                $stmt = $pdo->prepare('INSERT INTO cv_options (type, name, display_order) VALUES (?, ?, ?)');
                $stmt->execute([$type, $name, $nextOrder]);
                $msg = 'Seçenek başarıyla eklendi.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    $error = 'Bu isim zaten mevcut.';
                } else {
                    $error = 'Hata: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            $error = 'İsim alanı boş olamaz.';
        } elseif ($id <= 0) {
            $error = 'Geçersiz ID.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE cv_options SET name = ? WHERE id = ?');
                $stmt->execute([$name, $id]);
                $msg = 'Seçenek başarıyla güncellendi.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Bu isim zaten mevcut.';
                } else {
                    $error = 'Hata: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = 'Geçersiz ID.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM cv_options WHERE id = ?');
            $stmt->execute([$id]);
            $msg = 'Seçenek başarıyla silindi.';
        }
    } elseif ($action === 'toggle_active') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = 'Geçersiz ID.';
        } else {
            $stmt = $pdo->prepare('UPDATE cv_options SET is_active = NOT is_active WHERE id = ?');
            $stmt->execute([$id]);
            $msg = 'Durum güncellendi.';
        }
    } elseif ($action === 'reorder') {
        $items = $_POST['items'] ?? [];
        
        if (!empty($items) && is_array($items)) {
            $stmt = $pdo->prepare('UPDATE cv_options SET display_order = ? WHERE id = ?');
            foreach ($items as $order => $id) {
                $stmt->execute([$order, intval($id)]);
            }
            $msg = 'Sıralama güncellendi.';
        }
    }
}

// Fetch all options
$languages = $pdo->query('SELECT * FROM cv_options WHERE type = "language" ORDER BY display_order ASC, name ASC')->fetchAll();
$softwareFields = $pdo->query('SELECT * FROM cv_options WHERE type = "software_field" ORDER BY display_order ASC, name ASC')->fetchAll();

?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
    <div class="row">
        <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <h1 class="h2 mb-0"><i class="fas fa-cog text-primary"></i> CV Ayarları</h1>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($msg) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Programming Languages Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-code mr-2"></i> Programlama Dilleri</h5>
                    <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#addLanguageModal">
                        <i class="fas fa-plus mr-1"></i> Yeni Ekle
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($languages)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-code fa-3x mb-3"></i>
                            <p>Henüz programlama dili eklenmemiş.</p>
                        </div>
                    <?php else: ?>
                        <div class="row" id="languages-grid">
                            <?php foreach ($languages as $lang): ?>
                                <div class="col-md-4 col-sm-6 col-lg-3 mb-3" data-id="<?= $lang['id'] ?>">
                                    <div class="option-card card h-100 shadow-sm <?= $lang['is_active'] ? '' : 'inactive' ?>" style="border-left: 3px solid #007bff;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 font-weight-bold <?= $lang['is_active'] ? '' : 'text-muted' ?>">
                                                        <?= htmlspecialchars($lang['name']) ?>
                                                    </h6>
                                                    <?php if (!$lang['is_active']): ?>
                                                        <small class="text-muted"><i class="fas fa-eye-slash"></i> Pasif</small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="option-actions">
                                                    <button type="button" class="btn btn-sm btn-link text-primary p-1 edit-btn" 
                                                            data-id="<?= $lang['id'] ?>" 
                                                            data-name="<?= htmlspecialchars($lang['name']) ?>"
                                                            data-toggle="modal" 
                                                            data-target="#editLanguageModal"
                                                            title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Bu dili silmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $lang['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-1" title="Sil">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="id" value="<?= $lang['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?= $lang['is_active'] ? 'warning' : 'success' ?> btn-block" title="<?= $lang['is_active'] ? 'Pasif Yap' : 'Aktif Yap' ?>">
                                                        <i class="fas fa-<?= $lang['is_active'] ? 'eye-slash' : 'eye' ?> mr-1"></i>
                                                        <?= $lang['is_active'] ? 'Pasif Yap' : 'Aktif Yap' ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Software Fields Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-laptop-code mr-2"></i> Yazılım Alanları</h5>
                    <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#addSoftwareFieldModal">
                        <i class="fas fa-plus mr-1"></i> Yeni Ekle
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($softwareFields)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-laptop-code fa-3x mb-3"></i>
                            <p>Henüz yazılım alanı eklenmemiş.</p>
                        </div>
                    <?php else: ?>
                        <div class="row" id="software-fields-grid">
                            <?php foreach ($softwareFields as $field): ?>
                                <div class="col-md-4 col-sm-6 col-lg-3 mb-3" data-id="<?= $field['id'] ?>">
                                    <div class="option-card card h-100 shadow-sm <?= $field['is_active'] ? '' : 'inactive' ?>" style="border-left: 3px solid #28a745;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 font-weight-bold <?= $field['is_active'] ? '' : 'text-muted' ?>">
                                                        <?= htmlspecialchars($field['name']) ?>
                                                    </h6>
                                                    <?php if (!$field['is_active']): ?>
                                                        <small class="text-muted"><i class="fas fa-eye-slash"></i> Pasif</small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="option-actions">
                                                    <button type="button" class="btn btn-sm btn-link text-primary p-1 edit-btn" 
                                                            data-id="<?= $field['id'] ?>" 
                                                            data-name="<?= htmlspecialchars($field['name']) ?>"
                                                            data-toggle="modal" 
                                                            data-target="#editSoftwareFieldModal"
                                                            title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Bu alanı silmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $field['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-1" title="Sil">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="id" value="<?= $field['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?= $field['is_active'] ? 'warning' : 'success' ?> btn-block" title="<?= $field['is_active'] ? 'Pasif Yap' : 'Aktif Yap' ?>">
                                                        <i class="fas fa-<?= $field['is_active'] ? 'eye-slash' : 'eye' ?> mr-1"></i>
                                                        <?= $field['is_active'] ? 'Pasif Yap' : 'Aktif Yap' ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Language Modal -->
<div class="modal fade" id="addLanguageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <form method="post">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Yeni Programlama Dili Ekle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="type" value="language">
                    <div class="form-group">
                        <label for="add-language-name"><i class="fas fa-code mr-2 text-primary"></i>Dil Adı</label>
                        <input type="text" class="form-control form-control-lg" id="add-language-name" name="name" 
                               placeholder="Örn: Python, JavaScript, Java..." required autofocus>
                        <small class="form-text text-muted">Programlama dilinin adını girin.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> İptal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Language Modal -->
<div class="modal fade" id="editLanguageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <form method="post">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Programlama Dilini Düzenle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-language-id">
                    <div class="form-group">
                        <label for="edit-language-name"><i class="fas fa-code mr-2 text-primary"></i>Dil Adı</label>
                        <input type="text" class="form-control form-control-lg" id="edit-language-name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> İptal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Software Field Modal -->
<div class="modal fade" id="addSoftwareFieldModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Yeni Yazılım Alanı Ekle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="type" value="software_field">
                    <div class="form-group">
                        <label for="add-field-name"><i class="fas fa-laptop-code mr-2 text-success"></i>Alan Adı</label>
                        <input type="text" class="form-control form-control-lg" id="add-field-name" name="name" 
                               placeholder="Örn: Web Geliştirme, Mobil Geliştirme..." required autofocus>
                        <small class="form-text text-muted">Yazılım alanının adını girin.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> İptal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Software Field Modal -->
<div class="modal fade" id="editSoftwareFieldModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Yazılım Alanını Düzenle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-field-id">
                    <div class="form-group">
                        <label for="edit-field-name"><i class="fas fa-laptop-code mr-2 text-success"></i>Alan Adı</label>
                        <input type="text" class="form-control form-control-lg" id="edit-field-name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> İptal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modern Card Styles */
.option-card {
    transition: all 0.3s ease;
    border-radius: 8px;
    cursor: pointer;
}

.option-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
}

.option-card.inactive {
    opacity: 0.6;
    background-color: #f8f9fa;
}

.option-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.option-card:hover .option-actions {
    opacity: 1;
}

.option-actions .btn-link {
    text-decoration: none;
    padding: 4px 8px;
}

.option-actions .btn-link:hover {
    background-color: rgba(0,0,0,0.05);
    border-radius: 4px;
}

/* Gradient Headers */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

/* Modal Enhancements */
.modal-content {
    border: none;
    border-radius: 12px;
}

.modal-header {
    border-radius: 12px 12px 0 0;
}

.modal-body {
    padding: 2rem;
}

.form-control-lg {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control-lg:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.15);
}

/* Responsive Grid */
@media (max-width: 768px) {
    .col-md-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .option-actions {
        opacity: 1;
    }
}

/* Empty State */
.text-center.py-5 {
    color: #6c757d;
}

.text-center.py-5 i {
    opacity: 0.3;
}

/* Button Improvements */
.btn-sm {
    border-radius: 6px;
    font-weight: 500;
}

.btn-link {
    border: none;
}

/* Card Body Padding */
.card-body.p-3 {
    padding: 1.25rem !important;
}

/* Alert Improvements */
.alert {
    border: none;
    border-radius: 8px;
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #28a745;
}

.alert-danger {
    border-left-color: #dc3545;
}
</style>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Edit Language button handler
$(document).on('click', '#languages-grid .edit-btn', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    $('#edit-language-id').val(id);
    $('#edit-language-name').val(name);
});

// Edit Software Field button handler
$(document).on('click', '#software-fields-grid .edit-btn', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    $('#edit-field-id').val(id);
    $('#edit-field-name').val(name);
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    $('.alert').fadeOut('slow', function() {
        $(this).remove();
    });
}, 5000);

// Clear modal forms when closed
$('.modal').on('hidden.bs.modal', function() {
    $(this).find('form')[0].reset();
});
</script>
</body>
</html>
