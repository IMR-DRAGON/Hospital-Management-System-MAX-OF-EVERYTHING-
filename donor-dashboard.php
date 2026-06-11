<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in as a donor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 4) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$donor_id = $_SESSION['user_id'];

// Get donor information from the 'donors' table. This query is correct.
$donor_query = "SELECT * FROM donors WHERE donor_id = $donor_id";
$donor_result = mysqli_query($connection, $donor_query);
$donor = mysqli_fetch_assoc($donor_result);

// Get donor's donation history from the 'blood_inventory' table.
// FIX: Changed table from 'tbl_blood_bank' to 'blood_inventory'.
// FIX: The WHERE clause now correctly uses 'donor_id'.
// FIX: Ordered by 'collection_date' as 'donation_date' does not exist in this table.
$donations_query = "SELECT * FROM blood_inventory WHERE donor_id = $donor_id ORDER BY collection_date DESC LIMIT 10";
$donations_result = mysqli_query($connection, $donations_query);

// Calculate next eligible donation date (56 days after last donation)
$next_donation_date = null;
if ($donor && $donor['last_donation_date']) {
    $next_donation_date = date('Y-m-d', strtotime($donor['last_donation_date'] . ' +56 days'));
}

// Get blood requests for the donor's blood type. This query is correct based on the schema.
$blood_requests_query = "SELECT br.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                         FROM tbl_blood_request br 
                         LEFT JOIN tbl_patient p ON br.patient_id = p.id 
                         WHERE br.blood_group = '{$donor['blood_type']}' AND br.status = 'Pending' 
                         ORDER BY br.created_at DESC LIMIT 5";
$blood_requests_result = mysqli_query($connection, $blood_requests_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - HMS</title>
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
                        <h4 class="page-title">Welcome, <?php echo htmlspecialchars($donor['name'] ?? 'Donor'); ?>!</h4>
                    </div>
                    <div class="col-sm-6 text-right">
                        <span class="text-muted">Donor ID: <?php echo htmlspecialchars($donor['donor_code'] ?? ''); ?></span>
                    </div>
                </div>

                <!-- Donor Status Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-tint fa-3x text-danger mb-3"></i>
                                <h4><?php echo htmlspecialchars($donor['blood_type'] ?? 'N/A'); ?></h4>
                                <p>Blood Type</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-heart fa-3x text-success mb-3"></i>
                                <h4><?php echo htmlspecialchars($donor['total_donations'] ?? '0'); ?></h4>
                                <p>Total Donations</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-calendar fa-3x text-info mb-3"></i>
                                <h4><?php echo ($donor && $donor['last_donation_date']) ? date('M d', strtotime($donor['last_donation_date'])) : 'Never'; ?></h4>
                                <p>Last Donation</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-clock-o fa-3x text-warning mb-3"></i>
                                <h4><?php echo $next_donation_date ? date('M d', strtotime($next_donation_date)) : 'Available'; ?></h4>
                                <p>Next Eligible</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-tint fa-3x text-danger mb-3"></i>
                                <h4>Blood Bank</h4>
                                <p>View blood bank information and donation history</p>
                                <a href="<?php echo hms_url('modules/admin/donors.php'); ?>" class="btn btn-danger btn-lg">
                                    <i class="fa fa-eye"></i> View Blood Bank
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-comments fa-3x text-success mb-3"></i>
                                <h4>Medical Chat</h4>
                                <p>Chat with medical staff and get health information</p>
                                <a href="<?php echo hms_url('modules/shared/medical-chat.php'); ?>" class="btn btn-success btn-lg">
                                    <i class="fa fa-comment"></i> Start Chat
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Eligibility Status -->
                <div class="row">
                    <div class="col-md-12">
                        <?php if ($next_donation_date && strtotime($next_donation_date) > strtotime('today')): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Donation Status:</strong> You are eligible to donate blood after <?php echo date('F d, Y', strtotime($next_donation_date)); ?>. 
                            Blood donors should wait at least 56 days between donations.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> 
                            <strong>Great News!</strong> You are eligible to donate blood today. Please contact the blood bank to schedule your donation.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Donation History -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Your Donation History</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($donations_result && mysqli_num_rows($donations_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Blood Type</th>
                                                <th>Status</th>
                                                <th>Expiry</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($donation = mysqli_fetch_assoc($donations_result)): ?>
                                            <tr>
                                                <!-- FIX: Changed from donation_date to collection_date -->
                                                <td><?php echo date('M d, Y', strtotime($donation['collection_date'])); ?></td>
                                                <!-- FIX: Changed from blood_group to blood_type -->
                                                <td><span class="badge badge-danger"><?php echo htmlspecialchars($donation['blood_type']); ?></span></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($donation['status']) {
                                                        case 'Available': $status_class = 'badge-success'; break;
                                                        case 'Used': $status_class = 'badge-info'; break;
                                                        case 'Expired': $status_class = 'badge-secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($donation['status']); ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($donation['expiry_date'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No donation history found. Start your journey as a blood donor today!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Blood Requests for Your Type -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Patients Needing <?php echo htmlspecialchars($donor['blood_type'] ?? 'Your'); ?> Blood</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($blood_requests_result && mysqli_num_rows($blood_requests_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Units</th>
                                                <th>Urgency</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($request = mysqli_fetch_assoc($blood_requests_result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['patient_name']); ?></td>
                                                <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                                                <td>
                                                    <?php
                                                    $urgency_class = '';
                                                    switch($request['urgency_level']) {
                                                        case 'Critical': $urgency_class = 'badge-danger'; break;
                                                        case 'Urgent': $urgency_class = 'badge-warning'; break;
                                                        default: $urgency_class = 'badge-info'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $urgency_class; ?>"><?php echo htmlspecialchars($request['urgency_level']); ?></span>
                                                </td>
                                                <td><?php echo date('M d', strtotime($request['created_at'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No current requests for <?php echo htmlspecialchars($donor['blood_type'] ?? 'your'); ?> blood type.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Donor Information -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Your Donor Profile</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><strong>Personal Information</strong></h6>
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($donor['name'] ?? ''); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($donor['email'] ?? ''); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($donor['phone'] ?? ''); ?></p>
                                        <p><strong>Age:</strong> <?php echo htmlspecialchars($donor['age'] ?? ''); ?> years</p>
                                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($donor['gender'] ?? ''); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><strong>Donation Information</strong></h6>
                                        <p><strong>Blood Type:</strong> <span class="badge badge-danger"><?php echo htmlspecialchars($donor['blood_type'] ?? ''); ?></span></p>
                                        <p><strong>Total Donations:</strong> <?php echo htmlspecialchars($donor['total_donations'] ?? ''); ?></p>
                                        <p><strong>Last Donation:</strong> <?php echo ($donor && $donor['last_donation_date']) ? date('F d, Y', strtotime($donor['last_donation_date'])) : 'Never'; ?></p>
                                        <p><strong>Donor Since:</strong> <?php echo ($donor && $donor['created_at']) ? date('F d, Y', strtotime($donor['created_at'])) : ''; ?></p>
                                        <p><strong>Donor Code:</strong> <?php echo htmlspecialchars($donor['donor_code'] ?? ''); ?></p>
                                    </div>
                                </div>
                                <?php if ($donor && $donor['address']): ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6><strong>Address</strong></h6>
                                        <p><?php echo htmlspecialchars($donor['address']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4><i class="fa fa-heart text-danger"></i> Ready to Save Lives?</h4>
                                <p>Your blood donations make a real difference in people's lives. Contact our blood bank to schedule your next donation.</p>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="fa fa-phone fa-2x text-primary mb-2"></i>
                                            <h6>Call Us</h6>
                                            <p>(555) 123-4567</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="fa fa-envelope fa-2x text-primary mb-2"></i>
                                            <h6>Email Us</h6>
                                            <p>bloodbank@hospital.com</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="fa fa-map-marker fa-2x text-primary mb-2"></i>
                                            <h6>Visit Us</h6>
                                            <p>Hospital Blood Bank</p>
                                        </div>
                                    </div>
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

