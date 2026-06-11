<?php
require_once __DIR__ . '/includes/init.php';
session_start();
if(isset($_REQUEST['signup'])) {
    $first_name = $_REQUEST['first_name'];
    $last_name = $_REQUEST['last_name'];
    $username = $_REQUEST['username'];
    $emailid = $_REQUEST['emailid'];
    $pwd = $_REQUEST['pwd'];
    $dob = $_REQUEST['dob'];
    $employee_id = $_REQUEST['employee_id'];
    $joining_date = $_REQUEST['joining_date'];
    $gender = $_REQUEST['gender'];
    $phone = $_REQUEST['phone'];
    $address = $_REQUEST['address'];
    $bio = $_REQUEST['bio'];
    $status = 1;

    $insert_query = mysqli_query($connection, "INSERT INTO tbl_employee SET first_name='$first_name', last_name='$last_name', username='$username', emailid='$emailid', password='$pwd', dob='$dob', employee_id='$employee_id', joining_date='$joining_date', gender='$gender', address='$address', phone='$phone', bio='$bio', role=2, status='$status'");
    if($insert_query>0) {
        $_SESSION['name'] = $first_name.' '.$last_name;
        $_SESSION['role'] = 2; // doctor
        $_SESSION['user_id'] = $employee_id;
        header('Location: ' . hms_url('modules/shared/dashboard.php'));
        exit();
    } else {
        $msg = 'Error creating doctor.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Signup</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="main-wrapper account-wrapper">
        <div class="account-page">
            <div class="account-center">
                <div class="account-box">
                    <form method="post" class="form-signin">
                        <h4 class="text-center">Doctor Signup</h4>
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
                                    <label>Username</label>
                                    <input class="form-control" type="text" name="username" required>
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
                                    <label>Employee ID</label>
                                    <input class="form-control" type="text" name="employee_id" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Joining Date</label>
                                    <input type="text" class="form-control datetimepicker" name="joining_date" required>
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
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" class="form-control" name="address" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Short Biography</label>
                            <textarea class="form-control" rows="3" name="bio" required></textarea>
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
    <script src="assets/js/jquery-3.2.1.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>

