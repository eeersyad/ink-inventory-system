<?php
session_start();
require 'db.php';

// Ensure it's an AJAX request (basic check)
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Initialize empty cart array
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ADD TO CART
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $colour_id = $_POST['colour_id'] ?? null;
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    // We need to fetch details to show in the cart UI
    $stmt = $pdo->prepare("
        SELECT m.brand, m.model_name, c.colour, c.quantity as max_stock 
        FROM ink_colours c 
        JOIN ink_models m ON c.model_id = m.model_id 
        WHERE c.colour_id = ?
    ");
    $stmt->execute([$colour_id]);
    $ink = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ink) {
        // If already in cart, just update quantity (without exceeding max stock)
        if (isset($_SESSION['cart'][$colour_id])) {
            $newQty = $_SESSION['cart'][$colour_id]['request_qty'] + $quantity;
            $_SESSION['cart'][$colour_id]['request_qty'] = min($newQty, $ink['max_stock']);
        } else {
            // Add new item to cart
            $_SESSION['cart'][$colour_id] = [
                'colour_id' => $colour_id,
                'name' => $ink['brand'] . ' ' . $ink['model_name'],
                'colour' => $ink['colour'],
                'request_qty' => $quantity,
                'max_stock' => $ink['max_stock']
            ];
        }
        echo json_encode(['status' => 'success', 'cart_count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ink not found.']);
    }
    exit();
}

// GET CART CONTENTS
if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'success', 
        'items' => array_values($_SESSION['cart']), 
        'count' => count($_SESSION['cart'])
    ]);
    exit();
}

// CLEAR CART
if ($action === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['cart'] = [];
    echo json_encode(['status' => 'success']);
    exit();
}

// REMOVE SINGLE ITEM
if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $colour_id = $_POST['colour_id'] ?? null;
    if (isset($_SESSION['cart'][$colour_id])) {
        unset($_SESSION['cart'][$colour_id]);
    }
    echo json_encode(['status' => 'success', 'cart_count' => count($_SESSION['cart'])]);
    exit();
}