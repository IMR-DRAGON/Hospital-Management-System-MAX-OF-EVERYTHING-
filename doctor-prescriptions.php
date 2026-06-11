<?php
require_once __DIR__ . '/includes/init.php';
require_once hms_path('includes/doctor_helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 2) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$doctor_id = (int)$_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$patients = get_doctor_patients($connection, $doctor_id, $search);

$prescriptions_query = "SELECT pr.*,
    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
    CONCAT(e.first_name, ' ', e.last_name) AS doctor_name
    FROM prescriptions pr
    JOIN tbl_patient p ON pr.patient_id = p.id
    LEFT JOIN tbl_employee e ON pr.doctor_id = e.id
    WHERE pr.doctor_id = $doctor_id
    ORDER BY pr.prescribed_date DESC, pr.created_at DESC";
$prescriptions_result = mysqli_query($connection, $prescriptions_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <h4 class="page-title">Prescriptions</h4>
                        <p class="text-muted mb-0">Create and manage prescriptions for your patients after checkups.</p>
                    </div>
                </div>

                <?php if (isset($_SESSION['prescription_status'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['prescription_status']['type']); ?>">
                        <?php echo htmlspecialchars($_SESSION['prescription_status']['message']); ?>
                    </div>
                    <?php unset($_SESSION['prescription_status']); ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa fa-search"></i> Find Patient</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="<?php echo hms_url('modules/doctor/doctor-prescriptions.php'); ?>" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Search by name or admit ID..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Admit ID</th>
                                                <th>Patient Name</th>
                                                <th class="text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($patients)): ?>
                                                <?php foreach ($patients as $patient): ?>
                                                <tr>
                                                    <td>#<?php echo (int)$patient['id']; ?></td>
                                                    <td><?php echo htmlspecialchars(trim($patient['first_name'] . ' ' . $patient['last_name'])); ?></td>
                                                    <td class="text-right">
                                                        <button type="button" class="btn btn-sm btn-success prescribe-btn"
                                                                data-patient-id="<?php echo (int)$patient['id']; ?>"
                                                                data-patient-name="<?php echo htmlspecialchars(trim($patient['first_name'] . ' ' . $patient['last_name'])); ?>">
                                                            <i class="fa fa-plus"></i> Prescribe
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No patients found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa fa-file-text-o"></i> My Prescriptions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Patient</th>
                                                <th>Status</th>
                                                <th>Preview</th>
                                                <th class="text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($prescriptions_result && mysqli_num_rows($prescriptions_result) > 0): ?>
                                                <?php while ($rx = mysqli_fetch_assoc($prescriptions_result)): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($rx['prescribed_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($rx['patient_name']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php
                                                            echo $rx['status'] === 'Active' ? 'success' : ($rx['status'] === 'Completed' ? 'info' : 'warning');
                                                        ?>"><?php echo htmlspecialchars($rx['status']); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(substr($rx['prescription_text'], 0, 60)) . (strlen($rx['prescription_text']) > 60 ? '...' : ''); ?></td>
                                                    <td class="text-right">
                                                        <button type="button" class="btn btn-sm btn-primary edit-rx-btn"
                                                                data-id="<?php echo (int)$rx['prescription_id']; ?>"
                                                                data-patient-id="<?php echo (int)$rx['patient_id']; ?>"
                                                                data-patient-name="<?php echo htmlspecialchars($rx['patient_name']); ?>"
                                                                data-text="<?php echo htmlspecialchars($rx['prescription_text']); ?>"
                                                                data-date="<?php echo htmlspecialchars($rx['prescribed_date']); ?>"
                                                                data-status="<?php echo htmlspecialchars($rx['status']); ?>">
                                                            <i class="fa fa-pencil"></i> Edit
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No prescriptions yet.</td>
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

    <div class="modal fade" id="prescriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" action="<?php echo hms_url('modules/doctor/save-prescription.php'); ?>" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="prescriptionModalTitle">New Prescription</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="patient_id" id="rx_patient_id">
                        <input type="hidden" name="prescription_id" id="rx_prescription_id">
                        <input type="hidden" name="create_prescription" id="rx_create_flag" value="1">
                        <input type="hidden" name="update_prescription" id="rx_update_flag" value="">

                        <div class="form-group">
                            <label>Patient</label>
                            <input type="text" class="form-control" id="rx_patient_name" readonly>
                        </div>
                        <div class="form-group">
                            <label>Prescription Details <span class="text-danger">*</span></label>
                            <textarea name="prescription_text" id="rx_text" class="form-control" rows="8" required
                                      placeholder="Medicines, dosage, duration, instructions..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prescribed Date</label>
                                    <input type="date" name="prescribed_date" id="rx_date" class="form-control"
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" id="rx_status" class="form-control">
                                        <option value="Active">Active</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Attach Prescription File (optional)</label>
                            <input type="file" name="prescription_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="rx_submit_btn">Save Prescription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
    <script>
        function openCreateModal(patientId, patientName) {
            $('#prescriptionModalTitle').text('New Prescription');
            $('#rx_patient_id').val(patientId);
            $('#rx_patient_name').val(patientName);
            $('#rx_prescription_id').val('');
            $('#rx_text').val('');
            $('#rx_date').val('<?php echo date('Y-m-d'); ?>');
            $('#rx_status').val('Active');
            $('#rx_create_flag').val('1');
            $('#rx_update_flag').val('');
            $('#rx_submit_btn').text('Save Prescription');
            $('#prescriptionModal').modal('show');
        }

        function openEditModal(data) {
            $('#prescriptionModalTitle').text('Edit Prescription');
            $('#rx_patient_id').val(data.patientId);
            $('#rx_patient_name').val(data.patientName);
            $('#rx_prescription_id').val(data.id);
            $('#rx_text').val(data.text);
            $('#rx_date').val(data.date);
            $('#rx_status').val(data.status);
            $('#rx_create_flag').val('');
            $('#rx_update_flag').val('1');
            $('#rx_submit_btn').text('Update Prescription');
            $('#prescriptionModal').modal('show');
        }

        $('.prescribe-btn').on('click', function() {
            openCreateModal($(this).data('patient-id'), $(this).data('patient-name'));
        });

        $('.edit-rx-btn').on('click', function() {
            openEditModal({
                id: $(this).data('id'),
                patientId: $(this).data('patient-id'),
                patientName: $(this).data('patient-name'),
                text: $(this).data('text'),
                date: $(this).data('date'),
                status: $(this).data('status')
            });
        });
    </script>
</body>
</html>
