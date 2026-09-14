<?php
session_start();
require 'db.php';

// Panggil kelas PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Panggil fail PHPMailer dari folder sistem anda
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['cart'])) {
        echo json_encode(['status' => 'error', 'message' => 'Your cart is empty!']);
        exit();
    }

    $userId = $_SESSION['user_id'];
    
    // Catch the reason from the JavaScript FormData
    $reason = $_POST['reason'] ?? 'Tiada sebab dinyatakan';
    
    // ==========================================
    // GENERATE SEQUENTIAL BATCH ID (REQ-001)
    // ==========================================
    // 1. Get the most recent batch_id from the database
    $stmt = $pdo->query("SELECT batch_id FROM requests WHERE batch_id LIKE 'REQ-%' ORDER BY id DESC LIMIT 1");
    $lastBatch = $stmt->fetchColumn();

    if ($lastBatch) {
        // 2. If a previous request exists, extract the number and add 1
        $lastNumber = (int) str_replace('REQ-', '', $lastBatch);
        $nextNumber = $lastNumber + 1;
    } else {
        // 3. If no previous requests exist, start at 1
        $nextNumber = 1;
    }

    // 4. Format the number to always have at least 3 digits (e.g., 001, 015, 120)
    $batchId = 'REQ-' . sprintf('%03d', $nextNumber);
    // ==========================================
    
    try {
        $pdo->beginTransaction();
        
        // Prepare the insert query including batch_id and reason
        $insertStmt = $pdo->prepare("INSERT INTO requests (batch_id, user_id, colour_id, quantity_requested, status, reason) VALUES (?, ?, ?, ?, 'pending', ?)");
        
        foreach ($_SESSION['cart'] as $item) {
            $insertStmt->execute([$batchId, $userId, $item['colour_id'], $item['request_qty'], $reason]);
        }
        
        $pdo->commit();

        // ==========================================
        // HANTAR NOTIFIKASI EMEL (MENGGUNAKAN PHPMAILER)
        // ==========================================
        try {
            // Cari semua emel Admin di dalam sistem
            $stmtAdmin = $pdo->query("SELECT email FROM users WHERE role = 'admin' AND email IS NOT NULL AND email != ''");
            $admins = $stmtAdmin->fetchAll(PDO::FETCH_ASSOC);

            if (count($admins) > 0) {
                // Dapatkan maklumat staf yang memohon
                $stmtUser = $pdo->prepare("SELECT name, department FROM users WHERE id = ?");
                $stmtUser->execute([$userId]);
                $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                $subject = "Sistem Dakwat: Permohonan Baru (" . $batchId . ")";
                
                $message = "Salam Admin,\n\n";
                $message .= "Terdapat permohonan dakwat baru yang memerlukan pengesahan anda.\n\n";
                $message .= "Pemohon: " . ($user['name'] ?? 'Unknown') . "\n";
                $message .= "Jabatan: " . ($user['department'] ?? 'Unknown') . "\n";
                $message .= "Sebab: " . $reason . "\n\n";
                $message .= "Sila log masuk ke Sistem Inventori Dakwat Printer untuk melihat butiran lanjut dan meluluskan permohonan ini.\n\n";
                $message .= "Terima kasih.";

                $mail = new PHPMailer(true);

                // Konfigurasi Server SMTP Gmail
                $mail->isSMTP();
                $mail->Host       = '';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'YOUR_EMAIL_ADDRESS'; // Masukkan emel Gmail anda
                $mail->Password   = 'YOUR_APP_PASSWORD'; // Masukkan App Password di sini
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                // Tetapan Penghantar
                $mail->setFrom('YOUR_EMAIL_ADDRESS', 'Sistem Dakwat MDM');
                
                // Tambah semua e-mel penerima (Admin)
                foreach ($admins as $admin) {
                    $mail->addAddress($admin['email']); 
                }

                // Tetapan Kandungan Emel
                $mail->isHTML(false); 
                $mail->Subject = $subject;
                $mail->Body    = $message;

                // Hantar emel
                $mail->send();
            }
        } catch (Exception $e) {
            // Abaikan ralat emel supaya checkout staf tidak terganggu
            error_log("Gagal menghantar emel: " . $mail->ErrorInfo);
        }
        // ==========================================

        $_SESSION['cart'] = []; 
        
        echo json_encode(['status' => 'success', 'message' => 'All requests submitted successfully!']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to process checkout: ' . $e->getMessage()]);
    }
}
?>