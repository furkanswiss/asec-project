/**
 * ASEC Kulübü - Animasyon Kontrolcüsü
 * Bu dosya, cihaz türüne göre animasyonları optimize eder.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobil cihaz kontrolü
    const isMobile = window.innerWidth < 768;
    
    // Düşük performanslı cihaz kontrolü
    const isLowPerformance = isMobile || 
                            (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) ||
                            (navigator.deviceMemory && navigator.deviceMemory < 4);
    
    // Düşük performanslı cihazlarda animasyonları devre dışı bırak
    if (isLowPerformance) {
        document.documentElement.classList.add('low-performance-device');
        
        // ASEC animasyonunu basitleştir
        const letters = document.querySelectorAll('.letter');
        letters.forEach(letter => {
            letter.style.animation = 'none';
            letter.style.textShadow = '0 0 10px rgba(106, 13, 173, 0.8)';
        });
        
        // Animasyon container'ını basitleştir
        const animatedAsec = document.querySelector('.animated-asec');
        if (animatedAsec) {
            animatedAsec.style.animation = 'none';
        }
        
        // Binary spiral animasyonunu basitleştir
        const binaryBits = document.querySelectorAll('.binary-bit');
        binaryBits.forEach(bit => {
            bit.style.animation = 'none';
        });
    }
});
