<?php
require_once 'db.php';
ob_start(); // Docker'da hata almamak için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu başlat
}

// Determine language (use cookie from lang.php, fallback to 'tr')
$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');

?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('teams.page.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/takimlar.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <section class="teams-header">
            <div class="header-bg"></div>
            <div class="container">
                <div class="header-content">
                    <h2><?php echo __t('teams.page.title'); ?></h2>
                    <p><?php echo __t('teams.page.subtitle'); ?></p>
                    <div class="header-stats">
                        <div class="stat-item">
                            <span class="stat-number">7</span>
                            <span class="stat-label"><?php echo __t('teams.stat.active'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">180+</span>
                            <span class="stat-label"><?php echo __t('teams.stat.members'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">20+</span>
                            <span class="stat-label"><?php echo __t('teams.stat.projects'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="teams-grid">
                <div class="teams-grid-container">
                <!-- Siber Güvenlik Departmanı -->
                <div class="team-card">
                    <div class="team-banner"></div>
                    <div class="team-content">
                        <h3><?php echo __t('teams.dept.internship.title', "Staj, İş ve Burs Departmanı"); ?></h3>
                        <p class="team-description">
                        <?php echo __t('teams.dept.internship.desc', "Staj, İş ve Burs Departmanı; öğrencilerimizin mesleki gelişimlerini desteklemek, sektöre daha donanımlı bireyler olarak adım atmalarını sağlamak amacıyla faaliyet göstermektedir. Departman, staj olanakları, iş ilanları ve burs fırsatlarını araştırarak üyelerimize en güncel ve güvenilir bilgileri sunmayı hedefler. Aynı zamanda kariyer rehberliği, CV hazırlama desteği ve bilgilendirici etkinliklerle öğrencilere yol gösterici bir rol üstlenir. Emirkan Dağ başkanlığında yürütülen bu çalışmalar, kulüp üyelerimizin akademik bilginin ötesine geçerek gerçek dünya ile güçlü bağlar kurmalarını amaçlar."); ?>
                        </p>
                    </div>
                    <div class="team-members">
                        <div class="member-card">
                            <div class="member-image">
                                <img src="images/team/emirkan.jpg" alt="Emirkan Dağ">
                            </div>
                            <div class="member-info">
                                <h4>Emirkan Dağ</h4>
                                <p class="member-role"><?php echo __t('teams.role.lead'); ?></p>
                                <div class="member-social">
                                    <a href="https://www.linkedin.com/in/emirkan-da%C4%9F-ab18b8254?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" target="_blank"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    
                <!-- Yazılım Geliştirme Departmanı -->
                <div class="team-card">
                    <div class="team-banner"></div>
                    <div class="team-content">
                        <h3><?php echo __t('teams.dept.abroad.title', "Yurtdışı Komisyonu"); ?></h3>
                        <p class="team-description">
                        <?php echo __t('teams.dept.abroad.desc', "Yurtdışı Komisyonu, öğrencilerimizin mezun olmadan önce uluslararası deneyim kazanmalarını teşvik eden, vizyoner bir yaklaşımla faaliyet gösteren birimimizdir. Erasmus, staj, değişim programları ve çeşitli uluslararası fırsatlara erişim sağlamak amacıyla bilgilendirici içerikler ve yönlendirici etkinlikler düzenlenmektedir. Komisyonumuz, daha önce yurtdışı deneyimi yaşamış öğrencilerle iş birliği yaparak, üyelerimize gerçekçi ve yol gösterici bilgiler sunmayı hedefler. İlayda Akınet başkanlığında yürütülen çalışmalar; araştırma, yönlendirme ve mentorluk esaslarına dayanarak öğrencilerimizi global dünyaya hazır hale getirmeyi amaçlamaktadır."); ?>
                        </p>
                    </div>
                    <div class="team-members">
                        <div class="member-card">
                            <div class="member-image">
                                <img src="images/team/ilayda.jpg" alt="İlayda Akınet">
                            </div>
                            <div class="member-info">
                                <h4>İlayda Akınet</h4>
                                <p class="member-role"><?php echo __t('teams.role.lead'); ?></p>
                                <div class="member-social">
                                    <a href="https://www.linkedin.com/in/ilaydaakinet?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app" target="_blank"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="team-card">
                    <div class="team-banner"></div>
                    <div class="team-content">
                        <h3><?php echo __t('teams.dept.project.title', "Proje Komisyonu"); ?></h3>
                        <p class="team-description">
                        <?php echo __t('teams.dept.project.desc', "Proje Komisyonu, öğrencilerimizin kendi projelerini geliştirmelerine yardımcı olmanın yanı sıra, kulübümüzün yürüttüğü projeleri de üretir ve destekler. Komisyon, projelerin başlangıcından tamamlanmasına kadar tüm süreçlerde rehberlik sağlar, öğrencilerin fikirlerini sektöre kazandırmalarını ve hayata geçirmelerini amaçlar. Bu süreç, proje yönetimi, teknik destek ve mentorluk gibi çeşitli alanlarda yoğun bir çalışma gerektirir. Ali Baran Korkmaz başkanlığında yürütülen bu çalışmalar, yenilikçi ve sürdürülebilir projelerin ortaya çıkmasına ve sektörde değer kazanmasına olanak tanır."); ?>
                        </p>
                    </div>
                    <div class="team-members">
                        <div class="member-card">
                            <div class="member-image">
                                <img src="images/team/alibaran.jpg" alt="Ali Baran Korkmaz">
                            </div>
                            <div class="member-info">
                                <h4>Ali Baran Korkmaz</h4>
                                <p class="member-role"><?php echo __t('teams.role.lead'); ?></p>
                                <div class="member-social">
                                    <a href="https://www.linkedin.com/in/ali-baran-korkmaz-75b2861aa/" target="_blank"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>  
                </div>
                    
                <!-- Veri Bilimi Departmanı -->
                <div class="team-card">
                    <div class="team-banner"></div>
                    <div class="team-content">
                        <h3><?php echo __t('teams.dept.events.title', "Etkinlik Departmanı"); ?></h3>
                        <p class="team-description">
                        <?php echo __t('teams.dept.events.desc', "Etkinlik Departmanı, öğrencilerimizin mesleki gelişimlerine katkı sağlamak için sektörden uzmanlarla düzenlenen konferanslar, seminerler ve bilgilendirme etkinliklerine odaklanır. Bu etkinlikler, öğrencilerin yazılım ve inovasyon alanındaki en güncel gelişmeleri öğrenmelerini ve sektörle doğrudan bağlantı kurmalarını sağlar. Ayrıca, sosyal bağları güçlendirmek ve öğrencilerin moralini yükseltmek amacıyla piknikler, oyunlar ve yarışmalar gibi keyifli etkinlikler de düzenlenir. Beyzanur Arslan başkanlığında, profesyonel dünyaya dair derinlemesine bilgi edinme fırsatları sunulurken, aynı zamanda öğrencilerin eğlenceli ve dinlendirici aktivitelerle bir araya gelmesi sağlanır."); ?>
                        </p>
                    </div>
                    <div class="team-members">
                        <div class="member-card">
                            <div class="member-image">
                                <img src="images/team/beyzanur.jpg" alt="Beyzanur Arslan">
                            </div>
                            <div class="member-info">
                                <h4>Beyzanur Arslan</h4>
                                <p class="member-role"><?php echo __t('teams.role.lead'); ?></p>
                                <div class="member-social">
                                    <a href="https://www.linkedin.com/in/beyzanur-arslan-ba18b832a?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app" target="_blank"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-banner">
                    </div>
                    <div class="team-content">
                        <h3><?php echo __t('teams.dept.techtrip.title', "Teknik Gezi Komisyonu"); ?></h3>
                        <p class="team-description">
                        <?php echo __t('teams.dept.techtrip.desc', "Teknik Gezi Komisyonu, yazılım sektörünü yerinde gözlemleyerek öğrencilerimizin mesleki farkındalıklarını artırmayı hedefleyen birimimizdir. Komisyonumuz, sektörün önde gelen firmalarına teknik geziler düzenleyerek katılımcıların iş ortamlarını doğrudan tanımasını sağlar; çalışma kültürü, proje süreçleri ve teknolojik altyapılar hakkında bilgi edinmelerine olanak tanır. Aynı zamanda bu geziler, öğrencilerin profesyonel çevrelerini genişletmeleri ve değerli bağlantılar kurmaları açısından önemli bir networking fırsatı sunar. Komisyonumuz, Sencer Eren Yavuz liderliğinde planlama, iletişim ve organizasyon süreçlerini titizlikle yürütmektedir."); ?>
                        </p>
                    </div>
                    <div class="team-members">
                        <div class="member-card">
                            <div class="member-image">
                                <img src="images/team/sencer.jpg" alt="Sencer Eren Yavuz">
                            </div>
                            <div class="member-info">
                                <h4>Sencer Eren Yavuz</h4>
                                <p class="member-role"><?php echo __t('teams.role.lead'); ?></p>
                                <div class="member-social">
                                    <a href="https://www.linkedin.com/in/sencer-yavuz-285365256?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app" target="_blank"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>  
                </div>
                    
                <!-- Dijital Tasarım Departmanı -->
                <div class="team-card">
                    <div class="team-banner">
                    </div>
                    <div class="team-content">
                        <h3><?php echo __t('teams.dept.sponsorship.title', "Sponsorluk Departmanı"); ?></h3>
                        <p class="team-description">
                        <?php echo __t('teams.dept.sponsorship.desc', "Sponsorluk Departmanı, ASEC’in dış paydaşlarla sürdürülebilir iş birlikleri kurmasını sağlayan stratejik birimidir. Etkinlik, proje ve organizasyonlarımız için gerekli kaynakları temin etmek amacıyla kurumsal markalarla iletişim kurar, sponsorluk anlaşmaları yürütür ve karşılıklı fayda esasına dayalı iş birlikleri geliştirir. Departman Başkanımız Tuğçe Kaya liderliğinde yürütülen çalışmalar; profesyonel iletişim, ikna becerisi ve iş geliştirme odaklı bir yaklaşımla yürütülmektedir. ASEC’in her etkinliğinde arkasında güçlü bir sponsorluk yapısı bulunmasını sağlamak bu birimin öncelikli hedefidir."); ?>
                        </p>
                    </div>
                    <div class="team-members">
                        <div class="member-card">
                            <div class="member-image">
                                <img src="images/team/tuğçe.jpg" alt="Tuğçe Kaya">
                            </div>
                            <div class="member-info">
                                <h4>Tuğçe Kaya</h4>
                                <p class="member-role"><?php echo __t('teams.role.lead', "Departman Başkanı"); ?></p>
                                <div class="member-social">
                                    <a href="https://www.linkedin.com/in/tu%C4%9F%C3%A7e-kaya-8012b12b3/?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app" target="_blank"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-banner">
                    </div>
                    <div class="team-content">
                        <h3><?php echo __t('teams.dept.social.title', "Sosyal Medya Departmanı"); ?></h3>
                        <p class="team-description">
                        <?php echo __t('teams.dept.social.desc', "Sosyal Medya Departmanı, ASEC'in dijital iletişim stratejilerini yürüten ve marka kimliğini dijital mecralarda temsil eden birimidir. Instagram, YouTube ve LinkedIn platformlarında aktif olarak yer alarak kulübümüzün etkinliklerini, projelerini ve duyurularını hedef kitleyle etkili bir şekilde buluşturur. Aynı zamanda kulüp içi motivasyonu artırmaya yönelik özgün ve yaratıcı içerikler üreterek dijital etkileşimi güçlendirir. Departman Başkanımız Azra Kuyucu liderliğinde; içerik planlaması, görsel tasarım, algoritma takibi ve medya yönetimi süreçleri titizlikle yürütülmektedir."); ?>
                        </p>
                    </div>
                    <div class="team-members">
                        <div class="member-card">
                            <div class="member-image">
                                <img src="images/team/azra.jpg" alt="Azra Kuyucu">
                            </div>
                            <div class="member-info">
                                <h4>Azra Kuyucu</h4>
                                <p class="member-role"><?php echo __t('teams.role.lead', "Departman Başkanı"); ?></p>
                                <div class="member-social">
                                    <a href="https://www.linkedin.com/in/azra-kuyucu-0867782b4?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app" target="_blank"><i class="fab fa-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/script.js"></script>
</body>
</html>
