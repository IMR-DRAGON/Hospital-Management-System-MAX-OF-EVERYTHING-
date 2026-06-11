<?php
require_once __DIR__ . '/includes/init.php';
session_start();
if (isset($_REQUEST['login'])) {
    $username = $_REQUEST['username'];
    $pwd = $_REQUEST['pwd'];
    $roleType = $_REQUEST['role_type'];

    $userRow = null;
    $stmt = null;

    if ($roleType === 'Employee' || $roleType === 'Doctor' || $roleType === 'Admin') {
        $stmt = mysqli_prepare($connection, "SELECT * FROM tbl_employee WHERE username=? AND password=?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $pwd);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $userRow = mysqli_fetch_array($result);
            $_SESSION['role'] = $userRow['role'];
            $_SESSION['name'] = $userRow['first_name'] . ' ' . $userRow['last_name'];
            $_SESSION['user_id'] = $userRow['id'];
            $_SESSION['user_type'] = $userRow['role'] == 2 ? 'Doctor' : 'Employee';
            header('Location: dashboard.php');
            exit();
        }
    } elseif ($roleType === 'Associate') {
        // Associates log in via the associates table using username OR email + password
        $stmt = mysqli_prepare(
            $connection,
            "SELECT * FROM associates WHERE (username=? OR email=?) AND password=? AND status='Active'"
        );
        mysqli_stmt_bind_param($stmt, "sss", $username, $username, $pwd);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $userRow = mysqli_fetch_array($result);
            $_SESSION['role'] = 5; // 5 = Associate
            $_SESSION['name'] = $userRow['first_name'] . ' ' . $userRow['last_name'];
            $_SESSION['user_id'] = $userRow['associate_id'];
            $_SESSION['user_type'] = 'Associate';
            header('Location: associate-dashboard.php');
            exit();
        }
    } elseif ($roleType === 'Patient') {
        $stmt = mysqli_prepare($connection, "SELECT * FROM tbl_patient WHERE email=? AND password=?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $pwd);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $userRow = mysqli_fetch_array($result);
            $_SESSION['role'] = 3;
            $_SESSION['name'] = $userRow['first_name'] . ' ' . $userRow['last_name'];
            $_SESSION['user_id'] = $userRow['id'];
            $_SESSION['user_type'] = 'Patient';
            header('Location: patient-dashboard.php');
            exit();
        }
    } elseif ($roleType === 'Donor') {
        $stmt = mysqli_prepare($connection, "SELECT * FROM donors WHERE email=? AND password=? AND status='Active'");
        mysqli_stmt_bind_param($stmt, "ss", $username, $pwd);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $userRow = mysqli_fetch_array($result);
            $_SESSION['role'] = 4;
            $_SESSION['name'] = $userRow['name'];
            $_SESSION['user_id'] = $userRow['donor_id'];
            $_SESSION['user_type'] = 'Donor';
            header('Location: donor-dashboard.php');
            exit();
        }
    }
    $msg = "Incorrect login details.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <title>HMS - Login</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>

<body>
    <div class="main-wrapper account-wrapper">
        <div class="account-page">
            <div class="account-center">
                <div class="account-box">
                    <form method="post" class="form-signin">
                        <div class="account-logo">
                            <a href="#"><img src="assets/img/logo-dark.png" alt=""></a>
                        </div>
                        <div class="form-group">
                            <label>Login As</label>
                            <select class="form-control" name="role_type" required>
                                <option value="Admin">Admin</option>
                                <option value="Associate">Associate</option>
                                <option value="Doctor">Doctor</option>

                                <option value="Patient">Patient</option>

                            </select>
                        </div>
                        <div class="form-group">
                            <label>Username / Email</label>
                            <input type="text" autofocus class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="pwd" required>
                        </div>
                        <span style="color:red;"><?php if (!empty($msg)) {
                            echo $msg;
                        } ?></span>
                        <br>
                        <div class="form-group text-center">
                            <button type="submit" name="login" class="btn btn-primary account-btn">Login</button>
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