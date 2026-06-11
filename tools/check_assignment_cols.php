<?php
require_once dirname(__DIR__) . '/includes/init.php';

$result = mysqli_query($connection, "SHOW COLUMNS FROM associate_assignments");
$cols = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cols[] = $row['Field'] . " (" . $row['Type'] . ") - Null: " . $row['Null'];
}
echo "associate_assignments Columns: " . implode(", ", $cols);
?>
