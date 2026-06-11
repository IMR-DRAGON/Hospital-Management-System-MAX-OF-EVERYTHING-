<?php
require_once __DIR__ . '/includes/init.php';
// Error reporting for debugging (optional, remove for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

// Initialize variables for potential errors
$inventory_load_error = '';
$requests_load_error = '';
$shortage_load_error = '';
$all_donors_load_error = '';
$full_inventory_load_error = '';
$inventory_summary_array = [];
$requests_result = null;
$shortage_result = null;
$all_donors_result = null;
$full_inventory_result = null;

// 1. Get Blood Inventory Summary (Available Units by Type)
$inventory_query = "SELECT
    blood_group as blood_type,
    COUNT(*) as available_units
FROM tbl_blood_bank
WHERE status = 'Available' AND expiry_date >= CURDATE()
GROUP BY blood_group
ORDER BY FIELD(blood_group, 'O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-')"; // Logical order
$inventory_result = mysqli_query($connection, $inventory_query);
if (!$inventory_result) {
    $inventory_load_error = "Error fetching inventory summary: " . mysqli_error($connection);
} else {
    while ($row = mysqli_fetch_assoc($inventory_result)) {
        $inventory_summary_array[$row['blood_type']] = $row['available_units'];
    }
}

// 2. Get Recent Pending Blood Requests (Last 5) - CORRECTED QUERY
$requests_query = "SELECT
    br.id as request_id,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name, -- CORRECTED: Get name only from joined patient table
    br.blood_group as required_blood_type,
    br.quantity as units_required,
    br.created_at
FROM tbl_blood_request br
LEFT JOIN tbl_patient p ON br.patient_id = p.id
WHERE br.status = 'Pending'
ORDER BY br.created_at DESC
LIMIT 5";
$requests_result = mysqli_query($connection, $requests_query);
if (!$requests_result) {
    $requests_load_error = "Error fetching pending requests: " . mysqli_error($connection);
}

// 3. Get Critical Shortage Alerts (less than 5 units available)
$shortage_query = "SELECT blood_group as blood_type, COUNT(*) as available_units
FROM tbl_blood_bank
WHERE status = 'Available' AND expiry_date >= CURDATE()
GROUP BY blood_group
HAVING COUNT(*) < 5
ORDER BY FIELD(blood_group, 'O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-')"; // Logical order
$shortage_result = mysqli_query($connection, $shortage_query);
if (!$shortage_result) {
    $shortage_load_error = "Error fetching shortage alerts: " . mysqli_error($connection);
}

// 4. Get Full List of Active Donors
$all_donors_query = "SELECT donor_id, donor_code, name, email, phone, blood_type, age, gender, total_donations, last_donation_date, status
FROM donors
WHERE status = 'Active'
ORDER BY name ASC";
$all_donors_result = mysqli_query($connection, $all_donors_query);
if (!$all_donors_result) {
    $all_donors_load_error = "Error fetching donor list: " . mysqli_error($connection);
}

// 5. Get Full Blood Inventory Details
$full_inventory_query = "SELECT id, bag_id, blood_group, quantity, collection_date, expiry_date, status
FROM tbl_blood_bank
ORDER BY expiry_date ASC, collection_date DESC";
$full_inventory_result = mysqli_query($connection, $full_inventory_query);
if (!$full_inventory_result) {
    $full_inventory_load_error = "Error fetching full inventory: " . mysqli_error($connection);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Dashboard - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>"> </head>
<body>
    <?php include hms_path('includes/header.php'); ?>

    <div class="main-wrapper">
        <?php // Include sidebar if needed ?>
        <div class="page-wrapper">
            <div class="content">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <h4 class="page-title">Blood Bank Dashboard & Inventory</h4>
                    </div>
                    <div class="col-sm-6 text-right">
                        <?php if ($_SESSION['role'] == 1): ?>
                        <a href="<?php echo hms_url('modules/admin/add-blood-bag.php'); ?>" class="btn btn-success btn-rounded"><i class="fa fa-plus"></i> Add Blood Bag</a>
                        <a href="<?php echo hms_url('modules/admin/donors.php'); ?>" class="btn btn-primary btn-rounded ml-2"><i class="fa fa-users"></i> Manage Donors</a>
                        <?php endif; ?>
                        <a href="<?php echo hms_url('modules/shared/blood-requests.php'); ?>" class="btn btn-warning btn-rounded ml-2"><i class="fa fa-file-text-o"></i> Manage Requests</a>
                    </div>
                </div>

                <?php
                    if (isset($_SESSION['blood_bag_status'])) {
                        echo '<div class="alert alert-' . htmlspecialchars($_SESSION['blood_bag_status']['type']) . '">' . htmlspecialchars($_SESSION['blood_bag_status']['message']) . '</div>';
                        unset($_SESSION['blood_bag_status']);
                    }
                    if (!empty($inventory_load_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($inventory_load_error) . '</div>';
                    if (!empty($requests_load_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($requests_load_error) . '</div>';
                    if (!empty($shortage_load_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($shortage_load_error) . '</div>';
                    if (!empty($all_donors_load_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($all_donors_load_error) . '</div>';
                    if (!empty($full_inventory_load_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($full_inventory_load_error) . '</div>';
                ?>

                <?php if ($shortage_result && mysqli_num_rows($shortage_result) > 0): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            <strong><i class="fa fa-warning"></i> Warning! Critical Shortage:</strong> The following blood types have less than 5 units available:
                            <?php mysqli_data_seek($shortage_result, 0); // Reset pointer ?>
                            <?php while ($row = mysqli_fetch_assoc($shortage_result)): ?>
                                <span class="badge badge-danger ml-2" style="font-size: 1rem;">
                                    <?php echo htmlspecialchars($row['blood_type']); ?>: <?php echo htmlspecialchars($row['available_units']); ?>
                                </span>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header"><h5 class="card-title mb-0">Available Blood Units</h5></div>
                            <div class="card-body">
                                <div class="row d-flex justify-content-center">
                                    <?php
                                    $blood_types_all = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
                                    foreach ($blood_types_all as $bt) {
                                        $count = $inventory_summary_array[$bt] ?? 0;
                                        $badge_class = $count < 5 ? 'badge-danger' : 'badge-success';
                                        echo '<div class="col-6 col-sm-4 col-md-3 text-center mb-3">'; // Responsive columns
                                        echo '<h5 class="text-danger font-weight-bold">' . htmlspecialchars($bt) . '</h5>';
                                        echo '<span class="badge ' . $badge_class . ' badge-pill" style="font-size: 1.1rem;">' . $count . '</span>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <div class="text-center mt-2">
                                     <a href="#inventoryTableSection" class="btn btn-sm btn-outline-primary">View Full Inventory Details</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card">
                           <div class="card-header"><h5 class="card-title mb-0">Recent Pending Requests</h5></div>
                           <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                                <?php if ($requests_result && mysqli_num_rows($requests_result) > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php while ($row = mysqli_fetch_assoc($requests_result)): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-2"> <div>
                                                    <small><?php echo htmlspecialchars($row['patient_name'] ?? 'N/A'); ?></small><br> 
                                                    <span class="badge badge-danger mr-1"><?php echo htmlspecialchars($row['required_blood_type']); ?></span>
                                                    <span class="badge badge-info"><?php echo htmlspecialchars($row['units_required']); ?> Units</span>
                                                    <small class="text-muted d-block"><?php echo date('d M Y H:i', strtotime($row['created_at']));?></small> 
                                                </div>
                                                <a href="<?php echo hms_url('modules/shared/blood-requests.php'); ?>"?req_id=<?php echo $row['request_id']; ?>" class="btn btn-sm btn-warning">Process</a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No pending requests.</p>
                                <?php endif; ?>
                           </div>
                           <div class="card-footer text-center py-2"> 
                               <a href="<?php echo hms_url('modules/shared/blood-requests.php'); ?>" class="btn btn-sm btn-outline-secondary">View All Requests</a>
                           </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                           <div class="card-header"><h5 class="card-title mb-0">Active Donor List</h5></div>
                           <div class="card-body">
                                <div class="table-responsive">
                                    <table id="donorTable" class="table table-striped custom-table datatable">
                                        <thead>
                                            <tr>
                                                <th>Serial</th>
                                                <th>Name</th>
                                                <th>Contact</th>
                                                <th>Blood</th>
                                                <th>Age/Gen</th>
                                                <th>Donations</th>
                                                <th>Last Donated</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($all_donors_result && mysqli_num_rows($all_donors_result) > 0): ?>
                                                <?php $serial = 1; ?>
                                                <?php while ($row = mysqli_fetch_assoc($all_donors_result)): ?>
                                                <tr>
                                                    <td><strong><?php echo $serial; ?></strong></td>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars($row['email']); ?></small><br>
                                                        <small><?php echo htmlspecialchars($row['phone']); ?></small>
                                                    </td>
                                                    <td><span class="badge badge-danger"><?php echo htmlspecialchars($row['blood_type']); ?></span></td>
                                                    <td>
                                                        <?php
                                                            $genderSource = isset($row['gender']) ? (string)$row['gender'] : '';
                                                            $genderChar = $genderSource !== '' ? strtoupper(substr($genderSource, 0, 1)) : '-';
                                                            if ($genderChar !== 'M' && $genderChar !== 'F') {
                                                                $genderChar = '-';
                                                            }
                                                            echo htmlspecialchars($row['age']) . ' / ' . htmlspecialchars($genderChar);
                                                        ?>
                                                    </td>
                                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($row['total_donations']); ?></span></td>
                                                    <td><?php echo $row['last_donation_date'] ? date('d M Y', strtotime($row['last_donation_date'])) : '<span class="text-muted">Never</span>'; ?></td>
                                                    <td>
                                                        <?php $status_class = ($row['status'] == 'Active') ? 'badge-success' : 'badge-secondary'; ?>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                                    </td>
                                                </tr>
                                                <?php $serial++; endwhile; ?>
                                             <?php else: ?>
                                                 <tr><td colspan="8" class="text-center text-muted">No active donors found.</td></tr>
                                             <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                           </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4" id="inventoryTableSection">
                    <div class="col-md-12">
                        <div class="card">
                           <div class="card-header"><h5 class="card-title mb-0">Blood Inventory Details</h5></div>
                           <div class="card-body">
                                <div class="table-responsive">
                                    <table id="inventoryTable" class="table table-striped custom-table datatable">
                                        <thead>
                                            <tr>
                                                <th>Bag ID</th>
                                                <th>Blood Group</th>
                                                <th>Quantity</th>
                                                <th>Collection Date</th>
                                                <th>Expiry Date</th>
                                                <th>Status</th>
                                                <?php if ($_SESSION['role'] == 1): ?>
                                                <th class="text-right">Action</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                             <?php if ($full_inventory_result && mysqli_num_rows($full_inventory_result) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($full_inventory_result)):
                                                    $expiry_date = strtotime($row['expiry_date']);
                                                    $today = strtotime(date('Y-m-d'));
                                                    $days_left = ($expiry_date >= $today) ? floor(($expiry_date - $today) / (60 * 60 * 24)) : -1;

                                                    $row_class = '';
                                                    $status_badge = 'badge-secondary';
                                                    if ($row['status'] == 'Available') {
                                                        if ($days_left >= 0 && $days_left < 7) {
                                                            $row_class = 'table-warning';
                                                            $status_badge = 'badge-warning';
                                                        } elseif ($days_left < 0) {
                                                             $row_class = 'table-danger';
                                                             $status_badge = 'badge-danger';
                                                        } else {
                                                            $status_badge = 'badge-success';
                                                        }
                                                    } elseif ($row['status'] == 'Expired'){
                                                         $status_badge = 'badge-danger';
                                                    } elseif ($row['status'] == 'Used'){
                                                         $status_badge = 'badge-info';
                                                    }
                                                ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td><?php echo htmlspecialchars($row['bag_id']); ?></td>
                                                    <td><span class="badge badge-danger"><?php echo htmlspecialchars($row['blood_group']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($row['collection_date'])); ?></td>
                                                    <td>
                                                        <?php echo date('d M Y', $expiry_date); ?>
                                                        <?php if ($row['status'] == 'Available' && $days_left >= 0 && $days_left < 7): ?>
                                                            <small class="text-danger">(<?php echo $days_left; ?> days left)</small>
                                                        <?php elseif ($row['status'] == 'Available' && $days_left < 0): ?>
                                                            <small class="text-danger">(Expired)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                                    <td class="text-right">
                                                        <?php if ($_SESSION['role'] == 1): ?>
                                                        <a href="<?php echo hms_url('modules/admin/edit-blood-bag.php'); ?>"?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Details/Edit"><i class="fa fa-pencil"></i></a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="<?php echo $_SESSION['role'] == 1 ? '7' : '6'; ?>" class="text-center text-muted">No blood bags found in inventory.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                           </div>
                        </div>
                    </div>
                </div>

            </div> </div> </div> <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/jquery.dataTables.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/dataTables.bootstrap4.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>

     <script>
        $(document).ready(function() {
            // Initialize DataTable for Donors
            if ($('#donorTable tbody tr').length > 0 && $('#donorTable tbody td[colspan="8"]').length === 0) {
                 $('#donorTable').DataTable({
                     "order": [[ 1, "asc" ]],
                     "pageLength": 5
                 });
            }
            // Initialize DataTable for Inventory
            if ($('#inventoryTable tbody tr').length > 0 && $('#inventoryTable tbody td[colspan]').length === 0) {
                 $('#inventoryTable').DataTable({
                     "order": [[ 4, "asc" ]], // Order by expiry date
                     "pageLength": 10,
                     <?php if ($_SESSION['role'] == 1): ?>
                     "columnDefs": [
                         { "orderable": false, "searchable": false, "targets": 6 } // Action column
                      ]
                     <?php endif; ?>
                 });
            }
        });
    </script>
</body>
</html>