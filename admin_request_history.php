<?php
session_start();
require 'db.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch ALL requests (pending, accepted, and rejected) with user and ink details
$stmt = $pdo->query("
    SELECT r.id, r.batch_id, r.quantity_requested, r.status, r.request_date,
           u.name, u.department,
           c.colour, m.brand, m.model_name
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN ink_colours c ON r.colour_id = c.colour_id
    JOIN ink_models m ON c.model_id = m.model_id
    ORDER BY r.request_date DESC
");
$allRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// GROUP THEM BY BATCH_ID via PHP
$groupedHistory = [];
foreach ($allRequests as $req) {
    // Fallback: If it's an old request without a batch_id, it defaults to its own ID
    $bId = !empty($req['batch_id']) ? $req['batch_id'] : $req['id']; 
    
    if (!isset($groupedHistory[$bId])) {
        $groupedHistory[$bId] = [
            'batch_id' => $bId,
            'name' => $req['name'],
            'department' => !empty($req['department']) ? $req['department'] : 'N/A',
            'request_date' => $req['request_date'],
            'status' => $req['status'], // Status is shared across the batch
            'items' => []
        ];
    }
    $groupedHistory[$bId]['items'][] = $req;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai Permohonan - Admin</title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        /* Hide scrollbar */
        ::-webkit-scrollbar { display: none; }
        html, body { -ms-overflow-style: none; scrollbar-width: none; }

        .nav-link {
            display: flex;
            align-items: center;
        }

        .nav-link i {
            font-size: 1.2rem; /* Make the icons slightly bigger since they are the main focus */
            width: 24px;       /* Keep icons centered evenly */
            text-align: center;
        }

        .nav-text {
            max-width: 0;
            opacity: 0;
            overflow: hidden;
            white-space: nowrap; /* Prevents text from dropping to a new line */
            transition: max-width 0.4s ease, opacity 0.3s ease, margin-left 0.3s ease;
        }

        /* When hovering over the link, slide the text open */
        .nav-link:hover .nav-text {
            max-width: 150px; /* Adjust if you have longer words */
            opacity: 1;
            margin-left: 8px; /* Adds space between icon and text */
        }

        /* Keeps the text visible for the page you are currently viewing */
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

            /* General font adjustments for mobile */
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
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-file-invoice text-black me-2"></i>Senarai Permohonan</h5>
                        <span class="badge bg-secondary"><?= count($groupedHistory) ?> Permohonan</span>
                    </div>
                    
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (count($groupedHistory) == 0): ?>
                                <li class="list-group-item text-center text-muted py-5">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
                                    <p>Tiada permohonan dibuat setakat ini.</p>
                                </li>
                            <?php endif; ?>

                            <?php foreach ($groupedHistory as $batchId => $group): ?>
                            <li class="list-group-item p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                    <div>
                                        <strong class="text-dark fs-5"><i class="fa-solid fa-user text-secondary me-1"></i><?= htmlspecialchars($group['name']) ?></strong> 
                                        <span class="badge bg-info text-dark ms-1"><?= htmlspecialchars($group['department']) ?></span><br>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($group['request_date'])) ?> | Ref: <?= htmlspecialchars($batchId) ?></small>
                                    </div>
                                    
                                    <!-- Flexbox handles stacking the badge and receipt button on mobile -->
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        <?php if ($group['status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark py-2 px-3 border"><i class="fa-solid fa-hourglass-half me-1"></i> Pending</span>
                                        <?php elseif ($group['status'] === 'accepted'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 fw-bold"><i class="fa-solid fa-check me-1"></i> Diterima</span>
                                            
                                            <a href="generate_receipt.php?batch_id=<?= urlencode($batchId) ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold px-3">
                                                <i class="fa-solid fa-file-invoice me-1"></i> Resit
                                            </a>
                                            
                                        <?php elseif ($group['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-2 px-3 fw-bold"><i class="fa-solid fa-xmark me-1"></i> Ditolak</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="ps-3 border-start border-3 <?= $group['status'] === 'accepted' ? 'border-success' : ($group['status'] === 'rejected' ? 'border-danger' : 'border-warning') ?>">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <div class="mb-1">
                                            <span class="text-dark fw-bold"><?= htmlspecialchars($item['brand']) ?> <?= htmlspecialchars($item['model_name']) ?></span> 
                                            - <span class="badge bg-secondary"><?= htmlspecialchars($item['colour']) ?></span>
                                            <span class="fw-bold text-dark ms-2">x<?= $item['quantity_requested'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>