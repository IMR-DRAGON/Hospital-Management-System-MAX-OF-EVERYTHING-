<?php
// Email configuration for password reset
// In a production environment, these should be stored in environment variables

// SMTP Configuration (example for Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Replace with your email
define('SMTP_PASSWORD', 'your-app-password'); // Replace with your app password
define('SMTP_ENCRYPTION', 'tls');

// Alternative: Use PHP's built-in mail() function
define('USE_SMTP', false); // Set to true to use SMTP, false to use mail()

// From email address
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'HMS System');

// Email templates
define('RESET_SUBJECT', 'Password Reset Request - HMS System');
define('RESET_BODY_TEMPLATE', '
Dear User,

You have requested a password reset for your HMS account.

To reset your password, please click on the following link:
{reset_link}

This link will expire in 1 hour for security reasons.

If you did not request this password reset, please ignore this email.

Best regards,
HMS System Team
');

// Function to send email
function sendPasswordResetEmail($to_email, $reset_link) {
    if (USE_SMTP) {
        return sendEmailViaSMTP($to_email, $reset_link);
    } else {
        return sendEmailViaMail($to_email, $reset_link);
    }
}

// Send email using PHP's built-in mail() function
function sendEmailViaMail($to_email, $reset_link) {
    $subject = RESET_SUBJECT;
    $body = str_replace('{reset_link}', $reset_link, RESET_BODY_TEMPLATE);
    
    $headers = array(
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    );
    
    return mail($to_email, $subject, $body, implode("\r\n", $headers));
}

// Send email using SMTP (requires PHPMailer or similar library)
function sendEmailViaSMTP($to_email, $reset_link) {
    // This is a placeholder for SMTP implementation
    // You would need to install and configure PHPMailer or similar library
    
    // For now, we'll use the built-in mail function as fallback
    return sendEmailViaMail($to_email, $reset_link);
}
?>
