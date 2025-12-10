// apotek/screens/transaksi/script.js

document.addEventListener('DOMContentLoaded', function() {
    // Search with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('searchForm').submit();
        }, 500);
    });
    
    // Initialize product search for new transaction modal
    if (document.getElementById('productSearch')) {
        initProductSearch();
    }
    
    // Load cart data if exists
    loadCartData();
});

// Product search functionality
function initProductSearch() {
    const productSearch = document.getElementById('productSearch');
    let searchTimeout;
    
    productSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchProduct();
        }, 300);
    });
}

let selectedProducts = [];

function searchProduct() {
    const searchTerm = document.getElementById('productSearch').value;
    
    if (!searchTerm.trim()) {
        document.getElementById('productsList').innerHTML = '<p class="no-products">Masukkan kata kunci pencarian</p>';
        return;
    }
    
    fetch(`../../api/search_products.php?q=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
            const productsList = document.getElementById('productsList');
            
            if (data.success && data.products.length > 0) {
                let html = '<div class="search-results">';
                
                data.products.forEach(product => {
                    // Check if product already selected
                    const isSelected = selectedProducts.some(p => p.id == product.id_obat);
                    
                    html += `
                        <div class="product-result ${isSelected ? 'selected' : ''}" data-id="${product.id_obat}">
                            <div class="product-result-info">
                                <h5>${product.nama_obat}</h5>
                                <p class="product-category">${product.kategori}</p>
                                <p class="product-price">Rp ${formatNumber(product.harga)}</p>
                                <p class="product-stock ${product.stok > 10 ? 'in-stock' : product.stok > 0 ? 'low-stock' : 'out-stock'}">
                                    Stok: ${product.stok}
                                </p>
                            </div>
                            <div class="product-result-actions">
                                ${isSelected ? 
                                    `<button class="btn-remove" onclick="removeProduct(${product.id_obat})">
                                        <i class="fas fa-times"></i> Hapus
                                    </button>` :
                                    `<button class="btn-add" onclick="addProduct(${product.id_obat}, '${product.nama_obat}', ${product.harga}, ${product.stok})">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>`
                                }
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                productsList.innerHTML = html;
            } else {
                productsList.innerHTML = '<p class="no-products">Tidak ada produk ditemukan</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('productsList').innerHTML = '<p class="no-products">Error saat mencari produk</p>';
        });
}

function addProduct(productId, productName, price, stock) {
    // Check if already selected
    if (selectedProducts.some(p => p.id == productId)) {
        alert('Produk sudah ditambahkan');
        return;
    }
    
    // Check stock
    if (stock <= 0) {
        alert('Stok produk habis');
        return;
    }
    
    selectedProducts.push({
        id: productId,
        name: productName,
        price: price,
        stock: stock,
        quantity: 1
    });
    
    updateSelectedProducts();
    searchProduct(); // Refresh search results
}

function removeProduct(productId) {
    selectedProducts = selectedProducts.filter(p => p.id != productId);
    updateSelectedProducts();
    searchProduct(); // Refresh search results
}

function updateSelectedProducts() {
    const selectedProductsDiv = document.getElementById('selectedProducts');
    const subtotalElement = document.getElementById('subtotalAmount');
    const totalElement = document.getElementById('totalAmount');
    
    if (selectedProducts.length === 0) {
        selectedProductsDiv.innerHTML = '<p class="no-selected">Belum ada produk dipilih</p>';
        subtotalElement.textContent = 'Rp 0';
        totalElement.textContent = 'Rp 0';
        return;
    }
    
    let html = '<div class="selected-products-list">';
    let subtotal = 0;
    
    selectedProducts.forEach((product, index) => {
        const productTotal = product.price * product.quantity;
        subtotal += productTotal;
        
        html += `
            <div class="selected-product-item">
                <div class="selected-product-info">
                    <h5>${product.name}</h5>
                    <p class="selected-product-price">Rp ${formatNumber(product.price)}</p>
                </div>
                <div class="selected-product-controls">
                    <div class="quantity-controls">
                        <button class="btn-qty minus" onclick="updateQuantity(${index}, -1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" 
                               value="${product.quantity}" 
                               min="1" 
                               max="${product.stock}"
                               onchange="updateQuantityInput(${index}, this.value)">
                        <button class="btn-qty plus" onclick="updateQuantity(${index}, 1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="selected-product-total">
                        Rp ${formatNumber(productTotal)}
                    </div>
                    <button class="btn-remove-selected" onclick="removeProduct(${product.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    selectedProductsDiv.innerHTML = html;
    
    subtotalElement.textContent = 'Rp ' + formatNumber(subtotal);
    totalElement.textContent = 'Rp ' + formatNumber(subtotal);
}

function updateQuantity(index, change) {
    const product = selectedProducts[index];
    const newQuantity = product.quantity + change;
    
    if (newQuantity < 1) {
        removeProduct(product.id);
        return;
    }
    
    if (newQuantity > product.stock) {
        alert('Jumlah melebihi stok yang tersedia!');
        return;
    }
    
    product.quantity = newQuantity;
    updateSelectedProducts();
}

function updateQuantityInput(index, value) {
    const product = selectedProducts[index];
    const newQuantity = parseInt(value) || 1;
    
    if (newQuantity < 1) {
        product.quantity = 1;
    } else if (newQuantity > product.stock) {
        alert('Jumlah melebihi stok yang tersedia!');
        product.quantity = product.stock;
    } else {
        product.quantity = newQuantity;
    }
    
    updateSelectedProducts();
}

// Handle new transaction form submission
document.getElementById('newTransactionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (selectedProducts.length === 0) {
        alert('Pilih minimal satu produk untuk transaksi');
        return;
    }
    
    // Validate all quantities
    for (const product of selectedProducts) {
        if (product.quantity < 1) {
            alert('Jumlah produk tidak valid');
            return;
        }
        if (product.quantity > product.stock) {
            alert(`Jumlah ${product.name} melebihi stok yang tersedia`);
            return;
        }
    }
    
    const formData = new FormData(this);
    
    // Add products to form data
    selectedProducts.forEach((product, index) => {
        formData.append(`products[${index}][id]`, product.id);
        formData.append(`products[${index}][quantity]`, product.quantity);
        formData.append(`products[${index}][price]`, product.price);
    });
    
    // Calculate total
    const total = selectedProducts.reduce((sum, product) => sum + (product.price * product.quantity), 0);
    formData.append('total_harga', total);
    
    // Submit form
    fetch('../../api/create_transaksi.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Transaksi berhasil dibuat! ID Transaksi: ' + data.transaction_id);
            closeNewTransactionModal();
            location.reload(); // Refresh page to show new transaction
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat membuat transaksi');
    });
});

// Load cart data from sessionStorage
function loadCartData() {
    try {
        const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
        
        if (cart.length > 0) {
            // Convert cart to selectedProducts format
            selectedProducts = cart.map(item => ({
                id: item.id,
                name: item.name,
                price: item.price,
                stock: 100, // Default stock, should be fetched from server
                quantity: item.quantity || 1
            }));
            
            // If in new transaction modal, update display
            if (document.getElementById('selectedProducts')) {
                updateSelectedProducts();
            }
        }
    } catch (e) {
        console.error('Error loading cart:', e);
    }
}

// Format number helper
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Add styles for new transaction modal
const transactionStyles = document.createElement('style');
transactionStyles.textContent = `
    .search-results {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .product-result {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #e5e7eb;
        background: white;
        transition: all 0.3s;
    }
    
    .product-result:hover {
        background: #f9fafb;
    }
    
    .product-result.selected {
        background: #f0fdf4;
        border-left: 4px solid #10b981;
    }
    
    .product-result-info h5 {
        font-size: 1rem;
        color: #1f2937;
        margin-bottom: 5px;
    }
    
    .product-category {
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 5px;
    }
    
    .product-price {
        font-weight: 600;
        color: #10b981;
        font-size: 0.9rem;
    }
    
    .product-stock {
        font-size: 0.8rem;
        padding: 2px 8px;
        border-radius: 10px;
        display: inline-block;
    }
    
    .product-stock.in-stock {
        background: #d1fae5;
        color: #065f46;
    }
    
    .product-stock.low-stock {
        background: #fef3c7;
        color: #92400e;
    }
    
    .product-stock.out-stock {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .product-result-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-add, .btn-remove {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-add {
        background: #10b981;
        color: white;
    }
    
    .btn-add:hover {
        background: #059669;
    }
    
    .btn-remove {
        background: #ef4444;
        color: white;
    }
    
    .btn-remove:hover {
        background: #dc2626;
    }
    
    .no-products, .no-selected {
        text-align: center;
        color: #6b7280;
        padding: 20px;
        font-style: italic;
    }
    
    .selected-products-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .selected-product-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .selected-product-info h5 {
        font-size: 1rem;
        color: #1f2937;
        margin-bottom: 5px;
    }
    
    .selected-product-price {
        font-size: 0.9rem;
        color: #10b981;
        font-weight: 500;
    }
    
    .selected-product-controls {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-qty {
        width: 30px;
        height: 30px;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
    
    .btn-qty:hover {
        background: #f3f4f6;
    }
    
    .selected-product-controls input[type="number"] {
        width: 50px;
        padding: 5px;
        text-align: center;
        border: 1px solid #d1d5db;
        border-radius: 4px;
    }
    
    .selected-product-total {
        font-weight: 600;
        color: #1f2937;
        min-width: 100px;
        text-align: right;
    }
    
    .btn-remove-selected {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 1rem;
        padding: 5px;
    }
    
    .btn-remove-selected:hover {
        color: #dc2626;
    }
`;
document.head.appendChild(transactionStyles);