<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. DATA INVENTORI
$stmt = $pdo->query("
    SELECT m.model_id, m.brand, m.model_name, m.printer_compatibility, 
           c.colour_id, c.colour, c.quantity, c.image_path 
    FROM ink_models m
    LEFT JOIN ink_colours c ON m.model_id = c.model_id 
    ORDER BY m.brand ASC, m.model_name ASC, c.colour ASC
");
$rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$groupedModels = [];
foreach ($rawData as $row) {
    $mid = $row['model_id'];
    if (!isset($groupedModels[$mid])) {
        $groupedModels[$mid] = [
            'model_id' => $row['model_id'], 'brand' => $row['brand'], 'model_name' => $row['model_name'],
            'printer_compatibility' => $row['printer_compatibility'], 'total_stock' => 0, 'thumbnail' => null, 'colors' => []
        ];
    }
    if ($row['colour_id']) {
        $groupedModels[$mid]['colors'][] = $row;
        $groupedModels[$mid]['total_stock'] += $row['quantity'];
        if (!$groupedModels[$mid]['thumbnail'] && !empty($row['image_path']) && file_exists($row['image_path'])) {
            $groupedModels[$mid]['thumbnail'] = $row['image_path'];
        }
    }
}

// 2. DATA PERMOHONAN
$reqStmt = $pdo->prepare("
    SELECT r.id, r.batch_id, r.quantity_requested, r.status, r.request_date,
           c.colour, m.brand, m.model_name
    FROM requests r
    JOIN ink_colours c ON r.colour_id = c.colour_id
    JOIN ink_models m ON c.model_id = m.model_id
    WHERE r.user_id = ?
    ORDER BY r.request_date DESC
");
$reqStmt->execute([$user_id]);
$myRequests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

$groupedRequests = [];
foreach ($myRequests as $req) {
    $bId = !empty($req['batch_id']) ? $req['batch_id'] : $req['id']; 
    if (!isset($groupedRequests[$bId])) {
        $groupedRequests[$bId] = [
            'batch_id' => $bId,
            'formatted_date' => date('d M Y, h:i A', strtotime($req['request_date'])),
            'status' => $req['status'],
            'items' => []
        ];
    }
    $groupedRequests[$bId]['items'][] = $req;
}

echo json_encode([
    'inventory' => array_values($groupedModels),
    'requests' => [
        'count' => count($groupedRequests),
        'groups' => array_values($groupedRequests)
    ]
]);
?>