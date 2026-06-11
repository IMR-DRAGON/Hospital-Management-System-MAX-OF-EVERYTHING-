<?php
require_once __DIR__ . '/includes/init.php';
if(isset($_REQUEST['signup'])) {
    $first_name = $_REQUEST['first_name'];
    $last_name = $_REQUEST['last_name'];
    $emailid = $_REQUEST['emailid'];
    $pwd = $_REQUEST['pwd'];
    $dob = $_REQUEST['dob'];
    $gender = $_REQUEST['gender'];
    $patient_type = $_REQUEST['patient_type'];
    $phone = $_REQUEST['phone'];
    $address = $_REQUEST['address'];
    $status = 1;

    // Use prepared statement for security
    $stmt = mysqli_prepare($connection, "INSERT INTO tbl_patient (first_name, last_name, email, password, dob, gender, patient_type, address, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssssssi", $first_name, $last_name, $emailid, $pwd, $dob, $gender, $patient_type, $address, $phone, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['name'] = $first_name.' '.$last_name;
            $_SESSION['role'] = 3;
            $_SESSION['user_id'] = mysqli_insert_id($connection);
            header('Location: ' . hms_url('modules/patient/patient-dashboard.php'));
            exit();
        } else {
            $msg = 'Error creating patient: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = 'Error preparing statement: ' . mysqli_error($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Signup</title>
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
                        <h4 class="text-center">Patient Signup</h4>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input class="form-control" type="text" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input class="form-control" type="text" name="last_name" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input class="form-control" type="email" name="emailid" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Password</label>
                                    <input class="form-control" type="password" name="pwd" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="text" class="form-control datetimepicker" name="dob" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input class="form-control" type="text" name="phone" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select class="form-control" name="gender" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Patient Type</label>
                                    <select class="form-control" name="patient_type" required>
                                        <option value="InPatient">InPatient</option>
                                        <option value="OutPatient">OutPatient</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" class="form-control" name="address" required>
                                </div>
                            </div>
                        </div>
                        <span style="color:red;"><?php if(!empty($msg)){ echo $msg; } ?></span>
                        <div class="form-group text-center m-t-20">
                            <button type="submit" name="signup" class="btn btn-primary account-btn">Create Account</button>
                        </div>
                        <div class="text-center">
                            <a href="login.php">Back to Login</a>
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

