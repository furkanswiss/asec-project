<?php
// Galeri Yönetim Paneli - Folder System with CRUD
session_start(); // 1. ÖNCE OTURUM BAŞLATILMALI

// Oturum kontrolü
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once '../db.php'; // 2. SONRA VERİTABANI BAĞLANSIN

// Ensure tables exist
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS gallery_folders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        baslik VARCHAR(255) NOT NULL,
        aciklama TEXT,
        kategori VARCHAR(100) NOT NULL DEFAULT "events",
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_kategori (kategori)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    
    // Add aciklama column if it doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM gallery_folders LIKE 'aciklama'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec('ALTER TABLE gallery_folders ADD COLUMN aciklama TEXT AFTER baslik');
    }
    
    $columns = $pdo->query("SHOW COLUMNS FROM galeri LIKE 'folder_id'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec('ALTER TABLE galeri ADD COLUMN folder_id INT NULL AFTER id, ADD INDEX idx_folder_id (folder_id)');
    }
} catch (PDOException $e) {
    // Tables might already exist, continue
}

$msg = '';
$msgType = 'success';

// Folder Operations
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create Folder
    if(isset($_POST['create_folder'])) {
        $baslik = trim($_POST['folder_baslik'] ?? '');
        $aciklama = trim($_POST['folder_aciklama'] ?? '');
        $kategori = $_POST['folder_kategori'] ?? 'events';
        
        if(!empty($baslik)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO gallery_folders (baslik, aciklama, kategori) VALUES (?, ?, ?)");
                $stmt->execute([$baslik, $aciklama, $kategori]);
                $msg = 'Klasör başarıyla oluşturuldu!';
            } catch(PDOException $e) {
                $msg = 'Klasör oluşturulurken hata: ' . $e->getMessage();
                $msgType = 'error';
            }
        } else {
            $msg = 'Klasör adı boş olamaz!';
            $msgType = 'error';
        }
    }
    
    // Edit Folder
    if(isset($_POST['edit_folder'])) {
        $id = intval($_POST['folder_id'] ?? 0);
        $baslik = trim($_POST['folder_baslik'] ?? '');
        $aciklama = trim($_POST['folder_aciklama'] ?? '');
        $kategori = $_POST['folder_kategori'] ?? 'events';
        
        if($id > 0 && !empty($baslik)) {
            try {
                $stmt = $pdo->prepare("UPDATE gallery_folders SET baslik = ?, aciklama = ?, kategori = ? WHERE id = ?");
                $stmt->execute([$baslik, $aciklama, $kategori, $id]);
                $msg = 'Klasör güncellendi!';
            } catch(PDOException $e) {
                $msg = 'Klasör güncellenirken hata: ' . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
    
    // Bulk Move Photos to Folder
    if(isset($_POST['bulk_move_photos'])) {
        $target_folder_id = intval($_POST['target_folder_id'] ?? 0);
        $photo_ids_json = $_POST['selected_photos'] ?? '[]';
        $photo_ids = json_decode($photo_ids_json, true) ?? [];
        
        if(!empty($photo_ids) && $target_folder_id > 0) {
            try {
                $placeholders = implode(',', array_fill(0, count($photo_ids), '?'));
                $stmt = $pdo->prepare("UPDATE galeri SET folder_id = ? WHERE id IN ($placeholders)");
                $params = array_merge([$target_folder_id], $photo_ids);
                $stmt->execute($params);
                $msg = count($photo_ids) . ' fotoğraf klasöre taşındı!';
            } catch(PDOException $e) {
                $msg = 'Fotoğraflar taşınırken hata: ' . $e->getMessage();
                $msgType = 'error';
            }
        } else {
            $msg = 'Lütfen en az bir fotoğraf seçin ve hedef klasörü belirleyin!';
            $msgType = 'error';
        }
    }
    
    // Remove Photos from Folder
    if(isset($_POST['remove_photos_from_folder'])) {
        $folder_id = intval($_POST['folder_id'] ?? 0);
        $photo_ids_json = $_POST['selected_photos'] ?? '[]';
        $photo_ids = json_decode($photo_ids_json, true) ?? [];
        
        if(!empty($photo_ids) && $folder_id > 0) {
            try {
                $placeholders = implode(',', array_fill(0, count($photo_ids), '?'));
                $stmt = $pdo->prepare("UPDATE galeri SET folder_id = NULL WHERE id IN ($placeholders) AND folder_id = ?");
                $params = array_merge($photo_ids, [$folder_id]);
                $stmt->execute($params);
                $msg = count($photo_ids) . ' fotoğraf klasörden kaldırıldı!';
            } catch(PDOException $e) {
                $msg = 'Fotoğraflar kaldırılırken hata: ' . $e->getMessage();
                $msgType = 'error';
            }
        } else {
            $msg = 'Lütfen en az bir fotoğraf seçin!';
            $msgType = 'error';
        }
    }
    
    // Delete Folder
    if(isset($_POST['delete_folder'])) {
        $id = intval($_POST['folder_id'] ?? 0);
        if($id > 0) {
            try {
                // Set photos folder_id to NULL
                $pdo->prepare("UPDATE galeri SET folder_id = NULL WHERE folder_id = ?")->execute([$id]);
                // Delete folder
                $pdo->prepare("DELETE FROM gallery_folders WHERE id = ?")->execute([$id]);
                $msg = 'Klasör silindi!';
            } catch(PDOException $e) {
                $msg = 'Klasör silinirken hata: ' . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
    
    // Add Photos to Folder
    if(isset($_POST['add_photos'])) {
        $folder_id = intval($_POST['selected_folder'] ?? 0);
    $basarili_yukleme = 0;
    $basarisiz_yukleme = 0;
    
    if(isset($_FILES['dosya']) && is_array($_FILES['dosya']['name'])) {
        $dosya_sayisi = count($_FILES['dosya']['name']);
        
        for($i = 0; $i < $dosya_sayisi; $i++) {
            if($_FILES['dosya']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['dosya']['name'][$i], PATHINFO_EXTENSION);
                $newName = uniqid('galeri_', true).'.'.$ext;
                $targetDir = '../images/gallery/';
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                $target = $targetDir . $newName;
                
                if(move_uploaded_file($_FILES['dosya']['tmp_name'][$i], $target)) {
                    $dosya_yolu = 'images/gallery/'.$newName;
                    
                        $stmt = $pdo->prepare("INSERT INTO galeri (folder_id, baslik, aciklama, kategori, tarih, dosya_yolu) VALUES (?, ?, ?, ?, ?, ?)");
                        $baslik = $_POST['baslik'] ?? 'Fotoğraf';
                        $aciklama = $_POST['aciklama'] ?? '';
                        $kategori = $_POST['kategori'] ?? 'events';
                        $tarih = $_POST['tarih'] ?? date('Y-m-d');
                        
                        $stmt->execute([$folder_id > 0 ? $folder_id : null, $baslik, $aciklama, $kategori, $tarih, $dosya_yolu]);
                    $basarili_yukleme++;
                } else {
                    $basarisiz_yukleme++;
                }
            } else {
                $basarisiz_yukleme++;
            }
        }
        
        if($basarili_yukleme > 0) {
            $msg = $basarili_yukleme . ' fotoğraf başarıyla yüklendi!';
            if($basarisiz_yukleme > 0) {
                $msg .= ' ' . $basarisiz_yukleme . ' fotoğraf yüklenemedi.';
            }
        } else {
            $msg = 'Fotoğraflar yüklenemedi!';
                $msgType = 'error';
            }
        } else {
            $msg = 'Lütfen en az bir fotoğraf seçin!';
            $msgType = 'error';
        }
    }
    
    // Delete Photo
    if(isset($_POST['delete_photo'])) {
        $id = intval($_POST['photo_id'] ?? 0);
        if($id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT dosya_yolu FROM galeri WHERE id = ?");
                $stmt->execute([$id]);
                $photo = $stmt->fetch();
                if($photo && file_exists('../' . $photo['dosya_yolu'])) {
                    unlink('../' . $photo['dosya_yolu']);
                }
                $pdo->prepare("DELETE FROM galeri WHERE id = ?")->execute([$id]);
                $msg = 'Fotoğraf silindi!';
            } catch(PDOException $e) {
                $msg = 'Fotoğraf silinirken hata: ' . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
}

// Get folders with photo counts
$folders = $pdo->query("
    SELECT f.*, 
           COUNT(g.id) as photo_count,
           (SELECT dosya_yolu FROM galeri WHERE folder_id = f.id ORDER BY id ASC LIMIT 1) as cover_image
    FROM gallery_folders f
    LEFT JOIN galeri g ON g.folder_id = f.id
    GROUP BY f.id
    ORDER BY f.olusturma_tarihi DESC
")->fetchAll();

// Get all photos (for standalone photos without folder)
$allPhotos = $pdo->query("SELECT * FROM galeri WHERE folder_id IS NULL ORDER BY tarih DESC")->fetchAll();

// Get folder for editing
$editFolder = null;
if(isset($_GET['edit_folder'])) {
    $id = intval($_GET['edit_folder']);
    $stmt = $pdo->prepare("SELECT * FROM gallery_folders WHERE id = ?");
    $stmt->execute([$id]);
    $editFolder = $stmt->fetch();
}

// AJAX: Get folder photos for manage modal
if(isset($_GET['get_folder_photos'])) {
    $folder_id = intval($_GET['get_folder_photos']);
    $photos = $pdo->prepare("SELECT * FROM galeri WHERE folder_id = ? ORDER BY id ASC");
    $photos->execute([$folder_id]);
    $photos = $photos->fetchAll();
    
    if(empty($photos)) {
        echo '<div class="alert alert-info text-center">Bu klasörde henüz fotoğraf bulunmuyor.</div>';
    } else {
        echo '<div class="folder-photos-manage-grid">';
        foreach($photos as $photo) {
            echo '<div class="photo-item-manage" data-photo-id="' . $photo['id'] . '">';
            echo '<input type="checkbox" class="photo-checkbox-manage" value="' . $photo['id'] . '" onchange="updatePhotoSelection()">';
            echo '<img src="../' . htmlspecialchars($photo['dosya_yolu']) . '" alt="' . htmlspecialchars($photo['baslik']) . '">';
            echo '<div class="photo-overlay-manage">';
            echo '<h6>' . htmlspecialchars($photo['baslik']) . '</h6>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    exit;
}
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
    <div class="row">
        <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <h2>Galeri Yönetimi</h2>

            <?php if($msg): ?>
                <div class="alert alert-<?= $msgType === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Folder Management Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Klasör Yönetimi</h4>
                    <button type="button" class="btn btn-primary" onclick="showCreateFolderModal()">
                        <i class="fas fa-folder-plus"></i> Yeni Klasör
                    </button>
                </div>
                <div class="card-body">
                    <div class="folders-grid" id="foldersGrid">
                        <?php foreach($folders as $folder): ?>
                            <div class="folder-card" data-folder-id="<?= $folder['id'] ?>">
                                <div class="folder-cover">
                                    <?php if($folder['cover_image']): ?>
                                        <img src="../<?= htmlspecialchars($folder['cover_image']) ?>" alt="<?= htmlspecialchars($folder['baslik']) ?>">
                                    <?php else: ?>
                                        <div class="folder-placeholder">
                                            <i class="fas fa-folder"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="folder-info">
                                    <h5><?= htmlspecialchars($folder['baslik']) ?></h5>
                                    <p class="folder-meta">
                                        <span><i class="fas fa-images"></i> <?= $folder['photo_count'] ?> Fotoğraf</span>
                                        <span><i class="fas fa-calendar"></i> <?= date('d.m.Y', strtotime($folder['olusturma_tarihi'])) ?></span>
                                    </p>
                                    <p class="folder-category">
                                        <span class="badge badge-info"><?= htmlspecialchars($folder['kategori']) ?></span>
                                    </p>
                                </div>
                                <div class="folder-actions">
                                    <button class="btn btn-sm btn-info" onclick="manageFolderPhotos(<?= $folder['id'] ?>, '<?= htmlspecialchars($folder['baslik'], ENT_QUOTES) ?>')" title="Fotoğrafları Yönet">
                                        <i class="fas fa-images"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars($folder['baslik'], ENT_QUOTES) ?>', '<?= htmlspecialchars($folder['aciklama'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($folder['kategori']) ?>')" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars($folder['baslik'], ENT_QUOTES) ?>')" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Photo Upload Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Fotoğraf Yükle</h4>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" id="photoUploadForm">
                        <div class="form-group">
                            <label>Klasör Seç (Opsiyonel)</label>
                            <select name="selected_folder" class="form-control">
                                <option value="0">Klasörsüz</option>
                                <?php foreach($folders as $folder): ?>
                                    <option value="<?= $folder['id'] ?>"><?= htmlspecialchars($folder['baslik']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Başlık</label>
                            <input type="text" name="baslik" class="form-control" placeholder="Fotoğraf Başlığı">
                        </div>
                        <div class="form-group">
                            <label>Açıklama</label>
                            <input type="text" name="aciklama" class="form-control" placeholder="Açıklama">
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" class="form-control">
                <option value="events">Etkinlik</option>
                <option value="workshops">Atölye</option>
                <option value="teams">Takım</option>
                <option value="other">Diğer</option>
            </select>
                        </div>
                        <div class="form-group">
                            <label>Tarih</label>
                            <input type="date" name="tarih" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="modern-upload-container" id="dropZone">
                            <div class="upload-content">
                    <i class="fas fa-cloud-upload-alt"></i>
                                <h5>Fotoğrafları buraya sürükleyip bırakın</h5>
                                <p>veya <span class="upload-link">dosya seçmek için tıklayın</span></p>
                                <p class="upload-hint">Birden fazla fotoğraf seçebilirsiniz</p>
                            </div>
                            <input type="file" name="dosya[]" id="fileInput" accept="image/*" multiple required>
                            <div id="filePreview" class="file-preview-grid"></div>
                        </div>
                        
                        <button type="submit" name="add_photos" class="btn btn-primary btn-lg mt-3">
                            <i class="fas fa-upload"></i> Fotoğrafları Yükle
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Standalone Photos (without folder) -->
            <?php if(count($allPhotos) > 0): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Klasörsüz Fotoğraflar (<?= count($allPhotos) ?>)</h4>
                    <div class="bulk-actions" id="bulkActions" style="display: none;">
                        <select id="targetFolderSelect" class="form-control form-control-sm d-inline-block" style="width: auto; margin-right: 10px;">
                            <option value="">Klasör Seçin</option>
                            <?php foreach($folders as $folder): ?>
                                <option value="<?= $folder['id'] ?>"><?= htmlspecialchars($folder['baslik']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <form method="post" id="bulkMoveForm" style="display: inline;">
                            <input type="hidden" name="target_folder_id" id="target_folder_id">
                            <input type="hidden" name="selected_photos" id="selected_photos_input">
                            <button type="submit" name="bulk_move_photos" class="btn btn-sm btn-primary">
                                <i class="fas fa-folder"></i> Seçilenleri Taşı
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                            <i class="fas fa-times"></i> Seçimi Temizle
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="photos-grid">
                        <?php foreach($allPhotos as $photo): ?>
                            <div class="photo-item-selectable" data-photo-id="<?= $photo['id'] ?>">
                                <input type="checkbox" class="photo-checkbox" value="<?= $photo['id'] ?>" onchange="updateBulkActions()">
                                <img src="../<?= htmlspecialchars($photo['dosya_yolu']) ?>" alt="<?= htmlspecialchars($photo['baslik']) ?>">
                                <div class="photo-overlay">
                                    <h6><?= htmlspecialchars($photo['baslik']) ?></h6>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                        <input type="hidden" name="photo_id" value="<?= $photo['id'] ?>">
                                        <button type="submit" name="delete_photo" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Create/Edit Folder Modal -->
<div class="modal fade" id="folderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="folderModalTitle">Yeni Klasör</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post" id="folderForm">
                <div class="modal-body">
                    <input type="hidden" name="folder_id" id="folder_id">
                    <div class="form-group">
                        <label>Klasör Adı</label>
                        <input type="text" name="folder_baslik" id="folder_baslik" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Açıklama</label>
                        <textarea name="folder_aciklama" id="folder_aciklama" class="form-control" rows="3" placeholder="Klasör açıklaması (opsiyonel)"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="folder_kategori" id="folder_kategori" class="form-control">
                            <option value="events">Etkinlik</option>
                            <option value="workshops">Atölye</option>
                            <option value="teams">Takım</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" name="create_folder" id="folderSubmitBtn" class="btn btn-primary">Oluştur</button>
                </div>
        </form>
        </div>
    </div>
</div>

<!-- Manage Folder Photos Modal -->
<div class="modal fade" id="managePhotosModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="managePhotosModalTitle">Klasör Fotoğraflarını Yönet</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post" id="removePhotosForm">
                <div class="modal-body">
                    <input type="hidden" name="folder_id" id="manage_folder_id">
                    <input type="hidden" name="selected_photos" id="selected_photos_manage" value="[]">
                    <div id="folderPhotosContainer" class="folder-photos-manage-grid">
                        <!-- Photos will be loaded here via AJAX -->
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                    <div class="bulk-actions-manage" id="bulkActionsManage" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span id="selectedCount">0 fotoğraf seçildi</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="clearPhotoSelection()">
                                    <i class="fas fa-times"></i> Seçimi Temizle
                                </button>
                                <button type="submit" name="remove_photos_from_folder" class="btn btn-sm btn-danger">
                                    <i class="fas fa-folder-minus"></i> Klasörden Kaldır
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </form>
        </div>
    </div>
</div>
        
        <style>
.folders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.folder-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
.folder-card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.folder-cover {
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #9370db 0%, #6A0DAD 100%);
    position: relative;
}

.folder-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.folder-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 4rem;
    opacity: 0.7;
}

.folder-info {
    padding: 1rem;
}

.folder-info h5 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: #333;
}

.folder-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #666;
    margin: 0.5rem 0;
}

.folder-meta span {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.folder-category {
    margin: 0.5rem 0 0 0;
}

.folder-actions {
    padding: 0.5rem 1rem 1rem;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.modern-upload-container {
    border: 3px dashed #9370db;
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
    position: relative;
    margin: 1rem 0;
}

.modern-upload-container:hover,
.modern-upload-container.dragover {
    background: #e6e6fa;
    border-color: #6A0DAD;
    transform: scale(1.01);
}

.upload-content i {
    font-size: 4rem;
    color: #9370db;
    margin-bottom: 1rem;
}

.upload-content h5 {
    color: #333;
    margin-bottom: 0.5rem;
}

.upload-content p {
    color: #666;
    margin: 0.3rem 0;
}

.upload-link {
    color: #9370db;
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
}

.upload-hint {
    font-size: 0.85rem;
    color: #999;
            }
            
            #fileInput {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                opacity: 0;
                cursor: pointer;
            }
            
.file-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
            }
            
            .file-preview-item {
                position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
                overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .file-preview-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
.file-preview-item .remove-preview {
                position: absolute;
                top: 5px;
                right: 5px;
    background: rgba(255,255,255,0.9);
    border: none;
                border-radius: 50%;
    width: 28px;
    height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                color: #dc3545;
    font-size: 0.9rem;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.photo-item,
.photo-item-selectable {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.photo-item-selectable {
    cursor: pointer;
}

.photo-item-selectable.selected {
    border: 3px solid #9370db;
    box-shadow: 0 0 0 2px rgba(147, 112, 219, 0.3);
}

.photo-checkbox {
    position: absolute;
    top: 10px;
    left: 10px;
    width: 24px;
    height: 24px;
    z-index: 10;
    cursor: pointer;
    accent-color: #9370db;
}

.photo-item img,
.photo-item-selectable img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    padding: 1rem;
    color: #fff;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-item:hover .photo-overlay,
.photo-item-selectable:hover .photo-overlay {
    opacity: 1;
}

.photo-overlay h6 {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.photo-item-selectable:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Manage Folder Photos Modal Styles */
.folder-photos-manage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    max-height: 500px;
    overflow-y: auto;
    padding: 1rem;
}

.photo-item-manage {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.photo-item-manage.selected {
    border: 3px solid #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.3);
}

.photo-checkbox-manage {
    position: absolute;
    top: 10px;
    left: 10px;
    width: 24px;
    height: 24px;
    z-index: 10;
    cursor: pointer;
    accent-color: #dc3545;
}

.photo-item-manage img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-overlay-manage {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    padding: 0.8rem;
    color: #fff;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-item-manage:hover .photo-overlay-manage {
    opacity: 1;
}

.photo-overlay-manage h6 {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 500;
}

.photo-item-manage:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
        </style>
        
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
        <script>
function showCreateFolderModal() {
    document.getElementById('folderModalTitle').textContent = 'Yeni Klasör';
    document.getElementById('folder_id').value = '';
    document.getElementById('folder_baslik').value = '';
    document.getElementById('folder_aciklama').value = '';
    document.getElementById('folder_kategori').value = 'events';
    document.getElementById('folderSubmitBtn').name = 'create_folder';
    document.getElementById('folderSubmitBtn').textContent = 'Oluştur';
    $('#folderModal').modal('show');
}

function editFolder(id, baslik, aciklama, kategori) {
    document.getElementById('folderModalTitle').textContent = 'Klasör Düzenle';
    document.getElementById('folder_id').value = id;
    document.getElementById('folder_baslik').value = baslik;
    document.getElementById('folder_aciklama').value = aciklama || '';
    document.getElementById('folder_kategori').value = kategori;
    document.getElementById('folderSubmitBtn').name = 'edit_folder';
    document.getElementById('folderSubmitBtn').textContent = 'Güncelle';
    $('#folderModal').modal('show');
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.photo-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    
    if(checkboxes.length > 0) {
        bulkActions.style.display = 'block';
    } else {
        bulkActions.style.display = 'none';
    }
}

function clearSelection() {
    document.querySelectorAll('.photo-checkbox').forEach(cb => {
        cb.checked = false;
        const item = cb.closest('.photo-item-selectable');
        if(item) item.classList.remove('selected');
    });
    updateBulkActions();
}

// Bulk move form submission
document.addEventListener('DOMContentLoaded', function() {
    const bulkMoveForm = document.getElementById('bulkMoveForm');
    if(bulkMoveForm) {
        bulkMoveForm.addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.photo-checkbox:checked');
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            const targetFolder = document.getElementById('targetFolderSelect').value;
            
            if(selectedIds.length === 0) {
                e.preventDefault();
                alert('Lütfen en az bir fotoğraf seçin!');
                return false;
            }
            
            if(!targetFolder) {
                e.preventDefault();
                alert('Lütfen hedef klasörü seçin!');
                return false;
            }
            
            document.getElementById('target_folder_id').value = targetFolder;
            document.getElementById('selected_photos_input').value = JSON.stringify(selectedIds);
        });
    }
    
    // Update selected state on checkbox change
    document.querySelectorAll('.photo-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const item = this.closest('.photo-item-selectable');
            if(this.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
            updateBulkActions();
        });
    });
    
    // Handle manage photos modal form submission
    const removePhotosForm = document.getElementById('removePhotosForm');
    if(removePhotosForm) {
        removePhotosForm.addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('#folderPhotosContainer .photo-checkbox-manage:checked');
            if(checkboxes.length === 0) {
                e.preventDefault();
                alert('Lütfen en az bir fotoğraf seçin!');
                return false;
            }
            if(!confirm('Seçili ' + checkboxes.length + ' fotoğraf klasörden kaldırılacak. Devam etmek istiyor musunuz?')) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Update photo selection when checkboxes change in manage modal (delegated event)
    $(document).on('change', '#folderPhotosContainer .photo-checkbox-manage', function() {
        const item = this.closest('.photo-item-manage');
        if(this.checked) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
        updatePhotoSelection();
    });
});

function manageFolderPhotos(folderId, folderName) {
    document.getElementById('managePhotosModalTitle').textContent = 'Fotoğrafları Yönet: ' + folderName;
    document.getElementById('manage_folder_id').value = folderId;
    
    // Load photos via AJAX
    const container = document.getElementById('folderPhotosContainer');
    container.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Yükleniyor...</span></div></div>';
    
    // Fetch photos
    fetch('?get_folder_photos=' + folderId)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            updatePhotoSelection();
        })
        .catch(error => {
            container.innerHTML = '<div class="alert alert-danger">Fotoğraflar yüklenirken hata oluştu.</div>';
        });
    
    $('#managePhotosModal').modal('show');
}

function updatePhotoSelection() {
    const checkboxes = document.querySelectorAll('#folderPhotosContainer .photo-checkbox-manage:checked');
    const bulkActions = document.getElementById('bulkActionsManage');
    const selectedCount = document.getElementById('selectedCount');
    const hiddenInput = document.getElementById('selected_photos_manage');
    
    if(checkboxes.length > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = checkboxes.length + ' fotoğraf seçildi';
        
        // Update hidden input with selected photo IDs
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        if(hiddenInput) {
            hiddenInput.value = JSON.stringify(selectedIds);
        }
    } else {
        bulkActions.style.display = 'none';
        if(hiddenInput) {
            hiddenInput.value = '[]';
        }
    }
}

function clearPhotoSelection() {
    document.querySelectorAll('#folderPhotosContainer .photo-checkbox-manage').forEach(cb => {
        cb.checked = false;
        const item = cb.closest('.photo-item-manage');
        if(item) item.classList.remove('selected');
    });
    updatePhotoSelection();
}

function deleteFolder(id, baslik) {
    if(confirm('"' + baslik + '" klasörünü silmek istediğinize emin misiniz? Klasördeki fotoğraflar silinmeyecek, sadece klasör kaldırılacak.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="folder_id" value="' + id + '">' +
                        '<input type="hidden" name="delete_folder" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Drag & Drop Upload
            document.addEventListener('DOMContentLoaded', function() {
                const dropZone = document.getElementById('dropZone');
                const fileInput = document.getElementById('fileInput');
                const filePreview = document.getElementById('filePreview');
    let selectedFiles = [];
                
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                });
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
    });
    
                dropZone.addEventListener('drop', handleDrop, false);
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
        const files = Array.from(dt.files);
        addFiles(files);
    }
    
    fileInput.addEventListener('change', function() {
        addFiles(Array.from(this.files));
    });
    
    function addFiles(files) {
        files.forEach(file => {
            if(file.type.startsWith('image/')) {
                selectedFiles.push(file);
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const previewItem = document.createElement('div');
                                    previewItem.className = 'file-preview-item';
                    previewItem.dataset.fileName = file.name;
                                    
                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-preview';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.onclick = function() {
                        selectedFiles = selectedFiles.filter(f => f.name !== file.name);
                        previewItem.remove();
                        updateFileInput();
                    };
                                    
                                    previewItem.appendChild(img);
                    previewItem.appendChild(removeBtn);
                                    filePreview.appendChild(previewItem);
                };
                                reader.readAsDataURL(file);
                            }
        });
        updateFileInput();
    }
    
    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    dropZone.addEventListener('click', function(e) {
        if(e.target === dropZone || e.target.closest('.upload-content')) {
                    fileInput.click();
        }
                });
            });
        </script>
</body>
</html>
