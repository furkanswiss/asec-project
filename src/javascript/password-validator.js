document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;

    const lengthCheck = document.getElementById('length-check');
    const upperCheck = document.getElementById('upper-check');
    const lowerCheck = document.getElementById('lower-check');
    const numberCheck = document.getElementById('number-check');
    const specialCheck = document.getElementById('special-check');

    // Şifre gereksinimleri için düzenli ifadeler
    const lengthRegex = /.{8,}/;
    const upperRegex = /[A-Z]/;
    const lowerRegex = /[a-z]/;
    const numberRegex = /[0-9]/;
    const specialRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/;

    // Şifre alanı değiştiğinde kontrol et
    passwordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        
        // Uzunluk kontrolü
        if (lengthRegex.test(password)) {
            lengthCheck.classList.add('valid');
            lengthCheck.classList.remove('invalid');
            lengthCheck.innerHTML = '✓ 8 karakter uzunluğunda';
        } else {
            lengthCheck.classList.add('invalid');
            lengthCheck.classList.remove('valid');
            lengthCheck.innerHTML = '✗ 8 karakter uzunluğunda';
        }
        
        // Büyük harf kontrolü
        if (upperRegex.test(password)) {
            upperCheck.classList.add('valid');
            upperCheck.classList.remove('invalid');
            upperCheck.innerHTML = '✓ Bir büyük harf';
        } else {
            upperCheck.classList.add('invalid');
            upperCheck.classList.remove('valid');
            upperCheck.innerHTML = '✗ Bir büyük harf';
        }
        
        // Küçük harf kontrolü
        if (lowerRegex.test(password)) {
            lowerCheck.classList.add('valid');
            lowerCheck.classList.remove('invalid');
            lowerCheck.innerHTML = '✓ Bir küçük harf';
        } else {
            lowerCheck.classList.add('invalid');
            lowerCheck.classList.remove('valid');
            lowerCheck.innerHTML = '✗ Bir küçük harf';
        }
        
        // Rakam kontrolü
        if (numberRegex.test(password)) {
            numberCheck.classList.add('valid');
            numberCheck.classList.remove('invalid');
            numberCheck.innerHTML = '✓ Bir rakam';
        } else {
            numberCheck.classList.add('invalid');
            numberCheck.classList.remove('valid');
            numberCheck.innerHTML = '✗ Bir rakam';
        }
        
        // Özel karakter kontrolü
        if (specialRegex.test(password)) {
            specialCheck.classList.add('valid');
            specialCheck.classList.remove('invalid');
            specialCheck.innerHTML = '✓ Bir özel karakter içermelidir';
        } else {
            specialCheck.classList.add('invalid');
            specialCheck.classList.remove('valid');
            specialCheck.innerHTML = '✗ Bir özel karakter içermelidir';
        }
    });
});
