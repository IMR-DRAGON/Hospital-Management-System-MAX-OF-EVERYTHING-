<?php
require_once __DIR__ . '/includes/init.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_blood_bag'])) {
    $blood_group = mysqli_real_escape_string($connection, $_POST['blood_group']);
    $quantity = (int)$_POST['quantity'];
    $collection_date = mysqli_real_escape_string($connection, $_POST['collection_date']);
    $expiry_date = mysqli_real_escape_string($connection, $_POST['expiry_date']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    $bag_id = trim($_POST['bag_id'] ?? '');

    $allowed_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $allowed_status = ['Available', 'Reserved', 'Used', 'Expired'];

    if (!in_array($blood_group, $allowed_groups, true)) {
        $error = 'Please select a valid blood group.';
    } elseif ($quantity <= 0) {
        $error = 'Quantity must be greater than 0.';
    } elseif (strtotime($expiry_date) <= strtotime($collection_date)) {
        $error = 'Expiry date must be after collection date.';
    } elseif (!in_array($status, $allowed_status, true)) {
        $error = 'Invalid status selected.';
    } else {
        if ($bag_id === '') {
            $bag_id = 'BAG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        }
        $bag_id = mysqli_real_escape_string($connection, $bag_id);

        $dup_check = mysqli_query($connection, "SELECT id FROM tbl_blood_bank WHERE bag_id = '$bag_id' LIMIT 1");
        if ($dup_check && mysqli_num_rows($dup_check) > 0) {
            $error = 'Bag ID already exists. Please use a different Bag ID.';
        } else {
            $sql = "INSERT INTO tbl_blood_bank (bag_id, blood_group, quantity, collection_date, expiry_date, status)
                    VALUES ('$bag_id', '$blood_group', $quantity, '$collection_date', '$expiry_date', '$status')";

            if (mysqli_query($connection, $sql)) {
                $_SESSION['blood_bag_status'] = ['type' => 'success', 'message' => 'Blood bag added successfully.'];
                header('Location: ' . hms_url('modules/shared/blood-bank.php'));
                exit();
            }
            $error = 'Error adding blood bag: ' . mysqli_error($connection);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Blood Bag - HMS</title>
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
                        <h4 class="page-title">Add Blood Bag</h4>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="<?php echo hms_url('modules/shared/blood-bank.php'); ?>" class="btn btn-secondary btn-rounded">
                            <i class="fa fa-arrow-left"></i> Back to Blood Bank
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="<?php echo hms_url('modules/admin/add-blood-bag.php'); ?>">
                                    <div class="form-group">
                                        <label>Bag ID</label>
                                        <input type="text" name="bag_id" class="form-control"
                                               placeholder="Leave blank to auto-generate"
                                               value="<?php echo htmlspecialchars($_POST['bag_id'] ?? ''); ?>">
                                        <small class="form-text text-muted">Optional. Auto-generated if left empty.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Blood Group <span class="text-danger">*</span></label>
                                        <select name="blood_group" class="form-control" required>
                                            <option value="">Select Blood Group</option>
                                            <?php foreach (['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'] as $group): ?>
                                            <option value="<?php echo $group; ?>" <?php echo (($_POST['blood_group'] ?? '') === $group) ? 'selected' : ''; ?>>
                                                <?php echo $group; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Quantity (units) <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" min="1" required
                                               value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Collection Date <span class="text-danger">*</span></label>
                                        <input type="date" name="collection_date" id="collection_date" class="form-control" required
                                               value="<?php echo htmlspecialchars($_POST['collection_date'] ?? date('Y-m-d')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Expiry Date <span class="text-danger">*</span></label>
                                        <input type="date" name="expiry_date" id="expiry_date" class="form-control" required
                                               value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? date('Y-m-d', strtotime('+42 days'))); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-control" required>
                                            <?php foreach (['Available', 'Reserved', 'Used', 'Expired'] as $st): ?>
                                            <option value="<?php echo $st; ?>" <?php echo (($_POST['status'] ?? 'Available') === $st) ? 'selected' : ''; ?>>
                                                <?php echo $st; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" name="add_blood_bag" class="btn btn-success">
                                            <i class="fa fa-plus"></i> Add Blood Bag
                                        </button>
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
        document.getElementById('collection_date').addEventListener('change', function() {
            var collectionDate = new Date(this.value);
            if (isNaN(collectionDate.getTime())) return;
            var expiryDate = new Date(collectionDate);
            expiryDate.setDate(expiryDate.getDate() + 42);
            document.getElementById('expiry_date').value = expiryDate.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
