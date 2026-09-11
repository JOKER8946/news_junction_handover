<?php
session_start();

// Check if OTP is provided
if (isset($_POST['otp']) && !empty($_POST['otp'])) {
    $entered_otp = $_POST['otp'];
    
    // Check if the OTP entered matches the one in session
    if ($entered_otp == $_SESSION['otp']) {
        unset($_SESSION['otp']);
        
        $_SESSION['admin']="verified";
        $_SESSION['admin_set_time'] = time(); 

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid OTP. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'OTP is required.']);
}
?>