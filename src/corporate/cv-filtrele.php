<?php
// Corporate CV Filtering Page
require_once 'includes/config.php';

// Ensure user_cv_profiles table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS user_cv_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    major VARCHAR(255) DEFAULT NULL,
    languages TEXT DEFAULT NULL,
    software_fields TEXT DEFAULT NULL,
    companies TEXT DEFAULT NULL,
    cv_filename VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

// Filter options - Fetch from database
// Ensure cv_options table exists
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

// Fetch active options from database
$languageOptions = $pdo->query('SELECT name FROM cv_options WHERE type = "language" AND is_active = 1 ORDER BY display_order ASC, name ASC')->fetchAll(PDO::FETCH_COLUMN);
$softwareFieldOptions = $pdo->query('SELECT name FROM cv_options WHERE type = "software_field" AND is_active = 1 ORDER BY display_order ASC, name ASC')->fetchAll(PDO::FETCH_COLUMN);

// Fallback to default values if database is empty
if (empty($languageOptions)) {
    $languageOptions = [
        'C', 'C++', 'C#', 'Java', 'Python', 'JavaScript', 'TypeScript', 'PHP', 'Go', 'Rust', 'Kotlin', 'Swift', 'R', 'MATLAB', 'SQL'
    ];
}
if (empty($softwareFieldOptions)) {
    $softwareFieldOptions = [
        'Web Geliştirme', 'Mobil Geliştirme', 'Veri Bilimi', 'Makine Öğrenmesi', 'Yapay Zeka', 'DevOps', 'Siber Güvenlik', 'Oyun Geliştirme', 'Gömülü Sistemler', 'Bulut', 'Yazılım Testi', 'UI/UX', 'AR/VR'
    ];
}

// Get distinct universities and departments for filters
$universities = $pdo->query("SELECT DISTINCT university FROM users WHERE university IS NOT NULL AND university != '' ORDER BY university")->fetchAll(PDO::FETCH_COLUMN);
$departments = $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$majors = $pdo->query("SELECT DISTINCT major FROM user_cv_profiles WHERE major IS NOT NULL AND major != '' ORDER BY major")->fetchAll(PDO::FETCH_COLUMN);

// Get filter parameters
$selectedLanguages = $_GET['languages'] ?? [];
$selectedSoftwareFields = $_GET['software_fields'] ?? [];
$selectedUniversity = $_GET['university'] ?? '';
$selectedDepartment = $_GET['department'] ?? '';
$selectedMajor = $_GET['major'] ?? '';
$selectedCompany = trim($_GET['company'] ?? '');
$searchName = trim($_GET['search_name'] ?? '');

// Build query
$whereConditions = [];
$params = [];

// Only show users with CVs
$whereConditions[] = "cv.cv_filename IS NOT NULL AND cv.cv_filename != ''";

// Filter by name
if (!empty($searchName)) {
    $whereConditions[] = "u.name LIKE ?";
    $params[] = "%$searchName%";
}

// Filter by university
if (!empty($selectedUniversity)) {
    $whereConditions[] = "u.university = ?";
    $params[] = $selectedUniversity;
}

// Filter by department
if (!empty($selectedDepartment)) {
    $whereConditions[] = "u.department = ?";
    $params[] = $selectedDepartment;
}

// Filter by major
if (!empty($selectedMajor)) {
    $whereConditions[] = "cv.major = ?";
    $params[] = $selectedMajor;
}

// Filter by company
if (!empty($selectedCompany)) {
    $whereConditions[] = "cv.companies LIKE ?";
    $params[] = "%$selectedCompany%";
}

// Filter by programming languages (AND MANTIĞI İLE GÜNCELLENDİ)
if (!empty($selectedLanguages) && is_array($selectedLanguages)) {
    $langConditions = [];
    foreach ($selectedLanguages as $lang) {
        $langConditions[] = "cv.languages LIKE ?";
        $params[] = "%\"$lang\"%";
    }
    if (!empty($langConditions)) {
        // BURASI DEĞİŞTİ: "OR" yerine "AND" kullanıldı
        $whereConditions[] = "(" . implode(" AND ", $langConditions) . ")";
    }
}

// Filter by software fields (AND MANTIĞI İLE GÜNCELLENDİ)
if (!empty($selectedSoftwareFields) && is_array($selectedSoftwareFields)) {
    $fieldConditions = [];
    foreach ($selectedSoftwareFields as $field) {
        $fieldConditions[] = "cv.software_fields LIKE ?";
        $params[] = "%\"$field\"%";
    }
    if (!empty($fieldConditions)) {
        // BURASI DEĞİŞTİ: "OR" yerine "AND" kullanıldı
        $whereConditions[] = "(" . implode(" AND ", $fieldConditions) . ")";
    }
}

// Build final query
$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
$sql = "SELECT DISTINCT u.id, u.name, u.email, u.phone, u.university, u.department, u.class, 
               u.linkedin, u.instagram, u.bio, u.achievements,
               cv.major, cv.languages, cv.software_fields, cv.companies, cv.cv_filename
        FROM users u
        INNER JOIN user_cv_profiles cv ON u.id = cv.user_id
        $whereClause
        ORDER BY u.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Process candidates to decode JSON fields
foreach ($candidates as &$candidate) {
    $candidate['languages'] = $candidate['languages'] ? json_decode($candidate['languages'], true) : [];
    $candidate['software_fields'] = $candidate['software_fields'] ? json_decode($candidate['software_fields'], true) : [];
    $candidate['companies'] = $candidate['companies'] ? json_decode($candidate['companies'], true) : [];
}
unset($candidate);
?>
<?php include 'corporate-header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include 'corporate-sidebar.php'; ?>
        <main class="main-content col-md-9 ml-sm-auto col-lg-10">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">CV Filtreleme</h1>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filtreler</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="cv-filtrele.php">
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="search_name">Ad Soyad Ara</label>
                                <input type="text" class="form-control form-control-lg" id="search_name" name="search_name" 
                                       value="<?= htmlspecialchars($searchName) ?>" placeholder="İsim ile ara...">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="university">Üniversite</label>
                                <select class="form-control form-control-lg" id="university" name="university">
                                    <option value="">Tümü</option>
                                    <?php foreach($universities as $uni): ?>
                                        <option value="<?= htmlspecialchars($uni) ?>" <?= $selectedUniversity === $uni ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($uni) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="department">Bölüm</label>
                                <select class="form-control form-control-lg" id="department" name="department">
                                    <option value="">Tümü</option>
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept) ?>" <?= $selectedDepartment === $dept ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="major">Ana Dal / Uzmanlık</label>
                                <select class="form-control form-control-lg" id="major" name="major">
                                    <option value="">Tümü</option>
                                    <?php foreach($majors as $maj): ?>
                                        <option value="<?= htmlspecialchars($maj) ?>" <?= $selectedMajor === $maj ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($maj) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label>Programlama Dilleri <small class="text-muted">(Seçilenlerin HEPSİNE sahip olanlar listelenir)</small></label>
                                <div class="filter-chips">
                                    <?php foreach($languageOptions as $lang): ?>
                                        <label class="filter-chip">
                                            <input type="checkbox" name="languages[]" value="<?= htmlspecialchars($lang) ?>" 
                                                   <?= in_array($lang, $selectedLanguages) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($lang) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label>Yazılım Alanları <small class="text-muted">(Seçilenlerin HEPSİNE sahip olanlar listelenir)</small></label>
                                <div class="filter-chips">
                                    <?php foreach($softwareFieldOptions as $field): ?>
                                        <label class="filter-chip">
                                            <input type="checkbox" name="software_fields[]" value="<?= htmlspecialchars($field) ?>" 
                                                   <?= in_array($field, $selectedSoftwareFields) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($field) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="company">Çalıştığı Şirket</label>
                                <input type="text" class="form-control form-control-lg" id="company" name="company" 
                                       value="<?= htmlspecialchars($selectedCompany) ?>" placeholder="Şirket adı ile ara...">
                            </div>
                        </div>

                        <div class="form-group d-flex flex-column flex-md-row align-items-md-center" style="gap: 16px;">
                            <button type="submit" class="btn btn-primary btn-lg" style="min-width: 180px; min-height: 54px; flex: 0 0 auto;">
                                <i class="fas fa-search mr-2"></i>Filtrele
                            </button>
                            <a href="cv-filtrele.php" class="btn btn-secondary btn-lg" style="min-width: 180px; min-height: 54px; flex: 0 0 auto;">
                                <i class="fas fa-times mr-2"></i>Temizle
                            </a>
                            <span class="text-muted text-center text-md-left mt-2 mt-md-0" style="flex: 1;">
                                <strong><?= count($candidates) ?></strong> aday bulundu
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Bulunan Adaylar</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($candidates)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Kriterlerinize uygun aday bulunamadı.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach($candidates as $candidate): ?>
                                <div class="col-12 col-md-6 mb-4">
                                    <div class="card candidate-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($candidate['name']) ?>
                                            </h5>
                                            
                                            <div class="candidate-info mb-3">
                                                <?php if($candidate['email']): ?>
                                                    <div class="mb-2">
                                                        <i class="fas fa-envelope"></i> 
                                                        <a href="mailto:<?= htmlspecialchars($candidate['email']) ?>">
                                                            <?= htmlspecialchars($candidate['email']) ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if($candidate['phone']): ?>
                                                    <div class="mb-2">
                                                        <i class="fas fa-phone"></i> <?= htmlspecialchars($candidate['phone']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if($candidate['university']): ?>
                                                    <div class="mb-2">
                                                        <i class="fas fa-university"></i> <?= htmlspecialchars($candidate['university']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if($candidate['department']): ?>
                                                    <div class="mb-2">
                                                        <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($candidate['department']) ?>
                                                        <?php if($candidate['class']): ?>
                                                            - <?= htmlspecialchars($candidate['class']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if($candidate['major']): ?>
                                                    <div class="mb-2">
                                                        <i class="fas fa-certificate"></i> <?= htmlspecialchars($candidate['major']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if(!empty($candidate['languages'])): ?>
                                                <div class="mb-2">
                                                    <strong>Diller:</strong>
                                                    <div class="tag-list">
                                                        <?php foreach($candidate['languages'] as $lang): ?>
                                                            <span class="badge badge-primary"><?= htmlspecialchars($lang) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($candidate['software_fields'])): ?>
                                                <div class="mb-2">
                                                    <strong>Alanlar:</strong>
                                                    <div class="tag-list">
                                                        <?php foreach($candidate['software_fields'] as $field): ?>
                                                            <span class="badge badge-success"><?= htmlspecialchars($field) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($candidate['companies'])): ?>
                                                <div class="mb-2">
                                                    <strong>Çalıştığı Şirketler:</strong>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach($candidate['companies'] as $comp): ?>
                                                            <li><i class="fas fa-building"></i> <?= htmlspecialchars($comp) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>


                                            <div class="mt-3 d-flex flex-column gap-2">
                                                <a href="cv-goruntule.php?user_id=<?= $candidate['id'] ?>" 
                                                   class="btn btn-primary btn-block btn-lg">
                                                    <i class="fas fa-file-pdf mr-2"></i>CV'yi Görüntüle
                                                </a>
                                                <?php if($candidate['linkedin']): ?>
                                                    <a href="<?= htmlspecialchars($candidate['linkedin']) ?>" target="_blank" class="btn btn-outline-primary btn-block btn-sm">
                                                        <i class="fab fa-linkedin mr-2"></i>LinkedIn
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Filter and Clear button styling */
.form-group .btn-lg {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 24px;
    font-size: 1rem;
    font-weight: 500;
}

@media (max-width: 767px) {
    .form-group .btn-lg {
        width: 100%;
    }
}

.filter-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
    max-height: 200px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f8f9fa;
}

.filter-chip {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    background: #fff;
    border: 1px solid #9370db;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    white-space: nowrap;
}

.filter-chip:hover {
    background: #9370db;
    color: #fff;
}

.filter-chip input[type="checkbox"] {
    margin-right: 6px;
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.filter-chip input[type="checkbox"]:checked + span {
    font-weight: 600;
}

.filter-chip:has(input[type="checkbox"]:checked) {
    background: #9370db;
    color: #fff;
}

.candidate-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e0e0e0;
}

.candidate-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 5px;
}

.candidate-info div {
    font-size: 0.95rem;
    color: #555;
    word-break: break-word;
}

.candidate-info i {
    width: 20px;
    color: #9370db;
}

/* Mobile Styles */
@media (max-width: 768px) {
    .filter-chips {
        max-height: 150px;
        padding: 8px;
        gap: 6px;
    }
    
    .filter-chip {
        padding: 6px 10px;
        font-size: 0.85rem;
    }
    
    .candidate-card {
        margin-bottom: 15px;
    }
    
    .candidate-card .card-body {
        padding: 15px;
    }
    
    .candidate-info div {
        font-size: 0.875rem;
        margin-bottom: 8px;
    }
    
    .tag-list .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    
    .btn-block {
        margin-bottom: 10px;
    }
}

@media (max-width: 576px) {
    .filter-chips {
        max-height: 120px;
    }
    
    .filter-chip {
        padding: 5px 8px;
        font-size: 0.8rem;
    }
    
    .candidate-card .card-title {
        font-size: 1.1rem;
    }
    
    .form-control-lg {
        font-size: 16px; /* Prevents zoom on iOS */
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>