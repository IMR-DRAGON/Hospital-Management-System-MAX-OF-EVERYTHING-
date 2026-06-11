<?php
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Determine associate_id
if ($_SESSION['user_type'] === 'Associate') {
    $associate_id = intval($_SESSION['user_id']);
} elseif (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $associate_id = intval($_GET['id']);
} else {
    header('Location: associates.php');
    exit();
}

// Get associate information
$associate_result = mysqli_query($connection, "SELECT * FROM associates WHERE associate_id = $associate_id");
if (!$associate_result || mysqli_num_rows($associate_result) == 0) {
    header('Location: associates.php');
    exit();
}
$associate = mysqli_fetch_assoc($associate_result);

// Get associate's active assignments (Doctors they are assigned to)
$assignments_query = "SELECT aa.*,
                    CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name
                    FROM associate_assignments aa
                    JOIN tbl_employee d ON aa.doctor_id = d.id
                    LEFT JOIN tbl_patient p ON aa.patient_id = p.id
                    WHERE aa.associate_id = $associate_id AND aa.assignment_status = 'Active'
                    ORDER BY aa.priority_level DESC, aa.assignment_date";
$assignments_result = mysqli_query($connection, $assignments_query);

// Get recent tasks
$tasks_query = "SELECT * FROM associate_tasks 
                WHERE associate_id = $associate_id 
                ORDER BY due_date ASC, due_time ASC 
                LIMIT 10";
$tasks_result = mysqli_query($connection, $tasks_query);

// Salary calculations
$monthly_salary = floatval($associate['salary']);
// Find next payday (last day of the current month)
$next_payday = new DateTime('last day of this month');
$today = new DateTime();
$days_until_payday = $today->diff($next_payday)->days;

if ($days_until_payday === 0) {
    $payday_text = "Today!";
} else {
    $payday_text = "In " . $days_until_payday . " days (" . $next_payday->format('M d, Y') . ")";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associate Dashboard - <?php echo htmlspecialchars($associate['first_name'] . ' ' . $associate['last_name']); ?></title>
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
                    <div class="col-sm-6 col-6">
                        <h4 class="page-title">Dashboard: <?php echo htmlspecialchars($associate['first_name'] . ' ' . $associate['last_name']); ?></h4>
                    </div>
                    <?php if ($_SESSION['role'] == 1) : ?>
                    <div class="col-sm-6 col-6 text-right m-b-20">
                        <a href="add-task.php?associate_id=<?php echo $associate_id; ?>" class="btn btn-warning btn-rounded float-right ml-2">
                            <i class="fa fa-tasks"></i> Assign Task
                        </a>
                        <a href="add-assignment.php?associate_id=<?php echo $associate_id; ?>" class="btn btn-info btn-rounded float-right ml-2">
                            <i class="fa fa-user-md"></i> Assign Doctor
                        </a>
                        <a href="associates.php" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Salary & Paycheck Highlight -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow" style="border-left: 4px solid #009efb;">
                            <div class="card-body text-center">
                                <h3 class="text-primary" style="font-size: 32px;">$<?php echo number_format($monthly_salary, 2); ?></h3>
                                <p class="mb-0 font-weight-bold">Expected Net Paycheck</p>
                                <small class="text-muted">End of the month</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow" style="border-left: 4px solid #55ce63;">
                            <div class="card-body text-center">
                                <h3 class="text-success" style="font-size: 32px;"><i class="fa fa-calendar"></i> <?php echo $payday_text; ?></h3>
                                <p class="mb-0 font-weight-bold">Next Payday</p>
                                <small class="text-muted">Check will be issued by HR</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Doctors -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h4 class="card-title mb-0"><i class="fa fa-user-md text-info"></i> Assigned Doctors & Active Cases</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Doctor</th>
                                                <th>Patient</th>
                                                <th>Assignment Type</th>
                                                <th>Priority</th>
                                                <th>Assigned On</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($assignments_result) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($assignments_result)): ?>
                                                <tr>
                                                    <td><strong>Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($row['patient_name'] ?? 'General Assignment'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['assignment_type']); ?></td>
                                                    <td>
                                                        <?php
                                                        $priority_class = '';
                                                        switch($row['priority_level']) {
                                                            case 'Critical': $priority_class = 'badge-danger'; break;
                                                            case 'High': $priority_class = 'badge-warning'; break;
                                                            case 'Medium': $priority_class = 'badge-info'; break;
                                                            case 'Low': $priority_class = 'badge-secondary'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $priority_class; ?>"><?php echo htmlspecialchars($row['priority_level']); ?></span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($row['assignment_date'])); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center">No active doctor assignments found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Tasks -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h4 class="card-title mb-0"><i class="fa fa-tasks text-warning"></i> My Tasks</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($tasks_result) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($tasks_result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['task_title']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        switch($row['status']) {
                                                            case 'Completed': $status_class = 'badge-success'; break;
                                                            case 'In Progress': $status_class = 'badge-info'; break;
                                                            case 'Pending': $status_class = 'badge-warning'; break;
                                                            case 'Overdue': $status_class = 'badge-danger'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                                    </td>
                                                    <td style="width: 25%;">
                                                        <div class="progress" style="height: 15px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo intval($row['completion_percentage']); ?>%" aria-valuenow="<?php echo intval($row['completion_percentage']); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <small><?php echo intval($row['completion_percentage']); ?>% Complete</small>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center text-muted p-4">You have no tasks assigned right now.</td></tr>
                                            <?php endif; ?>
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
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
</body>
</html>
