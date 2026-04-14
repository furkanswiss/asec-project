// İlanlar Tab Navigation Script
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const tabNavigation = document.querySelector('.tab-navigation');

    // Tab switching functionality
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');

            // Scroll active tab into view on mobile
            if (window.innerWidth <= 768) {
                this.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
        });

        // Touch feedback for mobile
        button.addEventListener('touchstart', function() {
            this.style.opacity = '0.7';
        });

        button.addEventListener('touchend', function() {
            this.style.opacity = '1';
        });
    });

    // Handle URL hash for direct tab access
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        const targetButton = document.querySelector(`.tab-btn[data-tab="${hash}"]`);
        if (targetButton) {
            targetButton.click();
        }
    }

    // Prevent horizontal scroll on tab navigation container (mobile)
    if (tabNavigation && window.innerWidth <= 768) {
        let isScrolling = false;
        tabNavigation.addEventListener('scroll', function() {
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    // Allow smooth scrolling
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });
    }
});

