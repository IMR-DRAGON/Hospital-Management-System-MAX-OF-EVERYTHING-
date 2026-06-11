<?php
require_once __DIR__ . '/includes/init.php';
if(isset($_REQUEST['signup'])) {
    $name = $_REQUEST['name'];
    $email = $_REQUEST['email'];
    $phone = $_REQUEST['phone'];
    $password = $_REQUEST['password'];
    $blood_type = $_REQUEST['blood_type'];
    $age = (int)$_REQUEST['age'];
    $gender = $_REQUEST['gender'];
    $date_of_birth = $_REQUEST['date_of_birth'];
    $address = $_REQUEST['address'];
    $emergency_contact = $_REQUEST['emergency_contact'];
    $emergency_phone = $_REQUEST['emergency_phone'];

    // Generate donor code
    $donor_code = 'DON-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Check if email already exists
    $check_email = "SELECT donor_id FROM donors WHERE email = '$email'";
    $email_result = mysqli_query($connection, $check_email);
    
    if (mysqli_num_rows($email_result) > 0) {
        $msg = 'Email already exists. Please use a different email.';
    } else {
        // Use prepared statement for security
        $stmt = mysqli_prepare($connection, "INSERT INTO donors (name, email, phone, password, blood_type, age, gender, date_of_birth, address, emergency_contact, emergency_phone, donor_code, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssissssss", $name, $email, $phone, $password, $blood_type, $age, $gender, $date_of_birth, $address, $emergency_contact, $emergency_phone, $donor_code);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['name'] = $name;
                $_SESSION['role'] = 4; // donor role code
                $_SESSION['user_id'] = mysqli_insert_id($connection);
                $_SESSION['donor_code'] = $donor_code;
                header('Location: ' . hms_url('modules/donor/donor-dashboard.php'));
                exit();
            } else {
                $msg = 'Error creating donor account: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $msg = 'Error preparing statement: ' . mysqli_error($connection);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donor Registration - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
</head>
<body>
    <div class="main-wrapper account-wrapper">
        <div class="account-page">
            <div class="account-center">
                <div class="account-box">
                    <form method="post" class="form-signin">
                        <h4 class="text-center">Blood Donor Registration</h4>
                        <p class="text-center text-muted">Join our blood donor network and help save lives!</p>
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Full Name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="name" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Email <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Phone Number <span class="text-danger">*</span></label>
                                    <input class="form-control" type="tel" name="phone" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Password <span class="text-danger">*</span></label>
                                    <input class="form-control" type="password" name="password" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Blood Type <span class="text-danger">*</span></label>
                                    <select class="form-control" name="blood_type" required>
                                        <option value="">Select Blood Type</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Age <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" name="age" required min="18" max="65">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Gender <span class="text-danger">*</span></label>
                                    <select class="form-control" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input class="form-control" type="date" name="date_of_birth">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea class="form-control" name="address" rows="3" placeholder="Enter your full address"></textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Emergency Contact Person</label>
                                    <input class="form-control" type="text" name="emergency_contact" placeholder="Full name">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Emergency Contact Phone</label>
                                    <input class="form-control" type="tel" name="emergency_phone" placeholder="Phone number">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fa fa-info-circle"></i> Donor Requirements:</h6>
                            <ul class="mb-0">
                                <li>Must be between 18-65 years old</li>
                                <li>Must weigh at least 50 kg (110 lbs)</li>
                                <li>Should be in good health</li>
                                <li>No recent tattoos or piercings (within 6 months)</li>
                            </ul>
                        </div>

                        <span style="color:red;"><?php if(!empty($msg)){ echo $msg; } ?></span>
                        <div class="form-group text-center m-t-20">
                            <button type="submit" name="signup" class="btn btn-danger account-btn">
                                <i class="fa fa-heart"></i> Register as Blood Donor
                            </button>
                        </div>
                        <div class="text-center">
                            <a href="login.php">Already registered? Login here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
</body>
</html>
