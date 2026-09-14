<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 1. Catch the filters from the URL. 
// Default to the current year on initial page load!
$filterYear = $_GET['year'] ?? date('Y');
$filterMonth = $_GET['month'] ?? '';

// 2. Base query
$query = "
    SELECT t.transaction_id, t.type, t.quantity, t.transaction_date, 
           c.colour, m.brand, m.model_name
    FROM ink_transactions t
    JOIN ink_colours c ON t.colour_id = c.colour_id
    JOIN ink_models m ON c.model_id = m.model_id
    WHERE 1=1
";
$params = [];

// 3. Append to the query dynamically based on what the user selected
if ($filterYear !== '') {
    $query .= " AND YEAR(t.transaction_date) = ?";
    $params[] = $filterYear;
}
if ($filterMonth !== '') {
    $query .= " AND MONTH(t.transaction_date) = ?";
    $params[] = $filterMonth;
}

// 4. Order it and execute
$query .= " ORDER BY t.transaction_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Transaksi - Admin</title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        ::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        html, body {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        .nav-link {
            display: flex;
            align-items: center;
        }

        .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .nav-text {
            max-width: 0;
            opacity: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: max-width 0.4s ease, opacity 0.3s ease, margin-left 0.3s ease;
        }

        .nav-link:hover .nav-text {
            max-width: 150px;
            opacity: 1;
            margin-left: 8px;
        }

        .nav-link.active .nav-text {
            max-width: 150px;
            opacity: 1;
            margin-left: 8px;
        }

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

        /* 2. Fix Header / Filter Layout for Mobile */
        @media (max-width: 767px) {
            .card-header.d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .card-header form {
                width: 100%;
                flex-wrap: wrap;
                justify-content: flex-start;
            }
            .card-header select {
                flex: 1;
            }
            
            /* Make status badges span better on small screens */
            .table .badge.w-50 {
                width: 100% !important;
                padding: 8px !important;
                font-size: 0.75rem;
            }
            
            /* General font adjustments */
            body { font-size: 0.9rem; }
            h5 { font-size: 1.25rem; }
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
<body class="bg-light">

    <!-- Admin Navbar -->
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
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left text-black me-2"></i>Log Transaksi Stok</h5>
                
                <form action="admin_transactions.php" method="GET" class="d-flex align-items-center gap-2 m-0">
                    
                    <select name="year" class="form-select form-select-sm" style="width: auto; min-width: 100px;" onchange="this.form.submit()">
                        <option value="">-- Tahun --</option>
                        <?php 
                            $currentYear = date('Y');
                            for($y = $currentYear; $y >= $currentYear - 3; $y--) {
                                $selected = ($filterYear == $y) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                        ?>
                    </select>

                    <select name="month" class="form-select form-select-sm" style="width: auto; min-width: 140px;" onchange="this.form.submit()">
                        <option value="">-- Bulan --</option>
                        <?php
                            $months = [
                                '01' => 'Januari', '02' => 'Februari', '03' => 'Mac', 
                                '04' => 'April', '05' => 'Mei', '06' => 'Jun', 
                                '07' => 'Julai', '08' => 'Ogos', '09' => 'September', 
                                '10' => 'Oktober', '11' => 'November', '12' => 'Disember'
                            ];
                            foreach ($months as $num => $name) {
                                $selected = ($filterMonth == $num) ? 'selected' : '';
                                echo "<option value='$num' $selected>$name</option>";
                            }
                        ?>
                    </select>

                    <button type="submit" formaction="generate_report.php" formtarget="_blank" class="btn btn-primary btn-sm fw-bold text-nowrap">
                        <i class="fa-solid fa-file-pdf me-1"></i> Report
                    </button>
                    
                </form>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 text-nowrap">Tarikh & Masa</th>
                                <th class="text-nowrap">Model Dakwat</th>
                                <th>Warna</th>
                                <th class="text-nowrap">Jenis Pergerakan</th>
                                <th class="text-center pe-4">Kuantiti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-file-signature fa-3x mb-3"></i>
                                        <p>Tiada rekod transaksi direkod setakat ini.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td class="ps-4 text-muted small text-nowrap">
                                    <?= date('d M Y, h:i A', strtotime($txn['transaction_date'])) ?>
                                </td>
                                <td class="fw-bold text-nowrap">
                                    <span class="text-primary"><?= htmlspecialchars($txn['brand']) ?></span> 
                                    <?= htmlspecialchars($txn['model_name']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($txn['colour']) ?></span>
                                </td>
                                <td>
                                    <?php if ($txn['type'] === 'IN'): ?>
                                        <span class="badge bg-success w-50 py-2"><i class="fa-solid fa-arrow-down me-1"></i> MASUK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger w-50 py-2"><i class="fa-solid fa-arrow-up me-1"></i> KELUAR</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4 fw-bold fs-5">
                                    <?= $txn['type'] === 'IN' ? '+' : '-' ?><?= $txn['quantity'] ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>