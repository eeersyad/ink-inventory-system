<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belum Diproses - Admin</title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        ::-webkit-scrollbar { display: none; }
        html, body { -ms-overflow-style: none; scrollbar-width: none; }
        
        .nav-link { display: flex; align-items: center; }
        .nav-link i { font-size: 1.2rem; width: 24px; text-align: center; }
        .nav-text { max-width: 0; opacity: 0; overflow: hidden; white-space: nowrap; transition: max-width 0.4s ease, opacity 0.3s ease, margin-left 0.3s ease; }
        .nav-link:hover .nav-text, .nav-link.active .nav-text { max-width: 150px; opacity: 1; margin-left: 8px; }
        
        /* Smooth fade in for new items arriving live */
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
            body { font-size: 0.9rem; }
            h3 { font-size: 1.5rem; }
            h4 { font-size: 1.25rem; }
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

    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top mb-4 shadow-sm py-1">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="admin_dashboard.php">
                <img src="images/logo-semua.png" alt="Logo" height="70" class="d-inline-block align-text-top me-2">
                <div class="d-flex flex-column fs-6 fw-bold lh-sm text-dark">
                    <span>Sistem Inventori</span>
                    <span class="text-primary">Dakwat Printer</span>
                </div>
            </a>
            
            <!-- OFFCANVAS TOGGLER BUTTON -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminNav" aria-controls="adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- OFFCANVAS CONTAINER -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="adminNav" aria-labelledby="adminNavLabel">
                
                <!-- Only visible in mobile drawer view -->
                <div class="offcanvas-header border-bottom px-4">
                    <h5 class="offcanvas-title fw-bold text-dark" id="adminNavLabel">Menu Sistem</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                
                <div class="offcanvas-body px-4 px-lg-0">
                    <ul class="navbar-nav ms-auto me-lg-3 gap-2 align-items-lg-center flex-grow-1 flex-lg-grow-0">
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active text-primary fw-bold' : '' ?>" href="admin_dashboard.php">
                                <i class="fa-solid fa-chart-pie"></i>
                                <span class="nav-text">Laman Utama</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_inventory.php' ? 'active text-primary fw-bold' : '' ?>" href="admin_inventory.php">
                                <i class="fa-solid fa-boxes-stacked"></i>
                                <span class="nav-text">Kemaskini</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_requests.php' ? 'active text-primary fw-bold' : '' ?>" href="admin_requests.php">
                                <i class="fa-solid fa-bell"></i>
                                <span class="nav-text">Belum Diproses</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_request_history.php' ? 'active text-primary fw-bold' : '' ?>" href="admin_request_history.php">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span class="nav-text">Rekod</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_transactions.php' ? 'active text-primary fw-bold' : '' ?>" href="admin_transactions.php">
                                <i class="fa-solid fa-right-left"></i>
                                <span class="nav-text">Log Transaksi</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="d-flex align-items-center ms-lg-3 mt-4 mt-lg-0 pb-4 pb-lg-0">
                        <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i> Keluar</a>
                    </div>
                </div>
                
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-primary mb-5">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Permohonan Dakwat</h5>
                        <span id="pendingCountBadge" class="badge bg-light text-primary">Menyambung...</span>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="requestList">
                            <li class="list-group-item text-center text-muted p-5">
                                <i class="fa-solid fa-spinner fa-spin fa-2x mb-3"></i><br>
                                Menarik data secara langsung...
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    // ==========================================
    // AJAX POLLING (Real-Time Tanpa Freeze)
    // ==========================================
    function fetchPendingLive() {
        if (Swal.isVisible()) return; // Jangan ganggu pop-up SweetAlert jika admin sedang mengesahkan borang

        fetch('api_admin_live.php')
            .then(response => response.json())
            .then(fullData => {
                if (fullData.error) return;
                const data = fullData.pending_page;
                
                document.getElementById('pendingCountBadge').innerText = data.count + ' Permohonan';

                let listHTML = '';

                if (data.count === 0) {
                    listHTML = '<li class="list-group-item text-center text-muted p-5 fade-in-item"><i class="fa-solid fa-mug-hot fa-3x mb-3 opacity-50"></i><br>Tiada permohonan setakat ini.</li>';
                } else {
                    data.groups.forEach(group => {
                        let itemsHTML = '';
                        group.items.forEach(item => {
                            itemsHTML += `
                                <div class="mb-1">
                                    <span class="text-dark fw-bold">${item.brand} ${item.model_name}</span> 
                                    - <span class="badge bg-secondary">${item.colour}</span>
                                    <span class="fw-bold text-danger ms-2">x${item.quantity_requested}</span>
                                </div>
                            `;
                        });

                        listHTML += `
                            <li class="list-group-item p-4 fade-in-item" id="req-${group.batch_id}">
                                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                    <div>
                                        <strong class="text-primary fs-5"><i class="fa-solid fa-user me-1"></i>${group.name}</strong> 
                                        <span class="badge bg-info text-dark ms-1">${group.department}</span><br>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>${group.formatted_date} | Ref: ${group.batch_id}</small>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-success fw-bold" onclick="handleBatch('${group.batch_id}', 'accept')"><i class="fa-solid fa-check me-1"></i></button>
                                        <button class="btn btn-outline-danger" onclick="handleBatch('${group.batch_id}', 'reject')"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                </div>
                                
                                <div class="ps-3 border-start border-3 border-primary mb-3">
                                    ${itemsHTML}
                                </div>
                                
                                <div class="bg-light rounded p-3 border">
                                    <strong class="text-dark small text-uppercase"><i class="fa-solid fa-comment-dots me-1"></i> Sebab Permohonan:</strong><br>
                                    <span class="text-secondary fst-italic">${group.reason}</span>
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
    fetchPendingLive();
    setInterval(fetchPendingLive, 300000);

    // ==========================================
    // ACTION BUTTON LOGIC (Accept / Reject)
    // ==========================================
    function handleBatch(batchId, actionType) {
        Swal.fire({
            title: actionType === 'accept' ? 'Meluluskan Permohonan...' : 'Menolak Permohonan...',
            text: actionType === 'accept' ? 'Mengemaskini inventori dan menghantar email...' : 'Permohonan sedang diproses...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        let formData = new FormData();
        formData.append('batch_id', batchId);
        formData.append('action', actionType);

        fetch('update_request.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({ 
                    icon: 'success', 
                    title: actionType === 'accept' ? 'Permohonan Diterima!' : 'Permohonan Ditolak!', 
                    timer: 1500, 
                    showConfirmButton: false 
                });
                
                // Remove it from the screen immediately so it feels snappy
                document.getElementById('req-' + batchId).remove();
                
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#0d6efd' });
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not connect to the server.', confirmButtonColor: '#0d6efd' });
        });
    }
    </script>
</body>
</html>