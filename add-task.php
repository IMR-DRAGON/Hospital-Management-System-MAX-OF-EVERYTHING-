<?php
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ensure only admins can access this page
if ($_SESSION['user_type'] !== 'Admin' && $_SESSION['role'] != 1) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

$pre_associate_id = isset($_GET['associate_id']) ? intval($_GET['associate_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $associate_id     = intval($_POST['associate_id']);
    $task_title       = mysqli_real_escape_string($connection, trim($_POST['task_title']));
    $task_description = mysqli_real_escape_string($connection, trim($_POST['task_description']));
    $due_date         = mysqli_real_escape_string($connection, trim($_POST['due_date']));
    $due_time         = !empty($_POST['due_time']) ? "'" . mysqli_real_escape_string($connection, trim($_POST['due_time'])) . "'" : 'NULL';
    $status           = mysqli_real_escape_string($connection, trim($_POST['status']));

    if (empty($associate_id) || empty($task_title) || empty($due_date)) {
        $error = "Please fill in all required fields.";
    } else {
        $insert_query = "INSERT INTO associate_tasks 
            (associate_id, task_title, task_description, due_date, due_time, status)
            VALUES 
            ($associate_id, '$task_title', '$task_description', '$due_date', $due_time, '$status')";

        if (mysqli_query($connection, $insert_query)) {
            $message = "Task assigned successfully.";
            $pre_associate_id = $associate_id; // Keep selected
        } else {
            $error = "Database error: " . mysqli_error($connection);
        }
    }
}

// Fetch list of associates for the dropdown
$associates_result = mysqli_query($connection, "SELECT associate_id, first_name, last_name, associate_code FROM associates WHERE status='Active' ORDER BY first_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Task - HMS</title>
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
                    <div class="col-sm-4 col-3">
                        <h4 class="page-title">Assign Task to Associate</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('associates.php'); ?>" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-arrow-left"></i> Back to Associates
                        </a>
                    </div>
                </div>

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

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Task Details</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Assign To Associate <span class="text-danger">*</span></label>
                                                <select class="form-control" name="associate_id" required>
                                                    <option value="">Select Associate</option>
                                                    <?php while ($assoc = mysqli_fetch_assoc($associates_result)): ?>
                                                    <option value="<?php echo $assoc['associate_id']; ?>" <?php echo ($pre_associate_id == $assoc['associate_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($assoc['first_name'] . ' ' . $assoc['last_name'] . ' (' . $assoc['associate_code'] . ')'); ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Task Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="task_title" placeholder="e.g., File Medical Records" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Task Description</label>
                                                <textarea class="form-control" name="task_description" rows="4" placeholder="Enter detailed instructions for the associate..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Due Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="due_date" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Due Time</label>
                                                <input type="time" class="form-control" name="due_time">
                                                <small class="text-muted">Optional</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select class="form-control" name="status">
                                                    <option value="Pending" selected>Pending</option>
                                                    <option value="In Progress">In Progress</option>
                                                    <option value="Completed">Completed</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="m-t-20 text-center">
                                        <button class="btn btn-warning submit-btn" type="submit">
                                            <i class="fa fa-tasks"></i> Assign Task
                                        </button>
                                    </div>
                                </form>
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
