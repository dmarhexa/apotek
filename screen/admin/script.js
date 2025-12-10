// Admin Panel JavaScript - Enhanced Version
(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        AUTO_DISMISS_DELAY: 5000,
        DEBOUNCE_DELAY: 300,
        API_BASE_URL: '../../api/'
    };
    
    // State management
    const state = {
        currentTransactionId: null,
        isLoading: false
    };
    
    // DOM Elements cache
    let elements = {};
    
    // Main initialization
    document.addEventListener('DOMContentLoaded', function() {
        initializeElements();
        setupSidebarToggle();
        setupFormValidation();
        setupImagePreviews();
        setupSearch();
        setupEventListeners();
        initializeTransaksi();
    });
    
    // Initialize DOM elements
    function initializeElements() {
        elements = {
            sidebar: document.querySelector('.sidebar'),
            sidebarToggle: null,
            loadingOverlay: createLoadingOverlay()
        };
        
        // Create sidebar toggle button
        createSidebarToggle();
        
        // Append loading overlay to body
        document.body.appendChild(elements.loadingOverlay);
    }
    
    // Create sidebar toggle button
    function createSidebarToggle() {
        const toggle = document.createElement('button');
        toggle.className = 'sidebar-toggle';
        toggle.innerHTML = '<i class="fas fa-bars"></i>';
        toggle.setAttribute('aria-label', 'Toggle sidebar');
        
        toggle.addEventListener('click', toggleSidebar);
        document.body.appendChild(toggle);
        elements.sidebarToggle = toggle;
        
        updateSidebarToggleVisibility();
        window.addEventListener('resize', updateSidebarToggleVisibility);
    }
    
    // Toggle sidebar visibility
    function toggleSidebar() {
        elements.sidebar.classList.toggle('active');
        updateAriaAttributes();
    }
    
    // Update sidebar toggle visibility based on screen size
    function updateSidebarToggleVisibility() {
        if (window.innerWidth <= 992) {
            elements.sidebarToggle.style.display = 'flex';
        } else {
            elements.sidebarToggle.style.display = 'none';
            elements.sidebar.classList.remove('active');
        }
        updateAriaAttributes();
    }
    
    // Update ARIA attributes for accessibility
    function updateAriaAttributes() {
        const isExpanded = elements.sidebar.classList.contains('active');
        elements.sidebarToggle.setAttribute('aria-expanded', isExpanded);
        elements.sidebar.setAttribute('aria-hidden', !isExpanded);
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992 && elements.sidebar.classList.contains('active')) {
            if (!elements.sidebar.contains(e.target) && 
                !elements.sidebarToggle.contains(e.target)) {
                elements.sidebar.classList.remove('active');
                updateAriaAttributes();
            }
        }
    });
    
    // Form validation
    function setupFormValidation() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Skip validation for delete forms
            if (form.classList.contains('delete-form')) return;
            
            form.addEventListener('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                    showNotification('Harap isi semua field yang wajib', 'error');
                }
            });
            
            // Add real-time validation
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', () => clearFieldError(input));
            });
        });
    }
    
    // Validate single field
    function validateField(field) {
        const isValid = field.value.trim() !== '';
        
        if (!isValid) {
            markFieldAsInvalid(field, 'Field ini wajib diisi');
        } else {
            markFieldAsValid(field);
        }
        
        return isValid;
    }
    
    // Validate entire form
    function validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    // Mark field as invalid
    function markFieldAsInvalid(field, message) {
        field.style.borderColor = 'var(--danger)';
        
        let errorMsg = field.parentNode.querySelector('.error-message');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.style.cssText = `
                color: var(--danger);
                font-size: 0.85rem;
                margin-top: 5px;
            `;
            errorMsg.textContent = message;
            field.parentNode.appendChild(errorMsg);
        }
    }
    
    // Mark field as valid
    function markFieldAsValid(field) {
        field.style.borderColor = '';
        const errorMsg = field.parentNode.querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
    }
    
    // Clear field error
    function clearFieldError(field) {
        if (field.value.trim() !== '') {
            markFieldAsValid(field);
        }
    }
    
    // Image previews
    function setupImagePreviews() {
        const fileInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showNotification('Ukuran file maksimal 5MB', 'error');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        showNotification('Hanya file gambar yang diizinkan', 'error');
                        this.value = '';
                        return;
                    }
                    
                    showImagePreview(this, file);
                }
            });
        });
    }
    
    // Show image preview
    function showImagePreview(input, file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const parent = input.parentNode;
            let previewContainer = parent.querySelector('.preview-container');
            
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'preview-container';
                previewContainer.style.cssText = `
                    margin-top: 10px;
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                `;
                parent.appendChild(previewContainer);
            }
            
            let previewImg = previewContainer.querySelector('.preview-img');
            if (!previewImg) {
                previewImg = document.createElement('img');
                previewImg.className = 'preview-img';
                previewImg.style.cssText = `
                    max-width: 200px;
                    max-height: 200px;
                    border-radius: 8px;
                    border: 2px solid var(--border);
                `;
                previewImg.alt = 'Preview gambar';
                previewContainer.appendChild(previewImg);
            }
            
            let fileInfo = previewContainer.querySelector('.file-info');
            if (!fileInfo) {
                fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                fileInfo.style.cssText = `
                    font-size: 0.85rem;
                    color: var(--gray);
                `;
                previewContainer.appendChild(fileInfo);
            }
            
            previewImg.src = e.target.result;
            fileInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;
            
            // Remove button
            let removeBtn = previewContainer.querySelector('.remove-preview');
            if (!removeBtn) {
                removeBtn = document.createElement('button');
                removeBtn.className = 'remove-preview';
                removeBtn.type = 'button';
                removeBtn.innerHTML = '<i class="fas fa-times"></i> Hapus';
                removeBtn.style.cssText = `
                    background: var(--danger);
                    color: white;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                `;
                removeBtn.addEventListener('click', () => {
                    previewContainer.remove();
                    input.value = '';
                });
                previewContainer.appendChild(removeBtn);
            }
        };
        
        reader.readAsDataURL(file);
    }
    
    // Setup search functionality
    function setupSearch() {
        const searchInputs = document.querySelectorAll('.search-input, #tableSearch');
        
        searchInputs.forEach(input => {
            const debouncedSearch = debounce(function() {
                const table = findTableForSearch(this);
                if (table) {
                    filterTable(table, this.value);
                }
            }, CONFIG.DEBOUNCE_DELAY);
            
            input.addEventListener('input', debouncedSearch);
        });
    }
    
    // Find table for search input
    function findTableForSearch(input) {
        // Look for table in the same card
        const card = input.closest('.card');
        if (card) {
            return card.querySelector('table');
        }
        
        // Fallback to common table IDs
        const tableIds = ['transactionsTable', 'obatTable', 'dokterTable'];
        for (const id of tableIds) {
            const table = document.getElementById(id);
            if (table) return table;
        }
        
        return null;
    }
    
    // Filter table rows
    function filterTable(table, filterText) {
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const filter = filterText.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(filter);
            row.style.display = isVisible ? '' : 'none';
            row.setAttribute('aria-hidden', !isVisible);
        });
        
        // Show no results message if needed
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        if (visibleRows.length === 0 && filterText !== '') {
            showNoResultsMessage(table, filterText);
        } else {
            removeNoResultsMessage(table);
        }
    }
    
    // Show no results message
    function showNoResultsMessage(table, filterText) {
        let noResultsRow = table.querySelector('.no-results-row');
        
        if (!noResultsRow) {
            const tbody = table.querySelector('tbody');
            noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results-row';
            noResultsRow.innerHTML = `
                <td colspan="100%" class="text-center">
                    <i class="fas fa-search"></i>
                    <p>Tidak ada hasil untuk "${escapeHTML(filterText)}"</p>
                </td>
            `;
            tbody.appendChild(noResultsRow);
        }
    }
    
    // Remove no results message
    function removeNoResultsMessage(table) {
        const noResultsRow = table.querySelector('.no-results-row');
        if (noResultsRow) noResultsRow.remove();
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Escape key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
        
        // Close modals on outside click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Initialize delete confirmation
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', deleteTransaction);
        }
    }
    
    // ====== TRANSAKSI FUNCTIONS ======
    function initializeTransaksi() {
        // Setup search for transaksi
        const searchTable = document.getElementById('tableSearch');
        if (searchTable) {
            searchTable.addEventListener('input', function(e) {
                const filter = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('#transactionsTable tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
        
        // Setup spesialisasi lainnya toggle
        const spesialisasiSelect = document.getElementById('spesialisasi');
        const spesialisasiLainnya = document.getElementById('spesialisasi_lainnya');
        
        if (spesialisasiSelect && spesialisasiLainnya) {
            spesialisasiSelect.addEventListener('change', function(e) {
                if (e.target.value === 'Lainnya') {
                    spesialisasiLainnya.style.display = 'block';
                    spesialisasiLainnya.required = true;
                } else {
                    spesialisasiLainnya.style.display = 'none';
                    spesialisasiLainnya.required = false;
                }
            });
            
            spesialisasiLainnya.addEventListener('input', function(e) {
                if (e.target.value.trim() !== '') {
                    spesialisasiSelect.value = 'Lainnya';
                }
            });
        }
    }
    
    // View transaction detail
    async function viewTransactionDetail(transactionId) {
        try {
            showLoading();
            const response = await fetch(`${CONFIG.API_BASE_URL}get_transaction_detail.php?id=${transactionId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                showDetailModal(data.transaction, data.items || []);
            } else {
                showNotification('Gagal memuat detail: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat memuat detail transaksi', 'error');
        } finally {
            hideLoading();
        }
    }
    
    // Show detail modal
    function showDetailModal(transaction, items) {
        const modal = document.getElementById('detailModal');
        const modalBody = document.getElementById('detailModalBody');
        
        if (!modal || !modalBody) {
            console.error('Modal elements not found');
            return;
        }
        
        // Format items HTML
        let itemsHtml = '';
        if (items && items.length > 0) {
            itemsHtml = `
                <div class="detail-section">
                    <h4><i class="fas fa-shopping-cart"></i> Item Pembelian</h4>
                    <div class="items-list">
                        ${items.map(item => `
                            <div class="item-row">
                                <div class="item-name">${escapeHTML(item.nama_obat)}</div>
                                <div class="item-qty">${item.jumlah} x Rp ${formatNumber(item.harga)}</div>
                                <div class="item-total">Rp ${formatNumber(item.subtotal)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        // Set modal content
        modalBody.innerHTML = `
            <div class="detail-section">
                <h4><i class="fas fa-info-circle"></i> Informasi Transaksi</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">ID Transaksi</span>
                        <span class="detail-value">#TRX${transaction.id_transaksi.toString().padStart(6, '0')}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tanggal</span>
                        <span class="detail-value">${formatDate(transaction.tanggal_transaksi)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Nama Pembeli</span>
                        <span class="detail-value">${escapeHTML(transaction.nama_pembeli)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value status-badge ${transaction.status_pembayaran}">
                            ${transaction.status_pembayaran}
                        </span>
                    </div>
                </div>
            </div>
            
            ${itemsHtml}
            
            <div class="detail-section">
                <h4><i class="fas fa-money-bill-wave"></i> Informasi Pembayaran</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Metode Pembayaran</span>
                        <span class="detail-value">${escapeHTML(transaction.metode_pembayaran)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Pembayaran</span>
                        <span class="detail-value total">Rp ${formatNumber(transaction.total_harga)}</span>
                    </div>
                </div>
            </div>
            
            ${transaction.alamat_pengiriman ? `
            <div class="detail-section">
                <h4><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h4>
                <p>${escapeHTML(transaction.alamat_pengiriman)}</p>
            </div>
            ` : ''}
        `;
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus trap for accessibility
        trapFocus(modal);
    }
    
    // Confirm delete transaction
    function confirmDelete(transactionId) {
        state.currentTransactionId = transactionId;
        const deleteModal = document.getElementById('deleteModal');
        
        if (deleteModal) {
            deleteModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            trapFocus(deleteModal);
        }
    }
    
    // Delete transaction
    async function deleteTransaction() {
        if (!state.currentTransactionId) return;
        
        try {
            showLoading();
            const response = await fetch(`${CONFIG.API_BASE_URL}delete_transaksi.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: state.currentTransactionId })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Transaksi berhasil dihapus', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Gagal menghapus: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat menghapus transaksi', 'error');
        } finally {
            hideLoading();
            closeDeleteModal();
        }
    }
    
    // Close delete modal
    function closeDeleteModal() {
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        state.currentTransactionId = null;
    }
    
    // Close detail modal
    function closeDetailModal() {
        const detailModal = document.getElementById('detailModal');
        if (detailModal) {
            detailModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    // Close all modals
    function closeAllModals() {
        closeDetailModal();
        closeDeleteModal();
    }
    
    // ====== NOTIFICATION SYSTEM ======
    function showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.floating-notification');
        if (existingNotification) existingNotification.remove();
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `floating-notification ${type}`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        
        // Create safe HTML content
        const icon = document.createElement('i');
        icon.className = `fas ${getNotificationIcon(type)}`;
        
        const text = document.createTextNode(message);
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'notification-close';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.setAttribute('aria-label', 'Tutup notifikasi');
        closeBtn.addEventListener('click', () => removeNotification(notification));
        
        notification.appendChild(icon);
        notification.appendChild(text);
        notification.appendChild(closeBtn);
        
        document.body.appendChild(notification);
        
        // Auto remove after delay
        const timeoutId = setTimeout(() => removeNotification(notification), CONFIG.AUTO_DISMISS_DELAY);
        
        // Store timeout ID for possible cancellation
        notification.dataset.timeoutId = timeoutId;
    }
    
    function getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }
    
    function removeNotification(notification) {
        if (notification && notification.parentNode) {
            // Clear auto-dismiss timeout
            if (notification.dataset.timeoutId) {
                clearTimeout(parseInt(notification.dataset.timeoutId));
            }
            
            notification.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }
    
    // ====== UTILITY FUNCTIONS ======
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Format number with thousand separators
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    // Format date
    function formatDate(dateString) {
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString;
            }
            return date.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            console.error('Date formatting error:', error);
            return dateString;
        }
    }
    
    // Escape HTML to prevent XSS
    function escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Create loading overlay
    function createLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner"></div>';
        overlay.setAttribute('aria-label', 'Loading');
        overlay.setAttribute('aria-live', 'assertive');
        return overlay;
    }
    
    // Show loading overlay
    function showLoading() {
        state.isLoading = true;
        elements.loadingOverlay.style.display = 'flex';
    }
    
    // Hide loading overlay
    function hideLoading() {
        state.isLoading = false;
        elements.loadingOverlay.style.display = 'none';
    }
    
    // Trap focus inside modal for accessibility
    function trapFocus(modal) {
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        modal.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab') return;
            
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    lastElement.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastElement) {
                    firstElement.focus();
                    e.preventDefault();
                }
            }
        });
        
        // Focus first element
        setTimeout(() => firstElement.focus(), 100);
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
    `;
    document.head.appendChild(animationStyles);
    
    // Export functions to global scope
    window.AdminPanel = {
        showNotification,
        viewTransactionDetail,
        confirmDelete,
        closeDetailModal,
        closeDeleteModal,
        formatNumber,
        formatDate,
        escapeHTML
    };
})();