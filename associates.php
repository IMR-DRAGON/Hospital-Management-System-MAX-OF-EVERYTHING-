<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$associate_type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$where_conditions = array();
if (!empty($associate_type_filter)) {
    $where_conditions[] = "associate_type = '" . mysqli_real_escape_string($connection, $associate_type_filter) . "'";
}
if (!empty($department_filter)) {
    $where_conditions[] = "department = '" . mysqli_real_escape_string($connection, $department_filter) . "'";
}
if (!empty($status_filter)) {
    $where_conditions[] = "status = '" . mysqli_real_escape_string($connection, $status_filter) . "'";
}

$where_clause = '';
if ($_SESSION['role'] == 2) {
    $doctor_id = (int)$_SESSION['user_id'];
    $where_conditions[] = "a.associate_id IN (SELECT associate_id FROM associate_assignments WHERE doctor_id = $doctor_id)";
}
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(' AND ', $where_conditions);
}

$associates_query = "SELECT a.*, 
                    COUNT(aa.assignment_id) as total_assignments,
                    COUNT(CASE WHEN aa.assignment_status = 'Active' THEN 1 END) as active_assignments,
                    COUNT(CASE WHEN aa.assignment_status = 'Completed' THEN 1 END) as completed_assignments,
                    AVG(aa.satisfaction_rating) as avg_satisfaction
                    FROM associates a
                    LEFT JOIN associate_assignments aa ON a.associate_id = aa.associate_id
                    $where_clause
                    GROUP BY a.associate_id
                    ORDER BY a.last_name, a.first_name";

$associates_result = mysqli_query($connection, $associates_query);

// Get associate types for filter
$types_query = "SELECT DISTINCT associate_type FROM associates ORDER BY associate_type";
$types_result = mysqli_query($connection, $types_query);

// Get departments for filter
$departments_query = "SELECT DISTINCT department FROM associates WHERE department IS NOT NULL ORDER BY department";
$departments_result = mysqli_query($connection, $departments_query);

// Get recent assignments
$recent_assignments_query = "SELECT aa.*, 
                            CONCAT(a.first_name, ' ', a.last_name) as associate_name,
                            CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                            CONCAT(p.first_name, ' ', p.last_name) as patient_name
                            FROM associate_assignments aa
                            JOIN associates a ON aa.associate_id = a.associate_id
                            LEFT JOIN tbl_employee d ON aa.doctor_id = d.id
                            LEFT JOIN tbl_patient p ON aa.patient_id = p.id";

if ($_SESSION['role'] == 2) {
    $doctor_id = (int)$_SESSION['user_id'];
    $recent_assignments_query .= " WHERE aa.doctor_id = $doctor_id";
}

$recent_assignments_query .= " ORDER BY aa.assignment_date DESC LIMIT 10";

$recent_assignments_result = mysqli_query($connection, $recent_assignments_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associates Management - HMS</title>
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
                        <h4 class="page-title">Associates Management</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <?php if($_SESSION['role'] == 1) { ?>
                        <a href="<?php echo hms_url('modules/admin/add-associate.php'); ?>" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-plus"></i> Add Associate
                        </a>
                        <?php } ?>
                        <a href="<?php echo hms_url('modules/associate/associate-dashboard.php'); ?>" class="btn btn-info btn-rounded float-right mr-2">
                            <i class="fa fa-tachometer"></i> Dashboard
                        </a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-primary"><?php 
                                    $total_associates_query = "SELECT COUNT(*) as count FROM associates WHERE status = 'Active'";
                                    $total_associates_result = mysqli_query($connection, $total_associates_query);
                                    $total_associates = mysqli_fetch_assoc($total_associates_result)['count'];
                                    echo $total_associates;
                                ?></h3>
                                <p>Active Associates</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-success"><?php 
                                    $active_assignments_query = "SELECT COUNT(*) as count FROM associate_assignments WHERE assignment_status = 'Active'";
                                    $active_assignments_result = mysqli_query($connection, $active_assignments_query);
                                    $active_assignments = mysqli_fetch_assoc($active_assignments_result)['count'];
                                    echo $active_assignments;
                                ?></h3>
                                <p>Active Assignments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-info"><?php 
                                    $completed_assignments_query = "SELECT COUNT(*) as count FROM associate_assignments WHERE assignment_status = 'Completed' AND assignment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                                    $completed_assignments_result = mysqli_query($connection, $completed_assignments_query);
                                    $completed_assignments = mysqli_fetch_assoc($completed_assignments_result)['count'];
                                    echo $completed_assignments;
                                ?></h3>
                                <p>Completed (30 days)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="text-warning"><?php 
                                    $avg_satisfaction_query = "SELECT AVG(satisfaction_rating) as avg_rating FROM associate_assignments WHERE satisfaction_rating IS NOT NULL AND assignment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                                    $avg_satisfaction_result = mysqli_query($connection, $avg_satisfaction_query);
                                    $avg_satisfaction = mysqli_fetch_assoc($avg_satisfaction_result)['avg_rating'];
                                    echo number_format($avg_satisfaction, 1);
                                ?></h3>
                                <p>Avg Satisfaction</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Filter Associates</h4>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Associate Type</label>
                                                <select class="form-control" name="type">
                                                    <option value="">All Types</option>
                                                    <?php while ($type = mysqli_fetch_assoc($types_result)): ?>
                                                    <option value="<?php echo $type['associate_type']; ?>" <?php echo ($associate_type_filter == $type['associate_type']) ? 'selected' : ''; ?>>
                                                        <?php echo $type['associate_type']; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <select class="form-control" name="department">
                                                    <option value="">All Departments</option>
                                                    <?php while ($dept = mysqli_fetch_assoc($departments_result)): ?>
                                                    <option value="<?php echo $dept['department']; ?>" <?php echo ($department_filter == $dept['department']) ? 'selected' : ''; ?>>
                                                        <?php echo $dept['department']; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select class="form-control" name="status">
                                                    <option value="">All Status</option>
                                                    <option value="Active" <?php echo ($status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="Inactive" <?php echo ($status_filter == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="On Leave" <?php echo ($status_filter == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                                                    <option value="Suspended" <?php echo ($status_filter == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <div>
                                                    <button type="submit" class="btn btn-primary">Filter</button>
                                                    <a href="<?php echo hms_url('modules/admin/associates.php'); ?>" class="btn btn-secondary">Clear</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Associates Table -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Associates</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped custom-table" id="associatesTable">
                                        <thead>
                                            <tr>
                                                <th>Associate Code</th>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Department</th>
                                                <th>Phone</th>
                                                <th>Email</th>
                                                <th>Assignments</th>
                                                <th>Performance</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($associates_result)): ?>
                                            <tr>
                                                <td><strong><?php echo $row['associate_code']; ?></strong></td>
                                                <td>
                                                    <?php echo $row['first_name'] . ' ' . $row['last_name']; ?><br>
                                                    <small class="text-muted"><?php echo $row['position']; ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $type_class = '';
                                                    switch($row['associate_type']) {
                                                        case 'Patient Coordinator': $type_class = 'badge-primary'; break;
                                                        case 'Medical Assistant': $type_class = 'badge-info'; break;
                                                        case 'Nurse Coordinator': $type_class = 'badge-success'; break;
                                                        case 'Case Manager': $type_class = 'badge-warning'; break;
                                                        case 'Patient Advocate': $type_class = 'badge-secondary'; break;
                                                        case 'Clinical Coordinator': $type_class = 'badge-dark'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $type_class; ?>"><?php echo $row['associate_type']; ?></span>
                                                </td>
                                                <td><?php echo $row['department']; ?></td>
                                                <td><?php echo $row['phone']; ?></td>
                                                <td><?php echo $row['email']; ?></td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $row['active_assignments']; ?> Active</span><br>
                                                    <small class="text-muted"><?php echo $row['completed_assignments']; ?> Completed</small>
                                                </td>
                                                <td>
                                                    <?php if ($row['performance_rating'] > 0): ?>
                                                        <span class="badge badge-success"><?php echo number_format($row['performance_rating'], 1); ?>/5.0</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No Rating</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($row['status']) {
                                                        case 'Active': $status_class = 'badge-success'; break;
                                                        case 'Inactive': $status_class = 'badge-secondary'; break;
                                                        case 'On Leave': $status_class = 'badge-warning'; break;
                                                        case 'Suspended': $status_class = 'badge-danger'; break;
                                                        case 'Terminated': $status_class = 'badge-dark'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="action-icon dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                            <i class="fa fa-ellipsis-v"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <a class="dropdown-item" href="associate-dashboard.php?id=<?php echo $row['associate_id']; ?>">
                                                                <i class="fa fa-eye m-r-5"></i> View Details
                                                            </a>
                                                            <a class="dropdown-item" href="associate-dashboard.php?id=<?php echo $row['associate_id']; ?>">
                                                                <i class="fa fa-tasks m-r-5"></i> View Assignments
                                                            </a>
                                                            <a class="dropdown-item" href="associate-dashboard.php?id=<?php echo $row['associate_id']; ?>">
                                                                <i class="fa fa-calendar m-r-5"></i> View Schedule
                                                            </a>
                                                            <?php if($_SESSION['role'] == 1) { ?>
                                                            <a class="dropdown-item" href="edit-associate.php?id=<?php echo $row['associate_id']; ?>">
                                                                <i class="fa fa-pencil m-r-5"></i> Edit
                                                            </a>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
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

                <!-- Recent Assignments -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Assignments</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Assignment ID</th>
                                                <th>Associate</th>
                                                <th>Doctor</th>
                                                <th>Patient</th>
                                                <th>Type</th>
                                                <th>Priority</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($recent_assignments_result)): ?>
                                            <tr>
                                                <td>#<?php echo $row['assignment_id']; ?></td>
                                                <td><?php echo $row['associate_name']; ?></td>
                                                <td><?php echo $row['doctor_name']; ?></td>
                                                <td><?php echo $row['patient_name']; ?></td>
                                                <td><?php echo $row['assignment_type']; ?></td>
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
                                                    <span class="badge <?php echo $priority_class; ?>"><?php echo $row['priority_level']; ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($row['assignment_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($row['assignment_status']) {
                                                        case 'Active': $status_class = 'badge-success'; break;
                                                        case 'Completed': $status_class = 'badge-info'; break;
                                                        case 'Cancelled': $status_class = 'badge-danger'; break;
                                                        case 'On Hold': $status_class = 'badge-warning'; break;
                                                        case 'Transferred': $status_class = 'badge-secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $row['assignment_status']; ?></span>
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
            $('#associatesTable').DataTable({
                "pageLength": 25,
                "order": [[ 1, "asc" ]]
            });
        });
    </script>
</body>
</html>
