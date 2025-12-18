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
        .then(response => response.text()) // Get text first to debug
        .then(text => {
            console.log("Raw API Response:", text); // Debug log
            try {
                const data = JSON.parse(text);
                if (data.success && data.doctor) {
                    const doctor = data.doctor;
                    createDoctorDetailContent(doctor);
                } else {
                    showError(data.message || 'Dokter tidak ditemukan');
                }
            } catch (e) {
                console.error("JSON Parse Error:", e);
                showError('Respons server tidak valid');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showError('Gagal memuat detail dokter: ' + error.message);
        });
}

// ===== HELPER FUNCTIONS =====
function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function createDoctorDetailContent(doctor) {
    const detailHTML = `
        <div class="doctor-detail-view">
            <div class="detail-header-card">
                <div class="detail-avatar-wrapper">
                    <div class="detail-avatar-large">
                        ${doctor.foto ? 
                            `<img src="../../assets/images/dokter/${doctor.foto}" alt="${doctor.nama_dokter}" onerror="this.onerror=null;this.src='../../assets/images/dokter/default.png';">` : 
                            `<img src="../../assets/images/dokter/default.png" alt="Default Doctor">`
                        }
                        <div class="status-indicator online" title="Tersedia"></div>
                    </div>
                </div>
                
                <div class="profile-info-center">
                    <h2>Dr. ${doctor.nama_dokter}</h2>
                    <p class="specialist-text">${doctor.spesialisasi || 'Dokter Umum'}</p>
                    
                    <div class="rating-pill">
                        <i class="fas fa-star"></i>
                        <span>4.8</span>
                        <span class="divider">•</span>
                        <span>120+ Pasien</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-grid">
                <div class="detail-card info-card">
                    <h3><i class="fas fa-info-circle"></i> Info Profesional</h3>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="label">Pengalaman</span>
                            <span class="value">${doctor.pengalaman} Tahun</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Email</span>
                            <span class="value">${doctor.email}</span>
                        </div>
                         <div class="info-item">
                            <span class="label">Telepon</span>
                            <span class="value">${doctor.nomor_telepon}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-card schedule-card">
                    <h3><i class="fas fa-calendar-check"></i> Jadwal Praktik</h3>
                    <div class="schedule-box">
                        <p>${doctor.jadwal_praktek || 'Senin - Jumat: 09.00 - 17.00'}</p>
                    </div>
                </div>

                <div class="detail-card fee-card">
                    <h3><i class="fas fa-tag"></i> Biaya Konsultasi</h3>
                    <div class="price-box">
                        <span class="price-label">Mulai dari</span>
                        <span class="price-amount">Rp ${formatNumber(doctor.biaya_konsultasi)}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-actions-sticky">
                <button class="btn-secondary-action" onclick="closeDoctorDetail()">
                    Tutup
                </button>
                <button class="btn-primary-action" onclick="startWhatsAppConsult('${doctor.nama_dokter}', '${doctor.nomor_telepon}')">
                    <i class="fab fa-whatsapp"></i> Chat via WhatsApp
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('doctorDetailContent').innerHTML = detailHTML;
}

// ... existing code ...

// Add animation styles
const animationStyles = document.createElement('style');
animationStyles.textContent = `
    .doctor-detail-view {
        font-family: 'Inter', sans-serif;
        padding-bottom: 20px;
    }

    /* HEADER */
    .detail-header-card {
        text-align: center;
        margin-bottom: 30px;
        position: relative;
    }

    .detail-avatar-wrapper {
        position: relative;
        display: inline-block;
        margin-bottom: 15px;
    }

    .detail-avatar-large img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .status-indicator {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 3px solid #fff;
        background: #10b981; /* Default online */
    }

    .profile-info-center h2 {
        font-size: 1.5rem;
        color: #1f2937;
        margin-bottom: 5px;
    }

    .specialist-text {
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 12px;
    }

    .rating-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f3f4f6;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        color: #4b5563;
    }

    .rating-pill i { color: #f59e0b; }
    .rating-pill .divider { color: #d1d5db; }

    /* GRID LAYOUT */
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
        margin-bottom: 30px;
    }

    .detail-card {
        background: #f9fafb;
        border-radius: 12px;
        padding: 15px;
        border: 1px solid #e5e7eb;
    }

    .detail-card h3 {
        font-size: 1rem;
        color: #374151;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .detail-card h3 i { color: #10b981; }

    .info-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.95rem;
    }

    .info-item .label { color: #6b7280; }
    .info-item .value { color: #111827; font-weight: 500; }

    .schedule-box p {
        background: #fff;
        padding: 10px;
        border-radius: 8px;
        border: 1px dashed #d1d5db;
        color: #4b5563;
        font-size: 0.9rem;
        text-align: center;
    }

    .price-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ecfdf5;
        padding: 12px;
        border-radius: 8px;
        color: #065f46;
    }

    .price-amount {
        font-weight: 700;
        font-size: 1.1rem;
    }

    /* ACTIONS */
    .detail-actions-sticky {
        display: flex;
        gap: 12px;
        margin-top: 10px;
    }

    .btn-secondary-action, .btn-primary-action {
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        flex: 1;
        font-family: inherit;
        transition: transform 0.2s;
    }

    .btn-secondary-action {
        background: #f3f4f6;
        color: #374151;
    }

    .btn-primary-action {
        background: #10b981;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-primary-action:hover {
        background: #059669;
        transform: translateY(-2px);
    }
    
    /* Loading/Error States */
    .loading-state, .error-state {
        text-align: center;
        padding: 40px 20px;
    }
    .loading-state i { color: #10b981; margin-bottom: 20px; }
    .error-state i { color: #ef4444; margin-bottom: 20px; }
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