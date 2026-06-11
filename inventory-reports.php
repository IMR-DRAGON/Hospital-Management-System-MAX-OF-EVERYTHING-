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

// Get report parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Get categories for filter
$categories_query = "SELECT * FROM inventory_categories WHERE is_active = TRUE ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);

// Generate reports based on type
switch ($report_type) {
    case 'low_stock':
        $report_query = "SELECT 
            i.item_code, i.item_name, i.current_stock, i.minimum_stock_level, i.reorder_level,
            c.category_name, i.unit_cost, (i.current_stock * i.unit_cost) as total_value
            FROM inventory_items i
            LEFT JOIN inventory_categories c ON i.category_id = c.category_id
            WHERE i.current_stock <= i.reorder_level AND i.is_active = TRUE
            ORDER BY (i.current_stock - i.minimum_stock_level) ASC";
        $report_title = "Low Stock Report";
        break;
        
    case 'expiring':
        $report_query = "SELECT 
            b.batch_number, b.expiry_date, b.quantity_remaining, b.unit_cost,
            i.item_code, i.item_name, c.category_name,
            DATEDIFF(b.expiry_date, CURRENT_DATE) as days_until_expiry
            FROM inventory_batches b
            JOIN inventory_items i ON b.item_id = i.item_id
            LEFT JOIN inventory_categories c ON i.category_id = c.category_id
            WHERE b.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) 
            AND b.quantity_remaining > 0 AND b.is_active = TRUE
            ORDER BY b.expiry_date ASC";
        $report_title = "Expiring Items Report";
        break;
        
    case 'movements':
        $report_query = "SELECT 
            sm.movement_type, sm.quantity, sm.unit_cost, sm.total_cost, sm.movement_date, sm.notes,
            i.item_code, i.item_name, c.category_name, u.name as created_by
            FROM stock_movements sm
            JOIN inventory_items i ON sm.item_id = i.item_id
            LEFT JOIN inventory_categories c ON i.category_id = c.category_id
            LEFT JOIN users u ON sm.created_by = u.user_id
            WHERE DATE(sm.movement_date) BETWEEN '$date_from' AND '$date_to'
            ORDER BY sm.movement_date DESC";
        $report_title = "Stock Movements Report";
        break;
        
    case 'value':
        $report_query = "SELECT 
            c.category_name, COUNT(i.item_id) as item_count,
            SUM(i.current_stock) as total_stock,
            SUM(i.current_stock * i.unit_cost) as total_value,
            AVG(i.unit_cost) as avg_unit_cost
            FROM inventory_items i
            LEFT JOIN inventory_categories c ON i.category_id = c.category_id
            WHERE i.is_active = TRUE
            GROUP BY c.category_id, c.category_name
            ORDER BY total_value DESC";
        $report_title = "Inventory Value Report";
        break;
        
    default:
        $report_query = "SELECT 
            i.item_code, i.item_name, i.current_stock, i.minimum_stock_level, i.unit_cost,
            c.category_name, (i.current_stock * i.unit_cost) as total_value
            FROM inventory_items i
            LEFT JOIN inventory_categories c ON i.category_id = c.category_id
            WHERE i.is_active = TRUE
            ORDER BY i.item_name ASC";
        $report_title = "Inventory Summary Report";
}

$report_result = mysqli_query($connection, $report_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>">
    <style>
        .report-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .print-button {
            margin-bottom: 20px;
        }
        @media print {
            .no-print { display: none !important; }
            .report-card { border: 1px solid #ddd; }
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
                        <h4 class="page-title">Inventory Reports</h4>
                    </div>
                    <div class="col-sm-6 col-6 text-right">
                        <button class="btn btn-success print-button no-print" onclick="window.print()">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                        <a href="<?php echo hms_url('modules/admin/hospital-inventory.php'); ?>" class="btn btn-secondary btn-rounded ml-2 no-print">
                            <i class="fa fa-arrow-left"></i> Back to Inventory
                        </a>
                    </div>
                </div>

                <!-- Report Filters -->
                <div class="row no-print">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Report Filters</h4>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="<?php echo hms_url('modules/admin/inventory-reports.php'); ?>">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Report Type</label>
                                                <select name="report_type" class="form-control">
                                                    <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                                                    <option value="low_stock" <?php echo $report_type == 'low_stock' ? 'selected' : ''; ?>>Low Stock Report</option>
                                                    <option value="expiring" <?php echo $report_type == 'expiring' ? 'selected' : ''; ?>>Expiring Items Report</option>
                                                    <option value="movements" <?php echo $report_type == 'movements' ? 'selected' : ''; ?>>Stock Movements Report</option>
                                                    <option value="value" <?php echo $report_type == 'value' ? 'selected' : ''; ?>>Inventory Value Report</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Date From</label>
                                                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Date To</label>
                                                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Category</label>
                                                <select name="category_id" class="form-control">
                                                    <option value="0">All Categories</option>
                                                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Generate Report
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card report-card">
                            <div class="card-header">
                                <h4 class="card-title"><?php echo $report_title; ?></h4>
                                <p class="text-muted">Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($report_result) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="reportTable">
                                            <thead>
                                                <tr>
                                                    <?php 
                                                    // Get column headers based on report type
                                                    switch ($report_type) {
                                                        case 'low_stock':
                                                            echo '<th>Item Code</th><th>Item Name</th><th>Category</th><th>Current Stock</th><th>Minimum Level</th><th>Reorder Level</th><th>Unit Cost</th><th>Total Value</th>';
                                                            break;
                                                        case 'expiring':
                                                            echo '<th>Item Code</th><th>Item Name</th><th>Category</th><th>Batch Number</th><th>Expiry Date</th><th>Days Until Expiry</th><th>Remaining Qty</th><th>Unit Cost</th>';
                                                            break;
                                                        case 'movements':
                                                            echo '<th>Date</th><th>Item Code</th><th>Item Name</th><th>Category</th><th>Movement Type</th><th>Quantity</th><th>Unit Cost</th><th>Total Cost</th><th>Created By</th><th>Notes</th>';
                                                            break;
                                                        case 'value':
                                                            echo '<th>Category</th><th>Item Count</th><th>Total Stock</th><th>Total Value</th><th>Average Unit Cost</th>';
                                                            break;
                                                        default:
                                                            echo '<th>Item Code</th><th>Item Name</th><th>Category</th><th>Current Stock</th><th>Minimum Level</th><th>Unit Cost</th><th>Total Value</th>';
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($report_result)): ?>
                                                <tr>
                                                    <?php 
                                                    switch ($report_type) {
                                                        case 'low_stock':
                                                            echo '<td>' . $row['item_code'] . '</td>';
                                                            echo '<td>' . $row['item_name'] . '</td>';
                                                            echo '<td>' . $row['category_name'] . '</td>';
                                                            echo '<td><span class="badge badge-danger">' . $row['current_stock'] . '</span></td>';
                                                            echo '<td>' . $row['minimum_stock_level'] . '</td>';
                                                            echo '<td>' . $row['reorder_level'] . '</td>';
                                                            echo '<td>$' . number_format($row['unit_cost'], 2) . '</td>';
                                                            echo '<td>$' . number_format($row['total_value'], 2) . '</td>';
                                                            break;
                                                        case 'expiring':
                                                            echo '<td>' . $row['item_code'] . '</td>';
                                                            echo '<td>' . $row['item_name'] . '</td>';
                                                            echo '<td>' . $row['category_name'] . '</td>';
                                                            echo '<td>' . $row['batch_number'] . '</td>';
                                                            echo '<td>' . $row['expiry_date'] . '</td>';
                                                            $days_class = $row['days_until_expiry'] <= 7 ? 'badge-danger' : ($row['days_until_expiry'] <= 15 ? 'badge-warning' : 'badge-info');
                                                            echo '<td><span class="badge ' . $days_class . '">' . $row['days_until_expiry'] . ' days</span></td>';
                                                            echo '<td>' . $row['quantity_remaining'] . '</td>';
                                                            echo '<td>$' . number_format($row['unit_cost'], 2) . '</td>';
                                                            break;
                                                        case 'movements':
                                                            echo '<td>' . date('M d, Y', strtotime($row['movement_date'])) . '</td>';
                                                            echo '<td>' . $row['item_code'] . '</td>';
                                                            echo '<td>' . $row['item_name'] . '</td>';
                                                            echo '<td>' . $row['category_name'] . '</td>';
                                                            $type_class = $row['movement_type'] == 'Purchase' ? 'badge-success' : ($row['movement_type'] == 'Sale' ? 'badge-danger' : 'badge-info');
                                                            echo '<td><span class="badge ' . $type_class . '">' . $row['movement_type'] . '</span></td>';
                                                            echo '<td>' . $row['quantity'] . '</td>';
                                                            echo '<td>$' . number_format($row['unit_cost'], 2) . '</td>';
                                                            echo '<td>$' . number_format($row['total_cost'], 2) . '</td>';
                                                            echo '<td>' . $row['created_by'] . '</td>';
                                                            echo '<td>' . $row['notes'] . '</td>';
                                                            break;
                                                        case 'value':
                                                            echo '<td><strong>' . $row['category_name'] . '</strong></td>';
                                                            echo '<td>' . $row['item_count'] . '</td>';
                                                            echo '<td>' . number_format($row['total_stock']) . '</td>';
                                                            echo '<td>$' . number_format($row['total_value'], 2) . '</td>';
                                                            echo '<td>$' . number_format($row['avg_unit_cost'], 2) . '</td>';
                                                            break;
                                                        default:
                                                            echo '<td>' . $row['item_code'] . '</td>';
                                                            echo '<td>' . $row['item_name'] . '</td>';
                                                            echo '<td>' . $row['category_name'] . '</td>';
                                                            echo '<td>' . $row['current_stock'] . '</td>';
                                                            echo '<td>' . $row['minimum_stock_level'] . '</td>';
                                                            echo '<td>$' . number_format($row['unit_cost'], 2) . '</td>';
                                                            echo '<td>$' . number_format($row['total_value'], 2) . '</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No data found for the selected criteria.
                                    </div>
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
    <script src="<?php echo hms_url('assets/js/jquery.dataTables.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/dataTables.bootstrap4.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                "pageLength": 25,
                "order": [[ 0, "asc" ]],
                "dom": 'Bfrtip',
                "buttons": [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        });
    </script>
</body>
</html>
