<?php
session_start();
require 'db.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get the filters from the URL
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? '';
$isExport = isset($_GET['export']) && $_GET['export'] === 'excel';

// Format the display title 
$display_period = $year;
if (!empty($month)) {
    $monthName = date("F", mktime(0, 0, 0, $month, 10));
    $display_period = strtoupper("$monthName $year");
} else {
    $display_period = "$year (SEPANJANG TAHUN)";
}

// Query the database to get Stock Semasa, IN, and OUT data
$stmt = $pdo->prepare("
    SELECT 
        m.brand, m.model_name, c.colour, c.quantity AS current_stock,
        COALESCE(SUM(CASE WHEN t.type = 'IN' THEN t.quantity ELSE 0 END), 0) AS total_in,
        COALESCE(SUM(CASE WHEN t.type = 'OUT' THEN t.quantity ELSE 0 END), 0) AS total_out
    FROM ink_colours c
    JOIN ink_models m ON c.model_id = m.model_id
    LEFT JOIN ink_transactions t ON c.colour_id = t.colour_id 
          AND YEAR(t.transaction_date) = ? 
          AND (? = '' OR MONTH(t.transaction_date) = ?)
    GROUP BY c.colour_id
    ORDER BY m.brand, m.model_name, c.colour
");
$stmt->execute([$year, $month, $month]);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Insights
$total_all_in = 0;
$total_all_out = 0;
$total_current_stock = 0;
$highest_out_qty = 0;
$most_used_ink = "N/A";

foreach ($reportData as $row) {
    $total_all_in += $row['total_in'];
    $total_all_out += $row['total_out'];
    $total_current_stock += $row['current_stock'];
    
    if ($row['total_out'] > $highest_out_qty) {
        $highest_out_qty = $row['total_out'];
        $most_used_ink = $row['brand'] . ' ' . $row['model_name'] . ' (' . $row['colour'] . ')';
    }
}

// IF EXPORT TO EXCEL IS CLICKED
if ($isExport) {
    $filename = "Inventory_Report_" . str_replace(' ', '_', $display_period) . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/1999/xlink">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <table>
        <tr><td colspan="7" style="font-size: 16px; font-weight: bold; text-align: center;">MAJLIS DAERAH MARANG - LAPORAN RASMI INVENTORI</td></tr>
        <tr><td colspan="7" style="text-align: center;">UNTUK: <?= $display_period ?></td></tr>
        <tr><td colspan="7"></td></tr>
    </table>

    <table border="1">
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f2f2f2;">JUMLAH DAKWAT (OUT):</td>
            <td colspan="5"><?= $total_all_out ?> units</td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f2f2f2;">JUMLAH DAKWAT DITERIMA (IN):</td>
            <td colspan="5"><?= $total_all_in ?> units</td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f2f2f2;">JUMLAH STOK SEMASA:</td>
            <td colspan="5"><?= $total_current_stock ?> units</td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold; background-color: #f2f2f2;">INVENTORI DILULUSKAN TERTINGGI:</td>
            <td colspan="5"><?= htmlspecialchars($most_used_ink) ?> (<?= $highest_out_qty ?> units)</td>
        </tr>
    </table>

    <br>

    <table border="1">
        <thead>
            <tr style="background-color: #e0e0e0; font-weight: bold;">
                <th style="text-align: center;">No.</th>
                <th>Jenama</th>
                <th>Model</th>
                <th>Warna</th>
                <th style="text-align: center;">Jumlah IN</th>
                <th style="text-align: center;">Jumlah OUT</th>
                <th style="text-align: center;">Stok Semasa</th>
            </tr>
        </thead>
        <tbody>
            <?php $count = 1; foreach ($reportData as $row): ?>
            <tr>
                <td style="text-align: center;"><?= $count++ ?></td>
                <td><?= htmlspecialchars($row['brand']) ?></td>
                <td><?= htmlspecialchars($row['model_name']) ?></td>
                <td><?= htmlspecialchars($row['colour']) ?></td>
                <td style="text-align: center;"><?= $row['total_in'] ?></td>
                <td style="text-align: center;"><?= $row['total_out'] ?></td>
                <td style="text-align: center; font-weight: bold;"><?= $row['current_stock'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- PENTING: Meta Viewport untuk paparan Mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rasmi Inventori - <?= $display_period ?></title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #fff; color: #000; font-family: 'Times New Roman', Times, serif; }
        .report-container { max-width: 1000px; margin: 40px auto; padding: 40px; border: 1px solid #000; }
        
        .header-section { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .doc-title { font-size: 1.5rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .summary-table td { border: 1px solid #000; padding: 15px; width: 25%; vertical-align: top; }
        .summary-label { font-size: 0.8rem; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px; }
        .summary-data { font-size: 1.25rem; font-weight: bold; margin: 0; }

        .ledger-table { border: 2px solid #000; width: 100%; margin-bottom: 40px; }
        .ledger-table th, .ledger-table td { border: 1px solid #000; padding: 10px; color: #000; }
        .ledger-table th { background-color: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 0.85rem; }

        .sign-off { margin-top: 50px; }
        .sign-line { border-bottom: 1px solid #000; width: 250px; margin-top: 40px; margin-bottom: 5px; max-width: 100%; }

        @media print {
            .report-container { border: none; margin: 0; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
            .ledger-table th { background-color: #e9ecef !important; -webkit-print-color-adjust: exact; }
        }

        /* ========================================== */
        /* GLOBAL MOBILE RESPONSIVENESS FIXES         */
        /* (Hanya efek pada skrin, tidak pada cetakan)*/
        /* ========================================== */
        @media screen and (max-width: 767px) {
            body { font-size: 0.95rem; }
            .report-container { margin: 15px; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
            .header-section h2 { font-size: 1.25rem !important; }
            .doc-title { font-size: 1.1rem; }

            /* Susun butang di bahagian atas */
            .d-flex.justify-content-end.no-print.gap-2 {
                flex-direction: column;
                gap: 10px !important;
            }
            .no-print button, .no-print a {
                width: 100%;
            }

            /* Tukar jadual ringkasan dari mendatar (4 lajur) ke menegak */
            .summary-table, .summary-table tbody, .summary-table tr, .summary-table td {
                display: block;
                width: 100%;
            }
            .summary-table td {
                margin-bottom: -1px; /* Elak garisan berganda bertindih */
            }

            /* Ruangan tandatangan */
            .sign-off .col-md-6 {
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>

    <div class="report-container">
        <!-- Controls -->
        <div class="d-flex justify-content-end mb-4 no-print gap-2">
            <button onclick="window.close()" class="btn btn-outline-dark"><i class="fa-solid fa-xmark me-1"></i> Tutup</button>
            <button onclick="window.print()" class="btn btn-dark fw-bold"><i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF</button>
            <!-- NEW: Export to Excel Button -->
            <a href="generate_report.php?year=<?= urlencode($year) ?>&month=<?= urlencode($month) ?>&export=excel" class="btn btn-success fw-bold"><i class="fa-solid fa-file-excel me-1"></i> Export ke Excel</a>
        </div>

        <!-- Header -->
        <div class="header-section">
            <h2 class="fw-bold mb-1">MAJLIS DAERAH MARANG</h2>
            <div class="doc-title">LAPORAN RASMI INVENTORI</div>
            <p class="mt-2 mb-0"><strong>UNTUK:</strong> <?= $display_period ?></p>
        </div>

        <!-- Formal Summary Table -->
        <table class="summary-table">
            <tr>
                <td>
                    <span class="summary-label">Jumlah Dakwat Diluluskan (OUT)</span>
                    <p class="summary-data"><?= $total_all_out ?> unit</p>
                </td>
                <td>
                    <span class="summary-label">Jumlah Dakwat Diterima (IN)</span>
                    <p class="summary-data"><?= $total_all_in ?> unit</p>
                </td>
                <td>
                    <span class="summary-label">Stok Semasa Dakwat</span>
                    <p class="summary-data"><?= $total_current_stock ?> unit</p>
                </td>
                <td>
                    <span class="summary-label">Inventori Diluluskan Tertinggi</span>
                    <p class="summary-data" style="font-size: 1rem;"><?= htmlspecialchars($most_used_ink) ?></p>
                    <p class="mb-0 small">(<?= $highest_out_qty ?> unit)</p>
                </td>
            </tr>
        </table>

        <!-- Detailed Ledger Table -->
        <h6 class="fw-bold text-uppercase mb-2">Butiran Lejar Terperinci</h6>
        <!-- Balut dengan table-responsive -->
        <div class="table-responsive border-0">
            <table class="table ledger-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No.</th>
                        <th style="width: 25%; min-width: 120px;">Jenama</th>
                        <th style="width: 25%; min-width: 120px;">Model</th>
                        <th style="width: 15%; min-width: 100px;">Warna</th>
                        <th style="width: 10%; text-align: center; min-width: 90px;">Jumlah IN</th>
                        <th style="width: 10%; text-align: center; min-width: 90px;">Jumlah OUT</th>
                        <th style="width: 10%; text-align: center; min-width: 100px;">Stok Semasa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach ($reportData as $row): ?>
                    <tr>
                        <td class="text-center"><?= $count++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['brand']) ?></td>
                        <td><?= htmlspecialchars($row['model_name']) ?></td>
                        <td><?= htmlspecialchars($row['colour']) ?></td>
                        <td class="text-center"><?= $row['total_in'] ?></td>
                        <td class="text-center"><?= $row['total_out'] ?></td>
                        <td class="text-center fw-bold"><?= $row['current_stock'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Verification Sign-off -->
        <!-- Tukar col-6 kepada col-12 col-md-6 untuk responsif -->
        <div class="row sign-off no-break">
            <div class="col-12 col-md-6">
                <p class="mb-0 fw-bold">Laporan Dihasilkan Oleh:</p>
                <p class="mb-0 small">System Administrator</p>
                <p class="mt-2 small">Tarikh: <?= date('d F Y, H:i:s') ?></p>
            </div>
            <div class="col-12 col-md-6">
                <p class="mb-0 fw-bold">Disahkan Oleh:</p>
                <div class="sign-line"></div>
                <p class="mb-0 small">Nama: ______________________</p>
                <p class="mb-0 small">Tarikh: ______________________</p>
            </div>
        </div>
    </div>

</body>
</html>