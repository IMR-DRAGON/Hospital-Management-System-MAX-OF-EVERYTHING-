<?php
require_once __DIR__ . '/includes/init.php';

if (empty($_SESSION['name']) || (int)$_SESSION['role'] !== 3) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

include hms_path('includes/header.php');

$patient_id = (int)$_SESSION['user_id'];

// Fetch prescriptions for this patient
$prescriptions_result = mysqli_query($connection, "
    SELECT pr.*,
        CONCAT(e.first_name, ' ', e.last_name) AS doctor_name,
        e.bio AS doctor_specialization
    FROM prescriptions pr
    LEFT JOIN tbl_employee e ON pr.doctor_id = e.id
    WHERE pr.patient_id = $patient_id
    ORDER BY pr.prescribed_date DESC, pr.created_at DESC
");
?>

<div class="page-wrapper">
    <div class="content">
        <div class="row mb-3">
            <div class="col-sm-12">
                <h4 class="page-title"><i class="fa fa-medkit"></i> My Prescriptions</h4>
                <p class="text-muted">View prescriptions issued to you by your doctors.</p>
            </div>
        </div>

        <?php
        $count = $prescriptions_result ? mysqli_num_rows($prescriptions_result) : 0;
        if ($count > 0):
            while ($rx = mysqli_fetch_assoc($prescriptions_result)):
                $status_color = match($rx['status']) {
                    'Active'    => '#28a745',
                    'Completed' => '#17a2b8',
                    'Cancelled' => '#dc3545',
                    default     => '#6c757d'
                };
                $date_formatted = date('F j, Y', strtotime($rx['prescribed_date']));
        ?>
        <div class="card mb-3" style="border-left: 4px solid <?php echo $status_color; ?>; border-radius: 8px;">
            <div class="card-body">
                <div class="row align-items-start">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-2">
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                        width: 44px; height: 44px; border-radius: 50%;
                                        display: flex; align-items: center; justify-content: center;
                                        color: #fff; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                                <i class="fa fa-user-md"></i>
                            </div>
                            <div>
                                <strong style="font-size: 15px;">
                                    Dr. <?php echo htmlspecialchars($rx['doctor_name']); ?>
                                </strong>
                                <?php if (!empty($rx['doctor_specialization'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($rx['doctor_specialization']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="prescription-text p-3 mb-2"
                             style="background: #f8f9fa; border-radius: 6px; white-space: pre-wrap;
                                    font-size: 14px; line-height: 1.7; border: 1px solid #e9ecef;">
                            <?php echo nl2br(htmlspecialchars($rx['prescription_text'])); ?>
                        </div>

                        <?php if (!empty($rx['prescription_file']) && file_exists($rx['prescription_file'])): ?>
                        <div class="mt-2">
                            <a href="<?php echo hms_url($rx['prescription_file']); ?>" target="_blank"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-paperclip"></i> View Attached File
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                        <span class="badge" style="background-color: <?php echo $status_color; ?>; color: #fff;
                              font-size: 12px; padding: 5px 12px; border-radius: 20px;">
                            <?php echo htmlspecialchars($rx['status']); ?>
                        </span>
                        <div class="mt-2 text-muted" style="font-size: 13px;">
                            <i class="fa fa-calendar"></i>
                            <?php echo $date_formatted; ?>
                        </div>
                        <div class="mt-1 text-muted" style="font-size: 12px;">
                            <i class="fa fa-hashtag"></i> Rx #<?php echo (int)$rx['prescription_id']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
            endwhile;
        else:
        ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div style="font-size: 60px; color: #dee2e6; margin-bottom: 16px;">
                    <i class="fa fa-medkit"></i>
                </div>
                <h5 class="text-muted">No Prescriptions Yet</h5>
                <p class="text-muted">You currently have no prescriptions on record. Prescriptions issued by your doctor will appear here.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include hms_path('includes/footer.php'); ?>
