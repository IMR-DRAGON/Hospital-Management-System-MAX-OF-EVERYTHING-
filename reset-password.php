<?php
require_once __DIR__ . '/includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <title>Reset Password - HMS</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<?php
$msg = '';
$msg_type = '';
$token_valid = false;
$user_id = '';

// Check if token is provided and valid
if(isset($_GET['token']) && !empty($_GET['token'])) {
    $token = mysqli_real_escape_string($connection, $_GET['token']);
    
    // Check if token exists and is not expired
    $token_query = mysqli_query($connection, "SELECT * FROM password_reset_tokens WHERE token = '$token' AND expiry > NOW()");
    
    if(mysqli_num_rows($token_query) > 0) {
        $token_data = mysqli_fetch_array($token_query);
        $user_id = $token_data['user_id'];
        $token_valid = true;
    } else {
        $msg = "Invalid or expired reset token. Please request a new password reset.";
        $msg_type = 'error';
    }
} else {
    $msg = "No reset token provided.";
    $msg_type = 'error';
}

// Handle password reset
if(isset($_REQUEST['reset_password']) && $token_valid) {
    $new_password = mysqli_real_escape_string($connection, $_REQUEST['new_password']);
    $confirm_password = mysqli_real_escape_string($connection, $_REQUEST['confirm_password']);
    
    if($new_password === $confirm_password) {
        if(strlen($new_password) >= 6) {
            // Update password
            $update_query = mysqli_query($connection, "UPDATE tbl_employee SET password = '$new_password' WHERE id = '$user_id'");
            
            if($update_query) {
                // Delete the used token
                mysqli_query($connection, "DELETE FROM password_reset_tokens WHERE user_id = '$user_id'");
                
                $msg = "Password updated successfully! You can now login with your new password.";
                $msg_type = 'success';
                $token_valid = false; // Hide the form after successful reset
            } else {
                $msg = "Error updating password. Please try again.";
                $msg_type = 'error';
            }
        } else {
            $msg = "Password must be at least 6 characters long.";
            $msg_type = 'error';
        }
    } else {
        $msg = "Passwords do not match. Please try again.";
        $msg_type = 'error';
    }
}
?>
<body>
    <div class="main-wrapper account-wrapper">
        <div class="account-page">
            <div class="account-center">
                <div class="account-box">
                    <?php if(!empty($msg)): ?>
                        <div class="alert alert-<?php echo ($msg_type == 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($token_valid): ?>
                        <form method="post" class="form-signin">
                            <div class="account-logo">
                                <a href="index.php"><img src="assets/img/logo-dark.png" alt=""></a>
                            </div>
                            <h4 class="text-center mb-4">Reset Password</h4>
                            <p class="text-center text-muted mb-4">Enter your new password below.</p>
                            
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" class="form-control" name="new_password" id="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required minlength="6">
                            </div>
                            
                            <div class="form-group text-center">
                                <button type="submit" name="reset_password" class="btn btn-primary account-btn">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <a href="index.php" class="btn btn-secondary">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/jquery-3.2.1.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/app.js"></script>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = this.value;
            
            if(newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
