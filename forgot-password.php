<?php
require_once __DIR__ . '/includes/init.php';
include __DIR__ . '/includes/email_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <title>Forgot Password - HMS</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<?php

$msg = '';
$msg_type = '';

if(isset($_REQUEST['reset_request'])) {
    $username = mysqli_real_escape_string($connection, $_REQUEST['username']);
    
    // Check if user exists
    $check_query = mysqli_query($connection, "SELECT * FROM tbl_employee WHERE username = '$username'");
    
    if(mysqli_num_rows($check_query) > 0) {
        $user_data = mysqli_fetch_array($check_query);
        $user_id = $user_data['id'];
        $email = $user_data['email'];
        
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing tokens for this user
        mysqli_query($connection, "DELETE FROM password_reset_tokens WHERE user_id = '$user_id'");
        
        // Insert new token
        $insert_token = mysqli_query($connection, "INSERT INTO password_reset_tokens (user_id, token, expiry) VALUES ('$user_id', '$token', '$expiry')");
        
        if($insert_token) {
            // Generate reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
            
            // Send email if email is available
            if(!empty($email)) {
                $email_sent = sendPasswordResetEmail($email, $reset_link);
                if($email_sent) {
                    $msg = "Password reset link has been sent to your email address.";
                } else {
                    $msg = "Password reset link generated but email could not be sent. Reset Link: " . $reset_link;
                }
            } else {
                $msg = "Password reset link generated successfully. Reset Link: " . $reset_link;
            }
            $msg_type = 'success';
        } else {
            $msg = "Error generating reset link. Please try again.";
            $msg_type = 'error';
        }
    } else {
        $msg = "Username not found. Please check and try again.";
        $msg_type = 'error';
    }
}
?>
<body>
    <div class="main-wrapper account-wrapper">
        <div class="account-page">
            <div class="account-center">
                <div class="account-box">
                    <form method="post" class="form-signin">
                        <div class="account-logo">
                            <a href="index.php"><img src="assets/img/logo-dark.png" alt=""></a>
                        </div>
                        <h4 class="text-center mb-4">Forgot Password</h4>
                        <p class="text-center text-muted mb-4">Enter your username to receive a password reset link.</p>
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" autofocus="" class="form-control" name="username" required>
                        </div>
                        
                        <?php if(!empty($msg)): ?>
                            <div class="alert alert-<?php echo ($msg_type == 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <?php echo $msg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group text-center">
                            <button type="submit" name="reset_request" class="btn btn-primary account-btn">Send Reset Link</button>
                        </div>
                        
                        <div class="text-center">
                            <a href="index.php" class="text-muted">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/jquery-3.2.1.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
