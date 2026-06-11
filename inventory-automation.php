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

// Automated calculation functions
function calculateHospitalAmenities($connection) {
    // Calculate total amenities value and status
    $amenities_query = "SELECT 
        COUNT(*) as total_amenities,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_amenities,
        SUM(CASE WHEN status = 'Under Maintenance' THEN 1 ELSE 0 END) as maintenance_amenities,
        SUM(CASE WHEN status = 'Disposed' THEN 1 ELSE 0 END) as disposed_amenities
        FROM hospital_amenities";
    
    $result = mysqli_query($connection, $amenities_query);
    return mysqli_fetch_assoc($result);
}

function calculateInventoryValue($connection) {
    // Calculate total inventory value by category
    $value_query = "SELECT 
        c.category_name,
        COUNT(i.item_id) as item_count,
        SUM(i.current_stock) as total_stock,
        SUM(i.current_stock * i.unit_cost) as total_value,
        AVG(i.unit_cost) as avg_unit_cost
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        WHERE i.is_active = TRUE
        GROUP BY c.category_id, c.category_name
        ORDER BY total_value DESC";
    
    $result = mysqli_query($connection, $value_query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function checkLowStockItems($connection) {
    // Check for items that are low on stock
    $low_stock_query = "SELECT 
        i.item_id, i.item_code, i.item_name, i.current_stock, i.minimum_stock_level, 
        i.reorder_level, c.category_name, i.is_critical
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        WHERE i.current_stock <= i.reorder_level AND i.is_active = TRUE
        ORDER BY (i.current_stock - i.minimum_stock_level) ASC";
    
    $result = mysqli_query($connection, $low_stock_query);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    return $items;
}

function checkExpiringItems($connection) {
    // Check for items expiring within 30 days
    $expiring_query = "SELECT 
        b.batch_id, b.batch_number, b.expiry_date, b.quantity_remaining,
        i.item_code, i.item_name, i.is_critical, c.category_name
        FROM inventory_batches b
        JOIN inventory_items i ON b.item_id = i.item_id
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        WHERE b.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) 
        AND b.quantity_remaining > 0 AND b.is_active = TRUE
        ORDER BY b.expiry_date ASC";
    
    $result = mysqli_query($connection, $expiring_query);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    return $items;
}

function generateNotifications($connection) {
    // Generate notifications for low stock and expiring items
    $notifications = [];
    
    // Low stock notifications
    $low_stock_items = checkLowStockItems($connection);
    foreach ($low_stock_items as $item) {
        $priority = $item['is_critical'] ? 'Critical' : 'High';
        $message = "Item '{$item['item_name']}' is low on stock. Current: {$item['current_stock']}, Minimum: {$item['minimum_stock_level']}";
        
        $notification_query = "INSERT INTO inventory_notifications (item_id, notification_type, message, priority) 
                              VALUES ({$item['item_id']}, 'Low Stock', '$message', '$priority')";
        mysqli_query($connection, $notification_query);
        $notifications[] = $message;
    }
    
    // Expiring items notifications
    $expiring_items = checkExpiringItems($connection);
    foreach ($expiring_items as $item) {
        $days_until_expiry = ceil((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
        $priority = $days_until_expiry <= 7 ? 'Critical' : 'High';
        $message = "Item '{$item['item_name']}' (Batch: {$item['batch_number']}) expires in $days_until_expiry days on {$item['expiry_date']}";
        
        $notification_query = "INSERT INTO inventory_notifications (item_id, notification_type, message, priority) 
                              VALUES ({$item['item_id']}, 'Expiry Warning', '$message', '$priority')";
        mysqli_query($connection, $notification_query);
        $notifications[] = $message;
    }
    
    return $notifications;
}

// Get data for display
$amenities_data = calculateHospitalAmenities($connection);
$inventory_value_data = calculateInventoryValue($connection);
$low_stock_items = checkLowStockItems($connection);
$expiring_items = checkExpiringItems($connection);

// Generate notifications if requested
if (isset($_GET['generate_notifications'])) {
    $generated_notifications = generateNotifications($connection);
    $message = "Generated " . count($generated_notifications) . " notifications";
}

// Get recent notifications
$recent_notifications_query = "SELECT n.*, i.item_name 
                              FROM inventory_notifications n
                              LEFT JOIN inventory_items i ON n.item_id = i.item_id
                              ORDER BY n.created_at DESC 
                              LIMIT 20";
$recent_notifications_result = mysqli_query($connection, $recent_notifications_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Automation & Calculations</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>">
    <style>
        .calculation-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .alert-card {
            border-left: 4px solid #dc3545;
        }
        .warning-card {
            border-left: 4px solid #ffc107;
        }
        .success-card {
            border-left: 4px solid #28a745;
        }
        .chart-container {
            height: 300px;
            margin: 20px 0;
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
                        <h4 class="page-title">Inventory Automation & Calculations</h4>
                    </div>
                    <div class="col-sm-6 col-6 text-right">
                        <a href="?generate_notifications=1" class="btn btn-warning btn-rounded">
                            <i class="fa fa-bell"></i> Generate Notifications
                        </a>
                        <a href="<?php echo hms_url('modules/admin/hospital-inventory.php'); ?>" class="btn btn-primary btn-rounded ml-2">
                            <i class="fa fa-arrow-left"></i> Back to Inventory
                        </a>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Hospital Amenities Calculation -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card calculation-card">
                            <div class="card-header">
                                <h4 class="card-title">Hospital Amenities Status</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3 class="text-primary"><?php echo $amenities_data['total_amenities']; ?></h3>
                                            <p>Total Amenities</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3 class="text-success"><?php echo $amenities_data['active_amenities']; ?></h3>
                                            <p>Active</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3 class="text-warning"><?php echo $amenities_data['maintenance_amenities']; ?></h3>
                                            <p>Under Maintenance</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3 class="text-danger"><?php echo $amenities_data['disposed_amenities']; ?></h3>
                                            <p>Disposed</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Value Calculations -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card calculation-card">
                            <div class="card-header">
                                <h4 class="card-title">Inventory Value by Category</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Items</th>
                                                <th>Total Stock</th>
                                                <th>Total Value</th>
                                                <th>Avg Unit Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inventory_value_data as $category): ?>
                                            <tr>
                                                <td><strong><?php echo $category['category_name']; ?></strong></td>
                                                <td><?php echo $category['item_count']; ?></td>
                                                <td><?php echo number_format($category['total_stock']); ?></td>
                                                <td>$<?php echo number_format($category['total_value'], 2); ?></td>
                                                <td>$<?php echo number_format($category['avg_unit_cost'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Quick Actions</h4>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="add-inventory-item.php" class="list-group-item list-group-item-action">
                                        <i class="fa fa-plus"></i> Add New Item
                                    </a>
                                    <a href="<?php echo hms_url('modules/admin/inventory-reports.php'); ?>" class="list-group-item list-group-item-action">
                                        <i class="fa fa-chart-bar"></i> Generate Reports
                                    </a>
                                    <a href="stock-movements.php" class="list-group-item list-group-item-action">
                                        <i class="fa fa-exchange-alt"></i> View Stock Movements
                                    </a>
                                    <a href="<?php echo hms_url('modules/admin/expiry-management.php'); ?>" class="list-group-item list-group-item-action">
                                        <i class="fa fa-clock-o"></i> Expiry Management
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <?php if (count($low_stock_items) > 0): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card alert-card">
                            <div class="card-header">
                                <h4 class="card-title text-danger">Low Stock Alerts</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Category</th>
                                                <th>Current Stock</th>
                                                <th>Minimum Level</th>
                                                <th>Reorder Level</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock_items as $item): ?>
                                            <tr>
                                                <td><?php echo $item['item_code']; ?></td>
                                                <td><?php echo $item['item_name']; ?></td>
                                                <td><?php echo $item['category_name']; ?></td>
                                                <td><span class="badge badge-danger"><?php echo $item['current_stock']; ?></span></td>
                                                <td><?php echo $item['minimum_stock_level']; ?></td>
                                                <td><?php echo $item['reorder_level']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $item['is_critical'] ? 'badge-danger' : 'badge-warning'; ?>">
                                                        <?php echo $item['is_critical'] ? 'Critical' : 'Low Stock'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="reorder-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary">Reorder</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Expiring Items -->
                <?php if (count($expiring_items) > 0): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card warning-card">
                            <div class="card-header">
                                <h4 class="card-title text-warning">Items Expiring Soon</h4>
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
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($expiring_items as $item): ?>
                                            <?php 
                                            $days_until_expiry = ceil((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                                            $expiry_class = $days_until_expiry <= 7 ? 'badge-danger' : ($days_until_expiry <= 15 ? 'badge-warning' : 'badge-info');
                                            ?>
                                            <tr>
                                                <td><?php echo $item['item_code']; ?></td>
                                                <td><?php echo $item['item_name']; ?></td>
                                                <td><?php echo $item['batch_number']; ?></td>
                                                <td><?php echo $item['expiry_date']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $expiry_class; ?>">
                                                        <?php echo $days_until_expiry; ?> days
                                                    </span>
                                                </td>
                                                <td><?php echo $item['quantity_remaining']; ?></td>
                                                <td>
                                                    <a href="dispose-expired.php?id=<?php echo $item['batch_id']; ?>" class="btn btn-sm btn-warning">Dispose</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Notifications -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Notifications</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Item</th>
                                                <th>Message</th>
                                                <th>Priority</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($notification = mysqli_fetch_assoc($recent_notifications_result)): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $notification['notification_type']; ?></span>
                                                </td>
                                                <td><?php echo $notification['item_name']; ?></td>
                                                <td><?php echo $notification['message']; ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $notification['priority'] == 'Critical' ? 'badge-danger' : 
                                                             ($notification['priority'] == 'High' ? 'badge-warning' : 'badge-info'); 
                                                    ?>">
                                                        <?php echo $notification['priority']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($notification['is_read']): ?>
                                                        <span class="badge badge-success">Read</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Unread</span>
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
