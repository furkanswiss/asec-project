/**
 * ASEC Kulübü - Cihaz Algılama ve Performans Optimizasyonu
 * Bu dosya, kullanıcının cihaz türünü algılar ve animasyonları buna göre optimize eder.
 */

// Cihaz türünü algıla
const isMobile = {
    Android: function() {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function() {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function() {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function() {
        return navigator.userAgent.match(/IEMobile/i) || navigator.userAgent.match(/WPDesktop/i);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};

// Düşük performanslı cihazları algıla
const isLowPerformanceDevice = () => {
    // Mobil cihaz kontrolü
    if (isMobile.any()) {
        return true;
    }
    
    // CPU çekirdek sayısı kontrolü (düşük çekirdek sayısı = düşük performans)
    if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) {
        return true;
    }
    
    // Bellek kontrolü (mümkünse)
    if (navigator.deviceMemory && navigator.deviceMemory < 4) {
        return true;
    }
    
    return false;
};

// Animasyon ayarlarını cihaza göre optimize et
const optimizeAnimations = () => {
    // Düşük performanslı cihaz mı kontrol et
    const lowPerformance = isLowPerformanceDevice();
    
    // HTML'e cihaz türünü belirten bir sınıf ekle
    document.documentElement.classList.toggle('low-performance-device', lowPerformance);
    
    // Animasyon optimizasyonu
    if (lowPerformance) {
        console.log('Düşük performanslı cihaz algılandı. Animasyonlar optimize ediliyor...');
        
        // Matrix animasyonunu kaldır veya basitleştir
        const matrixCanvas = document.getElementById('matrix-canvas');
        if (matrixCanvas) {
            matrixCanvas.remove();
        }
        
        // Binary spiral animasyonunu basitleştir
        simplifyBinarySpiral();
        
        // ASEC animasyonunu basitleştir
        simplifyAsecAnimation();
        
        // Diğer ağır animasyonları devre dışı bırak
        disableHeavyAnimations();
    }
};

// Binary spiral animasyonunu basitleştir
const simplifyBinarySpiral = () => {
    const spiralContainer = document.querySelector('.binary-spiral');
    if (!spiralContainer) return;
    
    // Mevcut bitleri temizle
    spiralContainer.innerHTML = '';
    
    // Daha az bit ekle
    const totalBits = 30; // 120'den 30'a düşürüldü
    const spiralWidth = spiralContainer.offsetWidth;
    const spiralHeight = spiralContainer.offsetHeight;
    const centerX = spiralWidth / 2;
    const centerY = spiralHeight / 2;
    const circleRadius = Math.min(spiralWidth, spiralHeight) / 2 - 2;

    for (let i = 0; i < totalBits; i++) {
        const bit = document.createElement('span');
        bit.className = 'binary-bit';
        bit.textContent = Math.random() > 0.5 ? '1' : '0';
        
        const angle = (2 * Math.PI * i) / totalBits;
        const x = centerX + circleRadius * Math.cos(angle);
        const y = centerY + circleRadius * Math.sin(angle);
        bit.style.left = (x - spiralContainer.offsetLeft) + 'px';
        bit.style.top = (y - spiralContainer.offsetTop) + 'px';
        bit.style.transform = 'translate(-50%, -50%)';
        bit.style.fontSize = '1.4rem';
        bit.style.animation = 'none'; // Animasyonu kaldır
        
        spiralContainer.appendChild(bit);
    }
    
    // Daha seyrek bit değişimi
    setInterval(() => {
        const bits = document.querySelectorAll('.binary-bit');
        bits.forEach(bit => {
            if (Math.random() > 0.9) { // %10 olasılıkla değiştir
                bit.textContent = Math.random() > 0.5 ? '1' : '0';
            }
        });
    }, 2000); // 1 saniyeden 2 saniyeye çıkarıldı
};

// ASEC animasyonunu basitleştir
const simplifyAsecAnimation = () => {
    const letters = document.querySelectorAll('.letter');
    if (!letters.length) return;
    
    letters.forEach(letter => {
        // Karmaşık animasyonları kaldır
        letter.style.animation = 'none';
        letter.style.transform = 'none';
        letter.style.textShadow = '0 0 10px rgba(106, 13, 173, 0.6)';
        
        // Pseudo elementleri kaldır
        const style = document.createElement('style');
        style.textContent = `
            .letter::before, .letter::after {
                display: none !important;
            }
            .animated-asec {
                animation: none !important;
                transform: none !important;
            }
        `;
        document.head.appendChild(style);
    });
};

// Ağır animasyonları devre dışı bırak
const disableHeavyAnimations = () => {
    // CSS animasyonlarını devre dışı bırak
    const style = document.createElement('style');
    style.textContent = `
        .low-performance-device .feature-card {
            transform: none !important;
            transition: none !important;
        }
        
        .low-performance-device .feature-card:hover {
            transform: none !important;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
        }
        
        .low-performance-device .feature-card i {
            animation: none !important;
        }
        
        .low-performance-device .feature-card i::before,
        .low-performance-device .feature-card i::after {
            display: none !important;
        }
        
        .low-performance-device .event-card {
            transform: none !important;
            transition: none !important;
        }
        
        .low-performance-device .event-card:hover {
            transform: none !important;
        }
        
        @media (max-width: 768px) {
            .hero {
                min-height: 80vh !important;
            }
            
            .letter {
                font-size: 4rem !important;
            }
        }
    `;
    document.head.appendChild(style);
};

// Sayfa yüklendiğinde optimizasyonu başlat
document.addEventListener('DOMContentLoaded', optimizeAnimations);
