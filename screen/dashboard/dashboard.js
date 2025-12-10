// apotek/dashboard/dashboard.js
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.btn-add-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            
            // Simpan ke sessionStorage (nanti bisa diubah dengan AJAX ke PHP)
            let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: productId,
                    quantity: 1,
                    added_at: new Date().toISOString()
                });
            }
            
            sessionStorage.setItem('cart', JSON.stringify(cart));
            
            // Show success message
            showNotification('Produk berhasil ditambahkan ke keranjang!', 'success');
            
            // Update cart count
            updateCartCount();
        });
    });
    
    // Update cart count
    function updateCartCount() {
        const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.querySelectorAll('.cart-count').forEach(element => {
            element.textContent = totalItems;
        });
    }
    
    // Initialize cart count
    updateCartCount();
    
    // Notification system
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 2.7s forwards;
            border-left: 4px solid ${type === 'success' ? '#10b981' : '#ef4444'};
        `;
        
        document.body.appendChild(notification);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => notification.remove(), 300);
        });
        
        // Auto remove
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => notification.remove(), 300);
            }
        }, 3000);
    }
    
    // CSS for notifications
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification i {
            color: #10b981;
            font-size: 1.2rem;
        }
        
        .notification-error i {
            color: #ef4444;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #9ca3af;
            cursor: pointer;
            padding: 0 0 0 10px;
            margin-left: auto;
        }
        
        .notification-close:hover {
            color: #374151;
        }
    `;
    document.head.appendChild(style);
    
    // Mobile sidebar toggle
    const sidebarToggle = document.createElement('button');
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    sidebarToggle.className = 'mobile-toggle';
    sidebarToggle.style.cssText = `
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 999;
        background: #10b981;
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        display: none;
    `;
    
    document.body.appendChild(sidebarToggle);
    
    // Check screen size
    function checkScreenSize() {
        if (window.innerWidth <= 1024) {
            sidebarToggle.style.display = 'flex';
            document.querySelector('.main-content').style.marginLeft = '0';
        } else {
            sidebarToggle.style.display = 'none';
            document.querySelector('.main-content').style.marginLeft = '280px';
        }
    }
    
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    
    sidebarToggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        if (window.innerWidth <= 1024 && 
            !sidebar.contains(e.target) && 
            !sidebarToggle.contains(e.target) &&
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
    
    // Add hover effect to cards
    const cards = document.querySelectorAll('.product-card, .doctor-card, .feature-card, .category-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });
    
    // Initialize Swiper for carousel (jika ada)
    if (document.querySelector('.swiper')) {
        const swiper = new Swiper('.swiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Add parallax effect to welcome banner
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const banner = document.querySelector('.welcome-banner');
        if (banner) {
            banner.style.transform = `translateY(${scrolled * 0.1}px)`;
        }
    });
});

// Add analytics tracking (contoh sederhana)
function trackEvent(eventName, data = {}) {
    console.log(`Tracking: ${eventName}`, data);
    // Nanti bisa diintegrasikan dengan Google Analytics atau sistem tracking lainnya
}

// Export untuk modul lain (jika perlu)
window.ApotekDashboard = {
    showNotification,
    trackEvent,
    updateCartCount
};