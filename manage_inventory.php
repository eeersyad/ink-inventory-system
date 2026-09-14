<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $action = $_POST['action'];

    function handleImageUpload() {
        if (isset($_FILES['ink_image']) && $_FILES['ink_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            
            // Check if it failed due to WAMP file size limits
            if ($_FILES['ink_image']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['swal_msg'] = ['icon' => 'error', 'title' => 'Gambar Gagal', 'text' => 'Saiz gambar terlampau besar. Sila kurangkan saiz atau ubah tetapan WAMP.'];
                header("Location: admin_inventory.php");
                exit();
            }

            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['ink_image']['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            
            // Ditambah format 'heic' dan 'heif' untuk telefon pintar
            if (in_array(strtolower($ext), ['jpg', 'png', 'jpeg', 'gif', 'webp', 'heic', 'heif'])) {
                if (move_uploaded_file($_FILES['ink_image']['tmp_name'], $targetPath)) {
                    return $targetPath;
                }
            } else {
                $_SESSION['swal_msg'] = ['icon' => 'error', 'title' => 'Format Ditolak', 'text' => 'Sila muat naik gambar berformat JPG, PNG, atau WEBP sahaja.'];
                header("Location: admin_inventory.php");
                exit();
            }
        }
        return null;
    }

    try {
        if ($action === 'add') {
            $brand = trim($_POST['brand']);
            $model_name = trim($_POST['model_name']);
            $comp = trim($_POST['printer_compatibility']);
            $image_path = handleImageUpload();
            $new_quantity = (int)$_POST['quantity'];

            // 1. Get or Create Model
            $stmt = $pdo->prepare("SELECT model_id FROM ink_models WHERE brand = ? AND model_name = ?");
            $stmt->execute([$brand, $model_name]);
            $model_id = $stmt->fetchColumn();

            if (!$model_id) {
                $stmt = $pdo->prepare("INSERT INTO ink_models (brand, model_name, printer_compatibility) VALUES (?, ?, ?)");
                $stmt->execute([$brand, $model_name, $comp]);
                $model_id = $pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare("UPDATE ink_models SET printer_compatibility = ? WHERE model_id = ?");
                $stmt->execute([$comp, $model_id]);
            }

            // 2. Insert Color Variant
            $stmt = $pdo->prepare("INSERT INTO ink_colours (model_id, colour, quantity, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$model_id, trim($_POST['colour']), $new_quantity, $image_path]);
            $colour_id = $pdo->lastInsertId(); // Grab the new ID for the transaction log

            // 3. Log the 'IN' Transaction
            if ($new_quantity > 0) {
                $logStmt = $pdo->prepare("INSERT INTO ink_transactions (colour_id, type, quantity) VALUES (?, 'IN', ?)");
                $logStmt->execute([$colour_id, $new_quantity]);
            }

            $_SESSION['swal_msg'] = ['icon' => 'success', 'title' => 'Added!', 'text' => 'New ink added and logged.'];
        } 
        elseif ($action === 'edit') {
            $model_id = $_POST['model_id'];
            $colour_id = $_POST['colour_id'];
            $new_quantity = (int)$_POST['quantity'];
            $new_image_path = handleImageUpload();
            
            // Get old quantity to calculate the difference for the transaction log
            $qtyStmt = $pdo->prepare("SELECT quantity FROM ink_colours WHERE colour_id = ?");
            $qtyStmt->execute([$colour_id]);
            $old_quantity = (int)$qtyStmt->fetchColumn();
            
            if (!$new_image_path) {
                $stmt = $pdo->prepare("SELECT image_path FROM ink_colours WHERE colour_id = ?");
                $stmt->execute([$colour_id]);
                $new_image_path = $stmt->fetchColumn();
            }

            // Update parent model details
            $stmt = $pdo->prepare("UPDATE ink_models SET brand = ?, model_name = ?, printer_compatibility = ? WHERE model_id = ?");
            $stmt->execute([$_POST['brand'], $_POST['model_name'], $_POST['printer_compatibility'], $model_id]);

            // Update child color details
            $stmt = $pdo->prepare("UPDATE ink_colours SET colour = ?, quantity = ?, image_path = ? WHERE colour_id = ?");
            $stmt->execute([$_POST['colour'], $new_quantity, $new_image_path, $colour_id]);

            // Track Inventory Changes dynamically
            $qty_diff = $new_quantity - $old_quantity;
            if ($qty_diff > 0) {
                // Stock went up
                $logStmt = $pdo->prepare("INSERT INTO ink_transactions (colour_id, type, quantity) VALUES (?, 'IN', ?)");
                $logStmt->execute([$colour_id, $qty_diff]);
            } elseif ($qty_diff < 0) {
                // Stock went down (Admin manual adjustment)
                $logStmt = $pdo->prepare("INSERT INTO ink_transactions (colour_id, type, quantity) VALUES (?, 'OUT', ?)");
                $logStmt->execute([$colour_id, abs($qty_diff)]);
            }

            $_SESSION['swal_msg'] = ['icon' => 'success', 'title' => 'Updated!', 'text' => 'Ink details and stock levels updated.'];
        } 
            elseif ($action === 'delete') {
                $colour_id = $_POST['colour_id'];
                
                // 1. Get the image path AND the model_id BEFORE deleting the color
                $stmt = $pdo->prepare("SELECT image_path, model_id FROM ink_colours WHERE colour_id = ?");
                $stmt->execute([$colour_id]);
                $colorData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $img_to_delete = $colorData['image_path'] ?? null;
                $model_id = $colorData['model_id'] ?? null;
                
                // 2. Delete the color variant
                $stmt = $pdo->prepare("DELETE FROM ink_colours WHERE colour_id = ?");
                $stmt->execute([$colour_id]);
                
                // 3. Delete the image file from the server if it exists
                if ($img_to_delete && file_exists($img_to_delete)) {
                    unlink($img_to_delete);
                }

                // 4. CLEANUP: Check if the parent model has any colors left
                if ($model_id) {
                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ink_colours WHERE model_id = ?");
                    $countStmt->execute([$model_id]);
                    $remainingColors = $countStmt->fetchColumn();

                    // If no colors are left, delete the parent model automatically
                    if ($remainingColors == 0) {
                        $deleteModelStmt = $pdo->prepare("DELETE FROM ink_models WHERE model_id = ?");
                        $deleteModelStmt->execute([$model_id]);
                    }
                }
                
                $_SESSION['swal_msg'] = ['icon' => 'success', 'title' => 'Deleted!', 'text' => 'Ink removed from inventory.'];
            }
    } catch (PDOException $e) {
        $_SESSION['swal_msg'] = ['icon' => 'error', 'title' => 'Action Failed', 'text' => 'Database error. Make sure there are no pending requests for this item.'];
    }

    header("Location: admin_inventory.php");
    exit();
}
?>