<?php
require_once __DIR__ . '/includes/init.php';
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Determine current user type based on HMS role system
$user_type = '';
switch ($user_role) {
    case 1: $user_type = 'Admin'; break;
    case 2: $user_type = 'Doctor'; break;
    case 3: $user_type = 'Patient'; break;
    case 4: $user_type = 'Donor'; break;
    default: $user_type = 'Unknown'; break;
}

// Get requested type from AJAX call
$type = $_GET['type'] ?? null;
$blood_group = $_GET['blood_group'] ?? null;

$data = [];
$blood_groups = [];
$success = true;
$message = '';

try {
    if (!$type) {
        $success = false;
        $message = 'Conversation type not specified.';
    } else {
        // Enforce patient restriction: patients can only use Patient-Doctor
        if ($user_type == 'Patient' && $type !== 'Patient-Doctor') {
            $success = false;
            $message = 'Patients can only chat with doctors/admins.';
        } else {
            // Fetch list based on conversation type and current user
            if ($type == 'Patient-Doctor') {
                if ($user_type == 'Patient') {
                    // Patient wants to talk to a Doctor/Admin
                    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, 
                            COALESCE(bio, 'General Practice') as specialization 
                            FROM tbl_employee 
                            WHERE status = 1 AND role IN (1,2) 
                            ORDER BY first_name, last_name";
                } elseif ($user_type == 'Doctor') {
                    // Doctor wants to talk to a Patient
                    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email as type 
                            FROM tbl_patient 
                            WHERE status = 1 AND id != $user_id 
                            ORDER BY first_name, last_name";
                }

                if (isset($sql)) {
                    $result = mysqli_query($connection, $sql);
                    if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $data[] = $row;
                        }
                    }
                }

            } elseif ($type == 'Patient-Associate') {
                if ($user_type == 'Patient') {
                    // Patient wants to talk to an Associate
                    $sql = "SELECT associate_id as id, CONCAT(first_name, ' ', last_name) as name, associate_type as type 
                            FROM associates 
                            WHERE status = 'Active' 
                            ORDER BY first_name, last_name";
                } elseif ($user_type == 'Associate') {
                    // Associate wants to talk to a Patient
                    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email as type 
                            FROM tbl_patient 
                            WHERE status = 1 AND id != $user_id 
                            ORDER BY first_name, last_name";
                }

                if (isset($sql)) {
                    $result = mysqli_query($connection, $sql);
                    if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $data[] = $row;
                        }
                    }
                }

            } elseif ($type == 'Patient-Donor') {
                if ($user_type == 'Patient') {
                    // Patient wants to talk to a Donor
                    if ($blood_group) {
                        // Step 2: Load donors of the selected blood group
                        $blood_group_safe = mysqli_real_escape_string($connection, $blood_group);
                        $sql = "SELECT donor_id as id, name, blood_type 
                                FROM donors 
                                WHERE status = 'Active' AND blood_type = '$blood_group_safe'
                                ORDER BY name";
                        $result = mysqli_query($connection, $sql);
                        if ($result) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $data[] = $row;
                            }
                        }
                    } else {
                        // Step 1: Load available blood groups first
                        $sql = "SELECT DISTINCT blood_type FROM donors WHERE status = 'Active' ORDER BY blood_type";
                        $result = mysqli_query($connection, $sql);
                        if ($result) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $blood_groups[] = $row['blood_type'];
                            }
                        }
                    }
                } elseif ($user_type == 'Donor') {
                    // Donor wants to talk to a Patient
                    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email as type 
                            FROM tbl_patient 
                            WHERE status = 1 AND id != $user_id 
                            ORDER BY first_name, last_name";
                    $result = mysqli_query($connection, $sql);
                    if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $data[] = $row;
                        }
                    }
                }

            } elseif ($type == 'Doctor-Associate') {
                if ($user_type == 'Doctor') {
                    // Doctor wants to talk to an Associate
                    $sql = "SELECT associate_id as id, CONCAT(first_name, ' ', last_name) as name, associate_type as type 
                            FROM associates 
                            WHERE status = 'Active' 
                            ORDER BY first_name, last_name";
                } elseif ($user_type == 'Associate') {
                    // Associate wants to talk to a Doctor
                    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, 
                            COALESCE(bio, 'General Practice') as specialization 
                            FROM tbl_employee 
                            WHERE role = '2' AND status = 1 
                            ORDER BY first_name, last_name";
                }

                if (isset($sql)) {
                    $result = mysqli_query($connection, $sql);
                    if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $data[] = $row;
                        }
                    }
                }

            } else {
                $success = false;
                $message = 'Unsupported conversation type.';
            }
        }
    }

} catch (Exception $e) {
    $success = false;
    $message = 'Database error: ' . $e->getMessage();
    error_log("Chat AJAX Error: " . $e->getMessage()); 
}

// Prepare and send JSON response
$response = ['success' => $success];
if (!empty($data)) {
    $response['data'] = $data;
}
if (!empty($blood_groups)) {
    $response['blood_groups'] = $blood_groups;
}
if (!empty($message)) {
    $response['message'] = $message;
}

echo json_encode($response);
exit();
?>