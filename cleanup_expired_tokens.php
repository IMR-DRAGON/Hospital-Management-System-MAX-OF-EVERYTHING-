<?php
require_once __DIR__ . '/includes/init.php';
/**
 * Cleanup script for expired password reset tokens
 * This script can be run manually or via cron job to clean up expired tokens
 */
// Delete expired tokens
$cleanup_query = "DELETE FROM password_reset_tokens WHERE expiry < NOW()";
$result = mysqli_query($connection, $cleanup_query);

if($result) {
    $affected_rows = mysqli_affected_rows($connection);
    echo "Cleanup completed successfully. Removed $affected_rows expired tokens.\n";
} else {
    echo "Error during cleanup: " . mysqli_error($connection) . "\n";
}

// Close connection
mysqli_close($connection);
?>
