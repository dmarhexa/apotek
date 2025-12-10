// ===== SIDEBAR TOGGLE =====
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            
            // Toggle sidebar visibility on mobile
            if (window.innerWidth <= 1024) {
                if (sidebar.classList.contains('active')) {
                    mainContent.style.marginLeft = '0';
                } else {
                    mainContent.style.marginLeft = '280px';
                }
            }
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024 && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                mainContent.style.marginLeft = '0';
            }
        }
    });

    // Initialize filter functionality
    initFilters();
});

// ===== FILTER AND SEARCH =====
function initFilters() {
    const searchInput = document.getElementById('searchInput');
    const specializationFilter = document.getElementById('specializationFilter');
    const experienceFilter = document.getElementById('experienceFilter');
    
    // Debounce search function
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterDoctors, 300);
    });
    
    // Filter on change
    if (specializationFilter) {
        specializationFilter.addEventListener('change', filterDoctors);
    }
    
    if (experienceFilter) {
        experienceFilter.addEventListener('change', filterDoctors);
    }
}

function filterDoctors() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const selectedSpecialization = document.getElementById('specializationFilter').value;
    const selectedExperience = document.getElementById('experienceFilter').value;
    
    const doctorCards = document.querySelectorAll('.doctor-card');
    let visibleCount = 0;

    doctorCards.forEach(card => {
        const doctorName = card.getAttribute('data-name') || '';
        const specialization = card.getAttribute('data-specialization') || '';
        const experience = parseInt(card.getAttribute('data-experience')) || 0;
        
        // Check search term
        const matchesSearch = searchTerm === '' || 
            doctorName.includes(searchTerm) || 
            specialization.toLowerCase().includes(searchTerm);
        
        // Check specialization filter
        const matchesSpecialization = selectedSpecialization === '' || 
            specialization === selectedSpecialization;
        
        // Check experience filter
        let matchesExperience = true;
        if (selectedExperience) {
            if (selectedExperience === '0-5') {
                matchesExperience = experience >= 0 && experience <= 5;
            } else if (selectedExperience === '5-10') {
                matchesExperience = experience > 5 && experience <= 10;
            } else if (selectedExperience === '10-15') {
                matchesExperience = experience > 10 && experience <= 15;
            } else if (selectedExperience === '15+') {
                matchesExperience = experience > 15;
            }
        }
        
        // Show/hide card
        if (matchesSearch && matchesSpecialization && matchesExperience) {
            card.style.display = 'flex';
            visibleCount++;
            
            // Add animation
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 10);
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.display = 'none';
            }, 300);
        }
    });

    // Show empty state if no doctors found
    const emptyState = document.querySelector('.empty-state');
    if (emptyState) {
        if (visibleCount === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }
    }
}

function searchDoctors() {
    filterDoctors();
}

// ===== WHATSAPP CONSULTATION =====
let currentDoctor = null;

function startWhatsAppConsult(doctorName, phoneNumber) {
    currentDoctor = {
        name: doctorName,
        phone: phoneNumber
    };
    
    // Update modal content
    document.getElementById('doctorNameConfirm').textContent = 'Dr. ' + doctorName;
    
    // Show confirmation modal
    document.getElementById('whatsappModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeWhatsAppModal() {
    document.getElementById('whatsappModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    currentDoctor = null;
}

function proceedToWhatsApp() {
    if (!currentDoctor) return;
    
    // Format phone number
    const formattedNumber = currentDoctor.phone.replace(/[^\d]/g, '');
    
    // Create WhatsApp message
    const message = `Halo Dr. ${currentDoctor.name}, saya ingin berkonsultasi mengenai kesehatan saya melalui Apotek Sehat.`;
    const encodedMessage = encodeURIComponent(message);
    
    // Create WhatsApp URL
    const whatsappURL = `https://wa.me/${formattedNumber}?text=${encodedMessage}`;
    
    // Open WhatsApp
    window.open(whatsappURL, '_blank');
    
    // Close modal
    closeWhatsAppModal();
    
    // Show success notification
    showNotification('Membuka WhatsApp...', 'success');
}

// ===== DOCTOR DETAIL MODAL =====
function showDoctorDetail(doctorId) {
    // Show loading state
    document.getElementById('doctorDetailContent').innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Memuat detail dokter...</p>
        </div>
    `;
    
    document.getElementById('doctorDetailModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Fetch doctor details via AJAX
    fetch(`../../api/get_doctor_detail.php?id=${doctorId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.doctor) {
                const doctor = data.doctor;
                createDoctorDetailContent(doctor);
            } else {
                showError('Dokter tidak ditemukan');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Gagal memuat detail dokter');
        });
}

function createDoctorDetailContent(doctor) {
    const detailHTML = `
        <div class="doctor-detail-view">
            <div class="detail-profile">
                <div class="detail-avatar-large">
                    ${doctor.foto ? 
                        `<img src="../../uploads/dokter/${doctor.foto}" alt="${doctor.nama_dokter}">` : 
                        `<div class="avatar-placeholder-large">
                            <i class="fas fa-user-md"></i>
                        </div>`
                    }
                </div>
                <div class="profile-info">
                    <h2>Dr. ${doctor.nama_dokter}</h2>
                    <div class="profile-badges">
                        <span class="badge-specialization">${doctor.spesialisasi}</span>
                        <span class="badge-experience">
                            <i class="fas fa-briefcase"></i>
                            ${doctor.pengalaman} Tahun Pengalaman
                        </span>
                    </div>
                    <div class="profile-rating">
                        <div class="stars">
                            ${'<i class="fas fa-star"></i>'.repeat(5)}
                        </div>
                        <span class="rating-value">4.8</span>
                        <span class="reviews">(120 ulasan)</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-sections">
                <div class="detail-section">
                    <h3><i class="fas fa-id-card"></i> Informasi Kontak</h3>
                    <div class="section-content">
                        <div class="info-row">
                            <label><i class="fas fa-phone"></i> Telepon</label>
                            <span>${doctor.nomor_telepon}</span>
                        </div>
                        <div class="info-row">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <span>${doctor.email}</span>
                        </div>
                        <div class="info-row">
                            <label><i class="fas fa-money-bill-wave"></i> Biaya Konsultasi</label>
                            <span class="consultation-price">Rp ${formatNumber(doctor.biaya_konsultasi)}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3><i class="fas fa-calendar-alt"></i> Jadwal Praktek</h3>
                    <div class="section-content">
                        <pre class="schedule-display">${doctor.jadwal_praktek || 'Jadwal akan diinformasikan saat konsultasi'}</pre>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3><i class="fas fa-graduation-cap"></i> Keahlian</h3>
                    <div class="section-content">
                        <div class="expertise-tags">
                            <span class="expertise-tag">Konsultasi Umum</span>
                            <span class="expertise-tag">Resep Obat</span>
                            <span class="expertise-tag">Konsultasi Online</span>
                            <span class="expertise-tag">Follow-up</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="detail-actions">
                <button class="btn-close" onclick="closeDoctorDetail()">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <button class="btn-consult-large" onclick="startWhatsAppConsult('${doctor.nama_dokter}', '${doctor.nomor_telepon}')">
                    <i class="fab fa-whatsapp"></i> Konsultasi via WhatsApp
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('doctorDetailContent').innerHTML = detailHTML;
    addDetailModalStyles();
}

function closeDoctorDetail() {
    document.getElementById('doctorDetailModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function showError(message) {
    document.getElementById('doctorDetailContent').innerHTML = `
        <div class="error-state">
            <i class="fas fa-exclamation-triangle fa-2x"></i>
            <h4>Terjadi Kesalahan</h4>
            <p>${message}</p>
            <button class="btn-retry" onclick="closeDoctorDetail()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    `;
}

// ===== HELPER FUNCTIONS =====
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) existingNotification.remove();
    
    const notification = document.createElement('div');
    notification.className = `custom-notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas ${getNotificationIcon(type)}"></i>
        <span>${message}</span>
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
        z-index: 3000;
        animation: slideInRight 0.3s ease;
        border-left: 4px solid ${getNotificationColor(type)};
        font-family: 'Inter', sans-serif;
        min-width: 300px;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

function getNotificationIcon(type) {
    switch(type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}

function getNotificationColor(type) {
    switch(type) {
        case 'success': return '#10b981';
        case 'error': return '#ef4444';
        case 'warning': return '#f59e0b';
        default: return '#3b82f6';
    }
}

// Add animation styles
const animationStyles = document.createElement('style');
animationStyles.textContent = `
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
    
    .doctor-detail-view {
        font-family: 'Inter', sans-serif;
    }
    
    .detail-profile {
        display: flex;
        gap: 25px;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .detail-avatar-large {
        flex-shrink: 0;
    }
    
    .detail-avatar-large img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #e5e7eb;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .avatar-placeholder-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        border: 4px solid #e5e7eb;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .profile-info h2 {
        color: #1f2937;
        font-size: 1.8rem;
        margin-bottom: 10px;
    }
    
    .profile-badges {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .badge-specialization {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .badge-experience {
        background: #f3f4f6;
        color: #374151;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .badge-experience i {
        color: #f59e0b;
    }
    
    .profile-rating {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #6b7280;
    }
    
    .profile-rating .stars i {
        color: #fbbf24;
    }
    
    .rating-value {
        font-weight: 700;
        color: #1f2937;
    }
    
    .reviews {
        font-size: 0.9rem;
    }
    
    .detail-sections {
        margin-bottom: 30px;
    }
    
    .detail-section {
        margin-bottom: 25px;
    }
    
    .detail-section h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1f2937;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }
    
    .detail-section h3 i {
        color: #10b981;
    }
    
    .section-content {
        background: #f8fafc;
        padding: 20px;
        border-radius: 10px;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-row label {
        color: #4b5563;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .info-row span {
        color: #1f2937;
        font-weight: 600;
    }
    
    .consultation-price {
        color: #10b981 !important;
        font-size: 1.2rem;
    }
    
    .schedule-display {
        color: #4b5563;
        line-height: 1.6;
        white-space: pre-wrap;
        font-family: 'Inter', sans-serif;
        margin: 0;
    }
    
    .expertise-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .expertise-tag {
        background: #e0f2fe;
        color: #0369a1;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .detail-actions {
        display: flex;
        gap: 15px;
    }
    
    .btn-close {
        flex: 1;
        background: #f3f4f6;
        color: #374151;
        border: 2px solid #e5e7eb;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .btn-close:hover {
        background: #e5e7eb;
    }
    
    .btn-consult-large {
        flex: 2;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s;
    }
    
    .btn-consult-large:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .loading-state, .error-state {
        text-align: center;
        padding: 40px 20px;
    }
    
    .loading-state i {
        color: #10b981;
        margin-bottom: 20px;
    }
    
    .error-state i {
        color: #ef4444;
        margin-bottom: 20px;
    }
    
    .error-state h4 {
        color: #1f2937;
        margin-bottom: 10px;
    }
    
    .error-state p {
        color: #6b7280;
        margin-bottom: 20px;
    }
    
    .btn-retry {
        background: #10b981;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
`;
document.head.appendChild(animationStyles);

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const doctorModal = document.getElementById('doctorDetailModal');
    if (e.target === doctorModal) {
        closeDoctorDetail();
    }
    
    const whatsappModal = document.getElementById('whatsappModal');
    if (e.target === whatsappModal) {
        closeWhatsAppModal();
    }
});