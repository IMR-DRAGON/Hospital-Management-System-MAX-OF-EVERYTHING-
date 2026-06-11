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
    $donor_name = mysqli_real_escape_string($connection, $_POST['donor_name']);
    $donor_email = mysqli_real_escape_string($connection, $_POST['donor_email']);
    $donor_phone = mysqli_real_escape_string($connection, $_POST['donor_phone']);
    $donor_address = mysqli_real_escape_string($connection, $_POST['donor_address']);
    $donor_age = intval($_POST['donor_age']);
    $donor_gender = $_POST['donor_gender'];
    $blood_type = $_POST['blood_type'];
    $donor_weight = floatval($_POST['donor_weight']);
    $medical_conditions = mysqli_real_escape_string($connection, $_POST['medical_conditions']);
    $emergency_contact_name = mysqli_real_escape_string($connection, $_POST['emergency_contact_name']);
    $emergency_contact_phone = mysqli_real_escape_string($connection, $_POST['emergency_contact_phone']);
    
    // Validate required fields
    if (empty($donor_name) || empty($donor_phone) || empty($donor_age) || empty($donor_gender) || empty($blood_type) || empty($donor_weight)) {
        $error = "Please fill in all required fields.";
    } elseif ($donor_age < 18 || $donor_age > 65) {
        $error = "Donor age must be between 18 and 65 years.";
    } elseif ($donor_weight < 50) {
        $error = "Donor weight must be at least 50 kg.";
    } else {
        // Check if email already exists
        if (!empty($donor_email)) {
            $email_check = "SELECT donor_id FROM blood_donors WHERE donor_email = '$donor_email'";
            $email_result = mysqli_query($connection, $email_check);
            if (mysqli_num_rows($email_result) > 0) {
                $error = "Email address already exists.";
            }
        }
        
        if (empty($error)) {
            $insert_query = "INSERT INTO blood_donors (donor_name, donor_email, donor_phone, donor_address, donor_age, donor_gender, blood_type, donor_weight, medical_conditions, emergency_contact_name, emergency_contact_phone) 
                            VALUES ('$donor_name', '$donor_email', '$donor_phone', '$donor_address', $donor_age, '$donor_gender', '$blood_type', $donor_weight, '$medical_conditions', '$emergency_contact_name', '$emergency_contact_phone')";
            
            if (mysqli_query($connection, $insert_query)) {
                $message = "Donor added successfully!";
                // Reset form
                $_POST = array();
            } else {
                $error = "Error adding donor: " . mysqli_error($connection);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Blood Donor - HMS</title>
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
                        <h4 class="page-title">Add Blood Donor</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('modules/shared/blood-bank.php'); ?>" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-arrow-left"></i> Back to Blood Bank
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
                                <h4 class="card-title">Donor Information</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="donor_name" value="<?php echo isset($_POST['donor_name']) ? $_POST['donor_name'] : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Email Address</label>
                                                <input type="email" class="form-control" name="donor_email" value="<?php echo isset($_POST['donor_email']) ? $_POST['donor_email'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Phone Number <span class="text-danger">*</span></label>
                                                <input type="tel" class="form-control" name="donor_phone" value="<?php echo isset($_POST['donor_phone']) ? $_POST['donor_phone'] : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Age <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="donor_age" min="18" max="65" value="<?php echo isset($_POST['donor_age']) ? $_POST['donor_age'] : ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Gender <span class="text-danger">*</span></label>
                                                <select class="form-control" name="donor_gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male" <?php echo (isset($_POST['donor_gender']) && $_POST['donor_gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo (isset($_POST['donor_gender']) && $_POST['donor_gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Other" <?php echo (isset($_POST['donor_gender']) && $_POST['donor_gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Blood Type <span class="text-danger">*</span></label>
                                                <select class="form-control" name="blood_type" required>
                                                    <option value="">Select Blood Type</option>
                                                    <option value="A+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                                    <option value="A-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                                    <option value="B+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                                    <option value="B-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                                    <option value="AB+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                                    <option value="AB-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                                    <option value="O+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                                    <option value="O-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Weight (kg) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="donor_weight" min="50" step="0.1" value="<?php echo isset($_POST['donor_weight']) ? $_POST['donor_weight'] : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Address</label>
                                                <textarea class="form-control" name="donor_address" rows="3"><?php echo isset($_POST['donor_address']) ? $_POST['donor_address'] : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Medical Conditions</label>
                                                <textarea class="form-control" name="medical_conditions" rows="3" placeholder="Any medical conditions, allergies, or medications"><?php echo isset($_POST['medical_conditions']) ? $_POST['medical_conditions'] : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Emergency Contact Name</label>
                                                <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo isset($_POST['emergency_contact_name']) ? $_POST['emergency_contact_name'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Emergency Contact Phone</label>
                                                <input type="tel" class="form-control" name="emergency_contact_phone" value="<?php echo isset($_POST['emergency_contact_phone']) ? $_POST['emergency_contact_phone'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="m-t-20 text-center">
                                        <button class="btn btn-primary submit-btn" type="submit">Add Donor</button>
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
</body>
</html>
