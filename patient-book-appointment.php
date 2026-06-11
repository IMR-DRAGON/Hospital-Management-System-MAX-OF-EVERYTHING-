<?php
require_once __DIR__ . '/includes/init.php';
// Check if user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$patient_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get patient information
$patient_query = "SELECT * FROM tbl_patient WHERE id = $patient_id";
$patient_result = mysqli_query($connection, $patient_query);
$patient = mysqli_fetch_assoc($patient_result);

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = intval($_POST['doctor']);
    $department = mysqli_real_escape_string($connection, $_POST['department']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = mysqli_real_escape_string($connection, $_POST['reason']);
    
    // Generate appointment ID
    $fetch_query = mysqli_query($connection, "SELECT MAX(id) as id FROM tbl_appointment");
    $row = mysqli_fetch_row($fetch_query);
    $apt_id = ($row[0] == 0) ? 1 : $row[0] + 1;
    $appointment_id = 'APT-' . $apt_id;
    
    // Get doctor name
    $doctor_query = "SELECT first_name, last_name FROM tbl_employee WHERE id = $doctor_id";
    $doctor_result = mysqli_query($connection, $doctor_query);
    $doctor_data = mysqli_fetch_assoc($doctor_result);
    $doctor_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];
    
    // Check if appointment slot is available
    $check_query = "SELECT id FROM tbl_appointment WHERE doctor = '$doctor_name' AND date = '$appointment_date' AND time = '$appointment_time' AND status = 1";
    $check_result = mysqli_query($connection, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "This appointment slot is already booked. Please choose a different time.";
    } else {
        $patient_name = trim($patient['first_name']) . ' ' . trim($patient['last_name']);
        
        $insert_query = "INSERT INTO tbl_appointment (appointment_id, patient_name, department, doctor, date, time, message, status) VALUES ('$appointment_id', '$patient_name', '$department', '$doctor_name', '$appointment_date', '$appointment_time', '$reason', 1)";
        
        if (mysqli_query($connection, $insert_query)) {
            $message = "Appointment booked successfully! Your appointment ID is: " . $appointment_id;
        } else {
            $error = "Error booking appointment: " . mysqli_error($connection);
        }
    }
}

// Get available doctors (role = 2 for doctors)
$doctors_query = "SELECT id, first_name, last_name, role, bio, phone FROM tbl_employee WHERE status = 1 AND role = '2' ORDER BY first_name, last_name";
$doctors_result = mysqli_query($connection, $doctors_query);

// Get departments
$departments_query = "SELECT DISTINCT department_name FROM tbl_department ORDER BY department_name";
$departments_result = mysqli_query($connection, $departments_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - HMS</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap-datetimepicker.min.css'); ?>">
</head>
<body>
    <?php include hms_path('includes/header.php'); ?>
    
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-6">
                        <h4 class="page-title">Book Appointment</h4>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="<?php echo hms_url('modules/patient/patient-dashboard.php'); ?>" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Appointment Details</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Patient Name</label>
                                                <input type="text" class="form-control" value="<?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Patient Email</label>
                                                <input type="email" class="form-control" value="<?php echo $patient['email']; ?>" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Select Doctor <span class="text-danger">*</span></label>
                                                <select class="form-control" name="doctor" required>
                                                    <option value="">Choose a doctor</option>
                                                    <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                                                    <option value="<?php echo $doctor['id']; ?>">
                                                        Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?>
                                                        <?php if (!empty($doctor['bio'])): ?>
                                                            - <?php echo substr($doctor['bio'], 0, 50) . (strlen($doctor['bio']) > 50 ? '...' : ''); ?>
                                                        <?php endif; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <small class="form-text text-muted">
                                                    <i class="fa fa-info-circle"></i> Select a doctor to see their specialization and availability
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Department <span class="text-danger">*</span></label>
                                                <select class="form-control" name="department" required>
                                                    <option value="">Select Department</option>
                                                    <?php while ($dept = mysqli_fetch_assoc($departments_result)): ?>
                                                    <option value="<?php echo $dept['department_name']; ?>">
                                                        <?php echo $dept['department_name']; ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Preferred Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Preferred Time <span class="text-danger">*</span></label>
                                                <select class="form-control" name="appointment_time" required>
                                                    <option value="">Select Time</option>
                                                    <option value="09:00:00">9:00 AM</option>
                                                    <option value="10:00:00">10:00 AM</option>
                                                    <option value="11:00:00">11:00 AM</option>
                                                    <option value="12:00:00">12:00 PM</option>
                                                    <option value="14:00:00">2:00 PM</option>
                                                    <option value="15:00:00">3:00 PM</option>
                                                    <option value="16:00:00">4:00 PM</option>
                                                    <option value="17:00:00">5:00 PM</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Reason for Visit / Symptoms</label>
                                        <textarea class="form-control" name="reason" rows="4" placeholder="Please describe your symptoms or reason for the appointment"></textarea>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" name="book_appointment" class="btn btn-primary btn-lg">
                                            <i class="fa fa-calendar"></i> Book Appointment
                                        </button>
                                        <button type="reset" class="btn btn-secondary btn-lg">Reset</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Doctor Information Card -->
                        <div class="card mb-3" id="doctor-info-card" style="display: none;">
                            <div class="card-header">
                                <h4 class="card-title">
                                    <i class="fa fa-user-md text-primary"></i> Doctor Information
                                </h4>
                            </div>
                            <div class="card-body" id="doctor-info-content">
                                <!-- Doctor details will be loaded here via JavaScript -->
                            </div>
                        </div>

                        <!-- Booking Information Card -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Booking Information</h4>
                            </div>
                            <div class="card-body">
                                <h6><i class="fa fa-info-circle text-primary"></i> Booking Guidelines:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fa fa-check text-success"></i> Appointments can be booked up to 30 days in advance</li>
                                    <li><i class="fa fa-check text-success"></i> Same-day appointments are subject to availability</li>
                                    <li><i class="fa fa-check text-success"></i> You will receive a confirmation once your appointment is booked</li>
                                    <li><i class="fa fa-check text-success"></i> Please arrive 15 minutes before your scheduled time</li>
                                </ul>
                                
                                <hr>
                                
                                <h6><i class="fa fa-phone text-primary"></i> Need Help?</h6>
                                <p class="text-muted">Contact our appointment desk at:<br>
                                <strong>Phone:</strong> (555) 123-4567<br>
                                <strong>Email:</strong> appointments@hospital.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap-datetimepicker.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
    <script>
        // Doctor information data (loaded from PHP)
        const doctorData = {
            <?php 
            // Reset the doctors result pointer
            mysqli_data_seek($doctors_result, 0);
            $first = true;
            while ($doctor = mysqli_fetch_assoc($doctors_result)): 
                if (!$first) echo ",";
                $first = false;
            ?>
            <?php echo $doctor['id']; ?>: {
                name: "Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?>",
                bio: "<?php echo addslashes($doctor['bio']); ?>",
                phone: "<?php echo $doctor['phone']; ?>",
                email: "<?php echo $doctor['email']; ?>"
            }
            <?php endwhile; ?>
        };

        // Auto-populate department when doctor is selected and show doctor info
        document.querySelector('select[name="doctor"]').addEventListener('change', function() {
            const doctorId = this.value;
            const doctorInfoCard = document.getElementById('doctor-info-card');
            const doctorInfoContent = document.getElementById('doctor-info-content');
            
            if (doctorId && doctorData[doctorId]) {
                const doctor = doctorData[doctorId];
                
                // Show doctor information
                doctorInfoContent.innerHTML = `
                    <div class="text-center mb-3">
                        <div class="doctor-avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fa fa-user-md fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="text-center mb-3">${doctor.name}</h5>
                    ${doctor.bio ? `<p class="text-muted"><strong>Specialization:</strong><br>${doctor.bio}</p>` : ''}
                    ${doctor.phone ? `<p class="mb-1"><i class="fa fa-phone text-primary"></i> <strong>Phone:</strong> ${doctor.phone}</p>` : ''}
                    ${doctor.email ? `<p class="mb-0"><i class="fa fa-envelope text-primary"></i> <strong>Email:</strong> ${doctor.email}</p>` : ''}
                `;
                
                doctorInfoCard.style.display = 'block';
                
                // In a real implementation, you would fetch the doctor's department via AJAX
                console.log('Doctor selected:', doctorId, doctor);
            } else {
                doctorInfoCard.style.display = 'none';
            }
        });
    </script>
</body>
</html>
