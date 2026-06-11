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

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_read'])) {
        $notification_id = intval($_POST['notification_id']);
        $query = "UPDATE inventory_notifications SET is_read = TRUE, read_at = NOW() WHERE notification_id = $notification_id";
        mysqli_query($connection, $query);
        $message = "Notification marked as read.";
    }
    
    if (isset($_POST['mark_all_read'])) {
        $query = "UPDATE inventory_notifications SET is_read = TRUE, read_at = NOW() WHERE is_read = FALSE";
        mysqli_query($connection, $query);
        $message = "All notifications marked as read.";
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = intval($_POST['notification_id']);
        $query = "DELETE FROM inventory_notifications WHERE notification_id = $notification_id";
        mysqli_query($connection, $query);
        $message = "Notification deleted.";
    }
}

// Get notifications
$notifications_query = "SELECT 
    n.notification_id, n.notification_type, n.message, n.priority, n.is_read, n.created_at, n.read_at,
    i.item_code, i.item_name
    FROM inventory_notifications n
    LEFT JOIN inventory_items i ON n.item_id = i.item_id
    ORDER BY n.created_at DESC";

$notifications_result = mysqli_query($connection, $notifications_query);

// Get notification counts
$unread_count_query = "SELECT COUNT(*) as count FROM inventory_notifications WHERE is_read = FALSE";
$unread_count_result = mysqli_query($connection, $unread_count_query);
$unread_count = mysqli_fetch_assoc($unread_count_result)['count'];

$critical_count_query = "SELECT COUNT(*) as count FROM inventory_notifications WHERE priority = 'Critical' AND is_read = FALSE";
$critical_count_result = mysqli_query($connection, $critical_count_query);
$critical_count = mysqli_fetch_assoc($critical_count_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Notifications</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>">
    <style>
        .notification-card {
            border-left: 4px solid #007bff;
            margin-bottom: 10px;
        }
        .notification-unread {
            background-color: #f8f9fa;
            border-left-color: #dc3545;
        }
        .notification-critical {
            border-left-color: #dc3545;
            background-color: #fff5f5;
        }
        .notification-high {
            border-left-color: #ffc107;
            background-color: #fffbf0;
        }
        .notification-medium {
            border-left-color: #17a2b8;
            background-color: #f0f8ff;
        }
        .notification-low {
            border-left-color: #28a745;
            background-color: #f0fff4;
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
                        <h4 class="page-title">Inventory Notifications</h4>
                    </div>
                    <div class="col-sm-6 col-6 text-right">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="btn btn-success btn-rounded">
                                <i class="fa fa-check"></i> Mark All Read
                            </button>
                        </form>
                        <a href="<?php echo hms_url('modules/admin/hospital-inventory.php'); ?>" class="btn btn-secondary btn-rounded ml-2">
                            <i class="fa fa-arrow-left"></i> Back to Inventory
                        </a>
                    </div>
                </div>

                <!-- Notification Summary -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-primary"><?php echo $unread_count; ?></h3>
                                <p>Unread Notifications</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-danger"><?php echo $critical_count; ?></h3>
                                <p>Critical Alerts</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-warning"><?php 
                                    $low_stock_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock <= reorder_level AND is_active = TRUE"))['count'];
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
                                    $expiring_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM inventory_batches WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND quantity_remaining > 0"))['count'];
                                    echo $expiring_count;
                                ?></h3>
                                <p>Expiring Items</p>
                            </div>
                        </div>
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

                <!-- Notifications List -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">All Notifications</h4>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                                    <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                                    <div class="card notification-card <?php 
                                        echo $notification['is_read'] ? '' : 'notification-unread';
                                        echo ' notification-' . strtolower($notification['priority']);
                                    ?>">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6 class="card-title">
                                                        <i class="fa fa-<?php 
                                                            echo $notification['notification_type'] == 'Low Stock' ? 'exclamation-triangle' : 
                                                                 ($notification['notification_type'] == 'Expiry Warning' ? 'clock-o' : 'info-circle'); 
                                                        ?>"></i>
                                                        <?php echo $notification['notification_type']; ?>
                                                        <?php if (!$notification['is_read']): ?>
                                                            <span class="badge badge-danger">New</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="card-text"><?php echo $notification['message']; ?></p>
                                                    <small class="text-muted">
                                                        <i class="fa fa-clock-o"></i> <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                                        <?php if ($notification['read_at']): ?>
                                                            | <i class="fa fa-check"></i> Read: <?php echo date('M d, Y H:i', strtotime($notification['read_at'])); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-4 text-right">
                                                    <span class="badge <?php 
                                                        echo $notification['priority'] == 'Critical' ? 'badge-danger' : 
                                                             ($notification['priority'] == 'High' ? 'badge-warning' : 
                                                             ($notification['priority'] == 'Medium' ? 'badge-info' : 'badge-success')); 
                                                    ?>">
                                                        <?php echo $notification['priority']; ?>
                                                    </span>
                                                    <div class="mt-2">
                                                        <?php if (!$notification['is_read']): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                            <button type="submit" name="mark_read" class="btn btn-sm btn-success">
                                                                <i class="fa fa-check"></i> Mark Read
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                                            <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                            <button type="submit" name="delete_notification" class="btn btn-sm btn-danger">
                                                                <i class="fa fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No notifications found.
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
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
