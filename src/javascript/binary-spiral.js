document.addEventListener('DOMContentLoaded', function() {
    // Cihaz türünü kontrol et ve ona göre spiral oluştur
    if (typeof isLowPerformanceDevice === 'function' && isLowPerformanceDevice()) {
        createLightBinarySpiral();
    } else {
        createBinarySpiral();
    }
});

// Tam özellikli binary spiral (masaüstü için)
function createBinarySpiral() {
    const spiralContainer = document.querySelector('.binary-spiral');
    if (!spiralContainer) return;
    
    // Spiral parametreleri
    const totalBits = 80; // 120'den 80'e düşürüldü
    const spiralRect = spiralContainer.getBoundingClientRect();
    const spiralWidth = spiralContainer.offsetWidth;
    const spiralHeight = spiralContainer.offsetHeight;
    const centerX = spiralWidth / 2;
    const centerY = spiralHeight / 2;
    const circleRadius = Math.min(spiralWidth, spiralHeight) / 2 - 2;

    // Her bit için bir span oluştur (tam çember)
    for (let i = 0; i < totalBits; i++) {
        const bit = document.createElement('span');
        const value = Math.random() > 0.5 ? '1' : '0';
        bit.className = 'binary-bit';
        bit.textContent = value;
        bit.setAttribute('data-bit', value);

        // Tam çemberde eşit aralıklarla yerleştir
        const angle = (2 * Math.PI * i) / totalBits;
        const x = centerX + circleRadius * Math.cos(angle);
        const y = centerY + circleRadius * Math.sin(angle);
        bit.style.left = (x - spiralContainer.offsetLeft) + 'px';
        bit.style.top = (y - spiralContainer.offsetTop) + 'px';
        bit.style.transform = 'translate(-50%, -50%)';

        // Animasyon gecikmesi sabit
        bit.style.animationDelay = `0s`;
        
        // Sabit ve büyük font
        bit.style.fontSize = `1.6rem`;
        bit.style.letterSpacing = '0.12em';
        bit.style.opacity = 1;
        bit.style.color = '';
        
        spiralContainer.appendChild(bit);
    }
    
    // Bitleri periyodik olarak değiştir - daha az sıklıkla
    setInterval(() => {
        const bits = document.querySelectorAll('.binary-bit');
        const bitsToChange = Math.floor(bits.length * 0.1); // Sadece %10'unu değiştir
        
        // Rastgele bitleri seç ve değiştir
        for (let i = 0; i < bitsToChange; i++) {
            const randomIndex = Math.floor(Math.random() * bits.length);
            const bit = bits[randomIndex];
            
            bit.textContent = Math.random() > 0.5 ? '1' : '0';
            
            // Parıldama efekti
            bit.classList.add('glow');
            setTimeout(() => {
                bit.classList.remove('glow');
            }, 500);
        }
    }, 1500); // 1000ms'den 1500ms'ye çıkarıldı
}

// Hafif binary spiral (mobil cihazlar için)
function createLightBinarySpiral() {
    const spiralContainer = document.querySelector('.binary-spiral');
    if (!spiralContainer) return;
    
    // Daha az bit kullan
    const totalBits = 30;
    const spiralWidth = spiralContainer.offsetWidth;
    const spiralHeight = spiralContainer.offsetHeight;
    const centerX = spiralWidth / 2;
    const centerY = spiralHeight / 2;
    const circleRadius = Math.min(spiralWidth, spiralHeight) / 2 - 2;

    // Her bit için bir span oluştur (tam çember)
    for (let i = 0; i < totalBits; i++) {
        const bit = document.createElement('span');
        const value = Math.random() > 0.5 ? '1' : '0';
        bit.className = 'binary-bit';
        bit.textContent = value;
        
        // Tam çemberde eşit aralıklarla yerleştir
        const angle = (2 * Math.PI * i) / totalBits;
        const x = centerX + circleRadius * Math.cos(angle);
        const y = centerY + circleRadius * Math.sin(angle);
        bit.style.left = (x - spiralContainer.offsetLeft) + 'px';
        bit.style.top = (y - spiralContainer.offsetTop) + 'px';
        bit.style.transform = 'translate(-50%, -50%)';
        
        // Animasyonu kaldır
        bit.style.animation = 'none';
        bit.style.fontSize = '1.4rem';
        
        spiralContainer.appendChild(bit);
    }
    
    // Çok daha seyrek bit değişimi
    setInterval(() => {
        const bits = document.querySelectorAll('.binary-bit');
        // Sadece bir bit değiştir
        const randomIndex = Math.floor(Math.random() * bits.length);
        const bit = bits[randomIndex];
        bit.textContent = Math.random() > 0.5 ? '1' : '0';
    }, 2000);
}
