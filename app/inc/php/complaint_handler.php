<?php
require_once 'db_config.php';
require_once 'validate.logged.php';

header('Content-Type: application/json');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submitComplaint') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : $gUserPincode;
    $departmentName = isset($_POST['departmentName']) ? trim($_POST['departmentName']) : '';
    $departmentEmail = isset($_POST['departmentEmail']) ? trim($_POST['departmentEmail']) : '';

    // Validation
    if (empty($title) || empty($description) || empty($departmentName) || empty($departmentEmail)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    if (strlen($description) < 10) {
        echo json_encode(['status' => 'error', 'message' => 'Description must be at least 10 characters long']);
        exit;
    }

    if (!filter_var($departmentEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid department email address']);
        exit;
    }

    // Handle media upload
    $media_url = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['media'];
        $maxSize = 50 * 1024 * 1024; // 50MB for videos
        $validTypes = ['image/png', 'image/jpeg', 'image/gif', 'application/pdf', 'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

        if (!in_array($file['type'], $validTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Use PNG, JPG, GIF, PDF, MP4, WebM, or OGG']);
            exit;
        }

        if ($file['size'] > $maxSize) {
            echo json_encode(['status' => 'error', 'message' => 'File size exceeds 50MB limit']);
            exit;
        }

        // Create upload directory
        $uploadDir = dirname(__DIR__, 2) . '/uploads/complaints/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'complaint_' . time() . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $media_url = 'uploads/complaints/' . $filename;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload file']);
            exit;
        }
    }

    // Generate unique ticket ID
    $ticket_id = 'TK-' . date('Y-m-d-') . strtoupper(substr(md5(microtime()), 0, 8));

    // Sanitize inputs
    $title = mysqli_real_escape_string($creamdb, $title);
    $description = mysqli_real_escape_string($creamdb, $description);
    $departmentName = mysqli_real_escape_string($creamdb, $departmentName);
    $departmentEmail = mysqli_real_escape_string($creamdb, $departmentEmail);

    // Insert into database
    $sql = "INSERT INTO complaints (ticket_id, user_id, pincode, title, description, status, media_url, department_name, department_email) 
            VALUES (?, ?, ?, ?, ?, 'open', ?, ?, ?)";

    $stmt = $creamdb->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $creamdb->error]);
        exit;
    }

    $stmt->bind_param("ssisssss", $ticket_id, $gUserId, $pincode, $title, $description, $media_url, $departmentName, $departmentEmail);

    if ($stmt->execute()) {
        $complaint_id = $stmt->insert_id;

        // Send email to department (in background, don't block response)
        $emailResult = sendComplaintEmailToDepartment($complaint_id, $departmentEmail, $departmentName);

        // Send acknowledgment email to user
        $ackResult = sendComplaintAcknowledgmentEmail($complaint_id, $gUserEmail);

        // Log for debugging
        error_log("Email send result for complaint $complaint_id to $departmentEmail: " . ($emailResult ? 'success' : 'failed'));
        error_log("Acknowledgment email result for complaint $complaint_id to $gUserEmail: " . ($ackResult ? 'success' : 'failed'));

        echo json_encode([
            'status' => 'success',
            'message' => 'Complaint submitted successfully',
            'ticket_id' => $ticket_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit complaint: ' . $stmt->error]);
    }

    $stmt->close();
}

function sendComplaintEmailToDepartment($complaint_id, $recipient_email, $recipient_name)
{
    global $creamdb;

    try {
        // Fetch complaint details
        $complaintSql = "SELECT c.*, u.full_name as complainant_name, u.email as complainant_email,u.phone_no as complainant_phone from complaints c
                        JOIN user u ON c.user_id = u.id 
                        WHERE c.id = ?";
        $stmt = $creamdb->prepare($complaintSql);

        if (!$stmt) {
            error_log("Database prepare error: " . $creamdb->error);
            return false;
        }

        $stmt->bind_param("i", $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaint = $result->fetch_assoc();
        $stmt->close();

        if (!$complaint) {
            return false;
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

                <div class='title'>Formal Complaint Notification</div>

                <div class='ref-section'>
                    <div class='ref-item'>
                        <span class='ref-label'>Reference Number:</span>
                        <span class='ref-value'>{$complaint['ticket_id']}</span>
                    </div>
                    <div class='ref-item'>
                        <span class='ref-label'>Date of Issue:</span>
                        <span class='ref-value'>" . date('d-M-Y', strtotime($complaint['created_at'])) . "</span>
                    </div>
                </div>

                <div class='content-section'>
                    <div class='section-title'>Subject Matter</div>
                    <div class='content-text'><strong>" . htmlspecialchars($complaint['title']) . "</strong></div>
                </div>

                <div class='content-section'>
                    <div class='section-title'>Complaint Details</div>
                    <div class='content-text'>" . nl2br(htmlspecialchars($complaint['description'])) . "</div>
                </div>

                <div class='content-section'>
                    <div class='section-title'>Complainant Information</div>
                    <table class='details-table'>
                        <tr>
                            <td class='label'>Name:</td>
                            <td class='value'>" . htmlspecialchars($complaint['complainant_name']) . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Email:</td>
                            <td class='value'>" . htmlspecialchars($complaint['complainant_email']) . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Phone:</td>
                            <td class='value'>" . htmlspecialchars($complaint['complainant_phone'] ?? 'N/A') . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Pincode:</td>
                            <td class='value'>" . htmlspecialchars($complaint['pincode']) . "</td>
                        </tr>
                    </table>
                </div>";

        // Add attachment note if media exists
        if ($complaint['media_url']) {
            $emailBody .= "
                <div class='attachment-note'>
                    <strong>Supporting Documentation:</strong><br>
                    Supporting media/documentation is attached to this notification.
                </div>";
        }

        $emailBody .= "
                <div class='footer-section'>
                    <div class='footer-line'>Please do not reply to this email. For queries, contact the complainant directly.</div>
                </div>
            </div>
        </body>
        </html>";

        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'YOUR_GMAIL_ADDRESS';
        $mail->Password = 'YOUR_GMAIL_APP_PASSWORD_2';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('YOUR_GMAIL_ADDRESS', 'News Junction Complaint System');
        $mail->addAddress($recipient_email, $recipient_name);
        $mail->addReplyTo($complaint['complainant_email'], $complaint['complainant_name']);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        // Attach media if exists
        if ($complaint['media_url']) {
            $mediaPath = dirname(__DIR__, 2) . '/' . $complaint['media_url'];
            if (file_exists($mediaPath)) {
                $mail->addAttachment($mediaPath);
            }
        }

        $mail->send();

        // Log the email send in complaint_email_logs table
        $sentByUserId = null; // System email, no user_id
        $logSql = "INSERT INTO complaint_email_logs (complaint_id, recipient_email, recipient_name, subject, sent_by, status, sent_at) 
                   VALUES (?, ?, ?, ?, ?, 'sent', NOW())";
        $logStmt = $creamdb->prepare($logSql);
        if ($logStmt) {
            $logStmt->bind_param("isssi", $complaint_id, $recipient_email, $recipient_name, $subject, $sentByUserId);
            $logStmt->execute();
            $logStmt->close();
        } else {
            error_log("Failed to prepare email log statement: " . $creamdb->error);
        }

        return true;
    } catch (Exception $e) {
        // Log error
        $errorMsg = "Failed to send email: " . $e->getMessage();
        error_log("Email send error for complaint $complaint_id: " . $errorMsg);

        $sentByUserId = null; // System email, no user_id
        $logSql = "INSERT INTO complaint_email_logs (complaint_id, recipient_email, recipient_name, subject, sent_by, status, error_message, sent_at) 
                   VALUES (?, ?, ?, ?, ?, 'failed', ?, NOW())";
        $logStmt = $creamdb->prepare($logSql);
        if ($logStmt) {
            $logStmt->bind_param("issssis", $complaint_id, $recipient_email, $recipient_name, $subject, $sentByUserId, $errorMsg);
            $logStmt->execute();
            $logStmt->close();
        } else {
            error_log("Failed to log email error: " . $creamdb->error);
        }
        return false;
    }
}
function sendComplaintAcknowledgmentEmail($complaint_id, $recipient_email)
{
    global $creamdb;

    try {
        // Fetch complaint details
        $complaintSql = "SELECT c.*, u.full_name as complainant_name from complaints c
                        JOIN user u ON c.user_id = u.id 
                        WHERE c.id = ?";
        $stmt = $creamdb->prepare($complaintSql);

        if (!$stmt) {
            error_log("Database prepare error: " . $creamdb->error);
            return false;
        }

        $stmt->bind_param("i", $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaint = $result->fetch_assoc();
        $stmt->close();

        if (!$complaint) {
            return false;
        }

        // Prepare acknowledgment email content
        $subject = "Complaint Acknowledgment - Ref: " . $complaint['ticket_id'];

        $emailBody = "
        <html>
        <head>
            <style>
                * { margin: 0; padding: 0; }
                body { font-family: 'Calibri', 'Arial', sans-serif; line-height: 1.5; color: #1a1a1a; background: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; }
                .header-section { border-bottom: 3px solid #003d7a; padding-bottom: 20px; margin-bottom: 30px; }
                .title { text-align: center; font-size: 18px; font-weight: bold; color: #003d7a; margin: 30px 0; text-transform: uppercase; letter-spacing: 0.5px; }
                .ack-badge { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; text-align: center; margin: 20px 0; font-weight: bold; border-left: 4px solid #28a745; }
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
                .footer-section { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 11px; color: #666; text-align: center; }
                .footer-line { margin: 8px 0; }
                .info-box { background: #e8f4f8; padding: 15px; border-left: 4px solid #0066cc; margin: 20px 0; font-size: 13px; color: #0066cc; }
            </style>
        </head>
        <body>
            <div class='container'>

                <div class='title'>Complaint Acknowledgment</div>

                <div class='ack-badge'>Your complaint has been successfully sent</div>

                <div class='ref-section'>
                    <div class='ref-item'>
                        <span class='ref-label'>Reference Number:</span>
                        <span class='ref-value'><strong>{$complaint['ticket_id']}</strong></span>
                    </div>
                    <div class='ref-item'>
                        <span class='ref-label'>Submission Date:</span>
                        <span class='ref-value'>" . date('d-M-Y H:i:s', strtotime($complaint['created_at'])) . "</span>
                    </div>
                </div>

                

                <div class='content-section'>
                    <div class='section-title'>Complaint Summary</div>
                    <table class='details-table'>
                        <tr>
                            <td class='label'>Subject:</td>
                            <td class='value'>" . htmlspecialchars($complaint['title']) . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Description:</td>
                            <td class='value'>" . htmlspecialchars($complaint['description']) . "</td>
                        </tr>
                        <tr>
                            <td class='label'>Department:</td>
                            <td class='value'>" . htmlspecialchars($complaint['department_name']) . "</td>
                        </tr>
                    </table>
                </div>

                <div class='footer-section'>
                    <div class='footer-line'><strong>News Junction</strong></div>
                    <div class='footer-line'>This is an automated acknowledgment email. Please do not reply to this email.</div>
                </div>
            </div>
        </body>
        </html>";
        // <div class='content-section'>
        //     <div class='section-title'>Thank You</div>
        //     <div class='content-text'>
        //         Dear <strong>" . htmlspecialchars($complaint['complainant_name']) . "</strong>,<br><br>
        //         We sincerely thank you for submitting your complaint to us. Your feedback is important and helps us improve our services. 
        //         We have successfully received your complaint and it has been forwarded to the relevant department for review and action.
        //     </div>
        // </div>
        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'YOUR_GMAIL_ADDRESS';
        $mail->Password = 'YOUR_GMAIL_APP_PASSWORD_2';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('YOUR_GMAIL_ADDRESS', 'News Junction Complaint System');
        $mail->addAddress($recipient_email, $complaint['complainant_name']);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        // Attach media if exists
        if ($complaint['media_url']) {
            $mediaPath = dirname(__DIR__, 2) . '/' . $complaint['media_url'];
            if (file_exists($mediaPath)) {
                $mail->addAttachment($mediaPath);
            }
        }

        $mail->send();

        // Log the acknowledgment email
        $logSql = "INSERT INTO complaint_email_logs (complaint_id, recipient_email, recipient_name, subject, sent_by, status, sent_at) 
                   VALUES (?, ?, ?, ?, ?, 'sent', NOW())";
        $logStmt = $creamdb->prepare($logSql);
        if ($logStmt) {
            $sentByUserId = null;
            $logStmt->bind_param("isssi", $complaint_id, $recipient_email, $complaint['complainant_name'], $subject, $sentByUserId);
            $logStmt->execute();
            $logStmt->close();
        } else {
            error_log("Failed to prepare acknowledgment email log statement: " . $creamdb->error);
        }

        return true;
    } catch (Exception $e) {
        // Log error
        $errorMsg = "Failed to send acknowledgment email: " . $e->getMessage();
        error_log("Acknowledgment email send error for complaint $complaint_id: " . $errorMsg);

        $logSql = "INSERT INTO complaint_email_logs (complaint_id, recipient_email, recipient_name, subject, sent_by, status, error_message, sent_at) 
                   VALUES (?, ?, ?, ?, ?, 'failed', ?, NOW())";
        $logStmt = $creamdb->prepare($logSql);
        if ($logStmt) {
            $complaint = [];
            $sentByUserId = null;
            $logStmt->bind_param("issssis", $complaint_id, $recipient_email, $complaint['complainant_name'] ?? 'User', $subject ?? 'Acknowledgment Email', $sentByUserId, $errorMsg);
            $logStmt->execute();
            $logStmt->close();
        } else {
            error_log("Failed to log acknowledgment email error: " . $creamdb->error);
        }
        return false;
    }
}
