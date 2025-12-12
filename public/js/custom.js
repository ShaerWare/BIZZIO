/**
 * CUSTOM.JS - Главная страница Bizzo.ru
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. МОБИЛЬНОЕ МЕНЮ
    // ============================================
    const toggler = document.querySelector('.beehive-toggler');
    const navbar = document.querySelector('.navbar-main-container');
    
    if (toggler && navbar) {
        toggler.addEventListener('click', function() {
            navbar.classList.toggle('active');
            
            // Анимация иконки бургера
            const bars = toggler.querySelectorAll('.icon-bar');
            bars[0].style.transform = navbar.classList.contains('active') 
                ? 'rotate(45deg) translate(7px, 7px)' 
                : 'none';
            bars[1].style.opacity = navbar.classList.contains('active') ? '0' : '1';
            bars[2].style.transform = navbar.classList.contains('active') 
                ? 'rotate(-45deg) translate(7px, -7px)' 
                : 'none';
        });
        
        // Закрытие меню при клике вне его
        document.addEventListener('click', function(event) {
            if (!toggler.contains(event.target) && !navbar.contains(event.target)) {
                navbar.classList.remove('active');
                
                // Сброс анимации бургера
                const bars = toggler.querySelectorAll('.icon-bar');
                bars[0].style.transform = 'none';
                bars[1].style.opacity = '1';
                bars[2].style.transform = 'none';
            }
        });
    }
    
    
    // ============================================
    // 2. АВТОПЛЕЙ ВИДЕО
    // ============================================
    const video = document.querySelector('.video-container video');
    if (video) {
        // Убеждаемся, что видео проигрывается
        video.play().catch(function(error) {
            console.log('Autoplay prevented:', error);
        });
    }
    
    
    // ============================================
    // 3. ПЛАВНАЯ ПРОКРУТКА (если понадобится)
    // ============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    
    // ============================================
    // 4. ФОКУС НА ПЕРВОМ ПОЛЕ ФОРМЫ
    // ============================================
    const firstInput = document.querySelector('.form-control');
    if (firstInput && window.innerWidth > 768) {
        // Автофокус только на десктопе
        setTimeout(() => firstInput.focus(), 300);
    }
    
    
    // ============================================
    // 5. ВАЛИДАЦИЯ ФОРМЫ (простая)
    // ============================================
    const loginForm = document.querySelector('form[action*="login"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]');
            const password = this.querySelector('input[name="password"]');
            
            let isValid = true;
            
            // Проверка email
            if (!email.value.trim()) {
                email.style.borderColor = '#ff4a4a';
                isValid = false;
            } else {
                email.style.borderColor = '#e0e0e0';
            }
            
            // Проверка пароля
            if (!password.value.trim()) {
                password.style.borderColor = '#ff4a4a';
                isValid = false;
            } else {
                password.style.borderColor = '#e0e0e0';
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Пожалуйста, заполните все поля');
            }
        });
        
        // Сброс красной границы при вводе
        loginForm.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = '#e0e0e0';
            });
        });
    }
    
});