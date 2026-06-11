<?php
require_once dirname(__DIR__) . '/includes/init.php';

// Try to insert a dummy assignment
$insert_query = "INSERT INTO associate_assignments 
    (associate_id, doctor_id, patient_id, assignment_type, priority_level, assignment_date, assignment_status, notes)
    VALUES 
    (1, 1, NULL, 'General Assistance', 'Medium', '2026-06-11', 'Active', 'Test notes')";

if (mysqli_query($connection, $insert_query)) {
    echo "Successfully inserted assignment! ID: " . mysqli_insert_id($connection);
} else {
    echo "Insert error: " . mysqli_error($connection);
}
?>
