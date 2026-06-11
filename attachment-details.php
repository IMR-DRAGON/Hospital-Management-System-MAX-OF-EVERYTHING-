<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$attachment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get attachment details
$attachment_query = "SELECT pa.*, 
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    CONCAT(a.first_name, ' ', a.last_name) as associate_name,
                    CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                    ac.category_name, ac.category_code
                    FROM patient_attachments pa
                    JOIN patients p ON pa.patient_id = p.patient_id
                    JOIN associates a ON pa.associate_id = a.associate_id
                    JOIN doctors d ON pa.doctor_id = d.doctor_id
                    JOIN attachment_categories ac ON pa.category_id = ac.category_id
                    WHERE pa.attachment_id = $attachment_id AND pa.status != 'Deleted'";

$attachment_result = mysqli_query($connection, $attachment_query);
$attachment = mysqli_fetch_assoc($attachment_result);

if (!$attachment) {
    header('Location: ' . hms_url('modules/shared/patients.php'));
    exit();
}

// Get comments
$comments_query = "SELECT c.*, 
                  CASE 
                    WHEN c.user_type = 'Doctor' THEN CONCAT(d.first_name, ' ', d.last_name)
                    WHEN c.user_type = 'Associate' THEN CONCAT(a.first_name, ' ', a.last_name)
                    WHEN c.user_type = 'Admin' THEN 'Admin'
                    ELSE 'Unknown'
                  END as user_name
                  FROM attachment_comments c
                  LEFT JOIN doctors d ON c.user_id = d.doctor_id AND c.user_type = 'Doctor'
                  LEFT JOIN associates a ON c.user_id = a.associate_id AND c.user_type = 'Associate'
                  WHERE c.attachment_id = $attachment_id
                  ORDER BY c.comment_date DESC";

$comments_result = mysqli_query($connection, $comments_query);

// Get download history
$downloads_query = "SELECT * FROM attachment_downloads 
                   WHERE attachment_id = $attachment_id 
                   ORDER BY download_date DESC 
                   LIMIT 10";

$downloads_result = mysqli_query($connection, $downloads_query);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $comment_text = mysqli_real_escape_string($connection, $_POST['comment_text']);
    $is_internal = isset($_POST['is_internal']) ? 1 : 0;
    
    $comment_query = "INSERT INTO attachment_comments (attachment_id, user_id, user_type, comment_text, is_internal) 
                     VALUES ($attachment_id, {$_SESSION['user_id']}, '{$_SESSION['user_type']}', '$comment_text', $is_internal)";
    
    if (mysqli_query($connection, $comment_query)) {
        $message = "Comment added successfully!";
    } else {
        $error = "Error adding comment: " . mysqli_error($connection);
    }
}

// Handle file download
if (isset($_GET['download'])) {
    if (file_exists($attachment['file_path'])) {
        // Record download
        $record_download = "INSERT INTO attachment_downloads (attachment_id, downloaded_by, ip_address, download_reason) 
                           VALUES ($attachment_id, '{$_SESSION['user_name']}', '{$_SERVER['REMOTE_ADDR']}', 'User download')";
        mysqli_query($connection, $record_download);
        
        // Set headers for download
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
        header('Content-Length: ' . filesize($attachment['file_path']));
        readfile($attachment['file_path']);
        exit;
    } else {
        $error = "File not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attachment Details - HMS</title>
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
                        <h4 class="page-title">Attachment Details</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="?download=1&id=<?php echo $attachment_id; ?>" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-download"></i> Download
                        </a>
                        <a href="<?php echo hms_url('modules/shared/patient-attachments.php'); ?>"?patient_id=<?php echo $attachment['patient_id']; ?>" class="btn btn-secondary btn-rounded float-right mr-2">
                            <i class="fa fa-arrow-left"></i> Back to Attachments
                        </a>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <!-- Document Information -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Document Information</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Document Title:</strong> <?php echo $attachment['document_title']; ?></p>
                                        <p><strong>Original Filename:</strong> <?php echo $attachment['original_filename']; ?></p>
                                        <p><strong>File Type:</strong> <?php echo strtoupper($attachment['file_extension']); ?></p>
                                        <p><strong>File Size:</strong> <?php echo formatFileSize($attachment['file_size_bytes']); ?></p>
                                        <p><strong>MIME Type:</strong> <?php echo $attachment['mime_type']; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Category:</strong> <?php echo $attachment['category_name']; ?></p>
                                        <p><strong>Report Type:</strong> <?php echo $attachment['report_type']; ?></p>
                                        <p><strong>Document Date:</strong> <?php echo date('M d, Y', strtotime($attachment['document_date'])); ?></p>
                                        <p><strong>Upload Date:</strong> <?php echo date('M d, Y H:i', strtotime($attachment['upload_date'])); ?></p>
                                        <p><strong>Status:</strong> 
                                            <?php
                                            $status_class = '';
                                            switch($attachment['status']) {
                                                case 'Uploaded': $status_class = 'badge-secondary'; break;
                                                case 'Under Review': $status_class = 'badge-warning'; break;
                                                case 'Approved': $status_class = 'badge-success'; break;
                                                case 'Rejected': $status_class = 'badge-danger'; break;
                                                case 'Archived': $status_class = 'badge-info'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo $attachment['status']; ?></span>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($attachment['document_description']): ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p><strong>Description:</strong></p>
                                        <p><?php echo nl2br($attachment['document_description']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($attachment['test_date'] || $attachment['test_location'] || $attachment['test_reference_number']): ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Test Information</h5>
                                        <div class="row">
                                            <?php if ($attachment['test_date']): ?>
                                            <div class="col-md-4">
                                                <p><strong>Test Date:</strong> <?php echo date('M d, Y', strtotime($attachment['test_date'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($attachment['test_location']): ?>
                                            <div class="col-md-4">
                                                <p><strong>Test Location:</strong> <?php echo $attachment['test_location']; ?></p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($attachment['test_reference_number']): ?>
                                            <div class="col-md-4">
                                                <p><strong>Reference Number:</strong> <?php echo $attachment['test_reference_number']; ?></p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($attachment['doctor_notes'] || $attachment['associate_notes']): ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Notes</h5>
                                        <?php if ($attachment['doctor_notes']): ?>
                                        <p><strong>Doctor Notes:</strong></p>
                                        <p class="text-muted"><?php echo nl2br($attachment['doctor_notes']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($attachment['associate_notes']): ?>
                                        <p><strong>Associate Notes:</strong></p>
                                        <p class="text-muted"><?php echo nl2br($attachment['associate_notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Comments</h4>
                            </div>
                            <div class="card-body">
                                <!-- Add Comment Form -->
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Add Comment</label>
                                        <textarea class="form-control" name="comment_text" rows="3" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal">
                                            <label class="form-check-label" for="is_internal">
                                                Internal Comment (not visible to patients)
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                                </form>

                                <hr>

                                <!-- Comments List -->
                                <div class="comments-list">
                                    <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                                    <div class="comment-item mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo $comment['user_name']; ?></strong>
                                                <?php if ($comment['is_internal']): ?>
                                                    <span class="badge badge-warning ml-2">Internal</span>
                                                <?php endif; ?>
                                                <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($comment['comment_date'])); ?></small>
                                            </div>
                                            <?php if ($comment['is_resolved']): ?>
                                                <span class="badge badge-success">Resolved</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mt-2"><?php echo nl2br($comment['comment_text']); ?></p>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Patient Information -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Patient Information</h4>
                            </div>
                            <div class="card-body">
                                <p><strong>Patient:</strong> <?php echo $attachment['patient_name']; ?></p>
                                <p><strong>Patient ID:</strong> <?php echo $attachment['patient_id']; ?></p>
                                <p><strong>Access Level:</strong> 
                                    <?php
                                    $access_class = '';
                                    switch($attachment['access_level']) {
                                        case 'Public': $access_class = 'badge-success'; break;
                                        case 'Restricted': $access_class = 'badge-warning'; break;
                                        case 'Confidential': $access_class = 'badge-danger'; break;
                                        case 'Top Secret': $access_class = 'badge-dark'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $access_class; ?>"><?php echo $attachment['access_level']; ?></span>
                                </p>
                                <?php if ($attachment['is_confidential']): ?>
                                <p><strong>Confidential:</strong> <span class="badge badge-danger">Yes</span></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Upload Information -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Upload Information</h4>
                            </div>
                            <div class="card-body">
                                <p><strong>Uploaded By:</strong> <?php echo $attachment['associate_name']; ?></p>
                                <p><strong>Doctor:</strong> <?php echo $attachment['doctor_name']; ?></p>
                                <p><strong>Upload Date:</strong> <?php echo date('M d, Y H:i', strtotime($attachment['upload_date'])); ?></p>
                                <?php if ($attachment['updated_by']): ?>
                                <p><strong>Last Updated By:</strong> <?php echo $attachment['updated_by']; ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($attachment['updated_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Download History -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Downloads</h4>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($downloads_result) > 0): ?>
                                    <?php while ($download = mysqli_fetch_assoc($downloads_result)): ?>
                                    <div class="download-item mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span><?php echo $download['downloaded_by']; ?></span>
                                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($download['download_date'])); ?></small>
                                        </div>
                                        <?php if ($download['download_reason']): ?>
                                        <small class="text-muted"><?php echo $download['download_reason']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-muted">No downloads yet</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- File Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">File Actions</h4>
                            </div>
                            <div class="card-body">
                                <a href="?download=1&id=<?php echo $attachment_id; ?>" class="btn btn-primary btn-block mb-2">
                                    <i class="fa fa-download"></i> Download File
                                </a>
                                <a href="attachment-share.php?id=<?php echo $attachment_id; ?>" class="btn btn-info btn-block mb-2">
                                    <i class="fa fa-share"></i> Share File
                                </a>
                                <a href="attachment-edit.php?id=<?php echo $attachment_id; ?>" class="btn btn-warning btn-block mb-2">
                                    <i class="fa fa-edit"></i> Edit Details
                                </a>
                                <a href="attachment-print.php?id=<?php echo $attachment_id; ?>" class="btn btn-secondary btn-block" target="_blank">
                                    <i class="fa fa-print"></i> Print Details
                                </a>
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

<?php
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
