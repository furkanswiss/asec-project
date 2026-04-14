<?php
require_once '../db.php'; 

// 3. YETKİ KONTROLÜ
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['user_type'] !== 'corporate'){
    header("location: ../login.php");
    exit;
}
// Corporate Profile Page
require_once 'includes/config.php';

// Get corporate user data
$stmt = $pdo->prepare('SELECT * FROM corporate_users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: dashboard.php');
    exit;
}

$msg = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $tax_number = trim($_POST['tax_number'] ?? '');
        
        // Validation
        if (empty($company_name)) {
            $error = 'Şirket adı zorunludur!';
        } elseif (empty($contact_person)) {
            $error = 'İletişim kişisi zorunludur!';
        } else {
            $update_stmt = $pdo->prepare('UPDATE corporate_users SET company_name = ?, contact_person = ?, phone = ?, address = ?, tax_number = ? WHERE id = ?');
            $update_stmt->execute([$company_name, $contact_person, $phone, $address, $tax_number, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['user_name'] = $company_name;
            
            // Refresh user data
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            $msg = 'Profil başarıyla güncellendi!';
        }
    } catch (PDOException $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}
?>
<?php include 'corporate-header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include 'corporate-sidebar.php'; ?>
        <main class="main-content col-md-9 ml-sm-auto col-lg-10">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Profilim</h1>
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
            
            <div class="row">
                <div class="col-12 col-lg-8 mb-4 mb-lg-0">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-building"></i> Şirket Bilgileri</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="form-group">
                                    <label for="company_name">Şirket Adı <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?= htmlspecialchars($user['company_name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_person">İletişim Kişisi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                           value="<?= htmlspecialchars($user['contact_person'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">E-posta</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                                    <small class="form-text text-muted">E-posta adresi değiştirilemez.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Telefon</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="tax_number">Vergi Numarası</label>
                                    <input type="text" class="form-control" id="tax_number" name="tax_number" 
                                           value="<?= htmlspecialchars($user['tax_number'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Adres</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-block btn-lg mb-2">
                                        <i class="fas fa-save mr-2"></i>Kaydet
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary btn-block btn-lg">
                                        <i class="fas fa-times mr-2"></i>İptal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Hesap Bilgileri</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Kayıt Tarihi:</strong><br>
                                <small class="text-muted">
                                    <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?>
                                </small>
                            </div>
                            <div class="mb-3">
                                <strong>Son Güncelleme:</strong><br>
                                <small class="text-muted">
                                    <?= date('d.m.Y H:i', strtotime($user['updated_at'])) ?>
                                </small>
                            </div>
                            <hr>
                            <!-- <div class="mb-3">
                                <strong>Şifre Değiştirme:</strong><br>
                                <small class="text-muted">Şifrenizi değiştirmek için lütfen yönetici ile iletişime geçin.</small>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

