<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

// Get inventory summary by category
$inventory_summary_query = "SELECT 
    c.category_name,
    COUNT(i.item_id) as total_items,
    SUM(i.current_stock) as total_stock,
    SUM(i.current_stock * i.unit_cost) as total_value,
    SUM(CASE WHEN i.current_stock <= i.reorder_level THEN 1 ELSE 0 END) as low_stock_items
FROM inventory_items i
JOIN inventory_categories c ON i.category_id = c.category_id
WHERE i.is_active = TRUE
GROUP BY c.category_name
ORDER BY total_value DESC";

$inventory_summary_result = mysqli_query($connection, $inventory_summary_query);

// Get chamber inventory summary
$chamber_summary_query = "SELECT 
    c.chamber_id, c.chamber_number, c.chamber_name, c.chamber_type,
    COUNT(ci.item_id) as total_items,
    SUM(ci.quantity_allocated) as total_quantity
FROM chambers c
LEFT JOIN chamber_inventory ci ON c.chamber_id = ci.chamber_id
WHERE c.chamber_status = 'Active'
GROUP BY c.chamber_id, c.chamber_number, c.chamber_name, c.chamber_type
ORDER BY c.chamber_type, c.chamber_number";

$chamber_summary_result = mysqli_query($connection, $chamber_summary_query);

// Get low stock alerts
$low_stock_query = "SELECT i.item_id, i.item_code, i.item_name, i.current_stock, i.minimum_stock_level, c.category_name
FROM inventory_items i
LEFT JOIN inventory_categories c ON i.category_id = c.category_id
WHERE i.current_stock <= i.reorder_level AND i.is_active = TRUE
ORDER BY (i.current_stock - i.minimum_stock_level) ASC
LIMIT 10";

$low_stock_result = mysqli_query($connection, $low_stock_query);

// Get recent stock movements
$recent_movements_query = "SELECT sm.movement_id, i.item_code, i.item_name, sm.movement_type, sm.quantity, sm.movement_date,
       c.chamber_number, sm.notes
FROM stock_movements sm
JOIN inventory_items i ON sm.item_id = i.item_id
LEFT JOIN chambers c ON sm.chamber_id = c.chamber_id
ORDER BY sm.movement_date DESC
LIMIT 10";

$recent_movements_result = mysqli_query($connection, $recent_movements_query);

// Get critical items
$critical_items_query = "SELECT i.item_code, i.item_name, i.current_stock, i.minimum_stock_level, c.category_name
FROM inventory_items i
LEFT JOIN inventory_categories c ON i.category_id = c.category_id
WHERE i.is_critical = TRUE AND i.is_active = TRUE
ORDER BY i.current_stock ASC
LIMIT 10";

$critical_items_result = mysqli_query($connection, $critical_items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>">
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-4 col-3">
                        <h4 class="page-title">Inventory Management</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="add-inventory-item.php" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-plus"></i> Add Item
                        </a>
                        <a href="add-chamber.php" class="btn btn-primary btn-rounded float-right mr-2">
                            <i class="fa fa-plus"></i> Add Chamber
                        </a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-primary"><?php 
                                    $total_items_query = "SELECT COUNT(*) as count FROM inventory_items WHERE is_active = TRUE";
                                    $total_items_result = mysqli_query($connection, $total_items_query);
                                    $total_items = mysqli_fetch_assoc($total_items_result)['count'];
                                    echo $total_items;
                                ?></h3>
                                <p>Total Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-success"><?php 
                                    $total_chambers_query = "SELECT COUNT(*) as count FROM chambers WHERE chamber_status = 'Active'";
                                    $total_chambers_result = mysqli_query($connection, $total_chambers_query);
                                    $total_chambers = mysqli_fetch_assoc($total_chambers_result)['count'];
                                    echo $total_chambers;
                                ?></h3>
                                <p>Active Chambers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-warning"><?php 
                                    $low_stock_query = "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock <= reorder_level AND is_active = TRUE";
                                    $low_stock_count_result = mysqli_query($connection, $low_stock_query);
                                    $low_stock_count = mysqli_fetch_assoc($low_stock_count_result)['count'];
                                    echo $low_stock_count;
                                ?></h3>
                                <p>Low Stock Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-info"><?php 
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

                <!-- Low Stock Alerts -->
                <?php if (mysqli_num_rows($low_stock_result) > 0): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h4 class="card-title text-white">Low Stock Alerts</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Current Stock</th>
                                                <th>Minimum Required</th>
                                                <th>Category</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($low_stock_result)): ?>
                                            <tr>
                                                <td><?php echo $row['item_code']; ?></td>
                                                <td><?php echo $row['item_name']; ?></td>
                                                <td><span class="badge badge-danger"><?php echo $row['current_stock']; ?></span></td>
                                                <td><?php echo $row['minimum_stock_level']; ?></td>
                                                <td><?php echo $row['category_name']; ?></td>
                                                <td>
                                                    <a href="reorder-item.php?id=<?php echo $row['item_id']; ?>" class="btn btn-sm btn-primary">Reorder</a>
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

                <!-- Inventory Summary by Category -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Inventory Summary by Category</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped custom-table">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Total Items</th>
                                                <th>Total Stock</th>
                                                <th>Total Value</th>
                                                <th>Low Stock</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($inventory_summary_result)): ?>
                                            <tr>
                                                <td><strong><?php echo $row['category_name']; ?></strong></td>
                                                <td><?php echo $row['total_items']; ?></td>
                                                <td><?php echo $row['total_stock']; ?></td>
                                                <td>$<?php echo number_format($row['total_value'], 2); ?></td>
                                                <td>
                                                    <?php if ($row['low_stock_items'] > 0): ?>
                                                        <span class="badge badge-warning"><?php echo $row['low_stock_items']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="inventory-items.php?category=<?php echo urlencode($row['category_name']); ?>" class="btn btn-sm btn-primary">View Items</a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chamber Summary -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Chamber Summary</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Chamber</th>
                                                <th>Type</th>
                                                <th>Items</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($chamber_summary_result)): ?>
                                            <tr>
                                                <td><?php echo $row['chamber_number']; ?></td>
                                                <td><?php echo $row['chamber_type']; ?></td>
                                                <td><span class="badge badge-info"><?php echo $row['total_items']; ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Stock Movements -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Stock Movements</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th>Qty</th>
                                                <th>Chamber</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($recent_movements_result)): ?>
                                            <tr>
                                                <td><?php echo $row['item_code']; ?></td>
                                                <td>
                                                    <?php
                                                    $type_class = '';
                                                    switch($row['movement_type']) {
                                                        case 'Purchase': $type_class = 'badge-success'; break;
                                                        case 'Sale': $type_class = 'badge-danger'; break;
                                                        case 'Transfer In': $type_class = 'badge-info'; break;
                                                        case 'Transfer Out': $type_class = 'badge-warning'; break;
                                                        default: $type_class = 'badge-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $type_class; ?>"><?php echo $row['movement_type']; ?></span>
                                                </td>
                                                <td><?php echo $row['quantity']; ?></td>
                                                <td><?php echo $row['chamber_number']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['movement_date'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Critical Items -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Critical Items</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Stock</th>
                                                <th>Category</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($critical_items_result)): ?>
                                            <tr>
                                                <td><?php echo $row['item_code']; ?></td>
                                                <td><?php echo $row['item_name']; ?></td>
                                                <td>
                                                    <?php if ($row['current_stock'] <= $row['minimum_stock_level']): ?>
                                                        <span class="badge badge-danger"><?php echo $row['current_stock']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success"><?php echo $row['current_stock']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row['category_name']; ?></td>
                                                <td>
                                                    <a href="item-details.php?id=<?php echo $row['item_id']; ?>" class="btn btn-sm btn-primary">View</a>
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

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-boxes fa-3x text-primary mb-3"></i>
                                <h5>Inventory Items</h5>
                                <p>Manage all inventory items and stock levels</p>
                                <a href="inventory-items.php" class="btn btn-primary">Manage Items</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-door-open fa-3x text-success mb-3"></i>
                                <h5>Chambers</h5>
                                <p>Manage hospital chambers and room assignments</p>
                                <a href="chambers.php" class="btn btn-primary">Manage Chambers</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-exchange-alt fa-3x text-warning mb-3"></i>
                                <h5>Stock Movements</h5>
                                <p>Track all inventory movements and transfers</p>
                                <a href="stock-movements.php" class="btn btn-primary">View Movements</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa fa-chart-bar fa-3x text-info mb-3"></i>
                                <h5>Reports</h5>
                                <p>Generate inventory reports and analytics</p>
                                <a href="<?php echo hms_url('modules/admin/inventory-reports.php'); ?>" class="btn btn-primary">View Reports</a>
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
    <script src="<?php echo hms_url('assets/js/jquery.dataTables.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/dataTables.bootstrap4.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
    <script>
        $(document).ready(function() {
            $('.table').DataTable({
                "pageLength": 10,
                "order": [[ 0, "asc" ]]
            });
        });
    </script>
</body>
</html>
