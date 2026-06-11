<?php
require_once dirname(__DIR__) . '/includes/init.php';

$result = mysqli_query($connection, "SHOW TABLES LIKE 'associate%'");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}
echo "Existing tables: " . implode(", ", $tables);
?>
