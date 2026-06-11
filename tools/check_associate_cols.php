<?php
require_once dirname(__DIR__) . '/includes/init.php';

$result = mysqli_query($connection, "SHOW COLUMNS FROM associates");
$cols = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cols[] = $row['Field'] . " (" . $row['Type'] . ")";
}
echo "Associates Columns: " . implode(", ", $cols);
?>
