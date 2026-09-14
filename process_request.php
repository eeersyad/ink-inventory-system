<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
    $user_id = $_SESSION['user_id'];
    $colour_id = $_POST['colour_id'];
    $requested_qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Failsafe 1: Ensure quantity is at least 1
    if ($requested_qty < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Please request at least 1 unit.']);
        exit();
    }

    // Failsafe 2: Check if requested quantity exceeds available stock
    $checkStmt = $pdo->prepare("SELECT quantity FROM ink_colours WHERE colour_id = ?");
    $checkStmt->execute([$colour_id]);
    $currentStock = (int)$checkStmt->fetchColumn();

    if ($requested_qty > $currentStock) {
        echo json_encode(['status' => 'error', 'message' => "You requested $requested_qty, but only $currentStock are in stock."]);
        exit();
    }
    
    // Insert the request with the specific quantity
    $stmt = $pdo->prepare("INSERT INTO requests (user_id, colour_id, quantity_requested) VALUES (?, ?, ?)");
    
    if($stmt->execute([$user_id, $colour_id, $requested_qty])) {
        echo json_encode(['status' => 'success', 'message' => "Request for $requested_qty unit(s) sent to Admin."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send request.']);
    }
}
?>