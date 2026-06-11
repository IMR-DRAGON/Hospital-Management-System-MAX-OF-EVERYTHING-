<?php
require_once __DIR__ . '/includes/init.php';
header('Content-Type: application/json');

// --- Helper function to send JSON response ---
function send_json_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit();
}

// --- Check if user is logged in ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    send_json_response(false, 'User not logged in.');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_type = '';

switch ($user_role) {
    case 1: $user_type = 'Admin'; break;
    case 2: $user_type = 'Doctor'; break;
    case 3: $user_type = 'Patient'; break;
    case 4: $user_type = 'Donor'; break;
    default: $user_type = 'Unknown'; break;
}

if ($user_type == 'Unknown') {
     send_json_response(false, 'Invalid user role.');
}

// Use $_POST for actions
$action = $_POST['action'] ?? null;
$response = ['success' => false];

try {
    if ($action == 'create_conversation') {
        
        $conversation_type = $_POST['conversation_type'] ?? '';
        $other_party_id = intval($_POST['other_party_id'] ?? 0);
        $patient_id = null;
        $doctor_id = null;
        $associate_id = null;
        $donor_id = null;
        
        // Determine who is who based on type and logged-in user
        if ($user_type == 'Patient') {
            // Patients are only allowed to start conversations with doctors/admins
            if ($conversation_type !== 'Patient-Doctor') {
                send_json_response(false, 'Patients can only start chats with doctors/admins.');
            }
            $patient_id = $user_id;
            $doctor_id = $other_party_id;
        }
        // Add other 'if' blocks here if Doctors can start chats with Patients, etc.
        
        if ($other_party_id == 0) {
            send_json_response(false, 'Invalid participant selected.');
        }

        // Generate a simple UUID string for the conversation
        $conversation_uuid = uniqid('conv_', true);

        // Insert new conversation (match actual table columns)
        // chat_conversations: conversation_uuid, patient_id, doctor_id, associate_id, donor_id, conversation_type, created_by
        $stmt = mysqli_prepare(
            $connection,
            "INSERT INTO chat_conversations 
                (conversation_uuid, patient_id, doctor_id, associate_id, donor_id, conversation_type, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "siiiiss",
            $conversation_uuid,
            $patient_id,
            $doctor_id,
            $associate_id,
            $donor_id,
            $conversation_type,
            $user_type
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $new_conversation_id = mysqli_insert_id($connection);
            $response['success'] = true;
            $response['conversation_id'] = $new_conversation_id;
            send_json_response(true, 'Conversation created.', ['conversation_id' => $new_conversation_id]);
        } else {
            send_json_response(false, 'Failed to create conversation. '. mysqli_error($connection));
        }

    } elseif ($action == 'send_message') {
        
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (empty($message) || $conversation_id == 0) {
            send_json_response(false, 'Invalid message or conversation ID.');
        }
        
        // Insert message
        $stmt = mysqli_prepare($connection, "INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message_text, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        mysqli_stmt_bind_param($stmt, "iiss", $conversation_id, $user_id, $user_type, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update conversation timestamp
            $stmt_update = mysqli_prepare($connection, "UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE conversation_id = ?");
            mysqli_stmt_bind_param($stmt_update, "i", $conversation_id);
            mysqli_stmt_execute($stmt_update);
            
            send_json_response(true, 'Message sent.');
        } else {
            send_json_response(false, 'Failed to send message.');
        }

    } else {
        send_json_response(false, 'Invalid action specified.');
    }

} catch (Exception $e) {
    send_json_response(false, 'An error occurred: ' . $e->getMessage());
}

?>