<?php
require_once __DIR__ . '/includes/init.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$bag_id_param = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($bag_id_param <= 0) {
    header('Location: ' . hms_url('modules/shared/blood-bank.php'));
    exit();
}

$bag_query = mysqli_query($connection, "SELECT * FROM tbl_blood_bank WHERE id = $bag_id_param LIMIT 1");
$bag = $bag_query ? mysqli_fetch_assoc($bag_query) : null;

if (!$bag) {
    $_SESSION['blood_bag_status'] = ['type' => 'danger', 'message' => 'Blood bag not found.'];
    header('Location: ' . hms_url('modules/shared/blood-bank.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_blood_bag'])) {
    $blood_group = mysqli_real_escape_string($connection, $_POST['blood_group']);
    $quantity = (int)$_POST['quantity'];
    $collection_date = mysqli_real_escape_string($connection, $_POST['collection_date']);
    $expiry_date = mysqli_real_escape_string($connection, $_POST['expiry_date']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);

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
        $sql = "UPDATE tbl_blood_bank
                SET blood_group = '$blood_group',
                    quantity = $quantity,
                    collection_date = '$collection_date',
                    expiry_date = '$expiry_date',
                    status = '$status'
                WHERE id = $bag_id_param";

        if (mysqli_query($connection, $sql)) {
            $_SESSION['blood_bag_status'] = ['type' => 'success', 'message' => 'Blood bag updated successfully.'];
            header('Location: ' . hms_url('modules/shared/blood-bank.php'));
            exit();
        }
        $error = 'Error updating blood bag: ' . mysqli_error($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Blood Bag - HMS</title>
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
                        <h4 class="page-title">Edit Blood Bag</h4>
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
                                <form method="POST" action="<?php echo hms_url('modules/admin/edit-blood-bag.php'); ?>"?id=<?php echo (int)$bag['id']; ?>">
                                    <div class="form-group">
                                        <label>Bag ID</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($bag['bag_id']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Blood Group <span class="text-danger">*</span></label>
                                        <select name="blood_group" class="form-control" required>
                                            <?php foreach (['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'] as $group): ?>
                                            <option value="<?php echo $group; ?>" <?php echo ($bag['blood_group'] === $group) ? 'selected' : ''; ?>>
                                                <?php echo $group; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Quantity (units) <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" min="1" required
                                               value="<?php echo (int)$bag['quantity']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Collection Date <span class="text-danger">*</span></label>
                                        <input type="date" name="collection_date" class="form-control" required
                                               value="<?php echo htmlspecialchars($bag['collection_date']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Expiry Date <span class="text-danger">*</span></label>
                                        <input type="date" name="expiry_date" class="form-control" required
                                               value="<?php echo htmlspecialchars($bag['expiry_date']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-control" required>
                                            <?php foreach (['Available', 'Reserved', 'Used', 'Expired'] as $st): ?>
                                            <option value="<?php echo $st; ?>" <?php echo ($bag['status'] === $st) ? 'selected' : ''; ?>>
                                                <?php echo $st; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" name="update_blood_bag" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Blood Bag
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
</body>
</html>
