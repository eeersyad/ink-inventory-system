<?php
session_start();
require 'db.php';

// Include PHPMailer classes (Adjust paths if you are not using Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// require 'vendor/autoload.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // Receive batch_id instead of single request_id
    $batch_id = $_POST['batch_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$batch_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing Order ID.']);
        exit();
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Fetch ALL items belonging to this batch order
        $reqStmt = $pdo->prepare("
            SELECT r.id, r.colour_id, r.quantity_requested, 
                   u.email, u.name, u.position, u.department,
                   m.brand, m.model_name, c.colour 
            FROM requests r
            JOIN users u ON r.user_id = u.id
            JOIN ink_colours c ON r.colour_id = c.colour_id
            JOIN ink_models m ON c.model_id = m.model_id
            WHERE (r.batch_id = ? OR r.id = ?) AND r.status = 'pending'
        ");
        $reqStmt->execute([$batch_id, $batch_id]);
        $items = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new Exception("No pending items found for this request.");
        }

        // Setup user details for email (pulling from the first item)
        $userData = $items[0]; 

        if ($action === 'accept') {
            
            // Loop 1: Verify inventory for ALL items first
            foreach ($items as $item) {
                $invStmt = $pdo->prepare("SELECT quantity FROM ink_colours WHERE colour_id = ? FOR UPDATE");
                $invStmt->execute([$item['colour_id']]);
                $invData = $invStmt->fetch(PDO::FETCH_ASSOC);

                if ($invData['quantity'] < $item['quantity_requested']) {
                    throw new Exception("Not enough stock for " . $item['brand'] . " " . $item['model_name'] . " (" . $item['colour'] . ").");
                }
            }

            // Loop 2: Process the deductions and log transactions
            foreach ($items as $item) {
                // Deduct from main inventory
                $updateInv = $pdo->prepare("UPDATE ink_colours SET quantity = quantity - ? WHERE colour_id = ?");
                $updateInv->execute([$item['quantity_requested'], $item['colour_id']]);
                
                // Log the 'OUT' transaction into the ledger
                $logStmt = $pdo->prepare("INSERT INTO ink_transactions (colour_id, type, quantity) VALUES (?, 'OUT', ?)");
                $logStmt->execute([$item['colour_id'], $item['quantity_requested']]);
            }
            
            // Mark ALL staff requests in this batch as accepted
            $updateReq = $pdo->prepare("UPDATE requests SET status = 'accepted' WHERE batch_id = ? OR id = ?");
            $updateReq->execute([$batch_id, $batch_id]);

        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE batch_id = ? OR id = ?");
            $stmt->execute([$batch_id, $batch_id]);
        }

        // Commit transaction to database
        $pdo->commit();

        // ==========================================
        // SEND BATCH EMAIL NOTIFICATION
        // ==========================================
        if (($action === 'accept' || $action === 'reject') && !empty($userData['email'])) {
            try {
                $mail = new PHPMailer(true);

                // Server settings for Gmail
                $mail->isSMTP();
                $mail->Host       = '';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'YOUR_EMAIL_ADDRESS'; 
                $mail->Password   = 'YOUR_APP_PASSWORD';     
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('YOUR_EMAIL_ADDRESS', 'Admin - Sistem Dakwat Printer');
                $mail->addAddress($userData['email']); 

                // Preparing Variables
                $staffName = $userData['name']; 
                $position = !empty($userData['position']) ? $userData['position'] : 'N/A';
                $department = !empty($userData['department']) ? $userData['department'] : 'N/A';
                
                // Dynamically build the rows for the email receipt
                $itemRowsHtml = "";
                foreach ($items as $item) {
                    $itemRowsHtml .= "
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'>
                                <strong>{$item['brand']} {$item['model_name']}</strong><br>
                                <span style='font-size: 12px; color: #6c757d;'>{$item['colour']}</span>
                            </td>
                            <td style='padding: 10px; border-bottom: 1px solid #e9ecef; text-align: center; font-weight: bold;'>
                                {$item['quantity_requested']}
                            </td>
                        </tr>
                    ";
                }

                // ==========================================
                // DYNAMIC EMAIL CONTENT (ACCEPT VS REJECT)
                // ==========================================
                if ($action === 'accept') {
                    $headerColor = '#198754'; // Hijau untuk Lulus
                    $headerText = 'Permohonan Diluluskan';
                    $mail->Subject = 'Lulus: Permohonan Dakwat - ' . $staffName;
                    $messageBody = '<p>Permohonan dakwat pencetak anda telah <strong>diluluskan</strong> oleh Pentadbir IT.</p>';
                    $footerInstruction = '<p style="margin-bottom: 0;">Dakwat yang dimohon oleh anda akan dihantar oleh Pentadbir IT dengan segera. Sila berada di meja anda sementara menunggu.</p>';
                } else {
                    $headerColor = '#dc3545'; // Merah untuk Tolak
                    $headerText = 'Permohonan Ditolak';
                    $mail->Subject = 'Ditolak: Permohonan Dakwat - ' . $staffName;
                    $messageBody = '<p>Dukacita dimaklumkan bahawa permohonan dakwat pencetak anda telah <strong>ditolak</strong> oleh Pentadbir IT.</p>';
                    $footerInstruction = '<p style="margin-bottom: 0; color: #dc3545;">Sila hubungi Jabatan IT jika anda memerlukan penjelasan lanjut mengenai penolakan ini.</p>';
                }

                // Content
                $mail->isHTML(true);
                
                // Professional HTML Email Template
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                        <div style='background-color: {$headerColor}; color: white; padding: 20px; text-align: center;'>
                            <h2 style='margin: 0;'>{$headerText}</h2>
                        </div>
                        
                        <div style='padding: 20px;'>
                            <p>Hai <strong>{$staffName}</strong>,</p>
                            {$messageBody}
                            
                            <table style='width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px;'>
                                <tr>
                                    <td colspan='2' style='background-color: #f8f9fa; padding: 10px; font-weight: bold; border-bottom: 2px solid #dee2e6;'>Butiran Pemohon</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'><strong>Nama:</strong></td>
                                    <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'>{$staffName}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'><strong>Jawatan:</strong></td>
                                    <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'>{$position}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'><strong>Jabatan:</strong></td>
                                    <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'>{$department}</td>
                                </tr>
                                
                                <tr>
                                    <td colspan='2' style='background-color: #f8f9fa; padding: 10px; font-weight: bold; border-bottom: 2px solid #dee2e6; margin-top: 15px;'>Item Dimohon</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border-bottom: 2px solid #dee2e6; color: #6c757d; font-size: 13px;'>Butiran Dakwat</td>
                                    <td style='padding: 10px; border-bottom: 2px solid #dee2e6; color: #6c757d; font-size: 13px; text-align: center;'>Kuantiti</td>
                                </tr>
                                {$itemRowsHtml}
                            </table>

                            {$footerInstruction}
                        </div>
                        
                        <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e0e0e0;'>
                            Ini adalah mesej automatik yang dijana oleh Sistem Permohonan Dakwat Printer. Sila jangan balas e-mel ini.
                        </div>
                    </div>
                ";

                $mail->send();
            } catch (Exception $emailException) {
                error_log("Email failed to send: " . $mail->ErrorInfo);
            }
        }

        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>