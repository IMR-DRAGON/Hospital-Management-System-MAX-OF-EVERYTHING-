<?php
// One-time migration: adds username & password columns to the associates table
require_once dirname(__DIR__) . '/includes/init.php';

$results = [];

// Add username column if not exists
$r1 = mysqli_query($connection, "ALTER TABLE `associates` ADD COLUMN IF NOT EXISTS `username` VARCHAR(100) NULL AFTER `email`");
$results[] = $r1 ? "✅ username column added (or already existed)" : "❌ username: " . mysqli_error($connection);

// Add password column if not exists
$r2 = mysqli_query($connection, "ALTER TABLE `associates` ADD COLUMN IF NOT EXISTS `password` VARCHAR(255) NULL AFTER `username`");
$results[] = $r2 ? "✅ password column added (or already existed)" : "❌ password: " . mysqli_error($connection);

// Add unique index on username if not already
$r3 = @mysqli_query($connection, "ALTER TABLE `associates` ADD UNIQUE INDEX `idx_username` (`username`)");
$results[] = $r3 ? "✅ Unique index on username added" : "⚠️ username index may already exist (OK)";

echo "<h2>Associate Login Columns Migration</h2><ul>";
foreach ($results as $r) {
    echo "<li>$r</li>";
}
echo "</ul><p><a href='../login.php'>Go to Login</a></p>";
?>
