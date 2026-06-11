<?php
require_once __DIR__ . '/includes/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

// Handle Create New Blood Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_request'])) {
    $patient_id = (int)$_POST['patient_id'];
    $blood_type = mysqli_real_escape_string($connection, $_POST['required_blood_type']);
    $units = (int)$_POST['units_required'];
    $reason = mysqli_real_escape_string($connection, $_POST['reason']);

    if ($patient_id <= 0) {
        $_SESSION['error_message'] = "Please select a patient.";
        header('Location: ' . hms_url('modules/shared/blood-requests.php'));
        exit();
    }

    $patient_check = mysqli_query($connection, "SELECT id FROM tbl_patient WHERE id = $patient_id AND status = 1");
    if (!$patient_check || mysqli_num_rows($patient_check) === 0) {
        $_SESSION['error_message'] = "Selected patient not found. Please choose a valid patient.";
        header('Location: ' . hms_url('modules/shared/blood-requests.php'));
        exit();
    }

    if ($units <= 0) {
        $_SESSION['error_message'] = "Number of units must be greater than 0.";
        header('Location: ' . hms_url('modules/shared/blood-requests.php'));
        exit();
    }

    $query = "INSERT INTO tbl_blood_request (patient_id, blood_group, quantity, reason, status, request_date, created_at)
              VALUES ($patient_id, '$blood_type', $units, '$reason', 'Pending', CURDATE(), NOW())";

    if (mysqli_query($connection, $query)) {
        $_SESSION['success_message'] = "Blood request submitted successfully.";
    } else {
        $_SESSION['error_message'] = "Error creating request: " . mysqli_error($connection);
    }
    header('Location: ' . hms_url('modules/shared/blood-requests.php'));
    exit();
}

// Handle Fulfilling a Request
if (isset($_GET['fulfill'])) {
    $request_id = (int)$_GET['fulfill'];
    $blood_type = mysqli_real_escape_string($connection, $_GET['type']);
    $units_required = (int)$_GET['units'];

    // First, check if enough units are available
    $check_sql = "SELECT COUNT(*) as count FROM tbl_blood_bank WHERE blood_group = '$blood_type' AND status = 'Available' AND expiry_date > CURDATE()";
    $check_result = mysqli_query($connection, $check_sql);
    $available_units = mysqli_fetch_assoc($check_result)['count'];

    if ($available_units >= $units_required) {
        mysqli_begin_transaction($connection);
        try {
            // Step 1: Update the request status to 'Fulfilled'
            $req_sql = "UPDATE tbl_blood_request SET status = 'Fulfilled' WHERE id = $request_id";
            mysqli_query($connection, $req_sql);

            // Step 2: Update inventory units to 'Used'
            $inv_sql = "UPDATE tbl_blood_bank SET status = 'Used', request_id = $request_id 
                        WHERE blood_group = '$blood_type' AND status = 'Available' AND expiry_date > CURDATE() 
                        ORDER BY expiry_date ASC LIMIT $units_required";
            mysqli_query($connection, $inv_sql);
            
            mysqli_commit($connection);
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($connection);
        }
    } else {
        // Optional: Set a session variable to show an "insufficient stock" error message
        $_SESSION['error_message'] = "Not enough units of " . $blood_type . " available to fulfill this request.";
    }
    header('Location: ' . hms_url('modules/shared/blood-requests.php'));
    exit();
}

// Fetch all requests for display
$requests_query = "SELECT br.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name FROM tbl_blood_request br LEFT JOIN tbl_patient p ON br.patient_id = p.id ORDER BY br.created_at DESC";
$requests_result = mysqli_query($connection, $requests_query);

// Fetch active patients for the new request form
$patients_query = "SELECT id, first_name, last_name FROM tbl_patient WHERE status = 1 ORDER BY first_name, last_name";
$patients_result = mysqli_query($connection, $patients_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Requests - HMS</title>
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
                    <div class="col-sm-8">
                        <h4 class="page-title">Manage Blood Requests</h4>
                    </div>
                    <div class="col-sm-4 text-right">
                        <button class="btn btn-primary btn-rounded" data-toggle="modal" data-target="#requestModal"><i class="fa fa-plus"></i> New Request</button>
                    </div>
                </div>

                <?php 
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card-box">
                            <table class="table table-striped custom-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Blood Type</th>
                                        <th>Units</th>
                                        <th>Reason</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($requests_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['blood_group']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $status = $row['status'];
                                            $badge_class = ($status == 'Pending') ? 'badge-warning' : (($status == 'Fulfilled') ? 'badge-success' : 'badge-danger');
                                            echo "<span class='badge $badge_class'>$status</span>";
                                            ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if ($row['status'] == 'Pending'): ?>
                                                <a href="<?php echo hms_url('modules/shared/blood-requests.php'); ?>"?fulfill=<?php echo $row['id']; ?>&type=<?php echo $row['blood_group']; ?>&units=<?php echo $row['quantity']; ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   onclick="return confirm('This will deduct from inventory. Are you sure?');">
                                                   <i class="fa fa-check"></i> Fulfill
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?php echo hms_url('modules/shared/blood-requests.php'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Blood Request</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select Patient <span class="text-danger">*</span></label>
                            <select name="patient_id" class="form-control" required>
                                <option value="">Select Patient</option>
                                <?php if ($patients_result && mysqli_num_rows($patients_result) > 0): ?>
                                    <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                        <option value="<?php echo (int)$patient['id']; ?>">
                                            <?php echo htmlspecialchars(trim($patient['first_name'] . ' ' . $patient['last_name'])); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Required Blood Type</label>
                            <select name="required_blood_type" class="form-control" required>
                                <option value="">Select Blood Type</option>
                                <option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Units Required</label>
                            <input type="number" name="units_required" class="form-control" required min="1">
                        </div>
                        <div class="form-group">
                            <label>Reason / Medical Justification</label>
                            <textarea name="reason" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_request" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
</body>
</html>
