<?php
session_start();
require 'db.php';

// Ensure only staff can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current cart count for the navbar badge
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai Permohonan</title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        ::-webkit-scrollbar { display: none; }
        /* Hide scrollbar for IE, Edge and Firefox */
        html, body { -ms-overflow-style: none; scrollbar-width: none; }

        /* Animated Navbar Links */
        .nav-link { display: flex; align-items: center; }
        .nav-link i { font-size: 1.2rem; width: 24px; text-align: center; }
        .nav-text { max-width: 0; opacity: 0; overflow: hidden; white-space: nowrap; transition: max-width 0.4s ease, opacity 0.3s ease, margin-left 0.3s ease; }
        .nav-link:hover .nav-text, .nav-link.active .nav-text { max-width: 150px; opacity: 1; margin-left: 8px; }
        
        /* Custom Cart Badge styling */
        .cart-badge { position: absolute; top: -5px; right: -8px; font-size: 0.65rem; padding: 0.25em 0.5em; }

        /* Smooth fade in for live updates */
        .fade-in-item { animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* ========================================== */
        /* GLOBAL MOBILE RESPONSIVENESS FIXES         */
        /* ========================================== */
        
        /* 1. Fix Navbar Menu on Small Screens (Offcanvas Customization) */
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

        /* 2. Fix Request Cards & Mobile Typography */
        @media (max-width: 767px) {
            /* Stack flex items nicely instead of squishing them */
            .list-group-item > .d-flex.justify-content-between {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px;
            }
            
            /* Make action buttons take full width for easy tapping */
            .list-group-item .d-flex.flex-column.align-items-end {
                width: 100%;
                align-items: stretch !important;
            }
            .list-group-item .d-flex.flex-column.align-items-end .badge {
                text-align: center;
                padding: 8px !important;
            }
            .list-group-item .d-flex.flex-column.align-items-end .btn {
                padding: 10px;
            }

            /* Adjust Cart Items layout */
            #cartBody .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            
            /* KEMASKINI CSS DI SINI: Menyelesaikan konflik susunan troli pada skrin kecil */
            #cartBody > div > div:last-child {
                width: 100%;
                justify-content: space-between !important;
                border-top: 1px dashed #dee2e6;
                padding-top: 10px;
            }

            /* General font adjustments for mobile */
            body { font-size: 0.9rem; }
            h5 { font-size: 1.25rem; }
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

    <!-- Staff Navbar -->
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
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clipboard-list text-black me-2"></i>Senarai Permohonan</h5>
                        
                        <span id="totalRequestsBadge" class="badge bg-secondary">Menyambung...</span>
                    </div>
                    <div class="card-body p-0">
                        <!-- Senarai akan disuntik di sini secara Live Polling -->
                        <ul class="list-group list-group-flush" id="requestList">
                            <li class="list-group-item text-center text-muted py-5">
                                <i class="fa-solid fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Menarik data secara langsung...</p>
                            </li>
                        </ul>
                    </div>
                </div>
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

    <!-- MODAL: The Modern Shopping Cart Review -->
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
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
    // AJAX POLLING (LIVE UPDATES FOR STAFF)
    // ==========================================
    function fetchStaffRequestsLive() {
        if (Swal.isVisible()) return; 

        fetch('api_staff_live.php')
            .then(response => response.json())
            .then(fullData => {
                if (fullData.error) return;
                const data = fullData.requests;
                
                document.getElementById('totalRequestsBadge').innerText = data.count + ' Jumlah Permohonan';

                let listHTML = '';

                if (data.count === 0) {
                    listHTML = `
                        <li class="list-group-item text-center text-muted py-5 fade-in-item">
                            <i class="fa-regular fa-clipboard fa-3x mb-3 opacity-50"></i>
                            <p>Sebarang permohonan belum dibuat.</p>
                        </li>
                    `;
                } else {
                    data.groups.forEach(group => {
                        
                        let badgeHTML = '';
                        let borderClass = '';
                        
                        if (group.status === 'pending') {
                            badgeHTML = '<span class="badge bg-warning text-dark py-2 px-3 border"><i class="fa-solid fa-hourglass-half me-1"></i> Pending</span>';
                            borderClass = 'border-warning';
                        } else if (group.status === 'accepted') {
                            badgeHTML = `
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 fw-bold"><i class="fa-solid fa-check me-1"></i> Diterima</span>
                                <a href="generate_staff_receipt.php?batch_id=${encodeURIComponent(group.batch_id)}" target="_blank" class="btn btn-sm btn-outline-success fw-bold px-3 shadow-sm">
                                    <i class="fa-solid fa-file-lines me-1"></i> Papar Rujukan
                                </a>
                            `;
                            borderClass = 'border-success';
                        } else if (group.status === 'rejected') {
                            badgeHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle py-2 px-3 fw-bold"><i class="fa-solid fa-xmark me-1"></i> Ditolak</span>';
                            borderClass = 'border-danger';
                        }

                        let itemsHTML = '';
                        group.items.forEach(item => {
                            itemsHTML += `
                                <div class="mb-1">
                                    <span class="text-dark fw-bold">${item.brand} ${item.model_name}</span> 
                                    - <span class="badge bg-secondary">${item.colour}</span>
                                    <span class="fw-bold text-dark ms-2">x${item.quantity_requested}</span>
                                </div>
                            `;
                        });

                        listHTML += `
                            <li class="list-group-item p-4 fade-in-item">
                                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                    <div>
                                        <h6 class="fw-bold mb-1">Rujukan: <span class="text-primary">${group.batch_id}</span></h6>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>${group.formatted_date}</small>
                                    </div>
                                    
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        ${badgeHTML}
                                    </div>
                                </div>
                                
                                <div class="ps-3 border-start border-3 ${borderClass}">
                                    ${itemsHTML}
                                </div>
                            </li>
                        `;
                    });
                }

                document.getElementById('requestList').innerHTML = listHTML;
            })
            .catch(error => console.error("Ralat menyambung ke data langsung:", error));
    }

    // Tarik data sebaik sahaja halaman dibuka, kemudian ulang setiap 5 minit
    fetchStaffRequestsLive();
    setInterval(fetchStaffRequestsLive, 300000);

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
                    // Gunakan fungsi pewarnaan dinamik untuk troli
                    let cartDropletStyle = getDropletColor(item.colour);
                    
                    cartBody.innerHTML += `
                        <div class="d-flex align-items-center justify-content-between mb-2 p-2 p-md-3 bg-white border rounded-4 shadow-sm" style="transition: all 0.2s;">
                            <div class="d-flex align-items-center gap-2 gap-md-3">
                                <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
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