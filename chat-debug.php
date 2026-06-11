<?php
require_once __DIR__ . '/includes/init.php';
// Simple test file to debug chat functionality
echo "<h2>Chat System Debug Test</h2>";

// Check session
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ No user session found. Please login first.</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    exit();
}

echo "<p style='color: green;'>✅ User session found</p>";
echo "<p><strong>User ID:</strong> " . $_SESSION['user_id'] . "</p>";
echo "<p><strong>User Role:</strong> " . $_SESSION['role'] . "</p>";
echo "<p><strong>User Name:</strong> " . $_SESSION['name'] . "</p>";

// Test AJAX endpoint
echo "<h3>Testing AJAX Endpoint</h3>";
echo "<p><a href='<?php echo hms_url('modules/shared/chat-ajax.php'); ?>'?type=Patient-Doctor' target='_blank'>Test Doctor List</a></p>";
echo "<p><a href='<?php echo hms_url('modules/shared/chat-ajax.php'); ?>'?type=Patient-Associate' target='_blank'>Test Associate List</a></p>";
echo "<p><a href='<?php echo hms_url('modules/shared/chat-ajax.php'); ?>'?type=Patient-Donor' target='_blank'>Test Donor List</a></p>";

// Test chat page
echo "<h3>Chat System</h3>";
echo "<p><a href='<?php echo hms_url('modules/shared/medical-chat.php'); ?>'>Go to Medical Chat</a></p>";

// Test form submission
echo "<h3>Test Form Submission</h3>";
echo "<form method='POST' action='<?php echo hms_url('modules/shared/chat-actions.php'); ?>'>";
echo "<input type='hidden' name='conversation_type' value='Patient-Doctor'>";
echo "<input type='hidden' name='other_party_id' value='14'>";
echo "<input type='hidden' name='create_conversation' value='1'>";
echo "<button type='submit'>Test Create Conversation</button>";
echo "</form>";
?>
