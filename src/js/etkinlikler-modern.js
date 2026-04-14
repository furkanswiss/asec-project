// etkinlikler-modern.js - Dinamik detay modalı ve galeri

document.addEventListener('DOMContentLoaded', function() {
    const modalBg = document.createElement('div');
    modalBg.id = 'event-modal-bg';
    modalBg.innerHTML = `<div id="event-modal"><button class="close-btn">&times;</button><div id="event-modal-content"></div></div>`;
    document.body.appendChild(modalBg);
    const modal = document.getElementById('event-modal');
    const modalContent = document.getElementById('event-modal-content');
    document.body.addEventListener('click', async function(e) {
        if (e.target.classList.contains('detay-modal-btn')) {
            e.preventDefault();
            const etkinlikId = e.target.getAttribute('data-id');
            // AJAX ile detayları çek
            try {
                const resp = await fetch('etkinlik-detay.php?id=' + etkinlikId + '&modal=1');
                const html = await resp.text();
                modalContent.innerHTML = html;
                modalBg.classList.add('active');
            } catch(err) {modalContent.innerHTML = '<div style="color:red">Detay yüklenemedi!</div>';modalBg.classList.add('active');}
        }
        if (e.target.classList.contains('close-btn') || e.target === modalBg) {
            modalBg.classList.remove('active');
        }
    });
    // ESC ile kapama
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') modalBg.classList.remove('active');
    });
});
