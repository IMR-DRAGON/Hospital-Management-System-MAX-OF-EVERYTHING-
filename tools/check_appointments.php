<?php
require_once dirname(__DIR__) . '/includes/init.php';

$result = mysqli_query($connection, "SELECT * FROM tbl_appointment LIMIT 5");
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = "Patient: " . $row['patient_name'] . " | Doctor: " . $row['doctor'];
}
echo "Appointments:<br>" . implode("<br>", $rows);
?>
