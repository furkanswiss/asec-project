<!-- Ortak Admin Sidebar (Menü) -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="uyeler-yonetim.php">
                    <i class="fas fa-users"></i> Üyeler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cv-filtrele.php' ? 'active' : ''; ?>" href="cv-filtrele.php">
                    <i class="fas fa-search"></i> CV Filtreleme
                </a>
            </li>
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $yonetim_pages = [
                'blog-yonetim.php', 'blog-ekle.php', 'blog-duzenle.php',
                'etkinlikler-yonetim.php', 'etkinlik-ekle.php', 'etkinlik-duzenle.php',
                'duyurular-yonetim.php', 'duyuru-ekle.php', 'duyuru-duzenle.php',
                'ilanlar-yonetim.php', 'ilan-ekle.php', 'ilan-duzenle.php',
                'galeri-yonetim.php', 'galeri-duzenle.php',
                'sponsor-yonetim.php',
                'bilgi-yonetim.php', 'bilgi-ekle.php'
            ];
            $is_yonetim_active = in_array($current_page, $yonetim_pages);
            $is_yonetim_expanded = $is_yonetim_active ? 'show' : '';
            ?>
            <li class="nav-item">
                <a class="nav-link yonetim-toggle <?php echo $is_yonetim_active ? 'active' : ''; ?>" href="#" onclick="toggleYonetim(event)">
                    <i class="fas fa-cogs"></i> Yönetim
                    <i class="fas fa-chevron-down float-right yonetim-chevron" style="transition: transform 0.3s;"></i>
                </a>
                <ul class="nav flex-column submenu yonetim-submenu <?php echo $is_yonetim_expanded; ?>" style="<?php echo $is_yonetim_expanded ? '' : 'display: none;'; ?>">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (in_array($current_page, ['blog-yonetim.php', 'blog-ekle.php', 'blog-duzenle.php'])) ? 'active' : ''; ?>" href="blog-yonetim.php">
                            <i class="fas fa-blog"></i> Blog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (in_array($current_page, ['etkinlikler-yonetim.php', 'etkinlik-ekle.php', 'etkinlik-duzenle.php'])) ? 'active' : ''; ?>" href="etkinlikler-yonetim.php">
                            <i class="fas fa-calendar-alt"></i> Etkinlikler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (in_array($current_page, ['duyurular-yonetim.php', 'duyuru-ekle.php', 'duyuru-duzenle.php'])) ? 'active' : ''; ?>" href="duyurular-yonetim.php">
                            <i class="fas fa-bullhorn"></i> Duyurular
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (in_array($current_page, ['ilanlar-yonetim.php', 'ilan-ekle.php', 'ilan-duzenle.php'])) ? 'active' : ''; ?>" href="ilanlar-yonetim.php">
                            <i class="fas fa-briefcase"></i> İlanlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (in_array($current_page, ['galeri-yonetim.php', 'galeri-duzenle.php'])) ? 'active' : ''; ?>" href="galeri-yonetim.php">
                            <i class="fas fa-image"></i> Galeri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'sponsor-yonetim.php') ? 'active' : ''; ?>" href="sponsor-yonetim.php">
                            <i class="fas fa-handshake"></i> Sponsorlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (in_array($current_page, ['bilgi-yonetim.php', 'bilgi-ekle.php'])) ? 'active' : ''; ?>" href="bilgi-yonetim.php">
                            <i class="fas fa-info-circle"></i> Bilgilendirme
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="iletisim.php">
                    <i class="fas fa-envelope"></i> İletişim
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kurumsal-istekler.php' ? 'active' : ''; ?>" href="kurumsal-istekler.php">
                    <i class="fas fa-building"></i> Kurumsal İstekler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bireysel-istekler.php' ? 'active' : ''; ?>" href="bireysel-istekler.php">
                    <i class="fas fa-user"></i> Bireysel İstekler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ilan-istekleri.php' ? 'active' : ''; ?>" href="ilan-istekleri.php">
                    <i class="fas fa-file-alt"></i> İlan İstekleri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cv-ayarlari.php' ? 'active' : ''; ?>" href="cv-ayarlari.php">
                    <i class="fas fa-cog"></i> CV Ayarları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'board-yonetim.php' || basename($_SERVER['PHP_SELF']) == 'board-ekle.php' || basename($_SERVER['PHP_SELF']) == 'board-duzenle.php') ? 'active' : ''; ?>" href="board-yonetim.php">
                    <i class="fas fa-users-cog"></i> Yönetim Kurulu
                </a>
            </li>
        </ul>
    </div>
</nav>
<style>
.submenu {
    padding-left: 20px;
    background-color: rgba(0,0,0,0.2);
}
.submenu .nav-link {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
}
.yonetim-chevron {
    margin-top: 3px;
}
.yonetim-submenu.show .yonetim-chevron {
    transform: rotate(180deg);
}
</style>
<script>
function toggleYonetim(e) {
    e.preventDefault();
    const link = e.currentTarget;
    const parent = link.closest('.nav-item');
    const submenu = parent.querySelector('.yonetim-submenu');
    const chevron = link.querySelector('.yonetim-chevron');
    
    if (submenu.style.display === 'none' || !submenu.style.display) {
        submenu.style.display = 'block';
        submenu.classList.add('show');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
    } else {
        submenu.style.display = 'none';
        submenu.classList.remove('show');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
}
// Auto-expand if active on page load
document.addEventListener('DOMContentLoaded', function() {
    const submenus = document.querySelectorAll('.yonetim-submenu');
    submenus.forEach(function(submenu) {
        if (submenu.classList.contains('show')) {
            submenu.style.display = 'block';
            const parent = submenu.closest('.nav-item');
            const chevron = parent.querySelector('.yonetim-chevron');
            if (chevron) chevron.style.transform = 'rotate(180deg)';
        }
    });
});
</script>
