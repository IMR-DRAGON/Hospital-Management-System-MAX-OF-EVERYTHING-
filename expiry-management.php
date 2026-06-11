<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in and has access (Admin or Employee only)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

if ($_SESSION['role'] != 1 && $_SESSION['role'] != 2) {
    header('Location: ' . hms_url('modules/shared/dashboard.php'));
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_batch'])) {
        $item_id = intval($_POST['item_id']);
        $batch_number = mysqli_real_escape_string($connection, $_POST['batch_number']);
        $quantity_received = intval($_POST['quantity_received']);
        $unit_cost = floatval($_POST['unit_cost']);
        $expiry_date = mysqli_real_escape_string($connection, $_POST['expiry_date']);
        $supplier_name = mysqli_real_escape_string($connection, $_POST['supplier_name']);
        
        $query = "INSERT INTO inventory_batches (item_id, batch_number, quantity_received, quantity_remaining, unit_cost, expiry_date, supplier_name) 
                  VALUES ($item_id, '$batch_number', $quantity_received, $quantity_received, $unit_cost, '$expiry_date', '$supplier_name')";
        
        if (mysqli_query($connection, $query)) {
            // Update item stock
            $update_stock_query = "UPDATE inventory_items SET current_stock = current_stock + $quantity_received WHERE item_id = $item_id";
            mysqli_query($connection, $update_stock_query);
            
            // Record stock movement
            $movement_query = "INSERT INTO stock_movements (item_id, movement_type, quantity, unit_cost, total_cost, notes, created_by) 
                              VALUES ($item_id, 'Purchase', $quantity_received, $unit_cost, " . ($quantity_received * $unit_cost) . ", 'Batch: $batch_number', " . $_SESSION['user_id'] . ")";
            mysqli_query($connection, $movement_query);
            
            $message = "Batch added successfully!";
        } else {
            $error = "Error adding batch: " . mysqli_error($connection);
        }
    }
    
    if (isset($_POST['dispose_batch'])) {
        $batch_id = intval($_POST['batch_id']);
        $disposal_reason = mysqli_real_escape_string($connection, $_POST['disposal_reason']);
        
        // Get batch details
        $batch_query = "SELECT * FROM inventory_batches WHERE batch_id = $batch_id";
        $batch_result = mysqli_query($connection, $batch_query);
        $batch = mysqli_fetch_assoc($batch_result);
        
        if ($batch) {
            // Update batch
            $update_batch_query = "UPDATE inventory_batches SET quantity_remaining = 0, is_active = FALSE WHERE batch_id = $batch_id";
            mysqli_query($connection, $update_batch_query);
            
            // Update item stock
            $update_stock_query = "UPDATE inventory_items SET current_stock = current_stock - {$batch['quantity_remaining']} WHERE item_id = {$batch['item_id']}";
            mysqli_query($connection, $update_stock_query);
            
            // Record disposal movement
            $movement_query = "INSERT INTO stock_movements (item_id, batch_id, movement_type, quantity, notes, created_by) 
                              VALUES ({$batch['item_id']}, $batch_id, 'Disposed', {$batch['quantity_remaining']}, '$disposal_reason', " . $_SESSION['user_id'] . ")";
            mysqli_query($connection, $movement_query);
            
            $message = "Batch disposed successfully!";
        }
    }
}

// Get expiring items (within 30 days)
$expiring_query = "SELECT 
    b.batch_id, b.batch_number, b.expiry_date, b.quantity_remaining, b.unit_cost,
    i.item_id, i.item_code, i.item_name, i.is_critical, c.category_name,
    DATEDIFF(b.expiry_date, CURRENT_DATE) as days_until_expiry
    FROM inventory_batches b
    JOIN inventory_items i ON b.item_id = i.item_id
    LEFT JOIN inventory_categories c ON i.category_id = c.category_id
    WHERE b.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) 
    AND b.quantity_remaining > 0 AND b.is_active = TRUE
    ORDER BY b.expiry_date ASC";

$expiring_result = mysqli_query($connection, $expiring_query);

// Get expired items
$expired_query = "SELECT 
    b.batch_id, b.batch_number, b.expiry_date, b.quantity_remaining, b.unit_cost,
    i.item_id, i.item_code, i.item_name, i.is_critical, c.category_name,
    DATEDIFF(CURRENT_DATE, b.expiry_date) as days_expired
    FROM inventory_batches b
    JOIN inventory_items i ON b.item_id = i.item_id
    LEFT JOIN inventory_categories c ON i.category_id = c.category_id
    WHERE b.expiry_date < CURRENT_DATE 
    AND b.quantity_remaining > 0 AND b.is_active = TRUE
    ORDER BY b.expiry_date ASC";

$expired_result = mysqli_query($connection, $expired_query);

// Get all items for batch addition
$items_query = "SELECT item_id, item_code, item_name FROM inventory_items WHERE is_active = TRUE ORDER BY item_name";
$items_result = mysqli_query($connection, $items_query);

// Get batch tracking data
$batch_tracking_query = "SELECT 
    b.batch_id, b.batch_number, b.expiry_date, b.quantity_received, b.quantity_remaining,
    i.item_code, i.item_name, c.category_name, b.supplier_name, b.received_date
    FROM inventory_batches b
    JOIN inventory_items i ON b.item_id = i.item_id
    LEFT JOIN inventory_categories c ON i.category_id = c.category_id
    WHERE b.is_active = TRUE
    ORDER BY b.expiry_date ASC";

$batch_tracking_result = mysqli_query($connection, $batch_tracking_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Management</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>">
    <style>
        .expiry-critical { background-color: #ffebee; }
        .expiry-warning { background-color: #fff3e0; }
        .expiry-normal { background-color: #e8f5e8; }
        .expired { background-color: #f8d7da; }
    </style>
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-6 col-6">
                        <h4 class="page-title">Expiry Management & Tracking</h4>
                    </div>
                    <div class="col-sm-6 col-6 text-right">
                        <button class="btn btn-primary btn-rounded" data-toggle="modal" data-target="#addBatchModal">
                            <i class="fa fa-plus"></i> Add New Batch
                        </button>
                        <a href="<?php echo hms_url('modules/admin/hospital-inventory.php'); ?>" class="btn btn-secondary btn-rounded ml-2">
                            <i class="fa fa-arrow-left"></i> Back to Inventory
                        </a>
                    </div>
                </div>

                <!-- Display Messages -->
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

                <!-- Expired Items -->
                <?php if (mysqli_num_rows($expired_result) > 0): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card" style="border-left: 4px solid #dc3545;">
                            <div class="card-header bg-danger text-white">
                                <h4 class="card-title text-white">Expired Items</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Batch Number</th>
                                                <th>Expiry Date</th>
                                                <th>Days Expired</th>
                                                <th>Remaining Qty</th>
                                                <th>Value</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($expired_result)): ?>
                                            <tr class="expired">
                                                <td><?php echo $row['item_code']; ?></td>
                                                <td><?php echo $row['item_name']; ?></td>
                                                <td><?php echo $row['batch_number']; ?></td>
                                                <td><?php echo $row['expiry_date']; ?></td>
                                                <td><span class="badge badge-danger"><?php echo $row['days_expired']; ?> days</span></td>
                                                <td><?php echo $row['quantity_remaining']; ?></td>
                                                <td>$<?php echo number_format($row['quantity_remaining'] * $row['unit_cost'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" onclick="disposeBatch(<?php echo $row['batch_id']; ?>, '<?php echo htmlspecialchars($row['item_name']); ?>')">
                                                        Dispose
                                                    </button>
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
                <?php endif; ?>

                <!-- Expiring Items -->
                <?php if (mysqli_num_rows($expiring_result) > 0): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card" style="border-left: 4px solid #ffc107;">
                            <div class="card-header bg-warning text-white">
                                <h4 class="card-title text-white">Items Expiring Soon (Within 30 Days)</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Batch Number</th>
                                                <th>Expiry Date</th>
                                                <th>Days Until Expiry</th>
                                                <th>Remaining Qty</th>
                                                <th>Value</th>
                                                <th>Priority</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($expiring_result)): ?>
                                            <?php 
                                            $expiry_class = $row['days_until_expiry'] <= 7 ? 'expiry-critical' : 
                                                          ($row['days_until_expiry'] <= 15 ? 'expiry-warning' : 'expiry-normal');
                                            $priority = $row['days_until_expiry'] <= 7 ? 'Critical' : 
                                                      ($row['days_until_expiry'] <= 15 ? 'High' : 'Medium');
                                            ?>
                                            <tr class="<?php echo $expiry_class; ?>">
                                                <td><?php echo $row['item_code']; ?></td>
                                                <td><?php echo $row['item_name']; ?></td>
                                                <td><?php echo $row['batch_number']; ?></td>
                                                <td><?php echo $row['expiry_date']; ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $row['days_until_expiry'] <= 7 ? 'badge-danger' : 
                                                             ($row['days_until_expiry'] <= 15 ? 'badge-warning' : 'badge-info'); 
                                                    ?>">
                                                        <?php echo $row['days_until_expiry']; ?> days
                                                    </span>
                                                </td>
                                                <td><?php echo $row['quantity_remaining']; ?></td>
                                                <td>$<?php echo number_format($row['quantity_remaining'] * $row['unit_cost'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $priority == 'Critical' ? 'badge-danger' : 
                                                             ($priority == 'High' ? 'badge-warning' : 'badge-info'); 
                                                    ?>">
                                                        <?php echo $priority; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="disposeBatch(<?php echo $row['batch_id']; ?>, '<?php echo htmlspecialchars($row['item_name']); ?>')">
                                                        Dispose
                                                    </button>
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
                <?php endif; ?>

                <!-- Batch Tracking -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Batch Tracking</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="batchTable">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Batch Number</th>
                                                <th>Supplier</th>
                                                <th>Received Date</th>
                                                <th>Expiry Date</th>
                                                <th>Qty Received</th>
                                                <th>Qty Remaining</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($batch_tracking_result)): ?>
                                            <?php 
                                            $days_until_expiry = ceil((strtotime($row['expiry_date']) - time()) / (60 * 60 * 24));
                                            $status = $days_until_expiry < 0 ? 'Expired' : 
                                                    ($days_until_expiry <= 7 ? 'Expiring Soon' : 
                                                    ($days_until_expiry <= 30 ? 'Expiring' : 'Good'));
                                            $status_class = $days_until_expiry < 0 ? 'badge-danger' : 
                                                          ($days_until_expiry <= 7 ? 'badge-danger' : 
                                                          ($days_until_expiry <= 30 ? 'badge-warning' : 'badge-success'));
                                            ?>
                                            <tr>
                                                <td><?php echo $row['item_code']; ?></td>
                                                <td><?php echo $row['item_name']; ?></td>
                                                <td><?php echo $row['batch_number']; ?></td>
                                                <td><?php echo $row['supplier_name']; ?></td>
                                                <td><?php echo $row['received_date']; ?></td>
                                                <td><?php echo $row['expiry_date']; ?></td>
                                                <td><?php echo $row['quantity_received']; ?></td>
                                                <td><?php echo $row['quantity_remaining']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo $status; ?>
                                                    </span>
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
        </div>
    </div>

    <!-- Add Batch Modal -->
    <div class="modal fade" id="addBatchModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" action="<?php echo hms_url('modules/admin/expiry-management.php'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Batch</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Item *</label>
                                    <select name="item_id" class="form-control" required>
                                        <option value="">Select Item</option>
                                        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                            <option value="<?php echo $item['item_id']; ?>">
                                                <?php echo $item['item_code'] . ' - ' . $item['item_name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Batch Number *</label>
                                    <input type="text" name="batch_number" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Quantity Received *</label>
                                    <input type="number" name="quantity_received" class="form-control" required min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Unit Cost</label>
                                    <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expiry Date *</label>
                                    <input type="date" name="expiry_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Supplier Name</label>
                                    <input type="text" name="supplier_name" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_batch" class="btn btn-primary">Add Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Dispose Batch Modal -->
    <div class="modal fade" id="disposeBatchModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="<?php echo hms_url('modules/admin/expiry-management.php'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Dispose Batch</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Item: <strong id="disposeItemName"></strong></p>
                        <input type="hidden" name="batch_id" id="disposeBatchId">
                        <div class="form-group">
                            <label>Disposal Reason *</label>
                            <select name="disposal_reason" class="form-control" required>
                                <option value="">Select Reason</option>
                                <option value="Expired">Expired</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Contaminated">Contaminated</option>
                                <option value="Recalled">Recalled</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="dispose_batch" class="btn btn-danger">Dispose Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/jquery.dataTables.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/dataTables.bootstrap4.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
    <script>
        $(document).ready(function() {
            $('#batchTable').DataTable({
                "pageLength": 25,
                "order": [[ 5, "asc" ]] // Sort by expiry date
            });
        });

        function disposeBatch(batchId, itemName) {
            document.getElementById('disposeBatchId').value = batchId;
            document.getElementById('disposeItemName').innerText = itemName;
            $('#disposeBatchModal').modal('show');
        }
    </script>
</body>
</html>
