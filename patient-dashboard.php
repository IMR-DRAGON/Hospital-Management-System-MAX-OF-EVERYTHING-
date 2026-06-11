<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$patient_id = $_SESSION['user_id'];

// Get patient information
$patient_query = "SELECT * FROM tbl_patient WHERE id = $patient_id";
$patient_result = mysqli_query($connection, $patient_query);
$patient = mysqli_fetch_assoc($patient_result);

// Get upcoming appointments
$appointments_query = "SELECT * FROM tbl_appointment WHERE patient_name = '{$patient['first_name']} {$patient['last_name']}' AND status = 1 AND date >= CURDATE() ORDER BY date, time LIMIT 5";
$appointments_result = mysqli_query($connection, $appointments_query);

// Get blood requests
$blood_requests_query = "SELECT * FROM tbl_blood_request WHERE patient_id = $patient_id ORDER BY created_at DESC LIMIT 5";
$blood_requests_result = mysqli_query($connection, $blood_requests_query);

// Get available blood inventory
$blood_inventory_query = "SELECT blood_group, COUNT(*) as available_units FROM tbl_blood_bank WHERE status = 'Available' AND expiry_date > CURDATE() GROUP BY blood_group ORDER BY blood_group";
$blood_inventory_result = mysqli_query($connection, $blood_inventory_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HMS</title>
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
                        <h4 class="page-title">Welcome, <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>!</h4>
                    </div>
                    <div class="col-sm-6 text-right">
                        <span class="text-muted">Patient ID: <?php echo $patient['id']; ?></span>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-calendar fa-3x text-primary mb-3"></i>
                                <h4>Book Appointment</h4>
                                <p>Schedule an appointment with any available doctor</p>
                                <a href="<?php echo hms_url('modules/patient/patient-book-appointment.php'); ?>" class="btn btn-primary btn-lg">
                                    <i class="fa fa-plus"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-tint fa-3x text-danger mb-3"></i>
                                <h4>Blood Request</h4>
                                <p>Request blood from the blood bank</p>
                                <a href="<?php echo hms_url('modules/patient/patient-blood-request.php'); ?>" class="btn btn-danger btn-lg">
                                    <i class="fa fa-plus"></i> Request Blood
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-comments fa-3x text-success mb-3"></i>
                                <h4>Medical Chat</h4>
                                <p>Chat with your doctor and upload medical records</p>
                                <a href="<?php echo hms_url('modules/shared/medical-chat.php'); ?>" class="btn btn-success btn-lg">
                                    <i class="fa fa-comment"></i> Start Chat
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Upcoming Appointments -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Upcoming Appointments</h4>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($appointments_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Doctor</th>
                                                <th>Department</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>
                                            <tr>
                                                <td><?php echo $appointment['doctor']; ?></td>
                                                <td><?php echo $appointment['department']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($appointment['date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($appointment['time'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No upcoming appointments.</p>
                                <?php endif; ?>
                                <a href="<?php echo hms_url('modules/shared/appointments.php'); ?>" class="btn btn-sm btn-outline-primary">View All Appointments</a>
                            </div>
                        </div>
                    </div>

                    <!-- Blood Requests -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Blood Requests</h4>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($blood_requests_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Blood Type</th>
                                                <th>Units</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($request = mysqli_fetch_assoc($blood_requests_result)): ?>
                                            <tr>
                                                <td><strong><?php echo $request['blood_group']; ?></strong></td>
                                                <td><?php echo $request['quantity']; ?></td>
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
                                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No blood requests yet.</p>
                                <?php endif; ?>
                                <a href="<?php echo hms_url('modules/patient/patient-blood-request.php'); ?>" class="btn btn-sm btn-outline-danger">View All Requests</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blood Availability -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Blood Bank Availability</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php while ($blood = mysqli_fetch_assoc($blood_inventory_result)): ?>
                                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="card border">
                                                <div class="card-body p-3">
                                                    <h5 class="card-title text-danger"><?php echo $blood['blood_group']; ?></h5>
                                                    <p class="card-text">
                                                        <span class="badge badge-success"><?php echo $blood['available_units']; ?> units</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
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
