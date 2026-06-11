<?php
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ensure only admins can access this page
if ($_SESSION['user_type'] !== 'Admin' && $_SESSION['role'] != 1) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

$pre_associate_id = isset($_GET['associate_id']) ? intval($_GET['associate_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $associate_id    = intval($_POST['associate_id']);
    $doctor_id       = intval($_POST['doctor_id']);
    $patient_id      = !empty($_POST['patient_id']) ? intval($_POST['patient_id']) : 'NULL';
    $assignment_type = mysqli_real_escape_string($connection, trim($_POST['assignment_type']));
    $priority_level  = mysqli_real_escape_string($connection, trim($_POST['priority_level']));
    $assignment_date = mysqli_real_escape_string($connection, trim($_POST['assignment_date']));
    $notes           = mysqli_real_escape_string($connection, trim($_POST['notes']));

    if (empty($associate_id) || empty($doctor_id) || empty($assignment_type) || empty($assignment_date)) {
        $error = "Please fill in all required fields.";
    } else {
        $insert_query = "INSERT INTO associate_assignments 
            (associate_id, doctor_id, patient_id, assignment_type, priority_level, assignment_date, assignment_status, notes)
            VALUES 
            ($associate_id, $doctor_id, $patient_id, '$assignment_type', '$priority_level', '$assignment_date', 'Active', '$notes')";

        if (mysqli_query($connection, $insert_query)) {
            $message = "Assignment created successfully.";
            // Reset pre_associate_id so form clears
            $pre_associate_id = $associate_id; 
        } else {
            $error = "Database error: " . mysqli_error($connection);
        }
    }
}

// Fetch lists for dropdowns
$associates_result = mysqli_query($connection, "SELECT associate_id, first_name, last_name, associate_code FROM associates WHERE status='Active' ORDER BY first_name");
$doctors_result = mysqli_query($connection, "SELECT id, first_name, last_name FROM tbl_employee WHERE role=2 AND status=1 ORDER BY first_name");
$patients_result = mysqli_query($connection, "SELECT id, first_name, last_name FROM tbl_patient ORDER BY first_name");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Associate Assignment - HMS</title>
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
                        <h4 class="page-title">Assign Associate to Doctor</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('associates.php'); ?>" class="btn btn-primary btn-rounded float-right">
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
                                <h4 class="card-title">Assignment Details</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Associate <span class="text-danger">*</span></label>
                                                <select class="form-control" name="associate_id" required>
                                                    <option value="">Select Associate</option>
                                                    <?php while ($assoc = mysqli_fetch_assoc($associates_result)): ?>
                                                    <option value="<?php echo $assoc['associate_id']; ?>" <?php echo ($pre_associate_id == $assoc['associate_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($assoc['first_name'] . ' ' . $assoc['last_name'] . ' (' . $assoc['associate_code'] . ')'); ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Doctor <span class="text-danger">*</span></label>
                                                <select class="form-control" name="doctor_id" required>
                                                    <option value="">Select Doctor</option>
                                                    <?php while ($doc = mysqli_fetch_assoc($doctors_result)): ?>
                                                    <option value="<?php echo $doc['id']; ?>">
                                                        Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Patient (Optional)</label>
                                                <select class="form-control" name="patient_id">
                                                    <option value="">No Specific Patient</option>
                                                    <?php while ($pat = mysqli_fetch_assoc($patients_result)): ?>
                                                    <option value="<?php echo $pat['id']; ?>">
                                                        <?php echo htmlspecialchars($pat['first_name'] . ' ' . $pat['last_name']); ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <small class="text-muted">Select if this assignment is tied to a specific patient case.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Assignment Type <span class="text-danger">*</span></label>
                                                <select class="form-control" name="assignment_type" required>
                                                    <option value="General Assistance">General Assistance</option>
                                                    <option value="Ward Duty">Ward Duty</option>
                                                    <option value="Operation Theater">Operation Theater</option>
                                                    <option value="Patient Care">Patient Care</option>
                                                    <option value="Administrative">Administrative</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Priority Level</label>
                                                <select class="form-control" name="priority_level">
                                                    <option value="Low">Low</option>
                                                    <option value="Medium" selected>Medium</option>
                                                    <option value="High">High</option>
                                                    <option value="Critical">Critical</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Assignment Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="assignment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Additional Notes</label>
                                                <textarea class="form-control" name="notes" rows="3" placeholder="Enter any specific instructions or notes for the associate..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="m-t-20 text-center">
                                        <button class="btn btn-primary submit-btn" type="submit">
                                            <i class="fa fa-link"></i> Assign Doctor
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
