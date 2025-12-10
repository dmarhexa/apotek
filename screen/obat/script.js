// apotek/screens/obat/script.js

// ========== FUNGSI UTILITAS ==========

// Format number
function formatNumber(num) {
    if (!num) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Safe HTML output
function safeHtml(text) {
    if (typeof text !== 'string') return text || '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Notification system
function showNotification(message, type = 'success') {
    // Hapus notifikasi lama jika ada
    document.querySelectorAll('.notification-toast').forEach(n => n.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${safeHtml(message)}</span>
        <button class="notification-close">&times;</button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);

    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.remove();
    });
}

// ========== INITIALIZATION ==========

document.addEventListener('DOMContentLoaded', function () {
    console.log("Script loaded successfully");
    
    // View toggle
    const viewBtns = document.querySelectorAll('.view-btn');
    const productsView = document.getElementById('productsView');

    viewBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const view = this.getAttribute('data-view');
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            productsView.className = `products-${view}`;
            localStorage.setItem('productView', view);
        });
    });

    // Load saved view
    const savedView = localStorage.getItem('productView') || 'grid';
    const activeBtn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
    if (activeBtn) activeBtn.click();
    
    // Quick view buttons (jika ada)
    document.querySelectorAll('.quick-view').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            console.log("Quick view clicked for product:", productId);
            // showProductDetail(productId);
        });
    });
});

// ========== PURCHASE FORM FUNCTIONS ==========

let currentProduct = {};

function showPurchaseForm(productId, productName, price, stock) {
    console.log("Showing purchase form for:", productName, "ID:", productId);
    
    currentProduct = {
        id: productId,
        name: productName,
        price: price,
        stock: stock
    };

    document.getElementById('product_id').value = productId;
    document.getElementById('product_name').value = productName;
    document.getElementById('unit_price').value = 'Rp ' + formatNumber(price);
    document.getElementById('available_stock').textContent = stock;

    // Reset form
    document.getElementById('quantity').value = 1;
    document.getElementById('quantity').max = stock;
    calculateTotal();

    document.getElementById('purchaseModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closePurchaseForm() {
    document.getElementById('purchaseModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('purchaseForm').reset();
    currentProduct = {};
}

function calculateTotal() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const unitPrice = currentProduct.price;
    const total = quantity * unitPrice;
    
    document.getElementById('total_harga').value = 'Rp ' + formatNumber(total);
}

// Modal close on outside click
document.addEventListener('click', function (e) {
    const purchaseModal = document.getElementById('purchaseModal');
    if (purchaseModal && e.target === purchaseModal) {
        closePurchaseForm();
    }
});

// Handle form submission
document.addEventListener('DOMContentLoaded', function () {
    const purchaseForm = document.getElementById('purchaseForm');
    if (purchaseForm) {
        purchaseForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            
            if (quantity > currentProduct.stock) {
                showNotification('Jumlah melebihi stok yang tersedia!', 'error');
                return;
            }

            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            // Prepare form data
            const formData = new FormData(this);
            formData.append('quantity', quantity);

            // Debug form data
            console.log("Form data:");
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // Send request
            fetch('../../api/create_transaksi.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log("Response status:", response.status);
                return response.text();
            })
            .then(text => {
                console.log("Raw response:", text);
                
                try {
                    const data = JSON.parse(text);
                    console.log("Parsed JSON:", data);
                    
                    if (data.success) {
                        showNotification('✅ Transaksi berhasil dibuat!', 'success');
                        closePurchaseForm();
                        
                        // Show transaction popup
                        if (data.transaction_details && data.transaction_details.length > 0) {
                            setTimeout(() => {
                                showSimpleTransactionPopup(data.transaction_details);
                            }, 500);
                        } else {
                            setTimeout(() => {
                                showNotification('Transaksi berhasil! Halaman akan direfresh.', 'success');
                                setTimeout(() => location.reload(), 1500);
                            }, 1000);
                        }
                        
                    } else {
                        showNotification('❌ ' + data.message, 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                    
                } catch (e) {
                    console.error("JSON Parse Error:", e);
                    console.error("Response text was:", text);
                    showNotification('⚠️ Respons tidak valid dari server', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error("Fetch Error:", error);
                showNotification('🔌 Gagal terhubung ke server: ' + error.message, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});

// ========== SIMPLIFIED TRANSACTION POPUP ==========

function showSimpleTransactionPopup(transactionData) {
    console.log("Showing transaction popup with data:", transactionData);
    
    if (!transactionData || transactionData.length === 0) {
        showNotification('Data transaksi tidak ditemukan', 'error');
        return;
    }

    // Create popup
    const popup = document.createElement('div');
    popup.className = 'simple-transaction-popup';
    popup.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease;
    `;
    
    const transactionDate = new Date().toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const totalItems = transactionData.reduce((sum, item) => sum + (parseInt(item.jumlah) || 0), 0);
    const totalPrice = transactionData[0].total_harga || transactionData[0].subtotal || 0;
    const transactionId = transactionData[0].id_transaksi || '000000';
    
    popup.innerHTML = `
        <div class="popup-content" style="
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        ">
            <div class="popup-header" style="
                text-align: center;
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 2px solid #f0fdf4;
            ">
                <div style="font-size: 3rem; color: #10b981; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 style="color: #065f46; margin-bottom: 10px;">Transaksi Berhasil!</h2>
                <p style="color: #6b7280; margin-bottom: 5px;">${transactionDate}</p>
                <p style="color: #10b981; font-weight: 600; font-size: 1.1rem;">
                    ID: #${transactionId.toString().padStart(6, '0')}
                </p>
            </div>
            
            <div class="popup-body" style="margin-bottom: 25px;">
                <div style="
                    background: #f0fdf4;
                    padding: 20px;
                    border-radius: 12px;
                    margin-bottom: 20px;
                    border: 2px solid #d1fae5;
                ">
                    <h4 style="color: #065f46; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-pills"></i> Obat yang Dibeli
                    </h4>
                    
                    ${transactionData.map(item => `
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 12px 0;
                            border-bottom: 1px solid #d1fae5;
                        ">
                            <div>
                                <strong style="color: #1f2937;">${safeHtml(item.nama_obat || 'N/A')}</strong>
                                <div style="font-size: 0.9rem; color: #6b7280; margin-top: 4px;">
                                    ${item.jumlah || 0} x Rp ${formatNumber(item.harga_satuan || 0)}
                                </div>
                            </div>
                            <div style="font-weight: 700; color: #10b981;">
                                Rp ${formatNumber(item.subtotal || 0)}
                            </div>
                        </div>
                    `).join('')}
                    
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding-top: 15px;
                        margin-top: 15px;
                        border-top: 2px solid #d1fae5;
                    ">
                        <div>
                            <strong style="color: #1f2937; font-size: 1.1rem;">Total</strong>
                            <div style="font-size: 0.9rem; color: #6b7280;">
                                ${totalItems} item
                            </div>
                        </div>
                        <div style="font-size: 1.3rem; font-weight: 800; color: #065f46;">
                            Rp ${formatNumber(totalPrice)}
                        </div>
                    </div>
                </div>
                
                <div style="
                    background: #f8fafc;
                    padding: 15px;
                    border-radius: 10px;
                    text-align: center;
                    color: #6b7280;
                    font-size: 0.95rem;
                ">
                    <i class="fas fa-info-circle"></i>
                    Terima kasih telah berbelanja di Apotek Sehat
                </div>
            </div>
            
            <div class="popup-footer" style="text-align: center;">
                <button onclick="closeSimplePopup()" style="
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: white;
                    border: none;
                    padding: 15px 30px;
                    border-radius: 10px;
                    font-size: 1rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                    width: 100%;
                ">
                    <i class="fas fa-check"></i> Tutup & Lanjutkan Belanja
                </button>
                <p style="color: #9ca3af; font-size: 0.9rem; margin-top: 15px;">
                    Halaman akan otomatis refresh dalam <span id="countdown">5</span> detik
                </p>
            </div>
        </div>
    `;
    
    document.body.appendChild(popup);
    document.body.style.overflow = 'hidden';
    
    // Start countdown
    startSimpleCountdown(5);
}

function closeSimplePopup() {
    const popup = document.querySelector('.simple-transaction-popup');
    if (popup) {
        popup.style.opacity = '0';
        setTimeout(() => {
            popup.remove();
            document.body.style.overflow = 'auto';
            location.reload(); // Refresh halaman
        }, 300);
    }
}

function startSimpleCountdown(seconds) {
    let countdown = seconds;
    const countdownElement = document.getElementById('countdown');
    
    const interval = setInterval(() => {
        countdown--;
        if (countdownElement) {
            countdownElement.textContent = countdown;
        }
        
        if (countdown <= 0) {
            clearInterval(interval);
            closeSimplePopup();
        }
    }, 1000);
}

// ========== STYLES ==========

// Add styles for notifications
const styleElement = document.createElement('style');
styleElement.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .notification-toast {
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
        z-index: 10001;
        animation: fadeIn 0.3s ease;
        transition: all 0.3s;
        border-left: 4px solid #10b981;
        font-family: 'Inter', sans-serif;
        min-width: 300px;
        max-width: 400px;
    }
    
    .notification-error {
        border-left-color: #ef4444;
    }
    
    .notification-close {
        background: none;
        border: none;
        margin-left: auto;
        cursor: pointer;
        font-size: 1.2rem;
        color: #6b7280;
    }
    
    .no-products-message {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: #f8fafc;
        border-radius: 12px;
        margin: 20px 0;
    }
    
    .no-products-message h3 {
        color: #1f2937;
        margin-bottom: 10px;
    }
    
    .no-products-message p {
        color: #6b7280;
    }
    
    .fa-spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;

document.head.appendChild(styleElement);