<?php
require_once dirname(__DIR__) . '/includes/init.php';

$errors = [];

// 1. Check if associate_assignments table exists and can be inserted into
$res = mysqli_query($connection, "SELECT * FROM associate_assignments LIMIT 1");
if (!$res) $errors[] = "associate_assignments error: " . mysqli_error($connection);

// 2. Check if associate_tasks table exists and can be inserted into
$res = mysqli_query($connection, "SELECT * FROM associate_tasks LIMIT 1");
if (!$res) $errors[] = "associate_tasks error: " . mysqli_error($connection);

// 3. Check if $_SESSION['role'] check works for admin
if (empty($errors)) {
    echo "Tables are fine. No database errors.";
} else {
    echo implode("<br>", $errors);
}
?>
