<?php
/**
 * Email Configuration
 * 
 * Configure your email settings here.
 * You can use Gmail, SendGrid, or any SMTP service.
 */

// Email Configuration - Update these with your email service
define('MAIL_FROM_EMAIL', 'noreply@mucahub.local');
define('MAIL_FROM_NAME', 'MUCAHUB System');

// SMTP Configuration (using Gmail as example)
// To use Gmail, you need to:
// 1. Enable "Less secure app access" OR use an "App Password"
// 2. Uncomment the SMTP settings below and update with your credentials

// OPTION 1: Using Gmail (uncomment and update credentials)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'mucahub2026@gmail.com');
define('SMTP_PASS', 'ggqysvzibhldcciq');
define('SMTP_SECURE', 'ssl'); // 'tls' or 'ssl'
define('USE_SMTP', true);

// OPTION 2: Using SendGrid (uncomment and update API key)
/*
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USER', 'apikey');
define('SMTP_PASS', 'your-sendgrid-api-key');
define('SMTP_SECURE', 'tls');
define('USE_SMTP', true);
*/

// OPTION 3: Using PHP mail() function (default - may not work on Windows without setup)
// define('USE_SMTP', false); // Set to true if SMTP configured above

/**
 * Send Email Function
 */
function sendEmail($to, $subject, $message, $isHtml = true) {
    if (defined('USE_SMTP') && USE_SMTP) {
        return sendEmailViaSMTP($to, $subject, $message, $isHtml);
    } else {
        return sendEmailViaPhpMail($to, $subject, $message, $isHtml);
    }
}

/**
 * Send Email via SMTP using stream context
 */
function sendEmailViaSMTP($to, $subject, $message, $isHtml = true) {
    try {
        // Prepare stream context for SSL/TLS
        $contextOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            )
        );
        
        // Determine transport type
        if (SMTP_SECURE === 'ssl') {
            $transport = 'ssl';
        } elseif (SMTP_SECURE === 'tls') {
            $transport = 'tcp';
        } else {
            $transport = 'tcp';
        }
        
        $context = stream_context_create($contextOptions);
        $socket = @stream_socket_client(
            $transport . "://" . SMTP_HOST . ":" . SMTP_PORT,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        stream_set_timeout($socket, 10);
        
        // Helper function to read SMTP responses
        $readResponse = function() use ($socket) {
            $response = '';
            while ($line = fgets($socket, 512)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $response;
        };
        
        // Helper function to send commands
        $sendCommand = function($command) use ($socket, $readResponse) {
            fputs($socket, $command . "\r\n");
            $response = $readResponse();
            return $response;
        };
        
        // Read welcome message
        $response = $readResponse();
        if (strpos($response, '220') === false) {
            error_log("SMTP Welcome failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Send EHLO
        $response = $sendCommand("EHLO localhost");
        if (strpos($response, '250') === false) {
            error_log("SMTP EHLO failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Only do STARTTLS if using TLS (not SSL)
        if (SMTP_SECURE === 'tls' && SMTP_PORT == 587) {
            $response = $sendCommand("STARTTLS");
            if (strpos($response, '220') === false) {
                error_log("SMTP STARTTLS failed: " . $response);
                fclose($socket);
                return false;
            }
            
            // Enable crypto on the socket
            $cryptoResult = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Send EHLO again after TLS
            $response = $sendCommand("EHLO localhost");
            if (strpos($response, '250') === false) {
                error_log("SMTP EHLO after TLS failed: " . $response);
                fclose($socket);
                return false;
            }
        }
        
        // Authenticate
        $response = $sendCommand("AUTH LOGIN");
        if (strpos($response, '334') === false) {
            error_log("SMTP AUTH LOGIN failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Send username
        $response = $sendCommand(base64_encode(SMTP_USER));
        if (strpos($response, '334') === false) {
            error_log("SMTP username failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Send password
        $response = $sendCommand(base64_encode(SMTP_PASS));
        if (strpos($response, '235') === false) {
            error_log("SMTP password failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Send from
        $response = $sendCommand("MAIL FROM:<" . MAIL_FROM_EMAIL . ">");
        if (strpos($response, '250') === false) {
            error_log("SMTP MAIL FROM failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Send to
        $response = $sendCommand("RCPT TO:<" . $to . ">");
        if (strpos($response, '250') === false) {
            error_log("SMTP RCPT TO failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Send data
        $response = $sendCommand("DATA");
        if (strpos($response, '354') === false) {
            error_log("SMTP DATA failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Build email headers
        $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
        $headers .= "To: " . $to . "\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "X-Mailer: MUCAHUB\r\n";
        
        // Send headers and body
        fputs($socket, $headers . "\r\n" . $message . "\r\n");
        
        // End transmission
        $response = $sendCommand(".");
        if (strpos($response, '250') === false) {
            error_log("SMTP message send failed: " . $response);
            fclose($socket);
            return false;
        }
        
        // Close connection
        $sendCommand("QUIT");
        fclose($socket);
        
        return true;
    } catch (Exception $e) {
        error_log("SMTP Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Email via PHP mail() function
 */
function sendEmailViaPhpMail($to, $subject, $message, $isHtml = true) {
    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";
    $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
    $headers .= "X-Mailer: MUCAHUB\r\n";
    
    return @mail($to, $subject, $message, $headers);
}

?>
