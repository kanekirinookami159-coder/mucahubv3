<?php
include "../config/config.php";
include "../config/mail_config.php";
include "../includes/databases/db_connection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($role === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and role are required']);
    exit;
}

// Determine table based on role
$table = '';
if ($role === 'student') {
    $table = 'students';
} elseif ($role === 'instructor') {
    $table = 'instructors';
} elseif ($role === 'admin') {
    $table = 'admins';
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id, first_name FROM $table WHERE email = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Email not found']);
    exit;
}

// Generate random password (12 characters)
$new_password = bin2hex(random_bytes(6));
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Update password in database
$update_stmt = $conn->prepare("UPDATE $table SET password = ?, force_password_change = 1 WHERE email = ?");
if (!$update_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$update_stmt->bind_param('ss', $hashed_password, $email);
if (!$update_stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    exit;
}
$update_stmt->close();

// Send email with new password
$subject = "MUCAHUB Password Reset Request";
$role_display = ucfirst($role);

// HTML Email Template
$htmlMessage = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #556b2f, #6b8e23); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .credentials { background: white; padding: 20px; border-left: 4px solid #556b2f; margin: 20px 0; border-radius: 4px; }
        .credentials p { margin: 10px 0; }
        .label { font-weight: bold; color: #556b2f; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .footer { text-align: center; padding: 20px; color: #999; font-size: 12px; }
        button { background: #556b2f; color: white; padding: 12px 30px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>MUCAHUB</h1>
            <p>Password Reset Request</p>
        </div>
        
        <div class='content'>
            <p>Hello " . htmlspecialchars($user['first_name']) . ",</p>
            
            <p>We received a request to reset your password for your MUCAHUB account. Your password has been successfully reset.</p>
            
            <div class='credentials'>
                <p><span class='label'>Role:</span> " . htmlspecialchars($role_display) . "</p>
                <p><span class='label'>Email:</span> " . htmlspecialchars($email) . "</p>
                <p><span class='label'>Temporary Password:</span> <code style='background: #f0f0f0; padding: 5px 10px; border-radius: 4px; font-family: monospace;'>" . htmlspecialchars($new_password) . "</code></p>
            </div>
            
            <div class='warning'>
                <strong>⚠️ Important Security Notice:</strong>
                <p>Please log in with your temporary password and change it immediately to a secure password that only you know. Never share your password with anyone.</p>
            </div>
            
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Go to <a href='http://localhost/Mucahub/auth/login.php'>MUCAHUB Login</a></li>
                <li>Enter your email: " . htmlspecialchars($email) . "</li>
                <li>Enter the temporary password above</li>
                <li>After logging in, navigate to your profile settings and change your password</li>
            </ol>
            
            <p style='color: #999; font-size: 14px;'>If you did not request this password reset, please contact the administrator immediately at <a href='mailto:mucahub2026@gmail.com'>mucahub2026@gmail.com</a></p>
        </div>
        
        <div class='footer'>
            <p>MUCAHUB Learning Management System</p>
            <p>&copy; 2026. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";

// Plain text fallback
$plainMessage = "Hello " . $user['first_name'] . ",\n\n";
$plainMessage .= "We received a request to reset your password for your MUCAHUB account. Your password has been successfully reset.\n\n";
$plainMessage .= "Role: " . $role_display . "\n";
$plainMessage .= "Email: " . $email . "\n";
$plainMessage .= "Temporary Password: " . $new_password . "\n\n";
$plainMessage .= "IMPORTANT: Please log in and change your password immediately to a secure password that only you know.\n";
$plainMessage .= "Never share your password with anyone.\n\n";
$plainMessage .= "If you did not request this password reset, please contact the administrator immediately.\n\n";
$plainMessage .= "Best regards,\nMUCAHUB System";

// Send the email
$email_sent = sendEmail($email, $subject, $htmlMessage, true);

if ($email_sent) {
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Password reset email has been sent successfully! Please check your email inbox and spam folder for instructions.'
    ]);
} else {
    // Email failed - provide fallback with password display
    // In production, email should always work. This is for development environments without mail configured.
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Your password has been reset. Since email is not configured on this server, your temporary password is displayed below:',
        'password' => $new_password,
        'warning' => 'SECURITY NOTE: In a production environment, the password should only be sent via email, not displayed here.'
    ]);
}

$conn->close();
?>
