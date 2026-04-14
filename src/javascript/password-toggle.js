document.addEventListener('DOMContentLoaded', function() {
    // Tüm şifre alanlarını bul
    const passwordFields = document.querySelectorAll('input[type="password"]');
    
    // Her şifre alanı için göster/gizle düğmesi ekle
    passwordFields.forEach(function(field) {
        // Şifre alanının parent elementini al (form-group)
        const parentElement = field.parentElement;
        
        // Şifre alanını bir container içine al
        const passwordContainer = document.createElement('div');
        passwordContainer.className = 'password-container';
        
        // Şifre alanının pozisyonunu al
        const fieldPosition = field.getBoundingClientRect();
        
        // Field'in parent'i içindeki pozisyonunu koru
        const fieldIndex = Array.from(parentElement.children).indexOf(field);
        
        // Göster/gizle düğmesi oluştur
        const toggleButton = document.createElement('span');
        toggleButton.className = 'password-toggle';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        toggleButton.title = 'Şifreyi göster/gizle';
        
        // Şifre alanını container'a ekle
        field.parentNode.insertBefore(passwordContainer, field);
        passwordContainer.appendChild(field);
        passwordContainer.appendChild(toggleButton);
        
        // Göster/gizle düğmesine tıklama olayı ekle
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Şifre alanının tipini değiştir
            if (field.type === 'password') {
                field.type = 'text';
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                toggleButton.title = 'Şifreyi gizle';
            } else {
                field.type = 'password';
                toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
                toggleButton.title = 'Şifreyi göster';
            }
        });
    });
});
