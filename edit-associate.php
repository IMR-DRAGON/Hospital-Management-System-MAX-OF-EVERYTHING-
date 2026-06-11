<?php
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$associate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($associate_id === 0) {
    header('Location: associates.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name     = mysqli_real_escape_string($connection, trim($_POST['first_name']));
    $last_name      = mysqli_real_escape_string($connection, trim($_POST['last_name']));
    $email          = mysqli_real_escape_string($connection, trim($_POST['email'] ?? ''));
    $phone          = mysqli_real_escape_string($connection, trim($_POST['phone']));
    $position       = mysqli_real_escape_string($connection, trim($_POST['position'] ?? ''));
    $associate_type = mysqli_real_escape_string($connection, $_POST['associate_type']);
    $department     = mysqli_real_escape_string($connection, trim($_POST['department'] ?? 'Patient Services'));
    $username       = mysqli_real_escape_string($connection, trim($_POST['username'] ?? ''));
    $status         = mysqli_real_escape_string($connection, trim($_POST['status'] ?? 'Active'));
    $salary         = floatval($_POST['salary'] ?? 0.00);
    
    // Optional password update
    $password_update = "";
    if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($connection, trim($_POST['password']));
        $password_update = ", password='$password'";
    }

    if (empty($first_name) || empty($last_name) || empty($phone) || empty($associate_type) || empty($username)) {
        $error = "Please fill in all required fields (marked with *).";
    } else {
        // Check username uniqueness
        $user_result = mysqli_query($connection, "SELECT associate_id FROM associates WHERE username = '$username' AND associate_id != $associate_id");
        if (mysqli_num_rows($user_result) > 0) {
            $error = "Username <strong>$username</strong> is already taken by another associate.";
        } else {
            if (!empty($email)) {
                $email_result = mysqli_query($connection, "SELECT associate_id FROM associates WHERE email = '$email' AND associate_id != $associate_id");
                if (mysqli_num_rows($email_result) > 0) {
                    $error = "Email address is already registered to another associate.";
                }
            }

            if (empty($error)) {
                $update_query = "UPDATE associates SET 
                    first_name='$first_name', 
                    last_name='$last_name', 
                    email='$email', 
                    phone='$phone', 
                    position='$position', 
                    associate_type='$associate_type', 
                    department='$department', 
                    salary='$salary',
                    username='$username',
                    status='$status'
                    $password_update
                    WHERE associate_id = $associate_id";

                if (mysqli_query($connection, $update_query)) {
                    $message = "Associate details updated successfully.";
                } else {
                    $error = "Database error: " . mysqli_error($connection);
                }
            }
        }
    }
}

// Fetch current details
$associate_query = mysqli_query($connection, "SELECT * FROM associates WHERE associate_id = $associate_id");
if (!$associate_query || mysqli_num_rows($associate_query) == 0) {
    header('Location: associates.php');
    exit();
}
$associate = mysqli_fetch_assoc($associate_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Associate - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-4 col-3">
                        <h4 class="page-title">Edit Associate: <?php echo $associate['associate_code']; ?></h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="associates.php" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-arrow-left"></i> Back to Associates
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Associate Information</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <h5>Basic Information</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Associate Code</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($associate['associate_code']); ?>" disabled>
                                                <small class="text-muted">Associate code cannot be changed.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Status <span class="text-danger">*</span></label>
                                                <select class="form-control" name="status" required>
                                                    <option value="Active" <?php echo ($associate['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="Inactive" <?php echo ($associate['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="On Leave" <?php echo ($associate['status'] == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                                                    <option value="Suspended" <?php echo ($associate['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($associate['first_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($associate['last_name']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Email Address</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($associate['email']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Phone Number <span class="text-danger">*</span></label>
                                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($associate['phone']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="mt-4">Employment Details</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($associate['department']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Position</label>
                                                <input type="text" class="form-control" name="position" value="<?php echo htmlspecialchars($associate['position']); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Monthly Salary ($)</label>
                                                <input type="number" class="form-control" name="salary" min="0" step="0.01" value="<?php echo htmlspecialchars($associate['salary']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Associate Type <span class="text-danger">*</span></label>
                                                <select class="form-control" name="associate_type" required>
                                                    <option value="Patient Coordinator" <?php echo ($associate['associate_type'] == 'Patient Coordinator') ? 'selected' : ''; ?>>Patient Coordinator</option>
                                                    <option value="Medical Assistant" <?php echo ($associate['associate_type'] == 'Medical Assistant') ? 'selected' : ''; ?>>Medical Assistant</option>
                                                    <option value="Nurse Coordinator" <?php echo ($associate['associate_type'] == 'Nurse Coordinator') ? 'selected' : ''; ?>>Nurse Coordinator</option>
                                                    <option value="Case Manager" <?php echo ($associate['associate_type'] == 'Case Manager') ? 'selected' : ''; ?>>Case Manager</option>
                                                    <option value="Patient Advocate" <?php echo ($associate['associate_type'] == 'Patient Advocate') ? 'selected' : ''; ?>>Patient Advocate</option>
                                                    <option value="Clinical Coordinator" <?php echo ($associate['associate_type'] == 'Clinical Coordinator') ? 'selected' : ''; ?>>Clinical Coordinator</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="mt-4">Login Credentials</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Username <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($associate['username']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="text" class="form-control" name="password" placeholder="Leave blank to keep current password">
                                                <small class="text-muted">Only fill this to reset the associate's password.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="m-t-20 text-center">
                                        <button class="btn btn-primary submit-btn" type="submit">
                                            <i class="fa fa-save"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
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
