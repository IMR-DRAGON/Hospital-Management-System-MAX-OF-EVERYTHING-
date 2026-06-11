<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$patient_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get patient information
$patient_query = "SELECT * FROM tbl_patient WHERE id = $patient_id";
$patient_result = mysqli_query($connection, $patient_query);
$patient = mysqli_fetch_assoc($patient_result);

// Handle blood request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $blood_type = mysqli_real_escape_string($connection, $_POST['blood_type']);
    $units_required = intval($_POST['units_required']);
    $reason = mysqli_real_escape_string($connection, $_POST['reason']);
    $urgency = mysqli_real_escape_string($connection, $_POST['urgency']);
    $contact_person = mysqli_real_escape_string($connection, $_POST['contact_person']);
    $contact_phone = mysqli_real_escape_string($connection, $_POST['contact_phone']);
    
    // Validate required fields
    if (empty($blood_type) || empty($units_required) || empty($reason)) {
        $error = "Please fill in all required fields.";
    } elseif ($units_required <= 0) {
        $error = "Number of units must be greater than 0.";
    } else {
        // Check blood availability
        $check_query = "SELECT COUNT(*) as available_units FROM tbl_blood_bank WHERE blood_group = '$blood_type' AND status = 'Available' AND expiry_date > CURDATE()";
        $check_result = mysqli_query($connection, $check_query);
        $available = mysqli_fetch_assoc($check_result)['available_units'];
        
        if ($available < $units_required) {
            $error = "Only $available units of $blood_type blood are available. Please adjust your request.";
        } else {
            $insert_query = "INSERT INTO tbl_blood_request (patient_id, blood_group, quantity, reason, urgency_level, contact_person, contact_phone, status, created_at) VALUES ($patient_id, '$blood_type', $units_required, '$reason', '$urgency', '$contact_person', '$contact_phone', 'Pending', NOW())";
            
            if (mysqli_query($connection, $insert_query)) {
                $message = "Blood request submitted successfully! We will contact you shortly.";
            } else {
                $error = "Error submitting request: " . mysqli_error($connection);
            }
        }
    }
}

// Get patient's blood requests
$requests_query = "SELECT * FROM tbl_blood_request WHERE patient_id = $patient_id ORDER BY created_at DESC";
$requests_result = mysqli_query($connection, $requests_query);

// Get available blood inventory
$inventory_query = "SELECT blood_group, COUNT(*) as available_units FROM tbl_blood_bank WHERE status = 'Available' AND expiry_date > CURDATE() GROUP BY blood_group ORDER BY blood_group";
$inventory_result = mysqli_query($connection, $inventory_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Request - HMS</title>
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
                    <div class="col-sm-6">
                        <h4 class="page-title">Blood Request</h4>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="<?php echo hms_url('modules/patient/patient-dashboard.php'); ?>" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
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
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Request Blood</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Patient Name</label>
                                                <input type="text" class="form-control" value="<?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Patient ID</label>
                                                <input type="text" class="form-control" value="<?php echo $patient['id']; ?>" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Required Blood Type <span class="text-danger">*</span></label>
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
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Number of Units Required <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="units_required" min="1" max="10" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Urgency Level</label>
                                                <select class="form-control" name="urgency">
                                                    <option value="Normal">Normal</option>
                                                    <option value="Urgent">Urgent</option>
                                                    <option value="Critical">Critical</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Emergency Contact Person</label>
                                                <input type="text" class="form-control" name="contact_person" placeholder="Full name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Emergency Contact Phone</label>
                                                <input type="tel" class="form-control" name="contact_phone" placeholder="Phone number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Medical Reason / Justification <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="reason" rows="4" placeholder="Please provide medical justification for the blood request" required></textarea>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" name="submit_request" class="btn btn-danger btn-lg">
                                            <i class="fa fa-tint"></i> Submit Request
                                        </button>
                                        <button type="reset" class="btn btn-secondary btn-lg">Reset</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Blood Availability</h4>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($inventory_result) > 0): ?>
                                <div class="row">
                                    <?php while ($blood = mysqli_fetch_assoc($inventory_result)): ?>
                                    <div class="col-6 mb-3">
                                        <div class="text-center">
                                            <div class="card border">
                                                <div class="card-body p-2">
                                                    <h6 class="card-title text-danger mb-1"><?php echo $blood['blood_group']; ?></h6>
                                                    <span class="badge badge-success"><?php echo $blood['available_units']; ?> units</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No blood units available at the moment.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h4 class="card-title">Request Guidelines</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li><i class="fa fa-check text-success"></i> All requests require medical justification</li>
                                    <li><i class="fa fa-check text-success"></i> Critical requests are processed within 2 hours</li>
                                    <li><i class="fa fa-check text-success"></i> Normal requests are processed within 24 hours</li>
                                    <li><i class="fa fa-check text-success"></i> You will be contacted for verification</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient's Blood Requests History -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Your Blood Requests</h4>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($requests_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Blood Type</th>
                                                <th>Units</th>
                                                <th>Urgency</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Reason</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
                                            <tr>
                                                <td><strong>#<?php echo $request['id']; ?></strong></td>
                                                <td><span class="badge badge-danger"><?php echo $request['blood_group']; ?></span></td>
                                                <td><?php echo $request['quantity']; ?></td>
                                                <td>
                                                    <?php
                                                    $urgency_class = '';
                                                    switch($request['urgency_level']) {
                                                        case 'Critical': $urgency_class = 'badge-danger'; break;
                                                        case 'Urgent': $urgency_class = 'badge-warning'; break;
                                                        default: $urgency_class = 'badge-info'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $urgency_class; ?>"><?php echo $request['urgency_level']; ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($request['status']) {
                                                        case 'Pending': $status_class = 'badge-warning'; break;
                                                        case 'Fulfilled': $status_class = 'badge-success'; break;
                                                        case 'Rejected': $status_class = 'badge-danger'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $request['status']; ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                                <td><?php echo substr($request['reason'], 0, 50) . (strlen($request['reason']) > 50 ? '...' : ''); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No blood requests found.</p>
                                <?php endif; ?>
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
