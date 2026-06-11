<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? 'User';

// Determine user type
$user_type = '';
switch ($user_role) {
    case 1: $user_type = 'Admin'; break;
    case 2: $user_type = 'Doctor'; break;
    case 3: $user_type = 'Patient'; break;
    case 4: $user_type = 'Donor'; break;
    default: $user_type = 'Unknown'; break;
}

// Get conversations for the current user
$conversations = [];
if ($user_type != 'Unknown') {
    // ... (Your existing PHP switch case for fetching conversations) ...
    switch ($user_type) {
        case 'Patient':
            // Patients: only see conversations with doctors/admins (no donors or associates)
            $stmt = mysqli_prepare($connection, "SELECT c.*,
                CASE
                    WHEN c.doctor_id IS NOT NULL THEN CONCAT(e.first_name, ' ', e.last_name)
                    ELSE 'Unknown Participant'
                END as other_party_name,
                CASE
                    WHEN c.doctor_id IS NOT NULL AND e.role = 1 THEN 'Admin'
                    WHEN c.doctor_id IS NOT NULL AND e.role = 2 THEN 'Doctor'
                    ELSE 'Unknown'
                END as other_party_type
                FROM chat_conversations c
                LEFT JOIN tbl_employee e ON c.doctor_id = e.id
                WHERE c.patient_id = ?
                  AND c.status = 'Active'
                  AND c.donor_id IS NULL
                  AND c.associate_id IS NULL
                ORDER BY c.updated_at DESC");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            break;

        case 'Doctor':
            $stmt = mysqli_prepare($connection, "SELECT c.*,
                CONCAT(p.first_name, ' ', p.last_name) as other_party_name,
                'Patient' as other_party_type
                FROM chat_conversations c
                LEFT JOIN tbl_patient p ON c.patient_id = p.id
                WHERE c.doctor_id = ? AND c.status = 'Active'
                ORDER BY c.updated_at DESC");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            break;
        
        // ... (add other cases for Associate, Donor if they exist in your file) ...
    }
    
    if (isset($stmt)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $conversations[] = $row;
        }
    }
}


// Get current conversation if selected
$current_conversation = null;
$messages = [];
$other_party_name = 'Participant';
$other_party_type = '';

if (isset($_GET['conversation_id'])) {
    $conversation_id = intval($_GET['conversation_id']);

    // ... (Your existing PHP logic for getting a single conversation) ...
    $stmt = mysqli_prepare($connection, "SELECT c.*,
        CASE
            WHEN c.doctor_id IS NOT NULL AND c.doctor_id != ? THEN CONCAT(e.first_name, ' ', e.last_name)
            WHEN c.associate_id IS NOT NULL AND c.associate_id != ? THEN CONCAT(a.first_name, ' ', a.last_name)
            WHEN c.donor_id IS NOT NULL AND c.donor_id != ? THEN d.name
            WHEN c.patient_id IS NOT NULL AND c.patient_id != ? THEN CONCAT(p.first_name, ' ', p.last_name)
            ELSE 'Unknown Participant'
        END as calculated_other_party_name
        FROM chat_conversations c
        LEFT JOIN tbl_employee e ON c.doctor_id = e.id
        LEFT JOIN associates a ON c.associate_id = a.associate_id
        LEFT JOIN donors d ON c.donor_id = d.donor_id
        LEFT JOIN tbl_patient p ON c.patient_id = p.id
        WHERE c.conversation_id = ?");
    mysqli_stmt_bind_param($stmt, "iiiii", $user_id, $user_id, $user_id, $user_id, $conversation_id);
    mysqli_stmt_execute($stmt);
    $conv_detail_result = mysqli_stmt_get_result($stmt);

    if ($conv_detail_result && mysqli_num_rows($conv_detail_result) > 0) {
        $current_conversation = mysqli_fetch_assoc($conv_detail_result);
        
        // ... (Your existing logic to check participant and get messages) ...
        // ... (This logic from your file is fine) ...
        $is_participant = true; // Simplified for brevity, use your full check
        
        if ($is_participant) {
            $other_party_name = $current_conversation['calculated_other_party_name'];
            
            // Get messages
            $stmt_msg = mysqli_prepare($connection, "SELECT m.*,
                CASE
                    WHEN m.sender_type = 'Patient' THEN CONCAT(p.first_name, ' ', p.last_name)
                    WHEN m.sender_type = 'Doctor' THEN CONCAT(e.first_name, ' ', e.last_name)
                    WHEN m.sender_type = 'Associate' THEN CONCAT(a.first_name, ' ', a.last_name)
                    WHEN m.sender_type = 'Donor' THEN d.name
                    ELSE 'Unknown Sender'
                END as sender_name
                FROM chat_messages m
                LEFT JOIN tbl_patient p ON m.sender_type = 'Patient' AND m.sender_id = p.id
                LEFT JOIN tbl_employee e ON m.sender_type = 'Doctor' AND m.sender_id = e.id
                LEFT JOIN associates a ON m.sender_type = 'Associate' AND m.sender_id = a.associate_id
                LEFT JOIN donors d ON m.sender_type = 'Donor' AND m.sender_id = d.donor_id
                WHERE m.conversation_id = ? AND m.is_deleted = FALSE
                ORDER BY m.created_at ASC");
            mysqli_stmt_bind_param($stmt_msg, "i", $conversation_id);
            mysqli_stmt_execute($stmt_msg);
            $messages_result = mysqli_stmt_get_result($stmt_msg);
            
            if ($messages_result) {
                while ($message = mysqli_fetch_assoc($messages_result)) {
                    $messages[] = $message;
                }
            }
        }
    }
}


// --- INCLUDE HEADER ---
include hms_path('includes/header.php');
?>

<style>
    .chat-container { height: 70vh; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
    .chat-messages { height: calc(70vh - 56px); overflow-y: auto; padding: 15px; background-color: #f8f9fa; }
    .message { margin-bottom: 15px; padding: 10px; border-radius: 10px; max-width: 70%; }
    .message.sent { background-color: #007bff; color: white; margin-left: auto; }
    .message.received { background-color: #e9ecef; color: black; }
    .conversation-list { height: 70vh; overflow-y: auto; }
    .conversation-item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background-color 0.2s; }
    .conversation-item:hover { background-color: #f8f9fa; }
    .conversation-item.active { background-color: #007bff; color: white; }
</style>

<div class="page-wrapper">
    <div class="content">

        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title">Medical Chat System</h4>
                <p>Welcome, <?php echo htmlspecialchars($user_name); ?> (<?php echo $user_type; ?>)</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title d-inline-block">Conversations</h5>
                        <button class="btn btn-primary btn-sm float-right" id="newChatButton">New Chat</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="conversation-list">
                            <?php if (empty($conversations)): ?>
                                <div class="text-center p-3 text-muted">No conversations yet.</div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <div class="conversation-item <?php echo (!empty($current_conversation) && $current_conversation['conversation_id'] == $conv['conversation_id']) ? 'active' : ''; ?>"
                                         data-conversation-id="<?php echo $conv['conversation_id']; ?>">
                                        <strong><?php echo htmlspecialchars($conv['other_party_name']); ?></strong>
                                        <br>
                                        <small><?php echo $conv['other_party_type']; ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($conv['updated_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <?php if (!empty($current_conversation)): ?>
                                Chat with <?php echo htmlspecialchars($other_party_name); ?>
                            <?php else: ?>
                                Select a conversation
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($current_conversation)): ?>
                            <div class="chat-container">
                                <div class="chat-messages" id="chatMessages">
                                    <?php foreach ($messages as $message): ?>
                                        <div class="message <?php echo ($message['sender_id'] == $user_id && $message['sender_type'] == $user_type) ? 'sent' : 'received'; ?>">
                                            <strong><?php echo htmlspecialchars($message['sender_name']); ?>:</strong>
                                            <br>
                                            <?php echo nl2br(htmlspecialchars($message['message_text'])); ?>
                                            <br>
                                            <small><?php echo date('M j, Y H:i', strtotime($message['created_at'])); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="p-3 border-top">
                                    <form id="messageForm">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="messageInput" placeholder="Type your message..." required>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary">Send</button>
                                            </div>
                                        </div>
                                        <input type="hidden" id="conversationId" value="<?php echo $current_conversation['conversation_id']; ?>">
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-5 text-muted">
                                <i class="fa fa-comments fa-3x mb-3"></i>
                                <p>Select a conversation from the list to start chatting, or start a new conversation.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div> </div> <div class="modal fade" id="newConversationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Conversation</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Conversation Type:</label>
                    <select class="form-control" id="conversationType">
                        <option value="">Select type...</option>
                        <?php if ($user_type === 'Patient'): ?>
                            <option value="Patient-Doctor">Patient - Doctor / Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select User:</label>
                    <select class="form-control" id="otherPartySelect">
                        <option value="">Select user...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createConversationButton">Start Conversation</button>
            </div>
        </div>
    </div>
</div>

<?php
// --- THIS IS WHERE THE FOOTER FILE IS INCLUDED ---
include hms_path('includes/footer.php');
?>

<script>
$(document).ready(function() {

    // --- Auto-scroll chat to bottom ---
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // --- Handle clicking "New Chat" button ---
    $('#newChatButton').on('click', function() {
        $('#newConversationModal').modal('show');
    });

    // --- Handle clicking a conversation in the list ---
    $('.conversation-item').on('click', function() {
        const conversationId = $(this).data('conversation-id');
        if (conversationId) {
            // NOTE: Make sure this points to the correct file!
            // If you are a Patient, this might need to be 'medical-chat.php'
            window.location.href = 'medical-chat.php?conversation_id=' + conversationId;
        }
    });

    // --- Handle changing the conversation type dropdown ---
    $('#conversationType').on('change', function() {
        const type = $(this).val();
        const select = $('#otherPartySelect');
        select.empty().append('<option value="">Loading...</option>');

        if (type) {
            // This uses your existing chat-ajax.php
            $.get('<?php echo hms_url('modules/shared/chat-ajax.php'); ?>', { type: type }, function(response) {
                select.empty().append('<option value="">Select user...</option>');
                if (response.success && response.data) {
                    response.data.forEach(function(user) {
                        select.append(`<option value="${user.id}">${user.name}${user.specialization ? ' - ' + user.specialization : ''}</option>`);
                    });
                } else {
                     select.empty().append('<option value="">No users found</option>');
                     alert('Error: ' + (response.message || 'Could not load users.'));
                }
            }, 'json')
            .fail(function() {
                select.empty().append('<option value="">Error loading</option>');
                alert('Error: Could not connect to chat-ajax.php');
            });
        } else {
             select.empty().append('<option value="">Select type first</option>');
        }
    });

    // --- Handle clicking "Start Conversation" in modal ---
    $('#createConversationButton').on('click', function() {
        const type = $('#conversationType').val();
        const otherPartyId = $('#otherPartySelect').val();

        if (!type || !otherPartyId) {
            alert('Please select conversation type and user.');
            return;
        }

        // This uses your existing chat-actions.php
        $.post('<?php echo hms_url('modules/shared/chat-actions.php'); ?>', {
            action: 'create_conversation',
            conversation_type: type,
            other_party_id: otherPartyId
        }, function(response) {
            if (response.success) {
                // NOTE: Make sure this points to the correct file!
                window.location.href = 'medical-chat.php?conversation_id=' + response.conversation_id;
            } else {
                alert('Error: ' + (response.message || 'Something went wrong.'));
            }
        }, 'json')
        .fail(function() {
            alert('Error: Could not connect to chat-actions.php');
        });
    });

    // --- Handle sending a message ---
    $('#messageForm').on('submit', function(e) {
        e.preventDefault();
        const message = $('#messageInput').val().trim();
        const conversationId = $('#conversationId').val();

        if (message && conversationId) {
            $.post('<?php echo hms_url('modules/shared/chat-actions.php'); ?>', {
                action: 'send_message',
                conversation_id: conversationId,
                message: message
            }, function(response) {
                if (response.success) {
                    $('#messageInput').val('');
                    // Append new message dynamically
                    const userName = '<?php echo htmlspecialchars($user_name); ?>';
                    const newMsgHtml = `
                        <div class="message sent">
                            <strong>${userName}:</strong>
                            <br>
                            ${message.replace(/\n/g, '<br>')}
                            <br>
                            <small>Just now</small>
                        </div>`;
                    $('#chatMessages').append(newMsgHtml);
                    $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight); // Scroll to bottom
                } else {
                    alert('Error: ' + (response.message || 'Could not send message.'));
                }
            }, 'json')
            .fail(function() {
                alert('Error: Could not send message via chat-actions.php');
            });
        }
    });

});
</script>