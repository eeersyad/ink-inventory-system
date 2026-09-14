<?php
session_start();
require 'db.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Keep the Brands query for the static modal
$stmt = $pdo->query("SELECT DISTINCT brand FROM ink_models ORDER BY brand ASC");
$brandList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Keep Low Stock Alerts as static (or you can move it to SSE later if desired)
$stmt = $pdo->query("
    SELECT m.brand, m.model_name, c.colour, c.quantity 
    FROM ink_colours c 
    JOIN ink_models m ON c.model_id = m.model_id 
    WHERE c.quantity <= 5 
    ORDER BY c.quantity ASC 
    LIMIT 6
");
$lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Keep Chart Data static for initial load
$stmt = $pdo->query("
    SELECT CONCAT(m.brand, ' ', m.model_name, ' (', c.colour, ')') as ink_name, 
           SUM(t.quantity) as total_out
    FROM ink_transactions t
    JOIN ink_colours c ON t.colour_id = c.colour_id
    JOIN ink_models m ON c.model_id = m.model_id
    WHERE t.type = 'OUT'
    GROUP BY t.colour_id
    ORDER BY total_out DESC 
    LIMIT 5
");
$topInks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = [];
$chartData = [];
foreach ($topInks as $ink) {
    $chartLabels[] = $ink['ink_name'];
    $chartData[] = $ink['total_out'];
}
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
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { background-color: #f4f6f9; }
        ::-webkit-scrollbar { display: none; }
        html, body { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Animated Navbar Links */
        .nav-link { display: flex; align-items: center; }
        .nav-link i { font-size: 1.2rem; width: 24px; text-align: center; }
        .nav-text { max-width: 0; opacity: 0; overflow: hidden; white-space: nowrap; transition: max-width 0.4s ease, opacity 0.3s ease, margin-left 0.3s ease; }
        .nav-link:hover .nav-text, .nav-link.active .nav-text { max-width: 150px; opacity: 1; margin-left: 8px; }

        /* KPI Card Styling */
        .kpi-card { border-left: 5px solid; border-radius: 8px; transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
        .border-primary-kpi { border-left-color: #0d6efd; }
        .border-success-kpi { border-left-color: #198754; }
        .border-warning-kpi { border-left-color: #ffc107; }
        .border-info-kpi { border-left-color: #0dcaf0; }
        
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
            
        }

        /* 2. Fix Request Cards, Dashboards & Modals */
        @media (max-width: 767px) {
            /* Stack flex items nicely instead of squishing them */
            .list-group-item > .d-flex.justify-content-between {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px;
            }
            
            /* Make action buttons take full width for easy tapping */
            .list-group-item .btn-group, 
            .list-group-item .d-flex.flex-column.align-items-end {
                width: 100%;
                align-items: stretch !important;
            }
            .list-group-item .btn-group .btn {
                flex: 1; /* Makes Accept/Reject buttons equal size */
                padding: 10px;
            }

            /* Adjust Cart Items layout */
            #cartBody .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            #cartBody .gap-2 {
                width: 100%;
                justify-content: space-between;
                border-top: 1px dashed #dee2e6;
                padding-top: 10px;
            }

            /* Adjust Dashboard KPI Cards */
            .kpi-card {
                margin-bottom: 10px;
            }
            .kpi-card .card-body {
                padding: 1.25rem !important;
            }
            
            /* General font adjustments for mobile */
            body { font-size: 0.9rem; }
            h3 { font-size: 1.5rem; }
            h4 { font-size: 1.25rem; }
        }

        /* ========================================== */
        /* 3. RESIZE LOGO ON MOBILE (KEEP NAVBAR HEIGHT) */
        /* ========================================== */
        @media (max-width: 767px) {
            /* Kecilkan saiz logo */
            .navbar-brand img {
                height: 45px !important; 
            }
            /* Kunci saiz container supaya navbar tidak mengecut */
            .navbar-brand {
                min-height: 70px; 
            }
            /* Pilihan: Kecilkan sedikit teks tajuk supaya seimbang dengan logo */
            .navbar-brand .fs-6 {
                font-size: 0.9rem !important;
            }
        }
    </style>
</head>
<body>

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

    <div class="container mb-5">
        
        <!-- Welcome Header -->
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Laman Utama</h3>
                <p class="text-muted mb-0">Ringkasan status inventori dan aktiviti sistem.</p>
            </div>
            <div>
                <span class="badge bg-light text-dark border p-2"><i class="fa-regular fa-calendar me-1"></i> <?= date('d M Y') ?></span>
            </div>
        </div>

        <!-- ROW 1: Clickable KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <a href="admin_requests.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm kpi-card border-warning-kpi h-100">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">Permohonan Baru</p>
                                <h3 id="kpi-pending" class="fw-bold mb-0 text-dark">...</h3>
                            </div>
                            <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-bell fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="admin_inventory.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm kpi-card border-info-kpi h-100">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">Jumlah Keseluruhan Stok</p>
                                <h3 id="kpi-stock" class="fw-bold mb-0 text-dark">...</h3>
                            </div>
                            <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-boxes-stacked fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="admin_transactions.php?month=<?= date('m') ?>&year=<?= date('Y') ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm kpi-card border-success-kpi h-100">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">Dikeluarkan Bulan Ini</p>
                                <h3 id="kpi-issued" class="fw-bold mb-0 text-dark">...</h3>
                            </div>
                            <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-arrow-right-from-bracket fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="#" data-bs-toggle="modal" data-bs-target="#brandsModal" class="text-decoration-none">
                    <div class="card border-0 shadow-sm kpi-card border-primary-kpi h-100">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">Jenama Dakwat (Aktif)</p>
                                <h3 id="kpi-brands" class="fw-bold mb-0 text-dark">...</h3>
                            </div>
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-tags fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- ROW 2: Main Content Area -->
        <div class="row g-4">
            
            <!-- LEFT COLUMN: Charts & Activity -->
            <div class="col-lg-8">
                
                <!-- Chart Section -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-bar text-primary me-2"></i>5 Dakwat Paling Kerap Dimohon</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="topInksChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-bolt text-warning me-2"></i>Log Aktiviti Terkini</h6>
                        <a href="admin_request_history.php" class="btn btn-sm btn-outline-secondary">Papar</a>
                    </div>
                    <div class="card-body p-0">
                        <!-- Polling will inject list items here -->
                        <ul class="list-group list-group-flush" id="recentActivityList">
                            <li class="list-group-item p-4 text-center text-muted">
                                <i class="fa-solid fa-spinner fa-spin me-2"></i>Memuatkan data secara langsung...
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Alerts -->
            <div class="col-lg-4">

                <!-- Critical Alerts (Low Stock) -->
                <div class="card border-0 shadow-sm border-top border-danger border-3">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Amaran Stok Rendah</h6>
                        <small class="text-muted">Item dengan baki 5 ke bawah.</small>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if(empty($lowStockItems)): ?>
                                <li class="list-group-item p-4 text-center text-success fw-bold">
                                    <i class="fa-solid fa-circle-check fa-2x mb-2"></i><br>Semua stok berada dalam keadaan selamat.
                                </li>
                            <?php endif; ?>

                            <?php foreach ($lowStockItems as $item): ?>
                                <li class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($item['brand'] . ' ' . $item['model_name']) ?></h6>
                                        <span class="badge bg-secondary mt-1"><?= htmlspecialchars($item['colour']) ?></span>
                                    </div>
                                    <div class="text-center">
                                        <?php if ($item['quantity'] == 0): ?>
                                            <span class="badge bg-danger fs-6 py-2">KOSONG</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark fs-6 py-2 border"><?= $item['quantity'] ?> Baki</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Brands Modal -->
    <div class="modal fade" id="brandsModal" tabindex="-1" aria-labelledby="brandsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark" id="brandsModalLabel">
                        <i class="fa-solid fa-tags text-black me-2"></i> Senarai Jenama
                    </h5>
                    <!-- The "x" button to close the modal -->
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <ul class="list-group list-group-flush">
                        <?php if(empty($brandList)): ?>
                            <li class="list-group-item text-muted text-center border-0">Tiada jenama direkodkan.</li>
                        <?php else: ?>
                            <?php foreach ($brandList as $b): ?>
                                <li class="list-group-item d-flex align-items-center fw-bold text-dark border-0 border-bottom py-3">
                                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                    <?= htmlspecialchars($b['brand']) ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize Chart.js -->
    <script>
        const ctx = document.getElementById('topInksChart').getContext('2d');
        const topInksChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Jumlah Unit Dikeluarkan',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
    
    <!-- AJAX POLLING (Real-Time Tanpa Freeze) -->
    <script>
    function fetchDashboardLive() {
        fetch('api_admin_live.php')
            .then(response => response.json())
            .then(fullData => {
                if (fullData.error) return;
                const dashboardData = fullData.dashboard;

                document.getElementById('kpi-pending').innerText = dashboardData.kpis.pendingRequests;
                document.getElementById('kpi-stock').innerText = dashboardData.kpis.totalStock;
                document.getElementById('kpi-issued').innerText = dashboardData.kpis.monthlyIssued;
                document.getElementById('kpi-brands').innerText = dashboardData.kpis.totalBrands;

                let activityHTML = '';
                if (dashboardData.recentActivity.length === 0) {
                    activityHTML = '<li class="list-group-item p-4 text-center text-muted">Tiada aktiviti terkini direkodkan.</li>';
                } else {
                    dashboardData.recentActivity.forEach(act => {
                        let statusBadge = '';
                        if (act.status === 'pending') statusBadge = '<span class="badge bg-warning text-dark border">Pending</span>';
                        else if (act.status === 'accepted') statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">Approved</span>';
                        else if (act.status === 'rejected') statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rejected</span>';

                        activityHTML += `
                            <li class="list-group-item p-3" style="animation: fadeIn 0.4s ease-in-out;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-dark">${act.staff_name}</strong> requested 
                                        <span class="fw-bold">${act.brand} ${act.model_name}</span> 
                                        <span class="badge bg-secondary ms-1">${act.colour}</span>
                                        <span class="fw-bold ms-1">x${act.quantity_requested}</span>
                                        <div class="text-muted small mt-1"><i class="fa-regular fa-clock me-1"></i>${act.formatted_date} | Ref: ${act.ref_id}</div>
                                    </div>
                                    <div>${statusBadge}</div>
                                </div>
                            </li>
                        `;
                    });
                }
                document.getElementById('recentActivityList').innerHTML = activityHTML;
            })
            .catch(error => console.error("Ralat menyambung ke data langsung:", error));
    }

    // Tarik data sebaik sahaja halaman dibuka, kemudian ulang setiap 5 minit
    fetchDashboardLive();
    setInterval(fetchDashboardLive, 300000);
    </script>
</body>
</html>