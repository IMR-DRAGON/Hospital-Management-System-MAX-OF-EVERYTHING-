<?php
require_once __DIR__ . '/includes/init.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$logged_in_role = (int)$_SESSION['role'];
$is_patient = ($logged_in_role === 3);
$is_admin = ($logged_in_role === 1);
$patient_id = 0;
$filter_patient_id = 0;

if ($is_patient) {
    $patient_id = (int)$_SESSION['user_id'];
} elseif ($is_admin) {
    if (isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
        $filter_patient_id = (int)$_GET['patient_id'];
    }
} else {
    if (isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
        $filter_patient_id = (int)$_GET['patient_id'];
    } else {
        header('Location: ' . hms_url('modules/shared/patients.php'));
        exit();
    }
}

if ($is_admin) {
    $where = "a.doctor_id IS NULL";
    if ($filter_patient_id > 0) {
        $where .= " AND a.patient_id = $filter_patient_id";
    }
    $sql = "SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name
            FROM tbl_attachment a
            JOIN tbl_patient p ON a.patient_id = p.id
            WHERE $where
            ORDER BY a.uploaded_at DESC";
} else {
    $view_patient_id = $is_patient ? $patient_id : $filter_patient_id;
    $sql = "SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name
            FROM tbl_attachment a
            JOIN tbl_patient p ON a.patient_id = p.id
            WHERE a.patient_id = $view_patient_id AND a.doctor_id IS NULL
            ORDER BY a.uploaded_at DESC";
}
$attachments_result = mysqli_query($connection, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Attachments - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
</head>
<body>
    <div class="main-wrapper">
        <?php include hms_path('includes/header.php'); ?>

        <div class="page-wrapper">
            <div class="content">
                <div class="row mb-3">
                    <div class="col-sm-8">
                        <h4 class="page-title">
                            <?php echo $is_admin ? 'All Patient Attachments' : 'My Medical Documents'; ?>
                        </h4>
                        <?php if ($is_admin && $filter_patient_id > 0): ?>
                            <p class="text-muted mb-0">Showing attachments for patient #<?php echo $filter_patient_id; ?>
                                <a href="<?php echo hms_url('modules/shared/patient-attachments.php'); ?>">View all patients</a>
                            </p>
                        <?php elseif ($is_admin): ?>
                            <p class="text-muted mb-0">All documents uploaded by patients across the hospital.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_admin): ?>
                    <div class="col-sm-4 text-right">
                        <a href="<?php echo hms_url('modules/shared/patients.php'); ?>" class="btn btn-secondary btn-rounded">
                            <i class="fa fa-wheelchair"></i> Patients List
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_patient): ?>
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Upload New Document</h5>

                                <?php if (isset($_SESSION['upload_status'])): ?>
                                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['upload_status']['type']); ?>">
                                        <?php echo htmlspecialchars($_SESSION['upload_status']['message']); ?>
                                    </div>
                                <?php unset($_SESSION['upload_status']); endif; ?>

                                <form action="<?php echo hms_url('modules/patient/upload-attachment.php'); ?>" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Description (e.g., Old prescription, X-Ray report)</label>
                                        <textarea name="doc_desc" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Select File</label>
                                        <input type="file" name="doc_file" class="form-control-file" required>
                                        <small class="form-text text-muted">Allowed formats: PDF, JPG, PNG. Max size: 10MB.</small>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" name="submit_attachment" class="btn btn-primary">Upload Document</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo $is_admin ? 'All Uploaded Files' : 'My Uploaded Files'; ?>
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <?php if ($is_admin): ?>
                                                <th>Admit ID</th>
                                                <th>Patient Name</th>
                                                <?php endif; ?>
                                                <th>File Name</th>
                                                <th>Description</th>
                                                <th>Uploaded On</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($attachments_result && mysqli_num_rows($attachments_result) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($attachments_result)): ?>
                                                <tr>
                                                    <?php if ($is_admin): ?>
                                                    <td>#<?php echo (int)$row['patient_id']; ?></td>
                                                    <td>
                                                        <a href="<?php echo hms_url('modules/shared/patient-attachments.php'); ?>"?patient_id=<?php echo (int)$row['patient_id']; ?>">
                                                            <?php echo htmlspecialchars(trim($row['patient_name'])); ?>
                                                        </a>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['description'] ?: '-'); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?></td>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fa fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="<?php echo $is_admin ? '6' : '4'; ?>" class="text-center text-muted">
                                                        No attachments found.
                                                    </td>
                                                </tr>
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
