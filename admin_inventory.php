<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->query("
    SELECT m.model_id, m.brand, m.model_name, m.printer_compatibility, 
           c.colour_id, c.colour, c.quantity, c.image_path 
    FROM ink_colours c 
    JOIN ink_models m ON c.model_id = m.model_id 
    ORDER BY m.brand ASC, m.model_name ASC, c.colour ASC
");
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

$swal_msg = '';
if (isset($_SESSION['swal_msg'])) {
    $swal_msg = $_SESSION['swal_msg'];
    unset($_SESSION['swal_msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kemaskini Inventori - Admin</title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .product-card { transition: transform 0.2s, box-shadow 0.2s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .product-img-box { height: 160px; background-color: #f8f9fa; border-bottom: 1px solid #eaeaea; padding: 10px; }
        .product-img-box img { max-height: 100%; max-width: 100%; object-fit: contain; }

        /* Hide scrollbar for Chrome, Safari and Opera */
        ::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        html, body {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        /* Animated Navbar Links */
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

        /* 2. Fix Request Cards, Dashboards & Modals */
        @media (max-width: 767px) {
            /* Fix Card Footer Buttons for Inventory */
            .card-footer.d-flex {
                flex-direction: column;
                gap: 10px;
            }
            .card-footer .w-50 {
                width: 100% !important;
                margin: 0 !important;
            }
            
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
                    <!-- Dynamic animated ms-auto navigation -->
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold"><i class="fa-solid fa-warehouse text-black me-2"></i>Inventori Dakwat</h4>
            
            <!-- Updated Button: Only shows "+" on mobile, full text on larger screens -->
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addInkModal">
                <i class="fa-solid fa-plus"></i> 
                <span class="d-none d-sm-inline ms-1">Tambah Dakwat</span>
            </button>
            
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($inventory as $item): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0 product-card">
                    <div class="product-img-box d-flex justify-content-center align-items-center overflow-hidden">
                        <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['brand'] . ' ' . $item['model_name']) ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-fill-drip fa-4x text-secondary opacity-50"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold text-truncate">
                            <span class="text-primary"><?= htmlspecialchars($item['brand']) ?></span> <?= htmlspecialchars($item['model_name']) ?>
                        </h6>
                        <div class="text-muted small mb-3">
                            <div><i class="fa-solid fa-palette me-1 w-15px text-center"></i> <?= htmlspecialchars($item['colour']) ?></div>
                            <div class="text-truncate" title="<?= htmlspecialchars($item['printer_compatibility']) ?>">
                                <i class="fa-solid fa-print me-1 w-15px text-center"></i> <?= htmlspecialchars($item['printer_compatibility']) ?>
                            </div>
                        </div>
                        
                        <div class="mt-auto">
                            <?php if ($item['quantity'] > 3): ?>
                                <span class="badge bg-success w-100 py-2"><i class="fa-solid fa-check me-1"></i> Tersedia: <?= $item['quantity'] ?></span>
                            <?php elseif ($item['quantity'] > 0): ?>
                                <span class="badge bg-warning text-dark w-100 py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Kekurangan: <?= $item['quantity'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger w-100 py-2"><i class="fa-solid fa-xmark me-1"></i> Kehabisan</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between p-3 pt-0">
                        <button class="btn btn-outline-secondary w-50 me-1" onclick="openEditModal(<?= $item['colour_id'] ?>, <?= $item['model_id'] ?>, '<?= addslashes($item['brand']) ?>', '<?= addslashes($item['model_name']) ?>', '<?= addslashes($item['colour']) ?>', <?= $item['quantity'] ?>, '<?= addslashes($item['printer_compatibility']) ?>')">
                            <i class="fa-solid fa-pen-to-square"></i> Kemaskini
                        </button>
                        
                        <form id="delete-form-<?= $item['colour_id'] ?>" action="manage_inventory.php" method="POST" class="d-inline w-50 ms-1">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="colour_id" value="<?= $item['colour_id'] ?>">
                            <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDelete(<?= $item['colour_id'] ?>)">
                                <i class="fa-solid fa-trash"></i> Buang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div class="modal fade" id="addInkModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="manage_inventory.php" method="POST" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus text-primary me-2"></i>Tambah Dakwat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="fw-bold">Gambar Dakwat</label>
                    <input type="file" class="form-control" name="ink_image" accept="image/*">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Jenama</label>
                        <input type="text" class="form-control" name="brand" required placeholder="e.g. HP, Epson">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Model Dakwat</label>
                        <input type="text" class="form-control" name="model_name" required placeholder="e.g. 67 Original">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Warna</label>
                        <input type="text" class="form-control" name="colour" required placeholder="e.g. Black">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Kuantiti</label>
                        <input type="number" class="form-control" name="quantity" required min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Kesesuaian Printer</label>
                    <input type="text" class="form-control" name="printer_compatibility" placeholder="Terpakai kepada semua warna model ini">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editInkModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="manage_inventory.php" method="POST" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square text-secondary me-2"></i>Kemaskini Dakwat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="colour_id" id="edit_colour_id">
                <input type="hidden" name="model_id" id="edit_model_id">
                
                <div class="mb-3">
                    <label class="fw-bold">Kemaskini Gambar</label>
                    <input type="file" class="form-control" name="ink_image" accept="image/*">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Jenama</label>
                        <input type="text" class="form-control" name="brand" id="edit_brand" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Nama Model</label>
                        <input type="text" class="form-control" name="model_name" id="edit_model" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Warna</label>
                        <input type="text" class="form-control" name="colour" id="edit_color" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Kuantiti</label>
                        <input type="number" class="form-control" name="quantity" id="edit_qty" required min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Kesesuaian Printer</label>
                    <input type="text" class="form-control" name="printer_compatibility" id="edit_comp">
                    <small class="text-danger">Note: Kemaskini ini akan menjejaskan semua warna yang berkait dengan model tersebut.</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i> Kemaskini</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    <?php if ($swal_msg): ?>
        Swal.fire({ icon: '<?= $swal_msg['icon'] ?>', title: '<?= $swal_msg['title'] ?>', text: '<?= addslashes($swal_msg['text']) ?>', timer: 2500, showConfirmButton: false });
    <?php endif; ?>

    function confirmDelete(id) {
        Swal.fire({
            title: 'Buang dakwat??', text: "Tindakan ini tidak boleh diundur!", icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, buang!'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('delete-form-' + id).submit();
        });
    }

    function openEditModal(c_id, m_id, brand, model, color, qty, comp) {
        document.getElementById('edit_colour_id').value = c_id;
        document.getElementById('edit_model_id').value = m_id;
        document.getElementById('edit_brand').value = brand;
        document.getElementById('edit_model').value = model;
        document.getElementById('edit_color').value = color;
        document.getElementById('edit_qty').value = qty;
        document.getElementById('edit_comp').value = comp;
        
        let editModal = new bootstrap.Modal(document.getElementById('editInkModal'));
        editModal.show();
    }
    </script>
</body>
</html>