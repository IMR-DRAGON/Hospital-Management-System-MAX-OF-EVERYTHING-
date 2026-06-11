<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in and has access (Admin or Employee only)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

// Check if user is admin (role=1) or employee (role=2)
if ($_SESSION['role'] != 1 && $_SESSION['role'] != 2) {
    header('Location: ' . hms_url('modules/shared/dashboard.php'));
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item'])) {
        $item_code = mysqli_real_escape_string($connection, $_POST['item_code']);
        $item_name = mysqli_real_escape_string($connection, $_POST['item_name']);
        $description = mysqli_real_escape_string($connection, $_POST['description']);
        $category_id = intval($_POST['category_id']);
        $unit_of_measure = mysqli_real_escape_string($connection, $_POST['unit_of_measure']);
        $current_stock = intval($_POST['current_stock']);
        $minimum_stock_level = intval($_POST['minimum_stock_level']);
        $reorder_level = intval($_POST['reorder_level']);
        $unit_cost = floatval($_POST['unit_cost']);
        $is_critical = isset($_POST['is_critical']) ? 1 : 0;
        $is_perishable = isset($_POST['is_perishable']) ? 1 : 0;
        $shelf_life_days = intval($_POST['shelf_life_days']);
        
        $query = "INSERT INTO inventory_items (item_code, item_name, description, category_id, unit_of_measure, 
                  current_stock, minimum_stock_level, reorder_level, unit_cost, is_critical, is_perishable, 
                  shelf_life_days, created_by) VALUES ('$item_code', '$item_name', '$description', $category_id, 
                  '$unit_of_measure', $current_stock, $minimum_stock_level, $reorder_level, $unit_cost, 
                  $is_critical, $is_perishable, $shelf_life_days, " . $_SESSION['user_id'] . ")";
        
        if (mysqli_query($connection, $query)) {
            $message = "Item added successfully!";
        } else {
            $error = "Error adding item: " . mysqli_error($connection);
        }
    }
    
    if (isset($_POST['update_stock'])) {
        $item_id = intval($_POST['item_id']);
        $movement_type = mysqli_real_escape_string($connection, $_POST['movement_type']);
        $quantity = intval($_POST['quantity']);
        $notes = mysqli_real_escape_string($connection, $_POST['notes']);
        
        // Update stock based on movement type
        if ($movement_type == 'Purchase' || $movement_type == 'Transfer In') {
            $update_query = "UPDATE inventory_items SET current_stock = current_stock + $quantity WHERE item_id = $item_id";
        } else {
            $update_query = "UPDATE inventory_items SET current_stock = GREATEST(0, current_stock - $quantity) WHERE item_id = $item_id";
        }
        
        if (mysqli_query($connection, $update_query)) {
            // Record the movement
            $movement_query = "INSERT INTO stock_movements (item_id, movement_type, quantity, notes, created_by) 
                              VALUES ($item_id, '$movement_type', $quantity, '$notes', " . $_SESSION['user_id'] . ")";
            mysqli_query($connection, $movement_query);
            $message = "Stock updated successfully!";
        } else {
            $error = "Error updating stock: " . mysqli_error($connection);
        }
    }
}

// Get inventory data
$inventory_query = "SELECT i.*, c.category_name, 
                   CASE WHEN i.current_stock <= i.reorder_level THEN 'Low Stock' 
                        WHEN i.current_stock <= i.minimum_stock_level THEN 'Critical' 
                        ELSE 'Normal' END as stock_status
                   FROM inventory_items i 
                   LEFT JOIN inventory_categories c ON i.category_id = c.category_id 
                   WHERE i.is_active = TRUE 
                   ORDER BY i.item_name ASC";
$inventory_result = mysqli_query($connection, $inventory_query);

// Get categories for dropdown
$categories_query = "SELECT * FROM inventory_categories WHERE is_active = TRUE ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);

// Get low stock notifications
$notifications_query = "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock <= reorder_level AND is_active = TRUE";
$notifications_result = mysqli_query($connection, $notifications_query);
$low_stock_count = mysqli_fetch_assoc($notifications_result)['count'];

// Get expiring items (within 30 days)
$expiring_query = "SELECT COUNT(*) as count FROM inventory_batches WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND quantity_remaining > 0";
$expiring_result = mysqli_query($connection, $expiring_query);
$expiring_count = mysqli_fetch_assoc($expiring_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Inventory Management</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>">
    <style>
        .stock-critical { background-color: #ffebee; }
        .stock-low { background-color: #fff3e0; }
        .stock-normal { background-color: #e8f5e8; }
        .notification-badge {
            position: relative;
            display: inline-block;
        }
        .notification-badge .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-6 col-6">
                        <h4 class="page-title">Hospital Inventory Management</h4>
                    </div>
                    <div class="col-sm-6 col-6 text-right">
                        <button class="btn btn-primary btn-rounded" data-toggle="modal" data-target="#addItemModal">
                            <i class="fa fa-plus"></i> Add New Item
                        </button>
                        <a href="<?php echo hms_url('modules/admin/inventory-reports.php'); ?>" class="btn btn-info btn-rounded ml-2">
                            <i class="fa fa-chart-bar"></i> Reports
                        </a>
                    </div>
                </div>

                <!-- Notifications -->
                <?php if ($low_stock_count > 0 || $expiring_count > 0): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>Inventory Alerts:</strong>
                            <?php if ($low_stock_count > 0): ?>
                                <span class="notification-badge">
                                    <i class="fa fa-exclamation-triangle"></i> <?php echo $low_stock_count; ?> items are low on stock
                                </span>
                            <?php endif; ?>
                            <?php if ($expiring_count > 0): ?>
                                <span class="notification-badge ml-3">
                                    <i class="fa fa-clock-o"></i> <?php echo $expiring_count; ?> items are expiring soon
                                </span>
                            <?php endif; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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

                <!-- Quick Stats -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-primary"><?php 
                                    $total_items_query = "SELECT COUNT(*) as count FROM inventory_items WHERE is_active = TRUE";
                                    $total_items_result = mysqli_query($connection, $total_items_query);
                                    echo mysqli_fetch_assoc($total_items_result)['count'];
                                ?></h3>
                                <p>Total Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-warning"><?php echo $low_stock_count; ?></h3>
                                <p>Low Stock Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-danger"><?php echo $expiring_count; ?></h3>
                                <p>Expiring Soon</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-success"><?php 
                                    $total_value_query = "SELECT SUM(current_stock * unit_cost) as total FROM inventory_items WHERE is_active = TRUE";
                                    $total_value_result = mysqli_query($connection, $total_value_query);
                                    $total_value = mysqli_fetch_assoc($total_value_result)['total'];
                                    echo '$' . number_format($total_value, 2);
                                ?></h3>
                                <p>Total Value</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card-box">
                            <div class="table-responsive">
                                <table class="table table-striped custom-table" id="inventoryTable">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Category</th>
                                            <th>Current Stock</th>
                                            <th>Min Level</th>
                                            <th>Unit Cost</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($inventory_result)): ?>
                                        <tr class="stock-<?php echo strtolower(str_replace(' ', '-', $row['stock_status'])); ?>">
                                            <td><strong><?php echo htmlspecialchars($row['item_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $row['current_stock'] <= $row['minimum_stock_level'] ? 'badge-danger' : 
                                                         ($row['current_stock'] <= $row['reorder_level'] ? 'badge-warning' : 'badge-success'); 
                                                ?>">
                                                    <?php echo $row['current_stock']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['minimum_stock_level']; ?></td>
                                            <td>$<?php echo number_format($row['unit_cost'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $row['stock_status'] == 'Critical' ? 'badge-danger' : 
                                                         ($row['stock_status'] == 'Low Stock' ? 'badge-warning' : 'badge-success'); 
                                                ?>">
                                                    <?php echo $row['stock_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="openStockModal('Purchase', <?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['item_name'])); ?>')">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                                <button class="btn btn-warning btn-sm" onclick="openStockModal('Sale', <?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['item_name'])); ?>')">
                                                    <i class="fa fa-minus"></i> Remove
                                                </button>
                                                <a href="item-details.php?id=<?php echo $row['item_id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
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

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" action="<?php echo hms_url('modules/admin/hospital-inventory.php'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Inventory Item</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Item Code *</label>
                                    <input type="text" name="item_code" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Item Name *</label>
                                    <input type="text" name="item_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category *</label>
                                    <select name="category_id" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Unit of Measure</label>
                                    <select name="unit_of_measure" class="form-control">
                                        <option value="pieces">Pieces</option>
                                        <option value="boxes">Boxes</option>
                                        <option value="bottles">Bottles</option>
                                        <option value="liters">Liters</option>
                                        <option value="kg">Kilograms</option>
                                        <option value="grams">Grams</option>
                                        <option value="units">Units</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Initial Stock</label>
                                    <input type="number" name="current_stock" class="form-control" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Minimum Stock Level</label>
                                    <input type="number" name="minimum_stock_level" class="form-control" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Reorder Level</label>
                                    <input type="number" name="reorder_level" class="form-control" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Unit Cost</label>
                                    <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Shelf Life (Days)</label>
                                    <input type="number" name="shelf_life_days" class="form-control" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_critical" class="form-check-input" id="is_critical">
                                    <label class="form-check-label" for="is_critical">Critical Item</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_perishable" class="form-check-input" id="is_perishable">
                                    <label class="form-check-label" for="is_perishable">Perishable Item</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Update Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="<?php echo hms_url('modules/admin/hospital-inventory.php'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStockModalTitle">Update Stock</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Item: <strong id="modalItemName"></strong></p>
                        <input type="hidden" name="item_id" id="modalItemId">
                        <input type="hidden" name="movement_type" id="modalMovementType">
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" class="form-control" required min="1">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_stock" class="btn btn-primary" id="updateStockSubmitButton">Update Stock</button>
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
            $('#inventoryTable').DataTable({
                "pageLength": 25,
                "order": [[ 0, "asc" ]]
            });
        });

        function openStockModal(movementType, itemId, itemName) {
            document.getElementById('modalItemId').value = itemId;
            document.getElementById('modalMovementType').value = movementType;
            document.getElementById('modalItemName').innerText = itemName;
            document.getElementById('updateStockModalTitle').innerText = movementType + ' Stock';
            document.getElementById('updateStockSubmitButton').innerText = movementType + ' Stock';
            $('#updateStockModal').modal('show');
        }
    </script>
</body>
</html>
