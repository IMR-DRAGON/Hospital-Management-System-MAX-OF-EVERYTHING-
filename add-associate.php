<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $associate_code = mysqli_real_escape_string($connection, trim($_POST['associate_code']));
    $first_name     = mysqli_real_escape_string($connection, trim($_POST['first_name']));
    $last_name      = mysqli_real_escape_string($connection, trim($_POST['last_name']));
    $email          = mysqli_real_escape_string($connection, trim($_POST['email'] ?? ''));
    $phone          = mysqli_real_escape_string($connection, trim($_POST['phone']));
    $position       = mysqli_real_escape_string($connection, trim($_POST['position'] ?? ''));
    $associate_type = mysqli_real_escape_string($connection, $_POST['associate_type']);
    $department     = mysqli_real_escape_string($connection, trim($_POST['department'] ?? 'Patient Services'));
    $hire_date      = mysqli_real_escape_string($connection, $_POST['hire_date']);
    $username       = mysqli_real_escape_string($connection, trim($_POST['username'] ?? ''));
    $password       = mysqli_real_escape_string($connection, trim($_POST['password'] ?? ''));
    $salary         = floatval($_POST['salary'] ?? 0.00);

    // Validate required fields
    if (empty($associate_code) || empty($first_name) || empty($last_name) || empty($phone) || empty($associate_type) || empty($hire_date) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields (marked with *). Username and Password are required for login.";
    } else {
        // Check associate code uniqueness
        $code_result = mysqli_query($connection, "SELECT associate_id FROM associates WHERE associate_code = '$associate_code'");
        if (mysqli_num_rows($code_result) > 0) {
            $error = "Associate code <strong>$associate_code</strong> already exists. Please use a different code.";
        } else {
            // Check username uniqueness
            $user_result = mysqli_query($connection, "SELECT associate_id FROM associates WHERE username = '$username'");
            if (mysqli_num_rows($user_result) > 0) {
                $error = "Username <strong>$username</strong> is already taken. Please choose another.";
            } else {
                // Check email uniqueness
                if (!empty($email)) {
                    $email_result = mysqli_query($connection, "SELECT associate_id FROM associates WHERE email = '$email'");
                    if (mysqli_num_rows($email_result) > 0) {
                        $error = "Email address is already registered to another associate.";
                    }
                }

                if (empty($error)) {
                    $insert_query = "INSERT INTO associates 
                        (associate_code, first_name, last_name, email, phone, position, associate_type, department, hire_date, salary, status, username, password)
                        VALUES 
                        ('$associate_code', '$first_name', '$last_name', '$email', '$phone', '$position', '$associate_type', '$department', '$hire_date', '$salary', 'Active', '$username', '$password')";

                    if (mysqli_query($connection, $insert_query)) {
                        header('Location: associates.php?added=1');
                        exit();
                    } else {
                        $error = "Database error: " . mysqli_error($connection);
                    }
                }
            }
        }
    }
}

// Get employees for dropdown
$employees_result = mysqli_query($connection,
    "SELECT id as employee_id, first_name, last_name, role as position FROM tbl_employee WHERE status = 1 ORDER BY first_name, last_name"
);

// Get existing associates for 'Reporting Manager' dropdown
$managers_result = mysqli_query($connection,
    "SELECT associate_id, first_name, last_name, position FROM associates WHERE status = 'Active' ORDER BY first_name, last_name"
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Associate - HMS</title>
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
                        <h4 class="page-title">Add Associate</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('modules/admin/associates.php'); ?>" class="btn btn-primary btn-rounded float-right">
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
                                <h4 class="card-title">Associate Information</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <!-- Basic Information -->
                                    <h5>Basic Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Employee ID <span class="text-danger">*</span></label>
                                                <select class="form-control" name="employee_id" required>
                                                    <option value="">Select Employee</option>
                                                    <?php while ($employee = mysqli_fetch_assoc($employees_result)): ?>
                                                    <option value="<?php echo $employee['employee_id']; ?>" <?php echo (isset($_POST['employee_id']) && $_POST['employee_id'] == $employee['employee_id']) ? 'selected' : ''; ?>>
                                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['position'] . ')'; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Associate Code <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="associate_code" value="<?php echo isset($_POST['associate_code']) ? $_POST['associate_code'] : ''; ?>" required>
                                                <small class="form-text text-muted">e.g., ASC-001, PC-001</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name" value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name" value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Email Address</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Phone Number <span class="text-danger">*</span></label>
                                                <input type="tel" class="form-control" name="phone" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Mobile Number</label>
                                                <input type="tel" class="form-control" name="mobile" value="<?php echo isset($_POST['mobile']) ? $_POST['mobile'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Date of Birth</label>
                                                <input type="date" class="form-control" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Gender <span class="text-danger">*</span></label>
                                                <select class="form-control" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Information -->
                                    <h5 class="mt-4">Address Information</h5>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Address</label>
                                                <textarea class="form-control" name="address" rows="3"><?php echo isset($_POST['address']) ? $_POST['address'] : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>City</label>
                                                <input type="text" class="form-control" name="city" value="<?php echo isset($_POST['city']) ? $_POST['city'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>State</label>
                                                <input type="text" class="form-control" name="state" value="<?php echo isset($_POST['state']) ? $_POST['state'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Country</label>
                                                <input type="text" class="form-control" name="country" value="<?php echo isset($_POST['country']) ? $_POST['country'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Postal Code</label>
                                                <input type="text" class="form-control" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? $_POST['postal_code'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Emergency Contact -->
                                    <h5 class="mt-4">Emergency Contact</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Contact Name</label>
                                                <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo isset($_POST['emergency_contact_name']) ? $_POST['emergency_contact_name'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Contact Phone</label>
                                                <input type="tel" class="form-control" name="emergency_contact_phone" value="<?php echo isset($_POST['emergency_contact_phone']) ? $_POST['emergency_contact_phone'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Relationship</label>
                                                <input type="text" class="form-control" name="emergency_contact_relationship" value="<?php echo isset($_POST['emergency_contact_relationship']) ? $_POST['emergency_contact_relationship'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Professional Information -->
                                    <h5 class="mt-4">Professional Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Qualification</label>
                                                <input type="text" class="form-control" name="qualification" value="<?php echo isset($_POST['qualification']) ? $_POST['qualification'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Specialization</label>
                                                <input type="text" class="form-control" name="specialization" value="<?php echo isset($_POST['specialization']) ? $_POST['specialization'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>License Number</label>
                                                <input type="text" class="form-control" name="license_number" value="<?php echo isset($_POST['license_number']) ? $_POST['license_number'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>License Expiry Date</label>
                                                <input type="date" class="form-control" name="license_expiry_date" value="<?php echo isset($_POST['license_expiry_date']) ? $_POST['license_expiry_date'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Years of Experience</label>
                                                <input type="number" class="form-control" name="years_of_experience" min="0" value="<?php echo isset($_POST['years_of_experience']) ? $_POST['years_of_experience'] : '0'; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Languages Spoken</label>
                                                <input type="text" class="form-control" name="languages_spoken" value="<?php echo isset($_POST['languages_spoken']) ? $_POST['languages_spoken'] : ''; ?>" placeholder="e.g., English, Spanish, French">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Certifications</label>
                                                <textarea class="form-control" name="certifications" rows="3" placeholder="List any relevant certifications"><?php echo isset($_POST['certifications']) ? $_POST['certifications'] : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Employment Details -->
                                    <h5 class="mt-4">Employment Details</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Hire Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="hire_date" value="<?php echo isset($_POST['hire_date']) ? $_POST['hire_date'] : date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <input type="text" class="form-control" name="department" value="<?php echo isset($_POST['department']) ? $_POST['department'] : 'Patient Services'; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Position</label>
                                                <input type="text" class="form-control" name="position" value="<?php echo isset($_POST['position']) ? $_POST['position'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Employment Type <span class="text-danger">*</span></label>
                                                <select class="form-control" name="employment_type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="Full-time" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                                                    <option value="Part-time" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                                                    <option value="Contract" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                                    <option value="Intern" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Intern') ? 'selected' : ''; ?>>Intern</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Salary</label>
                                                <input type="number" class="form-control" name="salary" min="0" step="0.01" value="<?php echo isset($_POST['salary']) ? $_POST['salary'] : '0.00'; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Reporting Manager</label>
                                                <select class="form-control" name="reporting_manager_id">
                                                    <option value="">Select Manager (Optional)</option>
                                                    <?php while ($manager = mysqli_fetch_assoc($managers_result)): ?>
                                                    <option value="<?php echo $manager['associate_id']; ?>" <?php echo (isset($_POST['reporting_manager_id']) && $_POST['reporting_manager_id'] == $manager['associate_id']) ? 'selected' : ''; ?>>
                                                        <?php echo $manager['first_name'] . ' ' . $manager['last_name'] . ' (' . $manager['position'] . ')'; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Associate Specific Information -->
                                    <h5 class="mt-4">Associate Specific Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Associate Type <span class="text-danger">*</span></label>
                                                <select class="form-control" name="associate_type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="Patient Coordinator" <?php echo (isset($_POST['associate_type']) && $_POST['associate_type'] == 'Patient Coordinator') ? 'selected' : ''; ?>>Patient Coordinator</option>
                                                    <option value="Medical Assistant" <?php echo (isset($_POST['associate_type']) && $_POST['associate_type'] == 'Medical Assistant') ? 'selected' : ''; ?>>Medical Assistant</option>
                                                    <option value="Nurse Coordinator" <?php echo (isset($_POST['associate_type']) && $_POST['associate_type'] == 'Nurse Coordinator') ? 'selected' : ''; ?>>Nurse Coordinator</option>
                                                    <option value="Case Manager" <?php echo (isset($_POST['associate_type']) && $_POST['associate_type'] == 'Case Manager') ? 'selected' : ''; ?>>Case Manager</option>
                                                    <option value="Patient Advocate" <?php echo (isset($_POST['associate_type']) && $_POST['associate_type'] == 'Patient Advocate') ? 'selected' : ''; ?>>Patient Advocate</option>
                                                    <option value="Clinical Coordinator" <?php echo (isset($_POST['associate_type']) && $_POST['associate_type'] == 'Clinical Coordinator') ? 'selected' : ''; ?>>Clinical Coordinator</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Max Patients Per Day</label>
                                                <input type="number" class="form-control" name="max_patients_per_day" min="1" max="50" value="<?php echo isset($_POST['max_patients_per_day']) ? $_POST['max_patients_per_day'] : '10'; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Working Hours Start</label>
                                                <input type="time" class="form-control" name="working_hours_start" value="<?php echo isset($_POST['working_hours_start']) ? $_POST['working_hours_start'] : '09:00'; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Working Hours End</label>
                                                <input type="time" class="form-control" name="working_hours_end" value="<?php echo isset($_POST['working_hours_end']) ? $_POST['working_hours_end'] : '17:00'; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Preferred Communication Method</label>
                                                <select class="form-control" name="preferred_communication_method">
                                                    <option value="Phone" <?php echo (isset($_POST['preferred_communication_method']) && $_POST['preferred_communication_method'] == 'Phone') ? 'selected' : ''; ?>>Phone</option>
                                                    <option value="Email" <?php echo (isset($_POST['preferred_communication_method']) && $_POST['preferred_communication_method'] == 'Email') ? 'selected' : ''; ?>>Email</option>
                                                    <option value="SMS" <?php echo (isset($_POST['preferred_communication_method']) && $_POST['preferred_communication_method'] == 'SMS') ? 'selected' : ''; ?>>SMS</option>
                                                    <option value="WhatsApp" <?php echo (isset($_POST['preferred_communication_method']) && $_POST['preferred_communication_method'] == 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp</option>
                                                    <option value="In-person" <?php echo (isset($_POST['preferred_communication_method']) && $_POST['preferred_communication_method'] == 'In-person') ? 'selected' : ''; ?>>In-person</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" name="is_available_for_emergency" id="is_available_for_emergency" <?php echo (isset($_POST['is_available_for_emergency']) && $_POST['is_available_for_emergency']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_available_for_emergency">
                                                        Available for Emergency
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Login Credentials (set by Admin) -->
                                    <h5 class="mt-4">Login Credentials <small class="text-muted">(set by Admin)</small></h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Username <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="username"
                                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                                    placeholder="e.g., sarah.johnson" required autocomplete="off">
                                                <small class="form-text text-muted">Associate will use this to log in.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Password <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="password"
                                                    value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>"
                                                    placeholder="Set a temporary password" required autocomplete="off">
                                                <small class="form-text text-muted">Share this with the associate after creation.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="m-t-20 text-center">
                                        <button class="btn btn-primary submit-btn" type="submit">
                                            <i class="fa fa-user-plus"></i> Add Associate
                                        </button>
                                        <button class="btn btn-secondary" type="reset">Reset</button>
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
    <script>
        // Auto-generate associate code based on type
        document.querySelector('select[name="associate_type"]').addEventListener('change', function() {
            var associateType = this.value;
            var associateCodeInput = document.querySelector('input[name="associate_code"]');
            
            if (associateType) {
                var prefix = '';
                switch(associateType) {
                    case 'Patient Coordinator':
                        prefix = 'PC';
                        break;
                    case 'Medical Assistant':
                        prefix = 'MA';
                        break;
                    case 'Nurse Coordinator':
                        prefix = 'NC';
                        break;
                    case 'Case Manager':
                        prefix = 'CM';
                        break;
                    case 'Patient Advocate':
                        prefix = 'PA';
                        break;
                    case 'Clinical Coordinator':
                        prefix = 'CC';
                        break;
                }
                
                if (prefix) {
                    // Generate a random number for now - in real implementation, get next available number
                    var randomNum = Math.floor(Math.random() * 1000) + 1;
                    associateCodeInput.value = prefix + '-' + randomNum.toString().padStart(3, '0');
                }
            }
        });

        // Auto-set position based on associate type
        document.querySelector('select[name="associate_type"]').addEventListener('change', function() {
            var associateType = this.value;
            var positionInput = document.querySelector('input[name="position"]');
            
            if (associateType && !positionInput.value) {
                positionInput.value = associateType;
            }
        });
    </script>
</body>
</html>
