<?php
require_once 'includes/config.php';
// Oturum kontrolü
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
include 'admin-header.php';
include 'sidebar.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Geçersiz üye ID.');
}

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$uye = $result->fetch_assoc();
if (!$uye) {
    die('Üye bulunamadı.');
}

// CV bilgisini ayrı tablodan çek (tüm profil verileri ile)
$cv_sql = "SELECT major, languages, software_fields, companies, cv_filename FROM user_cv_profiles WHERE user_id = ?";
$cv_stmt = $conn->prepare($cv_sql);
$cv_stmt->bind_param('i', $id);
$cv_stmt->execute();
$cv_result = $cv_stmt->get_result();
$cv_data = $cv_result->fetch_assoc();

// JSON verilerini decode et
$languages = [];
$software_fields = [];
$companies = [];

if ($cv_data) {
    if (!empty($cv_data['languages'])) {
        $decoded = json_decode($cv_data['languages'], true);
        $languages = is_array($decoded) ? $decoded : [];
    }
    if (!empty($cv_data['software_fields'])) {
        $decoded = json_decode($cv_data['software_fields'], true);
        $software_fields = is_array($decoded) ? $decoded : [];
    }
    if (!empty($cv_data['companies'])) {
        $decoded = json_decode($cv_data['companies'], true);
        $companies = is_array($decoded) ? $decoded : [];
    }
}
?>
<main class="container-fluid">
    <div class="row">
        <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="font-weight-bold mb-0" style="color:#2d3a8c;">Üye Detay</h2>
                <a href="uyeler-yonetim.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Geri Dön</a>
            </div>
            <div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <?php
                $avatar_path = '../uploads/avatar/' . htmlspecialchars($uye['avatar']);
                $default_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($uye['name']);
                if($uye['avatar'] && file_exists($avatar_path)) {
                    echo '<img src="' . $avatar_path . '" alt="Avatar" class="rounded-circle border" style="width:100px;height:100px;object-fit:cover;">';
                } else {
                    echo '<img src="' . $default_avatar . '" alt="Avatar" class="rounded-circle border" style="width:100px;height:100px;object-fit:cover;">';
                }
                ?>
            </div>
            <div class="col-md-9">
                <h4 class="font-weight-bold mb-2"><?php echo htmlspecialchars($uye['name']); ?></h4>
            </div>
        </div>
        <ul class="list-group list-group-flush mb-2">
            <li class="list-group-item px-0 py-1"><strong>Email:</strong> <?php echo htmlspecialchars($uye['email']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Telefon:</strong> <?php echo htmlspecialchars($uye['phone']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Üniversite:</strong> <?php echo htmlspecialchars($uye['university']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Bölüm:</strong> <?php echo htmlspecialchars($uye['department']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Sınıf:</strong> <?php echo htmlspecialchars($uye['class']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Doğum Tarihi:</strong> <?php echo htmlspecialchars($uye['birthdate']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Kayıt Tarihi:</strong> <?php echo htmlspecialchars($uye['created_at']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Adres:</strong> <?php echo htmlspecialchars($uye['address']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Biyografi:</strong> <?php echo nl2br(htmlspecialchars($uye['bio'])); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Instagram:</strong> <?php echo htmlspecialchars($uye['instagram']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>LinkedIn:</strong> <?php echo htmlspecialchars($uye['linkedin']); ?></li>
            <li class="list-group-item px-0 py-1"><strong>Başarılar:</strong> <?php echo nl2br(htmlspecialchars($uye['achievements'])); ?></li>
            <li class="list-group-item px-0 py-1"><strong>CV:</strong> <?php
                if ($cv_data && !empty($cv_data['cv_filename'])) {
                    $cv_path = '../uploads/cv/' . htmlspecialchars($cv_data['cv_filename']);
                    if (file_exists($cv_path)) {
                        echo '<a href="' . $cv_path . '" target="_blank" class="btn btn-sm btn-outline-primary ml-2"><i class="fas fa-file-alt"></i> CV Görüntüle</a>';
                    } else {
                        echo '<span class="text-danger ml-2">CV dosyası bulunamadı!</span>';
                    }
                } else {
                    echo '<span class="text-muted ml-2">CV Yok</span>';
                }
            ?></li>
        </ul>
    </div>
</div>

            <!-- Professional Profile Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-briefcase"></i> Profesyonel Profil</h5>
                </div>
                <div class="card-body">
                    <!-- Major/Field of Study -->
                    <?php if (!empty($cv_data) && !empty($cv_data['major'])): ?>
                    <div class="mb-4">
                        <h6 class="text-muted mb-2"><i class="fas fa-graduation-cap"></i> Uzmanlık Alanı</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($cv_data['major']); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Programming Languages -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2"><i class="fas fa-code"></i> Programlama Dilleri</h6>
                        <?php if (!empty($languages)): ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($languages as $lang): ?>
                                    <span class="badge badge-primary mr-2 mb-2" style="background-color: #2d3a8c; padding: 8px 12px; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($lang); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0"><em>Programlama dili bilgisi girilmemiş</em></p>
                        <?php endif; ?>
                    </div>

                    <!-- Software/Interest Areas -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2"><i class="fas fa-laptop-code"></i> Yazılım/İlgi Alanları</h6>
                        <?php if (!empty($software_fields)): ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($software_fields as $field): ?>
                                    <span class="badge badge-info mr-2 mb-2" style="background-color: #17a2b8; padding: 8px 12px; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($field); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0"><em>Yazılım alanı bilgisi girilmemiş</em></p>
                        <?php endif; ?>
                    </div>

                    <!-- Work Experience / Companies -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2"><i class="fas fa-building"></i> Çalışma Deneyimi</h6>
                        <?php if (!empty($companies)): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($companies as $company): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        <strong><?php echo htmlspecialchars($company); ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0"><em>Çalışma deneyimi bilgisi girilmemiş</em></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

                <a href="uyeler-yonetim.php" class="btn btn-secondary mt-4"><i class="fas fa-arrow-left"></i> Geri Dön</a>
            </div>
        </div>
                </div>
        </div>
    </main>
</body>
</html>
