<?php
require_once __DIR__ . '/includes/init.php';
// ব্যবহারকারী লগইন করা আছে কিনা এবং সে পেশেন্ট (role 3) কিনা তা পরীক্ষা করুন
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 3) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

// চেক করুন ফর্মটি সাবমিট করা হয়েছে কিনা
if (isset($_POST['submit_attachment'])) {
    
    $patient_id = $_SESSION['user_id'];
    $description = mysqli_real_escape_string($connection, $_POST['doc_desc']);

    // ফাইল হ্যান্ডলিং
    $file = $_FILES['doc_file'];
    
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    // ফাইলের এক্সটেনশন বের করা
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // কোন কোন ফাইল আপলোড করা যাবে
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 10000000) { // 10MB ফাইল সাইজ লিমিট
                
                // ফাইলের জন্য একটি ইউনিক নাম তৈরি করা
                $newFileName = "patient_" . $patient_id . "_" . uniqid('', true) . "." . $fileExt;
                
                // প্রতিটি পেশেন্টের জন্য আলাদা ফোল্ডার তৈরি করা
                // নিশ্চিত করুন hms/uploads/ ফোল্ডারটি আছে
                $uploadDir = 'uploads/patient_attachments/' . $patient_id . '/';
                
                // যদি ফোল্ডার না থাকে তবে তৈরি করুন
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileDestination = $uploadDir . $newFileName;

                // ফাইলটি সার্ভারে আপলোড করুন
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    
                    // ফাইল সফলভাবে আপলোড হলে ডাটাবেসে তথ্য সেভ করুন
                    // (doctor_id এবং associate_id এখানে NULL থাকবে কারণ পেশেন্ট নিজে আপলোড করছে)
                    $sql = "INSERT INTO tbl_attachment (patient_id, doctor_id, associate_id, file_name, file_path, file_type, file_size, description) 
                            VALUES ('$patient_id', NULL, NULL, '$fileName', '$fileDestination', '$fileType', '$fileSize', '$description')";
                    
                    if (mysqli_query($connection, $sql)) {
                        $_SESSION['upload_status'] = ['type' => 'success', 'message' => 'ফাইল সফলভাবে আপলোড হয়েছে!'];
                    } else {
                        $_SESSION['upload_status'] = ['type' => 'danger', 'message' => 'ডাটাবেস এরর: ' . mysqli_error($connection)];
                    }
                } else {
                    $_SESSION['upload_status'] = ['type' => 'danger', 'message' => 'ফাইল আপলোড ব্যর্থ হয়েছে।'];
                }
            } else {
                $_SESSION['upload_status'] = ['type' => 'danger', 'message' => 'ফাইল সাইজ অনেক বড়! (সর্বোচ্চ 10MB)'];
            }
        } else {
            $_SESSION['upload_status'] = ['type' => 'danger', 'message' => 'ফাইল আপলোডে ত্রুটি হয়েছে।'];
        }
    } else {
        $_SESSION['upload_status'] = ['type' => 'danger', 'message' => 'অবৈধ ফাইল টাইপ! (শুধুমাত্র JPG, PNG, PDF অনুমোদিত)'];
    }
    
    // কাজ শেষে वापस patient-attachments.php পেজে রিডাইরেক্ট করুন
    header('Location: ' . hms_url('modules/shared/patient-attachments.php'));
    exit();
} else {
    // যদি কেউ সরাসরি এই ফাইল খোলার চেষ্টা করে
    header('Location: ' . hms_url('modules/shared/patient-attachments.php'));
    exit();
}
?>