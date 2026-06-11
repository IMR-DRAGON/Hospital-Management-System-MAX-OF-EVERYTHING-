<?php
require_once __DIR__ . '/includes/init.php';
require_once hms_path('includes/doctor_helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 2) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

if (isset($_POST['submit_record'])) {
    $doctor_id = (int)$_SESSION['user_id'];
    $patient_id = (int)$_POST['patient_id'];
    $title = mysqli_real_escape_string($connection, $_POST['record_title']);
    $type = mysqli_real_escape_string($connection, $_POST['record_type']);
    $description = mysqli_real_escape_string($connection, $_POST['record_description']);
    $record_date = mysqli_real_escape_string($connection, $_POST['record_date']);

    $patient_check = mysqli_query($connection, "SELECT id FROM tbl_patient WHERE id = $patient_id LIMIT 1");
    if (!$patient_check || mysqli_num_rows($patient_check) === 0) {
        $_SESSION['record_upload_status'] = ['type' => 'danger', 'message' => 'Invalid patient selected.'];
        header("Location: patient-medical-records.php?patient_id=" . $patient_id);
        exit();
    }

    $file_path_sql = "NULL";

    if (isset($_FILES['record_file']) && $_FILES['record_file']['error'] == 0) {
        $file = $_FILES['record_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'dcm'];

        if (in_array($fileExt, $allowed)) {
            if ($file['size'] < 20000000) {
                $newFileName = "record_" . $patient_id . "_" . uniqid('', true) . "." . $fileExt;
                $uploadDir = 'uploads/medical_records/' . $patient_id . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileDestination = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $fileDestination)) {
                    $file_path_sql = "'" . mysqli_real_escape_string($connection, $fileDestination) . "'";
                } else {
                    $_SESSION['record_upload_status'] = ['type' => 'danger', 'message' => 'File upload failed.'];
                    header("Location: patient-medical-records.php?patient_id=" . $patient_id);
                    exit();
                }
            } else {
                $_SESSION['record_upload_status'] = ['type' => 'danger', 'message' => 'File is too large (max 20MB).'];
                header("Location: patient-medical-records.php?patient_id=" . $patient_id);
                exit();
            }
        } else {
            $_SESSION['record_upload_status'] = ['type' => 'danger', 'message' => 'Invalid file type.'];
            header("Location: patient-medical-records.php?patient_id=" . $patient_id);
            exit();
        }
    }

    $sql = "INSERT INTO patient_medical_records (patient_id, doctor_id, record_type, record_title, record_description, file_path, record_date)
            VALUES ('$patient_id', '$doctor_id', '$type', '$title', '$description', $file_path_sql, '$record_date')";

    if (mysqli_query($connection, $sql)) {
        $_SESSION['record_upload_status'] = ['type' => 'success', 'message' => 'Medical record added successfully.'];
    } else {
        $_SESSION['record_upload_status'] = ['type' => 'danger', 'message' => 'Database error: ' . mysqli_error($connection)];
    }

    header("Location: patient-medical-records.php?patient_id=" . $patient_id);
    exit();
}

header('Location: ' . hms_url('modules/shared/dashboard.php'));
exit();
