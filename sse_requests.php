<?php
session_start();
require 'db.php';

session_write_close(); 

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); 

$lastHash = '';

while (true) {
    if (connection_aborted()) break; 

    // ==========================================
    // 1. DASHBOARD DATA
    // ==========================================
    
    // KPIs
    $totalBrands = $pdo->query("SELECT COUNT(DISTINCT brand) FROM ink_models")->fetchColumn() ?: 0;
    $totalStock = $pdo->query("SELECT SUM(quantity) FROM ink_colours")->fetchColumn() ?: 0;
    $pendingRequestsCount = $pdo->query("SELECT COUNT(DISTINCT COALESCE(batch_id, id)) FROM requests WHERE status = 'pending'")->fetchColumn() ?: 0;
    $monthlyIssued = $pdo->query("
        SELECT SUM(quantity) FROM ink_transactions 
        WHERE type = 'OUT' AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
    ")->fetchColumn() ?: 0;

    // Recent Activity (Last 6)
    $recentActivity = $pdo->query("
        SELECT COALESCE(r.batch_id, r.id) as ref_id, r.request_date, r.status, 
               u.name as staff_name, m.brand, m.model_name, c.colour, r.quantity_requested
        FROM requests r
        JOIN users u ON r.user_id = u.id
        JOIN ink_colours c ON r.colour_id = c.colour_id
        JOIN ink_models m ON c.model_id = m.model_id
        ORDER BY r.request_date DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Format dates for Recent Activity
    foreach($recentActivity as &$act) {
        $act['formatted_date'] = date('d M Y, h:i A', strtotime($act['request_date']));
    }

    // ==========================================
    // 2. PENDING REQUESTS DATA (For admin_requests.php)
    // ==========================================
    $allPending = $pdo->query("
        SELECT r.id, r.batch_id, r.reason, u.name, u.department, m.brand, m.model_name, c.colour, r.quantity_requested, r.request_date 
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        JOIN ink_colours c ON r.colour_id = c.colour_id 
        JOIN ink_models m ON c.model_id = m.model_id
        WHERE r.status = 'pending'
        ORDER BY r.request_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $groupedRequests = [];
    foreach ($allPending as $req) {
        $bId = !empty($req['batch_id']) ? $req['batch_id'] : $req['id']; 
        if (!isset($groupedRequests[$bId])) {
            $groupedRequests[$bId] = [
                'batch_id' => $bId,
                'name' => $req['name'],
                'department' => $req['department'],
                'formatted_date' => date('d M Y, h:i A', strtotime($req['request_date'])),
                'reason' => !empty($req['reason']) ? $req['reason'] : 'Tiada sebab dinyatakan.',
                'items' => []
            ];
        }
        $groupedRequests[$bId]['items'][] = $req;
    }

    // ==========================================
    // 3. PACKAGE AND PUSH DATA
    // ==========================================
    
    // Combine everything into one giant array
    $fullPayload = [
        'dashboard' => [
            'kpis' => [
                'totalBrands' => $totalBrands,
                'totalStock' => $totalStock,
                'pendingRequests' => $pendingRequestsCount,
                'monthlyIssued' => $monthlyIssued
            ],
            'recentActivity' => $recentActivity
        ],
        'pending_page' => [
            'count' => count($groupedRequests), 
            'groups' => array_values($groupedRequests)
        ]
    ];

    $currentHash = md5(json_encode($fullPayload));

    if ($currentHash !== $lastHash) {
        echo "data: " . json_encode($fullPayload) . "\n\n";
        ob_flush();
        flush();
        $lastHash = $currentHash;
    }

    sleep(3);
}
?>