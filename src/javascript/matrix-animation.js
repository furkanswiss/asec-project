document.addEventListener("DOMContentLoaded", function () {
  // Düşük performanslı cihazlarda matrix animasyonunu çalıştırma
  if (
    typeof isLowPerformanceDevice === "function" &&
    isLowPerformanceDevice()
  ) {
    console.log(
      "Düşük performanslı cihaz algılandı. Matrix animasyonu devre dışı bırakıldı."
    );
    return;
  }

  // Matrix animasyonu için canvas oluştur
  const canvas = document.createElement("canvas");
  canvas.id = "matrix-canvas";
  canvas.classList.add("matrix-canvas");

  // Canvas'ı auth-page veya hero bölümüne ekle
  const authPage = document.querySelector(".auth-page");
  const heroSection = document.querySelector(".hero");

  let container = null;

  if (authPage) {
    container = authPage;
  } else if (heroSection) {
    container = heroSection;
  }

  if (container) {
    // Canvas'ı container'a ekle
    container.appendChild(canvas);

    // Canvas'ı tam ekran yap
    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;

    // Pencere boyutu değiştiğinde canvas'ı yeniden boyutlandır
    window.addEventListener("resize", function () {
      canvas.width = container.offsetWidth;
      canvas.height = container.offsetHeight;
    });

    // Matrix animasyonunu başlat
    const ctx = canvas.getContext("2d");

    // Binary karakterler (0 ve 1)
    const binary = "01";

    // Font boyutu - mobil cihazlarda daha büyük font kullan
    const fontSize = window.innerWidth < 768 ? 16 : 14;

    // Sütun sayısı - performans için daha az sütun kullan
    const columnSpacing = window.innerWidth < 768 ? 2 : 1; // Mobil cihazlarda her 2 sütundan birini göster
    const columns = Math.floor(canvas.width / (fontSize * columnSpacing));

    // Her sütunun Y pozisyonu
    const drops = [];
    for (let i = 0; i < columns; i++) {
      drops[i] = Math.floor(Math.random() * -canvas.height);
    }

    // Animasyon hızı (ms)
    const animationSpeed = window.innerWidth < 768 ? 80 : 50; // Mobil cihazlarda daha yavaş animasyon

    // Karakterlerin çizimi
    function draw() {
      // Yarı saydam siyah arka plan (iz efekti için)
      ctx.fillStyle = "rgba(27, 31, 59, 0.05)";
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // Karakterlerin rengi ve fontu
      ctx.fillStyle = "rgba(235, 228, 249, 0.8)"; // Menekşe rengi
      ctx.font = fontSize + "px monospace";

      // Her sütun için
      for (let i = 0; i < drops.length; i++) {
        // Rastgele bir binary karakter seç
        const text = binary.charAt(Math.floor(Math.random() * binary.length));

        // Karakteri çiz
        ctx.fillText(text, i * fontSize * columnSpacing, drops[i] * fontSize);

        // Karakterin Y pozisyonunu güncelle
        drops[i]++;

        // Karakterin ekranın altına ulaştığında veya rastgele bir şekilde
        // yeniden başlamasını sağla
        if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
          drops[i] = Math.floor(Math.random() * -20);
        }
      }
    }

    // Animasyonu başlat
    const matrixInterval = setInterval(draw, animationSpeed);

    // Sayfa görünürlük durumuna göre animasyonu duraklat/devam ettir
    // Bu, arka planda gereksiz CPU kullanımını önler
    document.addEventListener("visibilitychange", function () {
      if (document.hidden) {
        clearInterval(matrixInterval);
      } else {
        setInterval(draw, animationSpeed);
      }
    });
  }
});
