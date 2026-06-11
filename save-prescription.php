<?php
require_once __DIR__ . '/includes/init.php';
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 2) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$doctor_id = (int)$_SESSION['user_id'];
$redirect = 'doctor-prescriptions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect");
    exit();
}

$patient_id = (int)($_POST['patient_id'] ?? 0);
$prescription_text = mysqli_real_escape_string($connection, trim($_POST['prescription_text'] ?? ''));
$prescribed_date = mysqli_real_escape_string($connection, $_POST['prescribed_date'] ?? date('Y-m-d'));
$status = mysqli_real_escape_string($connection, $_POST['status'] ?? 'Active');
$allowed_status = ['Active', 'Completed', 'Cancelled'];
if (!in_array($status, $allowed_status, true)) {
    $status = 'Active';
}

if ($patient_id <= 0 || $prescription_text === '') {
    $_SESSION['prescription_status'] = ['type' => 'danger', 'message' => 'Patient and prescription details are required.'];
    header("Location: $redirect");
    exit();
}

$patient_check = mysqli_query($connection, "SELECT id FROM tbl_patient WHERE id = $patient_id AND status = 1 LIMIT 1");
if (!$patient_check || mysqli_num_rows($patient_check) === 0) {
    $_SESSION['prescription_status'] = ['type' => 'danger', 'message' => 'Selected patient not found.'];
    header("Location: $redirect");
    exit();
}

$file_path_sql = "NULL";
if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] === UPLOAD_ERR_OK) {
    $fileExt = strtolower(pathinfo($_FILES['prescription_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($fileExt, $allowed) && $_FILES['prescription_file']['size'] < 10000000) {
        $uploadDir = 'uploads/prescriptions/' . $patient_id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $newFileName = 'rx_' . $patient_id . '_' . uniqid('', true) . '.' . $fileExt;
        $fileDestination = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['prescription_file']['tmp_name'], $fileDestination)) {
            $file_path_sql = "'" . mysqli_real_escape_string($connection, $fileDestination) . "'";
        }
    }
}

if (isset($_POST['create_prescription'])) {
    $sql = "INSERT INTO prescriptions (patient_id, doctor_id, prescription_text, prescription_file, prescribed_date, status)
            VALUES ($patient_id, $doctor_id, '$prescription_text', $file_path_sql, '$prescribed_date', '$status')";

    if (mysqli_query($connection, $sql)) {
        $_SESSION['prescription_status'] = ['type' => 'success', 'message' => 'Prescription created successfully.'];
    } else {
        $_SESSION['prescription_status'] = ['type' => 'danger', 'message' => 'Error saving prescription: ' . mysqli_error($connection)];
    }
} elseif (isset($_POST['update_prescription'])) {
    $prescription_id = (int)($_POST['prescription_id'] ?? 0);
    $existing = mysqli_query(
        $connection,
        "SELECT prescription_id, doctor_id, prescription_file FROM prescriptions WHERE prescription_id = $prescription_id LIMIT 1"
    );
    $existing_row = $existing ? mysqli_fetch_assoc($existing) : null;

    if (!$existing_row || (int)$existing_row['doctor_id'] !== $doctor_id) {
        $_SESSION['prescription_status'] = ['type' => 'danger', 'message' => 'You can only edit your own prescriptions.'];
        header("Location: $redirect");
        exit();
    }

    $file_update = '';
    if ($file_path_sql !== 'NULL') {
        $file_update = ", prescription_file = $file_path_sql";
    }

    $sql = "UPDATE prescriptions
            SET prescription_text = '$prescription_text',
                prescribed_date = '$prescribed_date',
                status = '$status'
                $file_update
            WHERE prescription_id = $prescription_id AND doctor_id = $doctor_id";

    if (mysqli_query($connection, $sql)) {
        $_SESSION['prescription_status'] = ['type' => 'success', 'message' => 'Prescription updated successfully.'];
    } else {
        $_SESSION['prescription_status'] = ['type' => 'danger', 'message' => 'Error updating prescription: ' . mysqli_error($connection)];
    }
}

header("Location: $redirect");
exit();
