<!-- Corporate Sidebar (Menü) -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <div class="px-3 pb-2 d-md-none border-bottom border-secondary mb-2">
            <div class="text-white">
                <i class="fas fa-building mr-2"></i>
                <strong><?php echo htmlspecialchars(mb_substr($_SESSION['user_name'] ?? 'Kurumsal Kullanıcı', 0, 25)); ?></strong>
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>" href="profil.php">
                    <i class="fas fa-user"></i> Profilim
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'ilanlar-yonetim.php' || basename($_SERVER['PHP_SELF']) == 'ilan-ekle.php' || basename($_SERVER['PHP_SELF']) == 'ilan-duzenle.php') ? 'active' : ''; ?>" href="ilanlar-yonetim.php">
                    <i class="fas fa-briefcase"></i> İlanlarım
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cv-filtrele.php' ? 'active' : ''; ?>" href="cv-filtrele.php">
                    <i class="fas fa-search"></i> CV Filtrele
                </a>
            </li>
        </ul>
    </div>
</nav>

