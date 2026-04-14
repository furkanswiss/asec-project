// Profilim sayfası için JS: Profil düzenleme modalı, avatar animasyonu, CV yüklemede dosya adı gösterimi, modal aç/kapat, avatar önizleme

document.addEventListener('DOMContentLoaded', function() {
    // Profil avatarına animasyon
    const avatar = document.getElementById('profil-avatar');
    if (avatar) {
        avatar.addEventListener('mouseenter', () => {
            avatar.style.transform = 'scale(1.09) rotate(-3deg)';
            avatar.style.boxShadow = '0 4px 24px #8e44ad44';
        });
        avatar.addEventListener('mouseleave', () => {
            avatar.style.transform = '';
            avatar.style.boxShadow = '';
        });
    }

    // CV dosya adı gösterimi
    const cvInput = document.getElementById('cv');
    if (cvInput) {
        cvInput.addEventListener('change', function() {
            let label = document.querySelector('label[for="cv"]');
            if (this.files && this.files.length > 0) {
                label.innerHTML = '<i class="fas fa-file-pdf"></i> Seçilen: ' + this.files[0].name;
            } else {
                label.innerHTML = '<i class="fas fa-file-pdf"></i> CV Yükle (PDF):';
            }
        });
    }

    // Profil düzenle modalı aç/kapat
    const guncelleBtn = document.getElementById('profil-guncelle-btn');
    const modal = document.getElementById('profil-modal');
    const modalClose = document.getElementById('profil-modal-close');
    
    if (guncelleBtn && modal) {
        guncelleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        });
    }
    
    if (modalClose && modal) {
        modalClose.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        });
    }
    
    // Close modal when clicking outside
    if (modal) {
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        };
    }

    // Profil fotoğrafı önizleme
    const avatarInput = document.querySelector('input[name="avatar"]');
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (avatar.querySelector('img')) {
                        avatar.querySelector('img').src = e.target.result;
                    } else {
                        let img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '72px';
                        img.style.height = '72px';
                        img.style.borderRadius = '50%';
                        img.style.objectFit = 'cover';
                        avatar.innerHTML = '';
                        avatar.appendChild(img);
                    }
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
