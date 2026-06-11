<?php
require_once __DIR__ . '/includes/init.php';
// Simple database setup script for chat system
echo "<h2>Chat Database Setup</h2>";

// Create chat_conversations table
$sql = "CREATE TABLE IF NOT EXISTS chat_conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_uuid VARCHAR(36) NOT NULL UNIQUE,
    patient_id INT NULL,
    doctor_id INT NULL,
    associate_id INT NULL,
    donor_id INT NULL,
    conversation_type ENUM('Patient-Doctor', 'Patient-Associate', 'Patient-Donor', 'Doctor-Associate') NOT NULL,
    title VARCHAR(200),
    description TEXT,
    status ENUM('Active', 'Closed', 'Archived') DEFAULT 'Active',
    is_active BOOLEAN DEFAULT TRUE,
    is_archived BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT TRUE,
    encryption_key VARCHAR(255) NULL,
    privacy_level ENUM('Private') DEFAULT 'Private',
    allow_file_sharing BOOLEAN DEFAULT FALSE,
    last_message_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100)
)";

if (mysqli_query($connection, $sql)) {
    echo "<span style='color: green;'>✓</span> chat_conversations table created<br>";
} else {
    echo "<span style='color: red;'>✗</span> Error creating chat_conversations: " . mysqli_error($connection) . "<br>";
}

// Create chat_messages table
$sql = "CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('Patient', 'Doctor', 'Associate', 'Donor') NOT NULL,
    message_text TEXT,
    message_type ENUM('Text', 'File', 'Prescription', 'Medical_Record') DEFAULT 'Text',
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($connection, $sql)) {
    echo "<span style='color: green;'>✓</span> chat_messages table created<br>";
} else {
    echo "<span style='color: red;'>✗</span> Error creating chat_messages: " . mysqli_error($connection) . "<br>";
}

// Create chat_files table
$sql = "CREATE TABLE IF NOT EXISTS chat_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_category ENUM('Medical_Record', 'Prescription', 'Lab_Report', 'X_Ray', 'MRI', 'CT_Scan', 'Blood_Test', 'Other') NOT NULL,
    description TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($connection, $sql)) {
    echo "<span style='color: green;'>✓</span> chat_files table created<br>";
} else {
    echo "<span style='color: red;'>✗</span> Error creating chat_files: " . mysqli_error($connection) . "<br>";
}

// Create chat_participants table
$sql = "CREATE TABLE IF NOT EXISTS chat_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('Patient', 'Doctor', 'Associate', 'Donor') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_participant (conversation_id, user_id, user_type)
)";

if (mysqli_query($connection, $sql)) {
    echo "<span style='color: green;'>✓</span> chat_participants table created<br>";
} else {
    echo "<span style='color: red;'>✗</span> Error creating chat_participants: " . mysqli_error($connection) . "<br>";
}

// Create file_categories table
$sql = "CREATE TABLE IF NOT EXISTS file_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_description TEXT,
    allowed_extensions TEXT,
    max_file_size INT DEFAULT 10485760,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($connection, $sql)) {
    echo "<span style='color: green;'>✓</span> file_categories table created<br>";
} else {
    echo "<span style='color: red;'>✗</span> Error creating file_categories: " . mysqli_error($connection) . "<br>";
}

// Insert default file categories
$categories = [
    ['Medical Records', 'General medical records and reports', 'pdf,doc,docx,jpg,jpeg,png', 10485760],
    ['Prescriptions', 'Doctor prescriptions and medication records', 'pdf,doc,docx,jpg,jpeg,png', 5242880],
    ['Lab Reports', 'Laboratory test results and reports', 'pdf,jpg,jpeg,png', 10485760],
    ['X-Ray Images', 'X-Ray and imaging results', 'jpg,jpeg,png,dcm', 20971520],
    ['MRI/CT Scans', 'MRI and CT scan results', 'jpg,jpeg,png,dcm', 52428800],
    ['Blood Tests', 'Blood test results and reports', 'pdf,jpg,jpeg,png', 10485760],
    ['Other Documents', 'Other medical documents and files', 'pdf,doc,docx,jpg,jpeg,png,txt', 10485760]
];

foreach ($categories as $category) {
    $sql = "INSERT IGNORE INTO file_categories (category_name, category_description, allowed_extensions, max_file_size) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $category[0], $category[1], $category[2], $category[3]);
    mysqli_stmt_execute($stmt);
}

echo "<span style='color: green;'>✓</span> Default file categories inserted<br>";

// Create uploads directory
$upload_dir = 'uploads/chat_files';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<span style='color: green;'>✓</span> Created upload directory: $upload_dir<br>";
    } else {
        echo "<span style='color: red;'>✗</span> Failed to create upload directory: $upload_dir<br>";
    }
} else {
    echo "<span style='color: green;'>✓</span> Upload directory exists: $upload_dir<br>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p><a href='<?php echo hms_url('modules/shared/chat.php'); ?>'>Go to Chat System</a></p>";
?>
