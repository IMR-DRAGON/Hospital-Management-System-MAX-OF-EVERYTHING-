<?php
require_once __DIR__ . '/includes/init.php';
require_once hms_path('includes/doctor_helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = intval($_SESSION['role']);
$is_doctor = ($user_role == 2);
$is_patient = ($user_role == 3);

if (!$is_doctor && !$is_patient) {
    header('Location: ' . hms_url('modules/shared/dashboard.php'));
    exit();
}

$patient_id = 0;

if ($is_doctor) {
    $patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

    if ($patient_id == 0) {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <?php include hms_path('includes/doctor_medical_records_dashboard.php'); ?>
            </div>
        </div>
    </div>
    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
</body>
</html>
        <?php
        exit();
    }
} elseif ($is_patient) {
    $patient_id = intval($user_id);
}

// --- Single patient record view ---

// Get patient information
$patient_query = "SELECT * FROM tbl_patient WHERE id = $patient_id";
$patient_result = mysqli_query($connection, $patient_query);

if (mysqli_num_rows($patient_result) == 0) {
    // No patient found with this ID
    header('Location: ' . hms_url('modules/shared/dashboard.php'));
    exit();
}
$patient_data = mysqli_fetch_assoc($patient_result);

// Get patient's medical records
$records_query = "SELECT * FROM patient_medical_records WHERE patient_id = $patient_id ORDER BY record_date DESC";
$records_result = mysqli_query($connection, $records_query);

// Get patient's prescriptions
$prescriptions_query = "SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) as doctor_name
                        FROM prescriptions p
                        LEFT JOIN tbl_employee e ON p.doctor_id = e.id
                        WHERE p.patient_id = $patient_id
                        ORDER BY p.prescribed_date DESC";
$prescriptions_result = mysqli_query($connection, $prescriptions_query);

// Get patient-uploaded documents
$patient_uploads_query = "SELECT * FROM tbl_attachment
                          WHERE patient_id = $patient_id
                          ORDER BY uploaded_at DESC";
$patient_uploads_result = mysqli_query($connection, $patient_uploads_query);

// Get chat files for this patient
$chat_files_query = "SELECT cf.*, m.created_at as upload_date, 
                       CONCAT(e.first_name, ' ', e.last_name) as uploaded_by_name
                       FROM chat_files cf
                       JOIN chat_messages m ON cf.message_id = m.message_id
                       JOIN chat_conversations c ON m.conversation_id = c.conversation_id
                       LEFT JOIN tbl_employee e ON cf.uploaded_by = e.employee_id
                       WHERE c.patient_id = $patient_id
                       ORDER BY cf.uploaded_at DESC";
$chat_files_result = mysqli_query($connection, $chat_files_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Medical Records - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/global-interactions.css'); ?>">
    <style>
        .patient-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .record-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .record-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .record-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            border-radius: 10px 10px 0 0;
        }
        
        .record-body {
            padding: 20px;
        }
        
        .file-preview {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            background-color: #f8f9fa;
        }
        
        .prescription-card {
            border-left: 4px solid #28a745;
            background-color: #f8fff8;
        }
        
        .lab-report-card {
            border-left: 4px solid #17a2b8;
            background-color: #f0f8ff;
        }
        
        .xray-card {
            border-left: 4px solid #ffc107;
            background-color: #fffbf0;
        }
        
        .other-card {
            border-left: 4px solid #6c757d;
            background-color: #f8f9fa;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #007bff;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #007bff;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #007bff;
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
                        <h4 class="page-title">Patient Medical Records</h4>
                    </div>
                    <div class="col-sm-6 col-6 text-right">
                        <?php if ($is_doctor): ?>
                        <a href="<?php echo hms_url('modules/doctor/doctor-prescriptions.php'); ?>"?search=<?php echo urlencode(trim($patient_data['first_name'] . ' ' . $patient_data['last_name'])); ?>" class="btn btn-success btn-rounded">
                            <i class="fa fa-file-text-o"></i> Add Prescription
                        </a>
                        <a href="<?php echo hms_url('modules/shared/patient-medical-records.php'); ?>" class="btn btn-secondary btn-rounded ml-2">
                            <i class="fa fa-arrow-left"></i> All Records
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="patient-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($patient_data['first_name'] . ' ' . $patient_data['last_name']); ?></h3>
                            <p class="mb-2">
                                <i class="fa fa-envelope"></i> <?php echo htmlspecialchars($patient_data['email']); ?> |
                                <i class="fa fa-phone"></i> <?php echo htmlspecialchars($patient_data['phone']); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fa fa-id-card"></i> Admit ID: #<?php echo (int)$patient_data['id']; ?> |
                                <i class="fa fa-calendar"></i> DOB: <?php echo date('M d, Y', strtotime($patient_data['dob'])); ?> |
                                <i class="fa fa-venus-mars"></i> <?php echo htmlspecialchars($patient_data['gender']); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="patient-avatar">
                                <i class="fa fa-user-circle fa-5x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Uploaded Documents -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa fa-paperclip"></i> Patient Uploaded Documents</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>Description</th>
                                                <th>Uploaded On</th>
                                                <th class="text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($patient_uploads_result && mysqli_num_rows($patient_uploads_result) > 0): ?>
                                                <?php while ($upload = mysqli_fetch_assoc($patient_uploads_result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($upload['file_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($upload['description']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($upload['uploaded_at'])); ?></td>
                                                    <td class="text-right">
                                                        <a href="<?php echo htmlspecialchars($upload['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fa fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No patient uploads yet.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medical Records Timeline -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fa fa-history"></i> Medical Records Timeline
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php while ($record = mysqli_fetch_assoc($records_result)): ?>
                                    <div class="timeline-item">
                                        <div class="record-card <?php 
                                            echo $record['record_type'] == 'Prescription' ? 'prescription-card' : 
                                                ($record['record_type'] == 'Lab_Report' ? 'lab-report-card' : 
                                                ($record['record_type'] == 'X_Ray' ? 'xray-card' : 'other-card')); 
                                        ?>">
                                            <div class="record-header">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="fa fa-<?php 
                                                                echo $record['record_type'] == 'Prescription' ? 'prescription' : 
                                                                    ($record['record_type'] == 'Lab_Report' ? 'flask' : 
                                                                    ($record['record_type'] == 'X_Ray' ? 'x-ray' : 'file')); 
                                                            ?>"></i>
                                                            <?php echo htmlspecialchars($record['record_title']); ?>
                                                        </h6>
                                                        <small class="text-muted"><?php echo $record['record_type']; ?></small>
                                                    </div>
                                                    <div class="text-right">
                                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="record-body">
                                                <?php if ($record['record_description']): ?>
                                                    <p><?php echo nl2br(htmlspecialchars($record['record_description'])); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if ($record['file_path'] && file_exists($record['file_path'])): ?>
                                                    <div class="file-preview">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="fa fa-file-o fa-2x text-primary"></i>
                                                                <span class="ml-2"><?php echo basename($record['file_path']); ?></span>
                                                            </div>
                                                            <div>
                                                                <a href="<?php echo $record['file_path']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                                    <i class="fa fa-eye"></i> View
                                                                </a>
                                                                <a href="<?php echo $record['file_path']; ?>" download class="btn btn-sm btn-success">
                                                                    <i class="fa fa-download"></i> Download
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prescriptions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fa fa-prescription"></i> Prescriptions
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($prescriptions_result && mysqli_num_rows($prescriptions_result) > 0): ?>
                                <?php while ($prescription = mysqli_fetch_assoc($prescriptions_result)): ?>
                                <div class="prescription-card record-card">
                                    <div class="record-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fa fa-prescription"></i>
                                                    Prescription #<?php echo $prescription['prescription_id']; ?>
                                                </h6>
                                                <small class="text-muted">Prescribed by: <?php echo $prescription['doctor_name']; ?></small>
                                            </div>
                                            <div class="text-right">
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($prescription['prescribed_date'])); ?></small>
                                                <span class="badge badge-<?php 
                                                    echo $prescription['status'] == 'Active' ? 'success' : 
                                                        ($prescription['status'] == 'Completed' ? 'info' : 'warning'); 
                                                ?> ml-2"><?php echo $prescription['status']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="record-body">
                                        <div class="prescription-content">
                                            <?php echo nl2br(htmlspecialchars($prescription['prescription_text'])); ?>
                                        </div>
                                        <?php if ($prescription['prescription_file']): ?>
                                            <div class="file-preview mt-3">
                                                <a href="<?php echo htmlspecialchars($prescription['prescription_file']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-file-pdf-o"></i> View Prescription File
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($is_doctor && (int)$prescription['doctor_id'] === (int)$user_id): ?>
                                            <div class="mt-3">
                                                <a href="<?php echo hms_url('modules/doctor/doctor-prescriptions.php'); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-pencil"></i> Edit Prescription
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <p class="text-muted mb-0">No prescriptions yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Files -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fa fa-cloud-upload"></i> Files Shared in Chat
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>Category</th>
                                                <th>Size</th>
                                                <th>Uploaded By</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($file = mysqli_fetch_assoc($chat_files_result)): ?>
                                            <tr>
                                                <td>
                                                    <i class="fa fa-file-o"></i>
                                                    <?php echo htmlspecialchars($file['file_name']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $file['file_category']; ?></span>
                                                </td>
                                                <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo $file['uploaded_by_name']; ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($file['upload_date'])); ?></td>
                                                <td>
                                                    <a href="<?php echo $file['file_path']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo $file['file_path']; ?>" download class="btn btn-sm btn-success">
                                                        <i class="fa fa-download"></i>
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

                <?php if ($is_doctor): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fa fa-plus-circle"></i> Add New Medical Record
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['record_upload_status'])): ?>
                                    <div class="alert alert-<?php echo $_SESSION['record_upload_status']['type']; ?>">
                                        <?php echo htmlspecialchars($_SESSION['record_upload_status']['message']); ?>
                                    </div>
                                <?php unset($_SESSION['record_upload_status']); endif; ?>

                                <form action="<?php echo hms_url('modules/doctor/upload-medical-record.php'); ?>" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                                    <div class="form-group">
                                        <label>Record Title</label>
                                        <input type="text" name="record_title" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Record Type</label>
                                        <select name="record_type" class="form-control" required>
                                            <option value="Prescription">Prescription</option>
                                            <option value="Lab_Report">Lab Report</option>
                                            <option value="X_Ray">X-Ray</option>
                                            <option value="MRI">MRI</option>
                                            <option value="CT_Scan">CT Scan</option>
                                            <option value="Blood_Test">Blood Test</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Description / Notes</label>
                                        <textarea name="record_description" class="form-control" rows="4"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Upload File (Optional)</label>
                                        <input type="file" name="record_file" class="form-control-file">
                                    </div>
                                    <div class="form-group">
                                        <label>Record Date</label>
                                        <input type="date" name="record_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" name="submit_record" class="btn btn-primary">Add Record</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/global-interactions.js'); ?>"></script>
    <script>
        $(document).ready(function() {
            $('.timeline-item').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
            });

            $('.file-preview a[target="_blank"]').on('click', function(e) {
                e.preventDefault();
                var fileUrl = $(this).attr('href');
                window.open(fileUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
            });
        });
    </script>

</body>
</html>
