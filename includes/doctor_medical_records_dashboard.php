<?php
$doctor_id = (int)$_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$patient_ids = get_doctor_patient_ids($connection, $doctor_id);
$patients = get_doctor_patients($connection, $doctor_id, $search);

$attachments = [];
$medical_records = [];
$prescriptions = [];

if (!empty($patient_ids)) {
    $id_list = implode(',', array_map('intval', $patient_ids));

    $attach_sql = "SELECT a.id, a.patient_id, a.file_name, a.file_path, a.description, a.uploaded_at,
                          CONCAT(p.first_name, ' ', p.last_name) AS patient_name
                   FROM tbl_attachment a
                   JOIN tbl_patient p ON a.patient_id = p.id
                   WHERE a.patient_id IN ($id_list)
                   ORDER BY a.uploaded_at DESC";
    $attach_result = mysqli_query($connection, $attach_sql);
    if ($attach_result) {
        while ($row = mysqli_fetch_assoc($attach_result)) {
            $row['source'] = 'Patient Upload';
            $attachments[] = $row;
        }
    }

    $records_sql = "SELECT pmr.record_id, pmr.patient_id, pmr.record_type, pmr.record_title, pmr.record_description,
                           pmr.file_path, pmr.record_date,
                           CONCAT(p.first_name, ' ', p.last_name) AS patient_name
                    FROM patient_medical_records pmr
                    JOIN tbl_patient p ON pmr.patient_id = p.id
                    WHERE pmr.patient_id IN ($id_list)
                    ORDER BY pmr.record_date DESC";
    $records_result = mysqli_query($connection, $records_sql);
    if ($records_result) {
        while ($row = mysqli_fetch_assoc($records_result)) {
            $row['source'] = 'Medical Record';
            $medical_records[] = $row;
        }
    }

    $rx_sql = "SELECT pr.prescription_id, pr.patient_id, pr.prescription_text, pr.prescription_file,
                      pr.prescribed_date, pr.status,
                      CONCAT(p.first_name, ' ', p.last_name) AS patient_name
               FROM prescriptions pr
               JOIN tbl_patient p ON pr.patient_id = p.id
               WHERE pr.patient_id IN ($id_list)
               ORDER BY pr.prescribed_date DESC";
    $rx_result = mysqli_query($connection, $rx_sql);
    if ($rx_result) {
        while ($row = mysqli_fetch_assoc($rx_result)) {
            $row['source'] = 'Prescription';
            $prescriptions[] = $row;
        }
    }
}
?>
<div class="row mb-3">
    <div class="col-sm-8">
        <h4 class="page-title">Medical Records</h4>
        <p class="text-muted mb-0">All files and records from patients under your care.</p>
    </div>
    <div class="col-sm-4 text-right">
        <a href="doctor-prescriptions.php" class="btn btn-success btn-rounded">
            <i class="fa fa-file-text-o"></i> Prescriptions
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fa fa-users"></i> My Patients</h5>
                <form method="GET" action="patient-medical-records.php" class="form-inline">
                    <input type="text" name="search" class="form-control form-control-sm mr-2"
                           placeholder="Search patient or admit ID..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Search</button>
                </form>
            </div>
            <div class="card-body">
                <?php if (!empty($patients)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Admit ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td>#<?php echo (int)$patient['id']; ?></td>
                                <td><?php echo htmlspecialchars(trim($patient['first_name'] . ' ' . $patient['last_name'])); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['patient_type']); ?></td>
                                <td class="text-right">
                                    <a href="patient-medical-records.php?patient_id=<?php echo (int)$patient['id']; ?>"
                                       class="btn btn-sm btn-primary">View Records</a>
                                    <a href="doctor-prescriptions.php?search=<?php echo urlencode(trim($patient['first_name'] . ' ' . $patient['last_name'])); ?>"
                                       class="btn btn-sm btn-success">Prescribe</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">No patients linked yet. Patients appear here after appointments, chats, prescriptions, or medical records.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fa fa-folder-open"></i> All Uploaded Files</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Source</th>
                                <th>Title / File</th>
                                <th>Date</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $has_files = false;
                            foreach ($attachments as $file):
                                $has_files = true;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['patient_name']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($file['source']); ?></span></td>
                                <td>
                                    <?php echo htmlspecialchars($file['file_name']); ?>
                                    <?php if ($file['description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($file['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?></td>
                                <td class="text-right">
                                    <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php foreach ($medical_records as $record):
                                $has_files = true;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['patient_name']); ?></td>
                                <td><span class="badge badge-primary"><?php echo htmlspecialchars($record['record_type']); ?></span></td>
                                <td>
                                    <?php echo htmlspecialchars($record['record_title']); ?>
                                    <?php if ($record['record_description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($record['record_description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                <td class="text-right">
                                    <?php if ($record['file_path'] && file_exists($record['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($record['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                    <?php endif; ?>
                                    <a href="patient-medical-records.php?patient_id=<?php echo (int)$record['patient_id']; ?>" class="btn btn-sm btn-secondary">Open</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$has_files): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No uploaded files from your patients yet.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fa fa-prescription"></i> Patient Prescriptions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Prescription</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($prescriptions)): ?>
                                <?php foreach ($prescriptions as $rx): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rx['patient_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($rx['prescribed_date'])); ?></td>
                                    <td><span class="badge badge-success"><?php echo htmlspecialchars($rx['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars(substr($rx['prescription_text'], 0, 80)) . (strlen($rx['prescription_text']) > 80 ? '...' : ''); ?></td>
                                    <td class="text-right">
                                        <?php if ($rx['prescription_file']): ?>
                                            <a href="<?php echo htmlspecialchars($rx['prescription_file']); ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-file"></i></a>
                                        <?php endif; ?>
                                        <a href="patient-medical-records.php?patient_id=<?php echo (int)$rx['patient_id']; ?>" class="btn btn-sm btn-secondary">View Patient</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No prescriptions found for your patients.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
