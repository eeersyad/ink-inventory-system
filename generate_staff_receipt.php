<?php
session_start();
require 'db.php';

// Ensure only staff can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("Unauthorized access.");
}

$batch_id = $_GET['batch_id'] ?? null;
if (!$batch_id) {
    die("Invalid Order ID.");
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Admin Details (Grabs the primary admin from the system)
$adminStmt = $pdo->prepare("SELECT name, position FROM users WHERE role = 'admin' LIMIT 1");
$adminStmt->execute();
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
$adminName = !empty($admin['name']) ? $admin['name'] : 'IT Administrator';
$adminPosition = !empty($admin['position']) ? $admin['position'] : 'IT Department';

// 2. Fetch Order and Staff Details
$reqStmt = $pdo->prepare("
    SELECT r.quantity_requested, r.request_date, r.status,
           u.name as staff_name, u.position as staff_position, u.department,
           c.colour, m.brand, m.model_name
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN ink_colours c ON r.colour_id = c.colour_id
    JOIN ink_models m ON c.model_id = m.model_id
    WHERE (r.batch_id = ? OR r.id = ?) AND r.user_id = ?
");
$reqStmt->execute([$batch_id, $batch_id, $user_id]);
$items = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    die("Order not found or you do not have permission to view it.");
}

$staffData = $items[0]; 
$printDate = date('d-M-Y H:i:s');
$requestDate = date('d-M-Y H:i:s', strtotime($staffData['request_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rujukan Staff - <?= htmlspecialchars($batch_id) ?></title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #e9ecef; color: #000; font-family: Arial, sans-serif; }
        .document-container { max-width: 850px; margin: 40px auto; background: #fff; padding: 50px; border: 1px solid #ccc; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        .doc-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .doc-title { font-size: 1.5rem; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-top: 10px; }
        .doc-subtitle { font-size: 1rem; font-weight: bold; color: #555; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .info-table td { border: 1px solid #000; padding: 10px; vertical-align: top; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; font-weight: bold; color: #555; display: block; margin-bottom: 3px; }
        .info-data { font-size: 1rem; font-weight: bold; margin: 0; }

        .item-table { border: 2px solid #000; min-width: 500px; }
        .item-table th, .item-table td { border: 1px solid #000; padding: 10px; color: #000; }
        .item-table th { background-color: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 0.85rem; }
        
        .audit-footer { margin-top: 50px; border-top: 2px solid #000; padding-top: 15px; font-size: 0.85rem; text-align: justify; color: #333; }

        @media print {
            body { background-color: #fff; margin: 0; padding: 0; }
            .document-container { box-shadow: none; border: none; max-width: 100%; margin: 0; padding: 20px; }
            .no-print { display: none !important; }
            .item-table th { background-color: #e9ecef !important; -webkit-print-color-adjust: exact; }
            .table-responsive { overflow-x: visible !important; }
        }

        /* ========================================== */
        /* GLOBAL MOBILE RESPONSIVENESS FIXES         */
        /* (Hanya efek pada paparan skrin)            */
        /* ========================================== */
        @media screen and (max-width: 767px) {
            body { font-size: 0.95rem; }
            .document-container { margin: 15px; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
            
            /* Susun butang di bahagian atas */
            .d-flex.justify-content-end.no-print.gap-2 {
                flex-direction: column;
                gap: 10px !important;
            }
            .no-print button {
                width: 100%;
            }

            /* Tukar info table dari mendatar ke menegak */
            .info-table, .info-table tbody, .info-table tr, .info-table td {
                display: block;
                width: 100% !important;
            }
            .info-table td {
                margin-bottom: -1px; /* Elak garisan bertindih */
            }

            h2 { font-size: 1.25rem !important; }
            .doc-title { font-size: 1.1rem; }
            .doc-subtitle { font-size: 0.8rem; }
        }
    </style>
</head>
<body>

    <div class="document-container">
        
        <!-- Controls -->
        <div class="d-flex justify-content-end mb-4 no-print gap-2">
            <button onclick="window.close()" class="btn btn-outline-dark"><i class="fa-solid fa-xmark me-1"></i> Tutup</button>
            <button onclick="window.print()" class="btn btn-dark fw-bold"><i class="fa-solid fa-print me-1"></i> Cetak</button>
        </div>

        <!-- Formal Header -->
        <div class="doc-header">
            <h2 class="mb-0 fw-bold">MAJLIS DAERAH MARANG</h2>
            <div class="doc-title">BORANG PERMOHONAN STOK</div>
            <div class="doc-subtitle">[ Rujukan Staf Sahaja ]</div>
        </div>

        <!-- Formal Info Grid -->
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <span class="info-label">Maklumat Pemohon</span>
                    <p class="info-data"><?= htmlspecialchars($staffData['staff_name']) ?></p>
                    <p class="mb-0 small"><?= htmlspecialchars($staffData['staff_position'] ?? 'N/A') ?> - <?= htmlspecialchars($staffData['department']) ?></p>
                </td>
                <td style="width: 50%;">
                    <span class="info-label">Maklumat Dokumen</span>
                    <p class="mb-1 small"><strong>No Rujukan:</strong> <span class="fw-bold fs-6"><?= htmlspecialchars($batch_id) ?></span></p>
                    <p class="mb-1 small"><strong>Tarikh Permohonan:</strong> <?= $requestDate ?></p>
                    <p class="mb-0 small"><strong>Tarikh Cetakan Dokumen:</strong> <?= $printDate ?></p>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="info-label">MAKLUMAT PELULUS (ADMIN)</span>
                    <p class="info-data"><?= htmlspecialchars($adminName) ?> <span class="fw-normal small text-muted">(<?= htmlspecialchars($adminPosition) ?>)</span></p>
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <h6 class="fw-bold text-uppercase mb-2">Butiran Permohonan Stok</h6>
        <div class="table-responsive border-0">
            <table class="table item-table mb-4">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No.</th>
                        <th style="width: 55%;">Penerangan Barangan Inventori</th>
                        <th style="width: 20%; text-align: center;">Warna</th>
                        <th style="width: 20%; text-align: center;">Kuantiti Diluluskan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; $totalQty = 0; foreach ($items as $item): ?>
                    <tr>
                        <td class="text-center fw-bold"><?= $count++ ?></td>
                        <td class="fw-bold">
                            <?= htmlspecialchars($item['brand']) ?> <?= htmlspecialchars($item['model_name']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($item['colour']) ?>
                        </td>
                        <td class="text-center fw-bold">
                            <?= $item['quantity_requested'] ?>
                        </td>
                    </tr>
                    <?php $totalQty += $item['quantity_requested']; endforeach; ?>
                    
                    <tr>
                        <td colspan="3" class="text-end fw-bold text-uppercase bg-light">Jumlah Kuantiti Diluluskan</td>
                        <td class="text-center fw-bold fs-5 bg-light"><?= $totalQty ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Audit Disclaimer (Replaces Signatures) -->
        <div class="audit-footer">
            <strong>DOKUMEN JANAAN SISTEM:</strong> Dokumen ini berfungsi sebagai salinan yang disahkan bagi permohonan inventori yang telah diluluskan, yang dijana oleh Sistem Dakwat Printer. Proses kelulusan tersebut direkodkan dengan selamat di dalam pangkalan data sistem di bawah No Rujukan <strong><?= htmlspecialchars($batch_id) ?></strong>. Tiada tandatangan fizikal diperlukan untuk salinan rujukan staf ini.
        </div>

    </div>

</body>
</html>