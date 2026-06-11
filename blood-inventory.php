<?php
require_once __DIR__ . '/includes/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

// Handle adding a new blood unit from a donation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_unit'])) {
    $donor_id = (int)$_POST['donor_id'];
    $collection_date = mysqli_real_escape_string($connection, $_POST['collection_date']);
    $expiry_date = date('Y-m-d', strtotime($collection_date . ' +42 days'));

    // Get donor's blood type
    $donor_info_query = "SELECT blood_type FROM tbl_donor WHERE donor_id = $donor_id";
    $donor_info_result = mysqli_query($connection, $donor_info_query);
    $blood_type = mysqli_fetch_assoc($donor_info_result)['blood_type'];

    // Use a transaction to ensure both operations succeed or fail together
    mysqli_begin_transaction($connection);
    try {
        $inv_query = "INSERT INTO tbl_blood_bank (donor_id, blood_group, collection_date, expiry_date, status) VALUES ($donor_id, '$blood_type', '$collection_date', '$expiry_date', 'Available')";
        mysqli_query($connection, $inv_query);
        
        $donor_query = "UPDATE tbl_donor SET last_donation_date = '$collection_date' WHERE donor_id = $donor_id";
        mysqli_query($connection, $donor_query);

        mysqli_commit($connection);
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($connection);
        // You could add error logging here for debugging
    }

    header('Location: ' . hms_url('modules/admin/blood-inventory.php'));
    exit();
}

// Fetch all available inventory and join with donors
$inventory_query = "SELECT inv.*, d.name as donor_name 
                    FROM tbl_blood_bank inv
                    LEFT JOIN tbl_donor d ON inv.donor_id = d.donor_id
                    WHERE inv.status = 'Available' AND inv.expiry_date > CURDATE()
                    ORDER BY inv.expiry_date ASC";
$inventory_result = mysqli_query($connection, $inventory_query);

// Fetch eligible donors (who haven't donated in the last 56 days) for the dropdown
$donors_list_query = "SELECT donor_id, name, blood_type FROM tbl_donor WHERE status = 'Active' AND (last_donation_date IS NULL OR last_donation_date <= DATE_SUB(CURDATE(), INTERVAL 56 DAY))";
$donors_list_result = mysqli_query($connection, $donors_list_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Inventory - HMS</title>
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
                        <h4 class="page-title">Available Blood Inventory</h4>
                    </div>
                    <div class="col-sm-4 text-right">
                        <button class="btn btn-primary btn-rounded" data-toggle="modal" data-target="#addUnitModal"><i class="fa fa-plus"></i> Add Donation Unit</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card-box">
                            <table class="table table-striped custom-table">
                                <thead>
                                    <tr>
                                        <th>Unit ID</th>
                                        <th>Blood Type</th>
                                        <th>Donor Name</th>
                                        <th>Collection Date</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($inventory_result)): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><span class="badge badge-primary" style="font-size: 14px;"><?php echo htmlspecialchars($row['blood_group']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['donor_name'] ?: 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['collection_date'])); ?></td>
                                        <td class="text-danger"><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></td>
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

    <!-- Add Donation Unit Modal -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?php echo hms_url('modules/admin/blood-inventory.php'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Record a New Donation</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select Eligible Donor</label>
                            <select name="donor_id" class="form-control" required>
                                <option value="">-- Select Donor --</option>
                                <?php while ($donor = mysqli_fetch_assoc($donors_list_result)): ?>
                                    <option value="<?php echo $donor['donor_id']; ?>">
                                        <?php echo htmlspecialchars($donor['name']); ?> (<?php echo htmlspecialchars($donor['blood_type']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Collection Date</label>
                            <input type="date" name="collection_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_unit" class="btn btn-primary">Add Unit to Inventory</button>
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
