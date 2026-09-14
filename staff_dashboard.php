<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Get initial cart count for the navbar badge
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laman Utama</title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .product-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .product-img-box { height: 160px; background-color: #f8f9fa; border-bottom: 1px solid #eaeaea; padding: 10px; }
        .product-img-box img { max-height: 100%; max-width: 100%; object-fit: contain; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        ::-webkit-scrollbar { display: none; }
        html, body { -ms-overflow-style: none; scrollbar-width: none; }
        
        .nav-link { display: flex; align-items: center; }
        .nav-link i { font-size: 1.2rem; width: 24px; text-align: center; }
        .nav-text { max-width: 0; opacity: 0; overflow: hidden; white-space: nowrap; transition: max-width 0.4s ease, opacity 0.3s ease, margin-left 0.3s ease; }
        .nav-link:hover .nav-text, .nav-link.active .nav-text { max-width: 150px; opacity: 1; margin-left: 8px; }

        .cart-badge { position: absolute; top: -5px; right: -8px; font-size: 0.65rem; padding: 0.25em 0.5em; }
        
        .btn-qty-hover:hover { background-color: #e9ecef !important; }

        /* Smooth fade in for live updates */
        .fade-in-item { animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* ========================================== */
        /* GLOBAL MOBILE RESPONSIVENESS FIXES         */
        /* ========================================== */
        
        /* 1. Fix Navbar Menu on Small Screens (Offcanvas Menu) */
        @media (max-width: 991px) {
            .offcanvas-end {
                width: 280px !important; 
            }
            .nav-link .nav-text {
                max-width: 100% !important;
                opacity: 1 !important;
                margin-left: 10px !important;
                white-space: normal;
            }
            .nav-item {
                padding: 10px 0;
                border-bottom: 1px solid #f1f1f1;
            }
            .nav-item:last-child {
                border-bottom: none;
            }
        }

        /* 2. Fix Request Cards, Dashboards & Modals */
        @media (max-width: 767px) {
            .list-group-item > .d-flex.justify-content-between {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px;
            }
            .list-group-item .btn-group, 
            .list-group-item .d-flex.flex-column.align-items-end {
                width: 100%;
                align-items: stretch !important;
            }
            .list-group-item .btn-group .btn {
                flex: 1; 
                padding: 10px;
            }
            #cartBody .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            #cartBody > div > div:last-child {
                width: 100%;
                justify-content: space-between !important;
                border-top: 1px dashed #dee2e6;
                padding-top: 10px;
            }
            .kpi-card {
                margin-bottom: 10px;
            }
            .kpi-card .card-body {
                padding: 1.25rem !important;
            }
            body { font-size: 0.9rem; }
            h3 { font-size: 1.5rem; }
            h4 { font-size: 1.25rem; }
        }

        /* Hover animation for floating button */
        .floating-cart-btn {
            transition: transform 0.2s;
        }
        .floating-cart-btn:active {
            transform: scale(0.9);
        }

        /* ========================================== */
        /* 3. RESIZE LOGO ON MOBILE (KEEP NAVBAR HEIGHT) */
        /* ========================================== */
        @media (max-width: 767px) {
            .navbar-brand img {
                height: 45px !important; 
            }
            .navbar-brand {
                min-height: 70px; 
            }
            .navbar-brand .fs-6 {
                font-size: 0.9rem !important;
            }
        }
    </style>
</head>
<body class="bg-light">

    <!-- Sticky Staff Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top mb-4 shadow-sm py-1">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="staff_dashboard.php">
                <img src="images/logo-semua.png" alt="Logo" height="70" class="d-inline-block align-text-top me-2">
                <div class="d-flex flex-column fs-6 fw-bold lh-sm text-dark">
                    <span>Sistem Inventori</span>
                    <span class="text-primary">Dakwat Printer</span>
                </div>
            </a>
            
            <!-- OFFCANVAS TOGGLER BUTTON -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#staffNav" aria-controls="staffNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- OFFCANVAS CONTAINER -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="staffNav" aria-labelledby="staffNavLabel">
                
                <!-- Only visible in mobile drawer view -->
                <div class="offcanvas-header border-bottom px-4">
                    <h5 class="offcanvas-title fw-bold text-dark" id="staffNavLabel">Menu Sistem</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                
                <div class="offcanvas-body px-4 px-lg-0">
                    <ul class="navbar-nav ms-auto me-lg-3 gap-2 align-items-lg-center flex-grow-1 flex-lg-grow-0">
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'staff_dashboard.php' ? 'active text-primary fw-bold' : '' ?>" href="staff_dashboard.php">
                                <i class="fa-solid fa-print"></i>
                                <span class="nav-text">Mohon Dakwat</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'staff_requests.php' ? 'active text-primary fw-bold' : '' ?>" href="staff_requests.php">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span class="nav-text">Rekod</span>
                            </a>
                        </li>
                        <!-- DESKTOP CART BUTTON (Hidden on Mobile) -->
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0 d-none d-lg-inline-flex">
                            <button type="button" class="btn btn-primary position-relative fw-bold rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 45px; height: 45px;" onclick="openCartModal()">
                                <i class="fa-solid fa-clipboard-list"></i>
                                <span id="navCartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" <?= $cartCount == 0 ? 'style="display:none;"' : '' ?>>
                                    <?= $cartCount ?>
                                </span>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="d-flex align-items-center ms-lg-3 mt-4 mt-lg-0 pb-4 pb-lg-0">
                        <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i> Keluar</a>
                    </div>
                </div>
                
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="mb-4">
            <h4 class="mb-0 fw-bold"><i class="fa-solid fa-print text-black me-2"></i>Pilih Dakwat Untuk Di Mohon</h4>
            <p class="text-muted">Pilih model dakwat anda di bawah untuk melihat pilihan warna.</p>
        </div>

        <!-- The Polling Listener will inject the inventory cards here -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="inventoryContainer">
            <div class="col-12 text-center text-muted py-5">
                <i class="fa-solid fa-spinner fa-spin fa-3x mb-3"></i>
                <p>Menarik data stok secara langsung...</p>
            </div>
        </div>
    </div>

    <!-- FLOATING CART BUTTON (Mobile Only) -->
    <button type="button" class="btn btn-primary position-fixed rounded-circle shadow-lg d-flex d-lg-none align-items-center justify-content-center floating-cart-btn" 
            style="bottom: 25px; right: 25px; width: 60px; height: 60px; z-index: 1050;" 
            onclick="openCartModal()">
        <i class="fa-solid fa-clipboard-list fa-xl"></i>
        <span id="mobileCartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" <?= $cartCount == 0 ? 'style="display:none;"' : '' ?>>
            <?= $cartCount ?>
        </span>
    </button>

    <!-- MODAL 2: The Modern Add to Cart Form -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                
                <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4 position-relative">
                    <h5 class="modal-title fw-bold text-dark w-100 text-center">
                        Sahkan Kuantiti
                    </h5>
                    <button type="button" class="btn-close position-absolute top-0 end-0 mt-3 me-3 shadow-none" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body text-center px-4 pt-2 pb-4">
                    <div id="reqImageContainer" class="mx-auto mb-3 d-flex justify-content-center align-items-center rounded-4 shadow-sm" style="height: 120px; width: 180px; background: linear-gradient(135deg, #f6f8fd 0%, #f1f5f9 100%); padding: 15px;">
                        <img id="reqImage" src="" alt="Ink Color" style="max-height: 100%; max-width: 100%; object-fit: contain; display: none;">
                        <i id="reqImagePlaceholder" class="fa-solid fa-droplet fa-3x text-dark opacity-50" style="display: none;"></i>
                    </div>
                    
                    <h5 id="reqBrandModel" class="fw-bolder mb-1 text-dark"></h5>
                    <div id="reqColour" class="mb-4"></div>
                    
                    <div class="bg-light rounded-4 p-3 mb-2 border">
                        <label class="form-label fw-bold text-secondary small text-uppercase mb-2">Kuantiti Diperlukan</label>
                        <div class="input-group input-group-lg mx-auto shadow-sm rounded-pill overflow-hidden" style="max-width: 150px; border: 1px solid #dee2e6;">
                            <button class="btn btn-white bg-white border-0 px-3 btn-qty-hover" type="button" onclick="adjustQty(-1)"><i class="fa-solid fa-minus text-dark"></i></button>
                            <input type="number" id="reqQuantity" class="form-control text-center fw-bold border-0 bg-white" value="1" min="1" readonly>
                            <button class="btn btn-white bg-white border-0 px-3 btn-qty-hover" type="button" onclick="adjustQty(1)"><i class="fa-solid fa-plus text-dark"></i></button>
                        </div>
                        <small class="text-muted d-block mt-2">Baki Stok: <span id="reqMaxQty" class="fw-bold text-dark"></span></small>
                    </div>
                    
                    <input type="hidden" id="reqColourId">
                    <input type="hidden" id="reqParentModelId">
                </div>
                
                <div class="modal-footer bg-white border-top-0 pt-0 px-4 pb-4 d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted border shadow-sm" onclick="goBackToModel()">Kembali</button>
                    <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" onclick="addToCart()">
                        Tambah <i class="fa-solid fa-clipboard-list ms-1"></i>
                    </button>
                </div>
                
            </div>
        </div>
    </div>

    <!-- MODAL 3: The Modern Shopping Cart Review -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-clipboard-list text-primary me-2"></i> Permohonan Anda
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body px-4 py-4" id="cartBody">
                    <!-- Cart items load here via JS -->
                </div>

                <div class="px-4 pb-3" id="sebabContainer" style="display: none;">
                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">Sebab Permohonan <span class="text-danger">*</span></label>
                    <textarea id="reqReason" class="form-control bg-light border-0 shadow-sm" rows="2" placeholder="Cth: Dakwat habis untuk printer Jabatan Kewangan..." required></textarea>
                </div>
                
                <div class="modal-footer bg-light border-top-0 rounded-bottom-4 px-4 py-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-light text-danger fw-bold border shadow-sm px-3" onclick="clearCart()">
                        <i class="fa-solid fa-trash-can"></i> 
                        <span class="d-none d-sm-inline ms-1">Kosongkan</span>
                    </button>
                    <button type="button" class="btn btn-primary fw-bold shadow-sm px-4 py-2" onclick="checkout()" id="btnCheckout">
                        <span class="d-none d-sm-inline me-1">Hantar Permohonan</span> 
                        <i class="fa-solid fa-check"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    let requestModalInstance = null; 
    let cartModalInstance = null;

    // ==========================================
    // FUNGSI PEWARNAAN DINAMIK
    // ==========================================
    function getDropletColor(colorName) {
        const name = colorName.toLowerCase();
        
        // Carian perkataan untuk menentukan warna
        if (name.includes('black') || name.includes('hitam')) return 'color: #212529;'; // Hitam
        if (name.includes('cyan') || name.includes('biru')) return 'color: #00b0f0;'; // Cyan
        if (name.includes('magenta') || name.includes('merah')) return 'color: #e83e8c;'; // Magenta
        if (name.includes('yellow') || name.includes('kuning')) return 'color: #ffc107;'; // Kuning
        if (name.includes('tri') || name.includes('colour') || name.includes('warna')) {
            // Jika Tricolour, gunakan gabungan gradient yang memukau
            return 'background: linear-gradient(135deg, #00b0f0, #e83e8c, #ffc107); -webkit-background-clip: text; -webkit-text-fill-color: transparent;';
        }
        
        // Warna lalai (Default)
        return 'color: #6c757d;';
    }

    // ==========================================
    // AJAX POLLING (LIVE INVENTORY)
    // ==========================================
    function fetchInventoryLive() {
        if (document.body.classList.contains('modal-open') || Swal.isVisible()) return;

        fetch('api_staff_live.php')
            .then(response => response.json())
            .then(fullData => {
                if (fullData.error) return;
                const models = fullData.inventory;
                
                let completeHTML = '';

                if (models.length === 0) {
                    completeHTML = '<div class="col-12 text-center text-muted py-5"><i class="fa-solid fa-box-open fa-3x mb-3"></i><p>Tiada inventori yang tersedia.</p></div>';
                } else {
                    models.forEach(model => {
                        let stockBadge = model.total_stock > 0
                            ? `<span class="badge bg-success-subtle text-success w-100 py-2 border border-success-subtle">Tersedia dalam ${model.colors.length} warna</span>`
                            : `<span class="badge bg-danger-subtle text-danger w-100 py-2 border border-danger-subtle">Kehabisan Stok</span>`;

                        let imgHTML = model.thumbnail
                            ? `<img src="${model.thumbnail}" alt="${model.brand} ${model.model_name}">`
                            : `<i class="fa-solid fa-fill-drip fa-4x text-secondary opacity-50"></i>`;

                        let listGroupHTML = '';
                        model.colors.forEach(color => {
                            let colorStatus = '';
                            let btnDisabled = '';
                            
                            if (color.quantity > 3) {
                                colorStatus = `<span class="text-success"><i class="fa-solid fa-check"></i> Tersedia (${color.quantity})</span>`;
                            } else if (color.quantity > 0) {
                                colorStatus = `<span class="text-warning text-dark"><i class="fa-solid fa-triangle-exclamation"></i> Kekurangan Stok (${color.quantity})</span>`;
                            } else {
                                colorStatus = `<span class="text-danger"><i class="fa-solid fa-xmark"></i> Kehabisan Stok</span>`;
                                btnDisabled = 'disabled';
                            }

                            let brandEsc = model.brand.replace(/'/g, "\\'");
                            let nameEsc = model.model_name.replace(/'/g, "\\'");
                            let colorEsc = color.colour.replace(/'/g, "\\'");
                            let imgPathEsc = color.image_path ? color.image_path.replace(/'/g, "\\'") : '';
                            
                            // Gunakan fungsi pewarnaan dinamik yang baru dicipta
                            let dropletStyle = getDropletColor(color.colour);

                            listGroupHTML += `
                            <div class="d-flex align-items-center justify-content-between mb-3 p-3 bg-white border rounded-4 shadow-sm" style="transition: transform 0.2s; cursor: default;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                        <!-- Ikon dengan warna dinamik -->
                                        <i class="fa-solid fa-droplet fs-5" style="${dropletStyle}"></i>
                                    </div>
                                    <div class="text-start">
                                        <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">${color.colour}</h6>
                                        <div class="mt-1">${colorStatus}</div>
                                    </div>
                                </div>
                                <div class="ms-2">
                                    <button class="btn btn-dark rounded-pill px-3 px-md-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-1 gap-md-2" 
                                            onclick="openRequestModal(${color.colour_id}, ${model.model_id}, '${brandEsc}', '${nameEsc}', '${colorEsc}', ${color.quantity}, '${imgPathEsc}')" 
                                            ${btnDisabled}>
                                        <span class="d-none d-md-inline">Pilih</span> <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>`;
                        });

                        completeHTML += `
                        <div class="col fade-in-item">
                            <div class="card h-100 shadow-sm border-0 product-card" data-bs-toggle="modal" data-bs-target="#modelModal${model.model_id}">
                                <div class="product-img-box d-flex justify-content-center align-items-center overflow-hidden">
                                    ${imgHTML}
                                </div>
                                <div class="card-body d-flex flex-column text-center">
                                    <h6 class="card-title fw-bold text-truncate mb-1"><span class="text-primary">${model.brand}</span> ${model.model_name}</h6>
                                    <div class="text-muted small mb-3 text-truncate"><i class="fa-solid fa-print me-1"></i> ${model.printer_compatibility}</div>
                                    <div class="mt-auto">
                                        ${stockBadge}
                                    </div>
                                </div>
                                <div class="card-footer bg-primary text-white text-center border-top-0 py-2 fw-bold">
                                    Pilih Warna <i class="fa-solid fa-arrow-right ms-1"></i>
                                </div>
                            </div>
                        </div>

                        <!-- REKAAN BARU MODAL 1 -->
                        <div class="modal fade" id="modelModal${model.model_id}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    
                                    <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4 position-relative">
                                        <h5 class="modal-title fw-bold text-dark w-100 text-center">
                                            ${model.brand} ${model.model_name}
                                        </h5>
                                        <button type="button" class="btn-close position-absolute top-0 end-0 mt-3 me-3 shadow-none" data-bs-dismiss="modal"></button>
                                    </div>
                                    
                                    <div class="modal-body px-4 pt-2 pb-4 bg-white">
                                        <p class="text-muted small text-center mb-4">Sila pilih warna dakwat yang diperlukan:</p>
                                        <div class="d-flex flex-column text-start">
                                            ${listGroupHTML}
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>`;
                    });
                }
                document.getElementById('inventoryContainer').innerHTML = completeHTML;
            })
            .catch(error => console.error("Ralat menyambung ke data langsung:", error));
    }

    fetchInventoryLive();
    setInterval(fetchInventoryLive, 300000);

    // --- ADD REQUEST MODAL LOGIC ---
    function openRequestModal(cId, mId, brand, model, color, maxQty, imagePath) {
        document.getElementById('reqColourId').value = cId;
        document.getElementById('reqParentModelId').value = mId;
        document.getElementById('reqBrandModel').innerText = brand + ' ' + model;
        
        document.getElementById('reqColour').innerHTML = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 fs-6">' + color + '</span>';
        
        let imgEl = document.getElementById('reqImage');
        let iconEl = document.getElementById('reqImagePlaceholder');
        if (imagePath && imagePath.trim() !== '') {
            imgEl.src = imagePath; imgEl.style.display = 'block'; iconEl.style.display = 'none';
        } else {
            imgEl.src = ''; imgEl.style.display = 'none'; iconEl.style.display = 'block';
        }
        
        let qtyInput = document.getElementById('reqQuantity');
        qtyInput.max = maxQty; qtyInput.value = 1;
        document.getElementById('reqMaxQty').innerText = maxQty;

        let parentModal = bootstrap.Modal.getInstance(document.getElementById('modelModal' + mId));
        if (parentModal) parentModal.hide();

        if (!requestModalInstance) requestModalInstance = new bootstrap.Modal(document.getElementById('requestModal'));
        requestModalInstance.show();
    }

    function adjustQty(change) {
        let input = document.getElementById('reqQuantity');
        let newVal = (parseInt(input.value) || 1) + change;
        if (newVal >= 1 && newVal <= (parseInt(input.max) || 1)) input.value = newVal;
    }

    function goBackToModel() {
        if (requestModalInstance) requestModalInstance.hide();
        let mId = document.getElementById('reqParentModelId').value;
        new bootstrap.Modal(document.getElementById('modelModal' + mId)).show();
    }

    // --- CART LOGIC ---
    function updateCartBadge(count) {
        let desktopBadge = document.getElementById('navCartCount');
        let mobileBadge = document.getElementById('mobileCartCount');
        
        if (desktopBadge) {
            desktopBadge.innerText = count;
            desktopBadge.style.display = count > 0 ? 'block' : 'none';
        }
        if (mobileBadge) {
            mobileBadge.innerText = count;
            mobileBadge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    function addToCart() {
        let formData = new FormData();
        formData.append('action', 'add');
        formData.append('colour_id', document.getElementById('reqColourId').value);
        formData.append('quantity', document.getElementById('reqQuantity').value);

        fetch('cart_api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                updateCartBadge(data.cart_count);
                if (requestModalInstance) requestModalInstance.hide();
                
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success', 
                    title: 'Ditambah ke Senarai!', showConfirmButton: false, timer: 1500
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        });
    }

    function openCartModal() {
        fetch('cart_api.php?action=get')
        .then(response => response.json())
        .then(data => {
            let cartBody = document.getElementById('cartBody');
            let btnCheckout = document.getElementById('btnCheckout');
            let sebabContainer = document.getElementById('sebabContainer');
            
            cartBody.innerHTML = ''; 
            
            if (data.count === 0) {
                sebabContainer.style.display = 'none';
                
                cartBody.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fa-solid fa-box-open text-muted fa-2x"></i>
                        </div>
                        <h6 class="fw-bold text-dark">Senarai masih kosong</h6>
                        <p class="small mb-0">Tiada dakwat dipilih</p>
                    </div>
                `;
                btnCheckout.disabled = true;
            } else {
                sebabContainer.style.display = 'block';
                document.getElementById('reqReason').value = ''; 
                btnCheckout.disabled = false;
                
                data.items.forEach(item => {
                    // Gunakan fungsi pewarnaan dinamik untuk troli juga
                    let cartDropletStyle = getDropletColor(item.colour);
                    
                    cartBody.innerHTML += `
                        <div class="d-flex align-items-center justify-content-between mb-2 p-2 p-md-3 bg-white border rounded-4 shadow-sm" style="transition: all 0.2s;">
                            <div class="d-flex align-items-center gap-2 gap-md-3">
                                <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <!-- Ikon dengan warna dinamik -->
                                    <i class="fa-solid fa-droplet small" style="${cartDropletStyle}"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark lh-sm" style="font-size: 0.85rem;">${item.name}</h6>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle mt-1 px-2 py-1" style="font-size: 0.7rem;">${item.colour}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-light rounded-3 px-2 py-1 fw-bold text-dark border" style="font-size: 0.85rem;">
                                    x${item.request_qty}
                                </div>
                                <button class="btn btn-sm btn-outline-danger border-0 rounded-circle shadow-none d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;" onclick="removeFromCart(${item.colour_id})" title="Remove item">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            }
            
            if (!cartModalInstance) cartModalInstance = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModalInstance.show();
        });
    }

    function removeFromCart(colourId) {
        let formData = new FormData();
        formData.append('action', 'remove');
        formData.append('colour_id', colourId);
        fetch('cart_api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            updateCartBadge(data.cart_count);
            openCartModal(); 
        });
    }

    function clearCart() {
        let formData = new FormData();
        formData.append('action', 'clear');
        fetch('cart_api.php', { method: 'POST', body: formData })
        .then(() => {
            updateCartBadge(0);
            if (cartModalInstance) cartModalInstance.hide();
        });
    }

    function checkout() {
        let reason = document.getElementById('reqReason').value;
        
        if (reason.trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Maklumat Tidak Lengkap',
                text: 'Sila nyatakan sebab permohonan dakwat anda.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        let btn = document.getElementById('btnCheckout');
        btn.innerHTML = '<span class="d-none d-sm-inline me-1">Submitting...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        let formData = new FormData();
        formData.append('reason', reason);

        fetch('checkout.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                if (cartModalInstance) cartModalInstance.hide();
                updateCartBadge(0);
                
                Swal.fire({
                    icon: 'success', title: 'Permohonan Dihantar!', 
                    text: 'Permohonan anda telah dihantar kepada Admin.',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                btn.innerHTML = '<span class="d-none d-sm-inline me-1">Hantar Permohonan</span> <i class="fa-solid fa-check"></i>';
                btn.disabled = false;
            }
        });
    }
    </script>
</body>
</html>