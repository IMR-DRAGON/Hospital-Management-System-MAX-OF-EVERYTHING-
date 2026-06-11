<?php
require_once __DIR__ . '/includes/init.php';
// Automated notification system for hospital inventory
// This script should be run via cron job every hour or as needed
// Function to generate low stock notifications
function generateLowStockNotifications($connection) {
    $low_stock_query = "SELECT 
        i.item_id, i.item_code, i.item_name, i.current_stock, i.minimum_stock_level, 
        i.reorder_level, i.is_critical, c.category_name
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        WHERE i.current_stock <= i.reorder_level AND i.is_active = TRUE
        ORDER BY (i.current_stock - i.minimum_stock_level) ASC";
    
    $result = mysqli_query($connection, $low_stock_query);
    $notifications_created = 0;
    
    while ($item = mysqli_fetch_assoc($result)) {
        // Check if notification already exists for this item
        $existing_query = "SELECT COUNT(*) as count FROM inventory_notifications 
                          WHERE item_id = {$item['item_id']} AND notification_type = 'Low Stock' 
                          AND DATE(created_at) = CURDATE()";
        $existing_result = mysqli_query($connection, $existing_query);
        $existing_count = mysqli_fetch_assoc($existing_result)['count'];
        
        if ($existing_count == 0) {
            $priority = $item['is_critical'] ? 'Critical' : 'High';
            $message = "Item '{$item['item_name']}' is low on stock. Current: {$item['current_stock']}, Minimum: {$item['minimum_stock_level']}";
            
            $notification_query = "INSERT INTO inventory_notifications (item_id, notification_type, message, priority) 
                                  VALUES ({$item['item_id']}, 'Low Stock', '$message', '$priority')";
            
            if (mysqli_query($connection, $notification_query)) {
                $notifications_created++;
            }
        }
    }
    
    return $notifications_created;
}

// Function to generate expiry notifications
function generateExpiryNotifications($connection) {
    $expiring_query = "SELECT 
        b.batch_id, b.item_id, b.batch_number, b.expiry_date, b.quantity_remaining,
        i.item_code, i.item_name, i.is_critical, c.category_name
        FROM inventory_batches b
        JOIN inventory_items i ON b.item_id = i.item_id
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        WHERE b.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) 
        AND b.quantity_remaining > 0 AND b.is_active = TRUE
        ORDER BY b.expiry_date ASC";
    
    $result = mysqli_query($connection, $expiring_query);
    $notifications_created = 0;
    
    while ($item = mysqli_fetch_assoc($result)) {
        $days_until_expiry = ceil((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
        
        // Generate notifications at different intervals
        $notification_intervals = [30, 15, 7, 3, 1];
        
        if (in_array($days_until_expiry, $notification_intervals)) {
            // Check if notification already exists for this batch
            $existing_query = "SELECT COUNT(*) as count FROM inventory_notifications 
                              WHERE item_id = {$item['item_id']} AND notification_type = 'Expiry Warning' 
                              AND message LIKE '%Batch: {$item['batch_number']}%' 
                              AND DATE(created_at) = CURDATE()";
            $existing_result = mysqli_query($connection, $existing_query);
            $existing_count = mysqli_fetch_assoc($existing_result)['count'];
            
            if ($existing_count == 0) {
                $priority = $days_until_expiry <= 7 ? 'Critical' : ($days_until_expiry <= 15 ? 'High' : 'Medium');
                $message = "Item '{$item['item_name']}' (Batch: {$item['batch_number']}) expires in $days_until_expiry days on {$item['expiry_date']}";
                
                $notification_query = "INSERT INTO inventory_notifications (item_id, notification_type, message, priority) 
                                      VALUES ({$item['item_id']}, 'Expiry Warning', '$message', '$priority')";
                
                if (mysqli_query($connection, $notification_query)) {
                    $notifications_created++;
                }
            }
        }
    }
    
    return $notifications_created;
}

// Function to generate expired item notifications
function generateExpiredNotifications($connection) {
    $expired_query = "SELECT 
        b.batch_id, b.item_id, b.batch_number, b.expiry_date, b.quantity_remaining,
        i.item_code, i.item_name, i.is_critical, c.category_name
        FROM inventory_batches b
        JOIN inventory_items i ON b.item_id = i.item_id
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        WHERE b.expiry_date < CURRENT_DATE 
        AND b.quantity_remaining > 0 AND b.is_active = TRUE
        ORDER BY b.expiry_date ASC";
    
    $result = mysqli_query($connection, $expired_query);
    $notifications_created = 0;
    
    while ($item = mysqli_fetch_assoc($result)) {
        // Check if notification already exists for this batch
        $existing_query = "SELECT COUNT(*) as count FROM inventory_notifications 
                          WHERE item_id = {$item['item_id']} AND notification_type = 'Expired' 
                          AND message LIKE '%Batch: {$item['batch_number']}%' 
                          AND DATE(created_at) = CURDATE()";
        $existing_result = mysqli_query($connection, $existing_query);
        $existing_count = mysqli_fetch_assoc($existing_result)['count'];
        
        if ($existing_count == 0) {
            $days_expired = ceil((time() - strtotime($item['expiry_date'])) / (60 * 60 * 24));
            $message = "Item '{$item['item_name']}' (Batch: {$item['batch_number']}) has expired $days_expired days ago on {$item['expiry_date']}. Please dispose immediately.";
            
            $notification_query = "INSERT INTO inventory_notifications (item_id, notification_type, message, priority) 
                                  VALUES ({$item['item_id']}, 'Expired', '$message', 'Critical')";
            
            if (mysqli_query($connection, $notification_query)) {
                $notifications_created++;
            }
        }
    }
    
    return $notifications_created;
}

// Function to generate reorder notifications
function generateReorderNotifications($connection) {
    $reorder_query = "SELECT 
        i.item_id, i.item_code, i.item_name, i.current_stock, i.minimum_stock_level, 
        i.reorder_level, i.is_critical, c.category_name
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        WHERE i.current_stock <= i.minimum_stock_level AND i.is_active = TRUE
        ORDER BY (i.current_stock - i.minimum_stock_level) ASC";
    
    $result = mysqli_query($connection, $reorder_query);
    $notifications_created = 0;
    
    while ($item = mysqli_fetch_assoc($result)) {
        // Check if notification already exists for this item
        $existing_query = "SELECT COUNT(*) as count FROM inventory_notifications 
                          WHERE item_id = {$item['item_id']} AND notification_type = 'Reorder Required' 
                          AND DATE(created_at) = CURDATE()";
        $existing_result = mysqli_query($connection, $existing_query);
        $existing_count = mysqli_fetch_assoc($existing_result)['count'];
        
        if ($existing_count == 0) {
            $priority = $item['is_critical'] ? 'Critical' : 'High';
            $message = "URGENT: Item '{$item['item_name']}' requires immediate reorder. Current stock: {$item['current_stock']}, Minimum required: {$item['minimum_stock_level']}";
            
            $notification_query = "INSERT INTO inventory_notifications (item_id, notification_type, message, priority) 
                                  VALUES ({$item['item_id']}, 'Reorder Required', '$message', '$priority')";
            
            if (mysqli_query($connection, $notification_query)) {
                $notifications_created++;
            }
        }
    }
    
    return $notifications_created;
}

// Main execution
if (isset($_GET['run']) && $_GET['run'] == 'auto') {
    $total_notifications = 0;
    
    // Generate all types of notifications
    $total_notifications += generateLowStockNotifications($connection);
    $total_notifications += generateExpiryNotifications($connection);
    $total_notifications += generateExpiredNotifications($connection);
    $total_notifications += generateReorderNotifications($connection);
    
    // Log the execution
    $log_query = "INSERT INTO inventory_reports (report_type, report_name, report_data, generated_by) 
                  VALUES ('Auto Notifications', 'Automated Notification Generation', 
                  '{\"notifications_created\": $total_notifications, \"execution_time\": \"" . date('Y-m-d H:i:s') . "\"}', 1)";
    mysqli_query($connection, $log_query);
    
    echo "Generated $total_notifications notifications at " . date('Y-m-d H:i:s');
} else {
    // Manual execution interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Automated Notifications</title>
        <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Automated Notification System</h4>
                        </div>
                        <div class="card-body">
                            <p>This system automatically generates notifications for:</p>
                            <ul>
                                <li>Low stock items</li>
                                <li>Items expiring soon</li>
                                <li>Expired items</li>
                                <li>Items requiring reorder</li>
                            </ul>
                            
                            <div class="mt-4">
                                <a href="?run=auto" class="btn btn-primary">
                                    <i class="fa fa-play"></i> Run Notification Generation
                                </a>
                                <a href="<?php echo hms_url('modules/admin/hospital-inventory.php'); ?>" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Inventory
                                </a>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Setup Cron Job</h5>
                                <p>To run this automatically, add this to your crontab:</p>
                                <code>0 * * * * /usr/bin/php <?php echo __FILE__; ?>?run=auto</code>
                                <p class="text-muted">This will run every hour.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
