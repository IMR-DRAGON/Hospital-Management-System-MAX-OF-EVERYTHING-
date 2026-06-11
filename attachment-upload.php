<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$associate_id = isset($_GET['associate_id']) ? intval($_GET['associate_id']) : 0;

// Get patient information
$patient_query = "SELECT patient_id, first_name, last_name, patient_id as patient_number FROM patients WHERE patient_id = $patient_id";
$patient_result = mysqli_query($connection, $patient_query);
$patient = mysqli_fetch_assoc($patient_result);

// Get doctor information
$doctor_query = "SELECT doctor_id, first_name, last_name FROM doctors WHERE doctor_id = $doctor_id";
$doctor_result = mysqli_query($connection, $doctor_query);
$doctor = mysqli_fetch_assoc($doctor_result);

// Get associate information
$associate_query = "SELECT associate_id, first_name, last_name FROM associates WHERE associate_id = $associate_id";
$associate_result = mysqli_query($connection, $associate_query);
$associate = mysqli_fetch_assoc($associate_result);

// Get attachment categories
$categories_query = "SELECT category_id, category_name, category_code, allowed_file_types, max_file_size_mb FROM attachment_categories WHERE is_active = TRUE ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);

$message = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_files'])) {
    $category_id = intval($_POST['category_id']);
    $document_title = mysqli_real_escape_string($connection, $_POST['document_title']);
    $document_description = mysqli_real_escape_string($connection, $_POST['document_description']);
    $document_date = $_POST['document_date'];
    $report_type = $_POST['report_type'];
    $test_date = $_POST['test_date'];
    $test_location = mysqli_real_escape_string($connection, $_POST['test_location']);
    $test_reference_number = mysqli_real_escape_string($connection, $_POST['test_reference_number']);
    $doctor_notes = mysqli_real_escape_string($connection, $_POST['doctor_notes']);
    $associate_notes = mysqli_real_escape_string($connection, $_POST['associate_notes']);
    $access_level = $_POST['access_level'];
    $is_confidential = isset($_POST['is_confidential']) ? 1 : 0;
    
    // Get category information
    $category_query = "SELECT allowed_file_types, max_file_size_mb FROM attachment_categories WHERE category_id = $category_id";
    $category_result = mysqli_query($connection, $category_query);
    $category = mysqli_fetch_assoc($category_result);
    
    $allowed_types = json_decode($category['allowed_file_types'], true);
    $max_size = $category['max_file_size_mb'] * 1024 * 1024; // Convert to bytes
    
    $upload_success = 0;
    $upload_errors = array();
    
    // Create upload directory
    $upload_dir = "uploads/attachments/" . date('Y/m/d') . "/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Process uploaded files
    if (isset($_FILES['files']['name']) && is_array($_FILES['files']['name'])) {
        for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
            if ($_FILES['files']['error'][$i] == UPLOAD_ERR_OK) {
                $file_name = $_FILES['files']['name'][$i];
                $file_size = $_FILES['files']['size'][$i];
                $file_tmp = $_FILES['files']['tmp_name'][$i];
                $file_type = $_FILES['files']['type'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Validate file type
                if (!in_array($file_ext, $allowed_types)) {
                    $upload_errors[] = "File '$file_name' has invalid file type. Allowed types: " . implode(', ', $allowed_types);
                    continue;
                }
                
                // Validate file size
                if ($file_size > $max_size) {
                    $upload_errors[] = "File '$file_name' is too large. Maximum size: " . $category['max_file_size_mb'] . "MB";
                    continue;
                }
                
                // Generate unique filename
                $stored_filename = 'att_' . date('YmdHis') . '_' . $i . '.' . $file_ext;
                $file_path = $upload_dir . $stored_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Insert into database
                    $insert_query = "INSERT INTO patient_attachments (patient_id, associate_id, doctor_id, category_id, original_filename, stored_filename, file_path, file_size_bytes, file_type, file_extension, mime_type, document_title, document_description, document_date, report_type, test_date, test_location, test_reference_number, doctor_notes, associate_notes, access_level, is_confidential, created_by) 
                                    VALUES ($patient_id, $associate_id, $doctor_id, $category_id, '$file_name', '$stored_filename', '$file_path', $file_size, '$file_type', '$file_ext', '$file_type', '$document_title', '$document_description', '$document_date', '$report_type', '$test_date', '$test_location', '$test_reference_number', '$doctor_notes', '$associate_notes', '$access_level', $is_confidential, '{$_SESSION['user_name']}')";
                    
                    if (mysqli_query($connection, $insert_query)) {
                        $upload_success++;
                        
                        // Create notification
                        $attachment_id = mysqli_insert_id($connection);
                        $notification_query = "INSERT INTO attachment_notifications (attachment_id, user_id, user_type, notification_type, notification_message) 
                                              VALUES ($attachment_id, $doctor_id, 'Doctor', 'Upload', 'New $report_type uploaded for patient {$patient['first_name']} {$patient['last_name']}')";
                        mysqli_query($connection, $notification_query);
                    } else {
                        $upload_errors[] = "Database error for file '$file_name': " . mysqli_error($connection);
                    }
                } else {
                    $upload_errors[] = "Failed to upload file '$file_name'";
                }
            }
        }
    }
    
    if ($upload_success > 0) {
        $message = "Successfully uploaded $upload_success file(s).";
        if (!empty($upload_errors)) {
            $message .= " Errors: " . implode('; ', $upload_errors);
        }
    } else {
        $error = "No files were uploaded. Errors: " . implode('; ', $upload_errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Attachments - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <style>
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .drop-zone.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        .file-preview {
            margin-top: 20px;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #fff;
        }
        .file-icon {
            font-size: 24px;
            margin-right: 10px;
            color: #007bff;
        }
        .file-info {
            flex-grow: 1;
        }
        .file-size {
            color: #666;
            font-size: 12px;
        }
        .remove-file {
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
        }
        .upload-progress {
            display: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-4 col-3">
                        <h4 class="page-title">Upload Attachments</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('modules/shared/patient-attachments.php'); ?>"?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-arrow-left"></i> Back to Attachments
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

                <!-- Patient Information -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Patient Information</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <p><strong>Patient:</strong> <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Patient ID:</strong> <?php echo $patient['patient_number']; ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Doctor:</strong> <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- File Upload Area -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Upload Files</h4>
                                </div>
                                <div class="card-body">
                                    <div class="drop-zone" id="dropZone">
                                        <i class="fa fa-cloud-upload fa-3x text-muted mb-3"></i>
                                        <h5>Drag & Drop files here</h5>
                                        <p class="text-muted">or click to select files</p>
                                        <input type="file" id="fileInput" name="files[]" multiple style="display: none;" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.txt,.dcm">
                                    </div>
                                    
                                    <div class="file-preview" id="filePreview"></div>
                                    
                                    <div class="upload-progress" id="uploadProgress">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <p class="text-center mt-2">Uploading files...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Document Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Document Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Category <span class="text-danger">*</span></label>
                                        <select class="form-control" name="category_id" id="categorySelect" required>
                                            <option value="">Select Category</option>
                                            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                            <option value="<?php echo $category['category_id']; ?>" data-allowed-types="<?php echo htmlspecialchars($category['allowed_file_types']); ?>" data-max-size="<?php echo $category['max_file_size_mb']; ?>">
                                                <?php echo $category['category_name']; ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Document Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="document_title" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea class="form-control" name="document_description" rows="3"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Document Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="document_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Report Type <span class="text-danger">*</span></label>
                                        <select class="form-control" name="report_type" required>
                                            <option value="">Select Type</option>
                                            <option value="Lab Report">Lab Report</option>
                                            <option value="X-Ray">X-Ray</option>
                                            <option value="MRI">MRI</option>
                                            <option value="CT Scan">CT Scan</option>
                                            <option value="Ultrasound">Ultrasound</option>
                                            <option value="ECG">ECG</option>
                                            <option value="Blood Test">Blood Test</option>
                                            <option value="Urine Test">Urine Test</option>
                                            <option value="Pathology Report">Pathology Report</option>
                                            <option value="Prescription">Prescription</option>
                                            <option value="Discharge Summary">Discharge Summary</option>
                                            <option value="Consultation Notes">Consultation Notes</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Test Date</label>
                                        <input type="date" class="form-control" name="test_date">
                                    </div>

                                    <div class="form-group">
                                        <label>Test Location</label>
                                        <input type="text" class="form-control" name="test_location">
                                    </div>

                                    <div class="form-group">
                                        <label>Reference Number</label>
                                        <input type="text" class="form-control" name="test_reference_number">
                                    </div>

                                    <div class="form-group">
                                        <label>Doctor Notes</label>
                                        <textarea class="form-control" name="doctor_notes" rows="3"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Associate Notes</label>
                                        <textarea class="form-control" name="associate_notes" rows="3"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Access Level</label>
                                        <select class="form-control" name="access_level">
                                            <option value="Restricted">Restricted</option>
                                            <option value="Confidential">Confidential</option>
                                            <option value="Public">Public</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_confidential" id="is_confidential">
                                            <label class="form-check-label" for="is_confidential">
                                                Confidential Document
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="upload_files" class="btn btn-primary btn-block" id="uploadBtn" disabled>
                                            <i class="fa fa-upload"></i> Upload Files
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
        let selectedFiles = [];
        let allowedTypes = [];
        let maxSize = 0;

        // Drop zone functionality
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const uploadBtn = document.getElementById('uploadBtn');
        const categorySelect = document.getElementById('categorySelect');

        // Update file input when category changes
        categorySelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            allowedTypes = JSON.parse(option.dataset.allowedTypes || '[]');
            maxSize = parseInt(option.dataset.maxSize || '10') * 1024 * 1024; // Convert to bytes
            
            // Update file input accept attribute
            const acceptString = allowedTypes.map(type => '.' + type).join(',');
            fileInput.setAttribute('accept', acceptString);
            
            // Clear previous files
            selectedFiles = [];
            updateFilePreview();
        });

        // Click to select files
        dropZone.addEventListener('click', () => fileInput.click());

        // File input change
        fileInput.addEventListener('change', handleFiles);

        // Drag and drop events
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        function handleFiles(files) {
            if (allowedTypes.length === 0) {
                alert('Please select a category first');
                return;
            }

            for (let file of files) {
                // Validate file type
                const fileExt = file.name.split('.').pop().toLowerCase();
                if (!allowedTypes.includes(fileExt)) {
                    alert(`File "${file.name}" has invalid file type. Allowed types: ${allowedTypes.join(', ')}`);
                    continue;
                }

                // Validate file size
                if (file.size > maxSize) {
                    alert(`File "${file.name}" is too large. Maximum size: ${(maxSize / 1024 / 1024).toFixed(1)}MB`);
                    continue;
                }

                // Add to selected files
                selectedFiles.push(file);
            }

            updateFilePreview();
        }

        function updateFilePreview() {
            filePreview.innerHTML = '';
            
            if (selectedFiles.length === 0) {
                uploadBtn.disabled = true;
                return;
            }

            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-icon">
                        <i class="fa fa-file-${getFileIcon(file.name)}"></i>
                    </div>
                    <div class="file-info">
                        <div>${file.name}</div>
                        <div class="file-size">${formatFileSize(file.size)}</div>
                    </div>
                    <div class="remove-file" onclick="removeFile(${index})">
                        <i class="fa fa-times"></i>
                    </div>
                `;
                filePreview.appendChild(fileItem);
            });

            uploadBtn.disabled = false;
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFilePreview();
        }

        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const iconMap = {
                'pdf': 'pdf-o',
                'jpg': 'image-o',
                'jpeg': 'image-o',
                'png': 'image-o',
                'doc': 'word-o',
                'docx': 'word-o',
                'txt': 'text-o',
                'dcm': 'file-o'
            };
            return iconMap[ext] || 'file-o';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form submission
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            if (selectedFiles.length === 0) {
                e.preventDefault();
                alert('Please select files to upload');
                return;
            }

            // Show progress
            document.getElementById('uploadProgress').style.display = 'block';
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Uploading...';
        });
    </script>
</body>
</html>
