// Önemli Bilgilendirmeler JavaScript
// Detail view ve scroll position yönetimi

document.addEventListener('DOMContentLoaded', function() {
    const cardGrid = document.getElementById('card-grid');
    const detailView = document.getElementById('detail-view');
    const detailContent = document.getElementById('detail-content');
    const backBtn = document.getElementById('back-to-grid');
    const devamiButtons = document.querySelectorAll('.devami-btn');
    
    let scrollPosition = 0;
    
    // Devamı butonlarına click event
    devamiButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const cardId = this.getAttribute('data-id');
            
            // Scroll pozisyonunu kaydet
            scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            // Detail view'ı göster
            showDetailView(cardId);
        });
    });
    
    // Kartlara click event (tüm karta tıklanabilir)
    document.querySelectorAll('.bilgi-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Eğer devamı butonuna tıklanmadıysa
            if (!e.target.closest('.devami-btn')) {
                const cardId = this.getAttribute('data-id');
                
                // Scroll pozisyonunu kaydet
                scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                
                // Detail view'ı göster
                showDetailView(cardId);
            }
        });
    });
    
    // Geri dön butonu
    backBtn.addEventListener('click', function() {
        hideDetailView();
    });
    
    // Detail view göster
    function showDetailView(cardId) {
        // AJAX ile detay bilgisini getir
        fetch(`ajax/onemli-bilgi-detay-getir.php?id=${cardId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Detail content'i doldur
                    let html = '';
                    
                    // Use display fields (language-aware) if available, otherwise fallback to original
                    const displayBaslik = data.bilgi.display_baslik || data.bilgi.baslik;
                    const displayAciklama = data.bilgi.display_aciklama || data.bilgi.aciklama;
                    const displayIcerik = data.bilgi.display_icerik || data.bilgi.icerik;
                    
                    if (data.bilgi.resim) {
                        html += `<img src="uploads/onemli-bilgiler/${data.bilgi.resim}" alt="${escapeHtml(displayBaslik)}" class="detail-header-image">`;
                    }
                    
                    html += `<h1 class="detail-title">${escapeHtml(displayBaslik)}</h1>`;
                    html += `<div class="detail-date"><i class="fas fa-calendar-alt"></i> ${formatDate(data.bilgi.tarih)}</div>`;
                    html += `<p class="detail-description">${escapeHtml(displayAciklama)}</p>`;
                    html += `<div class="detail-icerik">${escapeHtml(displayIcerik).replace(/\n/g, '<br>')}</div>`;
                    
                    detailContent.innerHTML = html;
                    
                    // Grid'i gizle, detail view'ı göster
                    cardGrid.style.display = 'none';
                    detailView.style.display = 'block';
                    
                    // Sayfanın başına scroll yap
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                } else {
                    alert('Bilgi yüklenirken bir hata oluştu.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bilgi yüklenirken bir hata oluştu.');
            });
    }
    
    // Detail view gizle
    function hideDetailView() {
        detailView.style.display = 'none';
        cardGrid.style.display = 'grid';
        
        // Kaydedilen scroll pozisyonuna dön
        window.scrollTo({
            top: scrollPosition,
            behavior: 'smooth'
        });
    }
    
    // HTML escape function
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    // Tarih formatla
    function formatDate(dateString) {
        const date = new Date(dateString);
        const months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 
                       'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
    }
});

