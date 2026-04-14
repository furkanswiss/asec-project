<!-- DEBUG: cookie-banner.php LOADED -->
<div id="cookie-banner" style="display: block !important; border: 5px solid red; z-index: 99999; position: fixed; bottom: 0; left: 0; right: 0; background: #fff; padding: 20px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div style="flex: 1; min-width: 250px;">
            <p style="margin: 0; color: #333; font-size: 14px;">
                Bu web sitesi, deneyiminizi iyileştirmek ve site trafiğini analiz etmek için çerezler kullanmaktadır. 
                Siteyi kullanmaya devam ederek çerez kullanımını kabul etmiş olursunuz.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="cerez-politikasi.php" style="color: #9370db; text-decoration: underline; font-size: 14px;">Çerez Politikası</a>
            <button id="acceptCookies" style="background: #9370db; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">Kabul Et</button>
            <button id="rejectCookies" style="background: #ccc; color: #333; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">Reddet</button>
        </div>
    </div>
</div>

<script>
// Cookie Banner JavaScript - GDPR/KVKK Compliant Opt-In
(function() {
    var cookieBanner = document.getElementById('cookie-banner');
    
    // Check if user has already made a choice
    var consent = localStorage.getItem('cookieConsent');
    if (consent === 'true' || consent === 'false') {
        cookieBanner.style.display = 'none';
        return;
    }
    
    // Accept button - Opt-In for Google Analytics
    document.getElementById('acceptCookies').addEventListener('click', function() {
        localStorage.setItem('cookieConsent', 'true');
        cookieBanner.style.display = 'none';
        
        // Trigger GA4 immediately without reload
        if (typeof window.loadGoogleAnalytics === 'function') {
            window.loadGoogleAnalytics();
        }
    });
    
    // Reject button - Opt-Out (GA4 will never load)
    document.getElementById('rejectCookies').addEventListener('click', function() {
        localStorage.setItem('cookieConsent', 'false');
        cookieBanner.style.display = 'none';
        // Do NOT call loadGoogleAnalytics() - user explicitly rejected
    });
})();
</script>





