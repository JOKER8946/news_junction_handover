<?php
require_once 'db_config.php';
require_once 'validate.logged.php';
require_once 'function.php';

header('Content-Type: application/json');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

function sendComplaintEmail($complaint_id, $recipient_email, $recipient_name, $contact_id = null)
{
    global $creamdb, $gUserId;

    try {
        // Fetch complaint details
        $complaintSql = "SELECT c.*, u.full_name as complainant_name, u.email as complainant_email 
                        FROM complaints c 
                        JOIN user u ON c.user_id = u.id 
                        WHERE c.id = ?";
        $stmt = $creamdb->prepare($complaintSql);
        $stmt->bind_param("i", $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaint = $result->fetch_assoc();
        $stmt->close();

        if (!$complaint) {
            return ['status' => 'failed', 'message' => 'Complaint not found'];
        }

        // Prepare email content
        $subject = "Official Complaint Notification - Ref: " . $complaint['ticket_id'];
        
        $emailBody = "
        <html>
        <head>
            <style>
                * { margin: 0; padding: 0; }
                body { font-family: 'Calibri', 'Arial', sans-serif; line-height: 1.5; color: #1a1a1a; background: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; }
                .header-section { border-bottom: 3px solid #003d7a; padding-bottom: 20px; margin-bottom: 30px; }
                .organization-name { font-size: 18px; font-weight: bold; color: #003d7a; margin-bottom: 5px; }
                .organization-info { font-size: 12px; color: #666; }
                .title { text-align: center; font-size: 16px; font-weight: bold; color: #003d7a; margin: 30px 0; text-transform: uppercase; letter-spacing: 0.5px; }
                .ref-section { background: #f9f9f9; padding: 15px; border-left: 4px solid #003d7a; margin-bottom: 25px; }
                .ref-item { margin-bottom: 10px; }
                .ref-label { font-weight: bold; color: #003d7a; display: inline-block; width: 140px; }
                .ref-value { color: #333; }
                .content-section { margin: 25px 0; }
                .section-title { font-weight: bold; color: #003d7a; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #003d7a; padding-bottom: 8px; margin-bottom: 12px; letter-spacing: 0.3px; }
                .content-text { text-align: justify; line-height: 1.7; color: #333; margin-bottom: 15px; }
                .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .details-table td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
                .details-table .label { font-weight: bold; color: #003d7a; width: 35%; background: #f9f9f9; }
                .details-table .value { color: #333; }
                .status-badge { display: inline-block; padding: 6px 12px; border-radius: 3px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
                .status-open { background: #fff3cd; color: #856404; }
                .status-in-progress { background: #d1ecf1; color: #0c5460; }
                .status-resolved { background: #d4edda; color: #155724; }
                .status-closed { background: #e2e3e5; color: #383d41; }
                .footer-section { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 11px; color: #666; text-align: center; }
                .footer-line { margin: 8px 0; }
                .attachment-note { background: #f0f7ff; padding: 12px; border-left: 4px solid #0066cc; margin: 20px 0; font-size: 12px; color: #0066cc; }
            </style>
        </head>
        <body>
            <div class='container'>
                <!-- Header -->
                <div class='header-section'>
                    <div class='organization-name'>COMPLAINT MANAGEMENT SYSTEM</div>
                    <div class='organization-info'>Official Complaint Notification</div>
                </div>

                <!-- Title -->
                <div class='title'>Formal Complaint Notification</div>

                <!-- Reference Details -->
                <div class='ref-section'>
                    <div class='ref-item'>
                        <span class='ref-label'>Reference Number:</span>
                        <span class='ref-value'>{$complaint['ticket_id']}</span>
                    </div>
                    <div class='ref-item'>
                        <span class='ref-label'>Date of Issue:</span>
                        <span class='ref-value'>" . date('d-M-Y', strtotime($complaint['created_at'])) . "</span>
                    </div>
                    <div class='ref-item'>
                        <span class='ref-label'>Current Status:</span>
                        <span class='status-badge status-{$complaint['status']}'>" . ucfirst(str_replace('-', ' ', $complaint['status'])) . "</span>
                    </div>
                </div>

                <!-- Complaint Title -->
                <div class='content-section'>
                    <div class='section-title'>Subject Matter</div>
                    <div class='content-text' style='font-weight: 600; color: #003d7a;'>" . htmlspecialchars($complaint['title']) . "</div>
                </div>

                <!-- Complainant Details -->
                <div class='content-section'>
                    <div class='section-title'>Complainant Details</div>
                    <table class='details-table'>
                        <tr>
                            <td class='label'>Name</td>
                            <td class='value'>" . htmlspecialchars($complaint['complainant_name']) . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Contact Email</td>
                            <td class='value'>" . htmlspecialchars($complaint['complainant_email']) . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Jurisdiction (Pincode)</td>
                            <td class='value'>" . htmlspecialchars($complaint['pincode'] ?? 'Not Specified') . "</td>
                        </tr>
                    </table>
                </div>

                <!-- Complaint Details -->
                <div class='content-section'>
                    <div class='section-title'>Complaint Details</div>
                    <div class='content-text'>" . nl2br(htmlspecialchars($complaint['description'])) . "</div>
                </div>

                <!-- Supporting Documents -->
                " . (!empty($complaint['media_url']) ? "
                <div class='content-section'>
                    <div class='section-title'>Supporting Documents</div>
                    <div class='attachment-note'>
                        📎 Supporting documentation has been attached to this correspondence for your review and action.
                    </div>
                </div>
                " : "") . "

                <!-- Admin Remarks (if any) -->
                " . (!empty($complaint['admin_remark']) ? "
                <div class='content-section'>
                    <div class='section-title'>Remarks</div>
                    <div class='content-text'>" . nl2br(htmlspecialchars($complaint['admin_remark'])) . "</div>
                </div>
                " : "") . "

                <!-- Important Notice -->
                <div class='content-section' style='background: #f5f5f5; padding: 15px; border-left: 4px solid #003d7a;'>
                    <div class='section-title'>Notice</div>
                    <div style='font-size: 12px; color: #333; line-height: 1.6;'>
                        This is an official notification regarding the registered complaint. Please treat this correspondence as formal communication. 
                        For any queries or follow-up information, please refer to the reference number mentioned above.
                    </div>
                </div>

                <!-- Footer -->
                <div class='footer-section'>
                    <div class='footer-line'><strong>Complaint Management System</strong></div>
                    <div class='footer-line'>This is an automated notification. Please do not reply to this email address.</div>
                    <div class='footer-line'>© " . date('Y') . " - All Rights Reserved</div>
                </div>
            </div>
        </body>
        </html>
        ";

        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        
        // Enable debugging if needed (comment out in production)
        // $mail->SMTPDebug = 2;
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'YOUR_SMTP_FROM_EMAIL';
        $mail->Password = 'YOUR_GMAIL_APP_PASSWORD_3';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom('YOUR_SMTP_FROM_EMAIL', 'Pulse Admin');
        $mail->addAddress($recipient_email, $recipient_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        // Add attachment if media exists
        if (!empty($complaint['media_url'])) {
            // Check if it's an absolute path or relative path
            $mediaPath = $complaint['media_url'];
            
            // If it's a relative path, convert to absolute path
            if (strpos($mediaPath, 'http') !== 0 && strpos($mediaPath, '/') === 0) {
                $mediaPath = $_SERVER['DOCUMENT_ROOT'] . $mediaPath;
            } elseif (strpos($mediaPath, 'http') !== 0) {
                // Relative path from current directory
                $mediaPath = __DIR__ . '/../../' . $mediaPath;
            }
            
            // Clean up the path
            $mediaPath = str_replace('\\', '/', $mediaPath);
            $mediaPath = realpath($mediaPath);
            
            // Add attachment if file exists
            if ($mediaPath && file_exists($mediaPath)) {
                try {
                    $mail->addAttachment($mediaPath);
                } catch (Exception $e) {
                    // Log attachment error but continue sending
                    error_log("Failed to attach media: " . $e->getMessage());
                }
            }
        }

        // Send
        $mail->send();

        // Log the successful send
        $logSql = "INSERT INTO complaint_email_logs (complaint_id, recipient_email, recipient_name, sent_by, subject, status, sent_at) 
                   VALUES (?, ?, ?, ?, ?, 'sent', NOW())";
        $logStmt = $creamdb->prepare($logSql);
        $logStmt->bind_param("issis", $complaint_id, $recipient_email, $recipient_name, $gUserId, $subject);
        $logStmt->execute();
        $logStmt->close();

        return ['status' => 'success', 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        // Log the failed attempt
        $error_msg = $e->getMessage();
        $logSql = "INSERT INTO complaint_email_logs (complaint_id, recipient_email, recipient_name, sent_by, subject, status, error_message) 
                   VALUES (?, ?, ?, ?, ?, 'failed', ?)";
        $logStmt = $creamdb->prepare($logSql);
        $logStmt->bind_param("ississ", $complaint_id, $recipient_email, $recipient_name, $gUserId, $subject, $error_msg);
        $logStmt->execute();
        $logStmt->close();

        return ['status' => 'failed', 'message' => 'Mailer Error: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Send complaint to emails
    if ($action == 'sendComplaintEmail') {
        $complaint_id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
        $emails = isset($_POST['emails']) ? $_POST['emails'] : '';

        if (!$complaint_id || empty($emails)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        $emailList = array_map('trim', explode(',', $emails));
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        $errorDetails = [];

        // Get contact details for each email
        foreach ($emailList as $email) {
            $email = filter_var($email, FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $failureCount++;
                $errorDetails[] = "Invalid email format";
                continue;
            }

            // Fetch contact name
            $contactSql = "SELECT name FROM complaint_email_contacts WHERE email = ?";
            $contactStmt = $creamdb->prepare($contactSql);
            $contactStmt->bind_param("s", $email);
            $contactStmt->execute();
            $contactResult = $contactStmt->get_result();
            $contact = $contactResult->fetch_assoc();
            $contactStmt->close();

            $recipientName = $contact['name'] ?? 'Recipient';

            // Send email
            $result = sendComplaintEmail($complaint_id, $email, $recipientName);
            if ($result['status'] == 'success') {
                $successCount++;
            } else {
                $failureCount++;
                $errorDetails[] = $email . ": " . $result['message'];
            }
            $results[] = $result;
        }

        if ($successCount > 0) {
            echo json_encode([
                'status' => 'success', 
                'message' => "Complaint sent to {$successCount} recipient(s)" . ($failureCount > 0 ? ", {$failureCount} failed" : ""),
                'details' => $errorDetails
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Failed to send emails',
                'details' => $errorDetails
            ]);
        }
        exit;
    }

    // Update complaint status
    if ($action == 'updateStatus') {
        $complaint_id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        if (!$complaint_id || empty($status)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        $valid_statuses = ['open', 'in-progress', 'resolved', 'closed'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            exit;
        }

        $sql = "UPDATE complaints SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $creamdb->prepare($sql);
        $stmt->bind_param("si", $status, $complaint_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Status updated']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
        }
        $stmt->close();
    }

    // Add admin remark
    if ($action == 'addRemark') {
        $complaint_id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
        $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';

        if (!$complaint_id || empty($remark)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        $remark = mysqli_real_escape_string($creamdb, $remark);

        $sql = "UPDATE complaints SET admin_remark = ?, admin_id = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $creamdb->prepare($sql);
        $stmt->bind_param("sii", $remark, $gUserId, $complaint_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Remarks saved']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save remarks']);
        }
        $stmt->close();
    }
}

// GET requests for fetching data
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Get all complaints
    if ($action == 'getAllComplaints') {
        $sql = "SELECT c.*, u.full_name, u.email 
                FROM complaints c 
                JOIN user u ON c.user_id = u.id 
                ORDER BY c.created_at DESC";

        $result = $creamdb->query($sql);
        $complaints = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $complaints]);
    }

    // Get complaint statistics
    if ($action == 'getStats') {
        $stats = [];

        // Total complaints
        $result = $creamdb->query("SELECT COUNT(*) as count FROM complaints");
        $stats['total'] = $result->fetch_assoc()['count'];

        // Open complaints
        $result = $creamdb->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'open'");
        $stats['open'] = $result->fetch_assoc()['count'];

        // In progress
        $result = $creamdb->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'in-progress'");
        $stats['in_progress'] = $result->fetch_assoc()['count'];

        // Resolved
        $result = $creamdb->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'resolved'");
        $stats['resolved'] = $result->fetch_assoc()['count'];

        echo json_encode(['status' => 'success', 'data' => $stats]);
    }
}
?>
