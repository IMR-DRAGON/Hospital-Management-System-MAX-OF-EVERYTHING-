<?php
require_once __DIR__ . '/includes/init.php';
if (!isset($_SESSION['user_id'])) {

    header('Location: ' . hms_url('login.php'));

    exit();

}

if ($_SESSION['role'] != 1) {

    header('Location: ' . hms_url('modules/shared/blood-bank.php'));

    exit();

}





if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_donor'])) {

    $name = mysqli_real_escape_string($connection, $_POST['name']);

    $email = mysqli_real_escape_string($connection, $_POST['email']);

    $phone = mysqli_real_escape_string($connection, $_POST['phone']);

    $blood_type = mysqli_real_escape_string($connection, $_POST['blood_type']);

    $age = (int)$_POST['age'];

    $gender = mysqli_real_escape_string($connection, $_POST['gender']);

    $address = mysqli_real_escape_string($connection, $_POST['address']);

    $emergency_contact = mysqli_real_escape_string($connection, $_POST['emergency_contact']);

    $emergency_phone = mysqli_real_escape_string($connection, $_POST['emergency_phone']);

   

    $donor_code = 'DON-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);



   

    $query = "INSERT INTO donors (name, email, phone, blood_type, age, gender, address, emergency_contact, emergency_phone, donor_code, status, created_at)

              VALUES ('$name', '$email', '$phone', '$blood_type', $age, '$gender', '$address', '$emergency_contact', '$emergency_phone', '$donor_code', 'Active', NOW())";



    if (!mysqli_query($connection, $query)) {

       

        $_SESSION['donor_action_status'] = ['type' => 'danger', 'message' => 'Error adding donor: ' . mysqli_error($connection)];

    } else {

        $_SESSION['donor_action_status'] = ['type' => 'success', 'message' => 'Donor added successfully!'];

    }

    header('Location: ' . hms_url('modules/admin/donors.php'));

}





if (isset($_GET['delete'])) {

    $donor_id = (int)$_GET['delete'];

   

    $query = "UPDATE donors SET status='Inactive' WHERE donor_id=$donor_id";

    if (!mysqli_query($connection, $query)) {

         $_SESSION['donor_action_status'] = ['type' => 'danger', 'message' => 'Error deactivating donor: ' . mysqli_error($connection)];

    } else {

         $_SESSION['donor_action_status'] = ['type' => 'success', 'message' => 'Donor marked as inactive successfully!'];

    }

    header('Location: ' . hms_url('modules/admin/donors.php'));

    exit();

}





$donors_query = "SELECT * FROM donors WHERE status = 'Active' ORDER BY name";

$donors_result = mysqli_query($connection, $donors_query);

if (!$donors_result) {

   

    $donor_load_error = "Error fetching donors: " . mysqli_error($connection);

}





$total_donors = 0;

$total_donors_query = "SELECT COUNT(*) as total FROM donors WHERE status = 'Active'";

$total_donors_result = mysqli_query($connection, $total_donors_query);

if ($total_donors_result) {

    $total_donors = mysqli_fetch_assoc($total_donors_result)['total'];

}



$recent_donations = 0;



$recent_donations_query = "SELECT COUNT(*) as recent FROM tbl_blood_bank WHERE collection_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

$recent_donations_result = mysqli_query($connection, $recent_donations_query);

if ($recent_donations_result) {

    $recent_donations_row = mysqli_fetch_assoc($recent_donations_result);

    $recent_donations = $recent_donations_row ? $recent_donations_row['recent'] : 0;

}



$blood_type_stats_query = "SELECT blood_type, COUNT(*) as count FROM donors WHERE status = 'Active' GROUP BY blood_type ORDER BY count DESC";

$blood_type_stats_result = mysqli_query($connection, $blood_type_stats_query);



if (!$blood_type_stats_result) {

    $blood_stats_error = "Error fetching blood type stats: " . mysqli_error($connection);

}



?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Donor Management - HMS</title>

    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">

    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">

    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">

    <link rel="stylesheet" href="<?php echo hms_url('assets/css/dataTables.bootstrap4.min.css'); ?>">

</head>

<body>

    <?php include hms_path('includes/header.php'); ?>

    <div class="main-wrapper">

        <?php  ?>

        <div class="page-wrapper">

            <div class="content">

                <div class="row mb-3">

                    <div class="col-sm-8 col-md-9">

                        <h4 class="page-title">Blood Donor Management</h4>

                    </div>

                    <div class="col-sm-4 col-md-3 text-right">

                        <button class="btn btn-primary btn-rounded" data-toggle="modal" data-target="#addDonorModal"><i class="fa fa-plus"></i> Add Donor</button>

                       

                         <a href="<?php echo hms_url('modules/donor/donor-signup.php'); ?>" class="btn btn-danger btn-rounded ml-2"><i class="fa fa-heartbeat"></i> Public Signup</a>

                    </div>

                </div>



               

                <?php

                if(isset($_SESSION['donor_action_status'])) {

                    echo '<div class="alert alert-' . $_SESSION['donor_action_status']['type'] . ' alert-dismissible fade show" role="alert">';

                    echo htmlspecialchars($_SESSION['donor_action_status']['message']);

                    echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';

                    echo '</div>';

                    unset($_SESSION['donor_action_status']);

                }

               

                if (isset($donor_load_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($donor_load_error) . '</div>';

                if (isset($blood_stats_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($blood_stats_error) . '</div>';

                ?>



               

                <div class="row">

                    <div class="col-md-6 col-lg-3">

                        <div class="card dash-widget">

                            <div class="card-body text-center">

                                <span class="dash-widget-icon"><i class="fa fa-users text-primary"></i></span>

                                <div class="dash-widget-info">

                                    <h3><?php echo $total_donors; ?></h3>

                                    <span>Active Donors</span>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-6 col-lg-3">

                        <div class="card dash-widget">

                            <div class="card-body text-center">

                                <span class="dash-widget-icon"><i class="fa fa-tint text-success"></i></span>

                                <div class="dash-widget-info">

                                     <h3><?php echo $recent_donations; ?></h3>

                                    <span>Recent Donations (30d)</span>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-12 col-lg-6">

                        <div class="card">

                            <div class="card-body">

                                <h6 class="card-title text-center mb-3">Blood Type Distribution (Active Donors)</h6>

                                <div class="row d-flex justify-content-center">

                                    <?php

                                    $blood_types_available = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];

                                    $stats_array = [];

                                    if ($blood_type_stats_result) {

                                        while ($stat = mysqli_fetch_assoc($blood_type_stats_result)) {

                                            $stats_array[$stat['blood_type']] = $stat['count'];

                                        }

                                    }

                                    foreach ($blood_types_available as $bt) {

                                        $count = $stats_array[$bt] ?? 0;

                                        echo '<div class="col-3 text-center mb-2">';

                                        echo '<h5 class="text-danger font-weight-bold">' . htmlspecialchars($bt) . '</h5>';

                                        echo '<span class="badge badge-primary badge-pill" style="font-size: 1rem;">' . $count . '</span>';

                                        echo '</div>';

                                    }

                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <div class="row">

                    <div class="col-md-12">

                        <div class="card">

                           <div class="card-header"><h5 class="card-title mb-0">Active Donor List</h5></div>

                           <div class="card-body">

                                <div class="table-responsive">

                                    <table id="donorTable" class="table table-striped custom-table datatable">

                                        <thead>

                                            <tr>

                                                <th>Serial</th>

                                                <th>Name</th>

                                                <th>Contact</th>

                                                <th>Blood</th>

                                                <th>Age/Gen</th>

                                                <th>Donations</th>

                                                <th>Last Donated</th>

                                                <th>Status</th>

                                                <th class="text-right">Action</th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            <?php if ($donors_result && mysqli_num_rows($donors_result) > 0): ?>

                                                <?php $serial = 1; ?>
                                                <?php while ($row = mysqli_fetch_assoc($donors_result)): ?>

                                                <tr>

                                                    <td><strong><?php echo $serial; ?></strong></td>

                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>

                                                    <td>

                                                        <small><?php echo htmlspecialchars($row['email']); ?></small><br>

                                                        <small><?php echo htmlspecialchars($row['phone']); ?></small>

                                                    </td>

                                                    <td><span class="badge badge-danger"><?php echo htmlspecialchars($row['blood_type']); ?></span></td>

                                                    <td>
                                                        <?php
                                                            $genderSource = isset($row['gender']) ? (string)$row['gender'] : '';
                                                            $genderChar = $genderSource !== '' ? strtoupper(substr($genderSource, 0, 1)) : '-';
                                                            if ($genderChar !== 'M' && $genderChar !== 'F') {
                                                                $genderChar = '-';
                                                            }
                                                            echo htmlspecialchars($row['age']) . ' / ' . htmlspecialchars($genderChar);
                                                        ?>
                                                    </td>

                                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($row['total_donations']); ?></span></td>

                                                    <td><?php echo $row['last_donation_date'] ? date('d M Y', strtotime($row['last_donation_date'])) : '<span class="text-muted">Never</span>'; ?></td>

                                                    <td>

                                                        <?php $status_class = ($row['status'] == 'Active') ? 'badge-success' : 'badge-secondary'; ?>

                                                        <span class="badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>

                                                    </td>

                                                    <td class="text-right">


                                                         <button class="btn btn-sm btn-primary mr-1 edit-donor-btn" data-id="<?php echo $row['donor_id']; ?>" title="Edit"><i class="fa fa-pencil"></i></button>

                                                        <a href="<?php echo hms_url('modules/admin/donors.php'); ?>"?delete=<?php echo $row['donor_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Mark this donor as inactive?');" title="Mark Inactive"><i class="fa fa-times-circle"></i></a>

                                                    </td>

                                                </tr>

                                                <?php $serial++; endwhile; ?>

                                             <?php else: ?>

                                                 <tr><td colspan="9" class="text-center text-muted">No active donors found. Add one using the button above.</td></tr>

                                             <?php endif; ?>

                                        </tbody>

                                    </table>

                                </div>

                           </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="modal fade" id="addDonorModal" tabindex="-1" role="dialog" aria-labelledby="addDonorModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-lg" role="document">

            <div class="modal-content">

                <form method="POST" action="<?php echo hms_url('modules/admin/donors.php'); ?>" id="addDonorForm">

                        <h5 class="modal-title" id="addDonorModalLabel">Add New Donor</h5>

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>

                    </div>

                    <div class="modal-body">

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>Full Name <span class="text-danger">*</span></label>

                                    <input type="text" name="name" class="form-control" required>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>Email</label>

                                    <input type="email" name="email" class="form-control">

                                </div>

                            </div>

                        </div>

                         <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>Phone <span class="text-danger">*</span></label>

                                    <input type="tel" name="phone" class="form-control" required pattern="[0-9+ ]{7,15}" title="Enter a valid phone number">

                                </div>

                             </div>

                             <div class="col-md-6">

                                <div class="form-group">

                                    <label>Blood Type <span class="text-danger">*</span></label>

                                    <select name="blood_type" class="form-control" required>

                                        <option value="">Select...</option>

                                        <option value="A+">A+</option><option value="A-">A-</option>

                                        <option value="B+">B+</option><option value="B-">B-</option>

                                        <option value="AB+">AB+</option><option value="AB-">AB-</option>

                                        <option value="O+">O+</option><option value="O-">O-</option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                             <div class="col-md-6">

                                <div class="form-group">

                                    <label>Age <span class="text-danger">*</span></label>

                                    <input type="number" name="age" class="form-control" required min="18" max="65" placeholder="Between 18 and 65">

                                </div>

                             </div>

                             <div class="col-md-6">

                                <div class="form-group">

                                    <label>Gender <span class="text-danger">*</span></label>

                                    <select name="gender" class="form-control" required>

                                        <option value="">Select...</option>

                                        <option value="Male">Male</option>

                                        <option value="Female">Female</option>

                                        <option value="Other">Other</option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="form-group">

                            <label>Address</label>

                            <textarea name="address" class="form-control" rows="2"></textarea>

                        </div>

                         <hr>

                         <h6 class="text-muted">Emergency Contact (Optional)</h6>

                        <div class="row">

                             <div class="col-md-6">

                                <div class="form-group">

                                    <label>Contact Person</label>

                                    <input type="text" name="emergency_contact" class="form-control">

                                </div>

                             </div>

                              <div class="col-md-6">

                                <div class="form-group">

                                    <label>Contact Phone</label>

                                    <input type="tel" name="emergency_phone" class="form-control" pattern="[0-9+ ]{7,15}" title="Enter a valid phone number">

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>

                        <button type="submit" name="save_donor" class="btn btn-primary">Save Donor</button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>

    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>

    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>

   

    <script src="<?php echo hms_url('assets/js/jquery.dataTables.min.js'); ?>"></script>

    <script src="<?php echo hms_url('assets/js/dataTables.bootstrap4.min.js'); ?>"></script>

    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>



   

     <script>

        $(document).ready(function() {

            $('#donorTable').DataTable({

                 "order": [[ 1, "asc" ]]

            });



             

             $('#addDonorModal').on('hidden.bs.modal', function () {

                 

             });

        });

    </script>

</body>

</html>

<?php ?>

