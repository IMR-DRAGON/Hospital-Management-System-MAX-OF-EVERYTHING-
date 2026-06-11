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
    $blood_type = $_POST['blood_type'];
    $blood_group = $_POST['blood_group'];
    $collection_date = $_POST['collection_date'];
    $expiry_date = $_POST['expiry_date'];
    $donor_id = intval($_POST['donor_id']);
    $unit_volume = floatval($_POST['unit_volume']);
    $storage_location = mysqli_real_escape_string($connection, $_POST['storage_location']);
    $temperature = floatval($_POST['temperature']);
    $blood_pressure_systolic = intval($_POST['blood_pressure_systolic']);
    $blood_pressure_diastolic = intval($_POST['blood_pressure_diastolic']);
    $hemoglobin_level = floatval($_POST['hemoglobin_level']);
    $blood_sugar_level = floatval($_POST['blood_sugar_level']);
    
    // Validate required fields
    if (empty($blood_type) || empty($collection_date) || empty($expiry_date) || empty($storage_location)) {
        $error = "Please fill in all required fields.";
    } elseif (strtotime($expiry_date) <= strtotime($collection_date)) {
        $error = "Expiry date must be after collection date.";
    } elseif ($unit_volume <= 0 || $unit_volume > 2) {
        $error = "Unit volume must be between 0.1 and 2.0 liters.";
    } else {
        $insert_query = "INSERT INTO blood_inventory (blood_type, blood_group, collection_date, expiry_date, donor_id, unit_volume, storage_location, temperature, blood_pressure_systolic, blood_pressure_diastolic, hemoglobin_level, blood_sugar_level) 
                        VALUES ('$blood_type', '$blood_group', '$collection_date', '$expiry_date', $donor_id, $unit_volume, '$storage_location', $temperature, $blood_pressure_systolic, $blood_pressure_diastolic, $hemoglobin_level, $blood_sugar_level)";
        
        if (mysqli_query($connection, $insert_query)) {
            $message = "Blood unit added successfully!";
            // Reset form
            $_POST = array();
        } else {
            $error = "Error adding blood unit: " . mysqli_error($connection);
        }
    }
}

// Get donors for dropdown
$donors_query = "SELECT donor_id, donor_name, blood_type FROM blood_donors WHERE status = 'Active' ORDER BY donor_name";
$donors_result = mysqli_query($connection, $donors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Blood Unit - HMS</title>
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
                        <h4 class="page-title">Add Blood Unit</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('modules/admin/blood-inventory.php'); ?>" class="btn btn-primary btn-rounded float-right">
                            <i class="fa fa-arrow-left"></i> Back to Inventory
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
                                <h4 class="card-title">Blood Unit Information</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
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
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Blood Group</label>
                                                <input type="text" class="form-control" name="blood_group" value="<?php echo isset($_POST['blood_group']) ? $_POST['blood_group'] : ''; ?>" placeholder="e.g., O+">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Collection Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="collection_date" value="<?php echo isset($_POST['collection_date']) ? $_POST['collection_date'] : date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Expiry Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="expiry_date" value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+42 days')); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Donor</label>
                                                <select class="form-control" name="donor_id">
                                                    <option value="">Select Donor (Optional)</option>
                                                    <?php while ($donor = mysqli_fetch_assoc($donors_result)): ?>
                                                    <option value="<?php echo $donor['donor_id']; ?>" <?php echo (isset($_POST['donor_id']) && $_POST['donor_id'] == $donor['donor_id']) ? 'selected' : ''; ?>>
                                                        <?php echo $donor['donor_name'] . ' (' . $donor['blood_type'] . ')'; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Unit Volume (Liters) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="unit_volume" min="0.1" max="2.0" step="0.1" value="<?php echo isset($_POST['unit_volume']) ? $_POST['unit_volume'] : '1.0'; ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Storage Location <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="storage_location" value="<?php echo isset($_POST['storage_location']) ? $_POST['storage_location'] : ''; ?>" placeholder="e.g., Refrigerator A-1" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Storage Temperature (°C)</label>
                                                <input type="number" class="form-control" name="temperature" min="-10" max="10" step="0.1" value="<?php echo isset($_POST['temperature']) ? $_POST['temperature'] : '4.0'; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Systolic Blood Pressure (mmHg)</label>
                                                <input type="number" class="form-control" name="blood_pressure_systolic" min="70" max="200" value="<?php echo isset($_POST['blood_pressure_systolic']) ? $_POST['blood_pressure_systolic'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Diastolic Blood Pressure (mmHg)</label>
                                                <input type="number" class="form-control" name="blood_pressure_diastolic" min="40" max="120" value="<?php echo isset($_POST['blood_pressure_diastolic']) ? $_POST['blood_pressure_diastolic'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Hemoglobin Level (g/dL)</label>
                                                <input type="number" class="form-control" name="hemoglobin_level" min="8" max="20" step="0.1" value="<?php echo isset($_POST['hemoglobin_level']) ? $_POST['hemoglobin_level'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Blood Sugar Level (mg/dL)</label>
                                                <input type="number" class="form-control" name="blood_sugar_level" min="70" max="200" value="<?php echo isset($_POST['blood_sugar_level']) ? $_POST['blood_sugar_level'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="m-t-20 text-center">
                                        <button class="btn btn-primary submit-btn" type="submit">Add Blood Unit</button>
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
        // Auto-set expiry date when collection date changes
        document.querySelector('input[name="collection_date"]').addEventListener('change', function() {
            var collectionDate = new Date(this.value);
            var expiryDate = new Date(collectionDate);
            expiryDate.setDate(expiryDate.getDate() + 42); // 42 days shelf life
            
            var expiryInput = document.querySelector('input[name="expiry_date"]');
            expiryInput.value = expiryDate.toISOString().split('T')[0];
        });

        // Auto-set blood group when blood type changes
        document.querySelector('select[name="blood_type"]').addEventListener('change', function() {
            var bloodGroupInput = document.querySelector('input[name="blood_group"]');
            bloodGroupInput.value = this.value;
        });
    </script>
</body>
</html>
