<?php
require_once __DIR__ . '/includes/init.php';
if(empty($_SESSION['name']))
{
    header('Location: ' . hms_url('login.php'));
}
include hms_path('includes/header.php');
?>
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-4 col-3">
                        <h4 class="page-title">Patients</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('modules/admin/add-patient.php'); ?>" class="btn btn-primary btn-rounded float-right"><i class="fa fa-plus"></i> Add Patient</a>
                    </div>
                </div>
                <div class="table-responsive">
                                    <table class="datatable table table-stripped ">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Age</th>
                                            <th>Address</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Category</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if(isset($_GET['ids'])){
                                        $id = $_GET['ids'];
                                        $delete_query = mysqli_query($connection, "delete from tbl_patient where id='$id'");
                                        }
                                        if($_SESSION['role'] == 5) {
                                            $associate_id = $_SESSION['user_id'];
                                            $fetch_query = mysqli_query($connection, "
                                                SELECT DISTINCT p.* 
                                                FROM tbl_patient p
                                                LEFT JOIN tbl_appointment a ON CONCAT(p.first_name, ' ', p.last_name) = a.patient_name
                                                WHERE 
                                                    a.doctor IN (
                                                        SELECT CONCAT(d.first_name, ' ', d.last_name) 
                                                        FROM tbl_employee d 
                                                        JOIN associate_assignments aa ON aa.doctor_id = d.id 
                                                        WHERE aa.associate_id = $associate_id AND aa.assignment_status = 'Active'
                                                    )
                                                    OR p.id IN (
                                                        SELECT patient_id 
                                                        FROM associate_assignments 
                                                        WHERE associate_id = $associate_id AND assignment_status = 'Active' AND patient_id IS NOT NULL
                                                    )
                                            ");
                                        } elseif($_SESSION['role'] == 2) {
                                            $doctor_id = (int)$_SESSION['user_id'];
                                            $doctor_name = mysqli_real_escape_string($connection, $_SESSION['name']);
                                            $fetch_query = mysqli_query($connection, "
                                                SELECT DISTINCT p.* 
                                                FROM tbl_patient p
                                                LEFT JOIN tbl_appointment a ON TRIM(SUBSTRING_INDEX(a.patient_name, ',', 1)) = TRIM(CONCAT(p.first_name, ' ', p.last_name))
                                                LEFT JOIN associate_assignments aa ON p.id = aa.patient_id
                                                WHERE a.doctor = '$doctor_name' OR aa.doctor_id = $doctor_id
                                            ");
                                        } else {
                                            $fetch_query = mysqli_query($connection, "select * from tbl_patient");
                                        }
                                        while($row = mysqli_fetch_array($fetch_query))
                                        {
                                            $dob = $row['dob'];
                                            $date = str_replace('/', '-', $dob); 
                                            $dob = date('Y-m-d', strtotime($date));
                                            $year = (date('Y') - date('Y',strtotime($dob)));
                                            
                                        ?>
                                        <tr>
                                            <td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
                                            <td><?php echo $year; ?></td>
                                            <td><?php echo $row['address']; ?></td>
                                            <td><?php echo $row['email']; ?></td>
                                            <td><?php echo $row['phone']; ?></td>
                                             <?php if($row['patient_type']=="InPatient") { ?>
                                            <td><span class="custom-badge status-red"><?php echo $row['patient_type']; ?></span></td>
                                        <?php } else {?>
                                            <td><span class="custom-badge status-green"><?php echo $row['patient_type']; ?></span></td>
                                        <?php } ?>
                                            <td class="text-right">
                                            <div class="dropdown dropdown-action">
                                                <a href="#" class="action-icon dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="<?php echo hms_url('modules/admin/edit-patient.php?id=' . (int)$row['id']); ?>"><i class="fa fa-pencil m-r-5"></i> Edit</a>
                                                    <a class="dropdown-item" href="<?php echo hms_url('modules/shared/patients.php?ids=' . (int)$row['id']); ?>" onclick="return confirmDelete()"><i class="fa fa-trash-o m-r-5"></i> Delete</a>
                                                    <a class="dropdown-item" href="<?php echo hms_url('modules/shared/patient-attachments.php?patient_id=' . (int)$row['id']); ?>"><i class="fa fa-paperclip m-r-5"></i> Attachments</a>
                                                </div>
                                            </div>
                                        </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                
            </div>
            
        </div>
        
   
<?php
include hms_path('includes/footer.php');
?>
<script language="JavaScript" type="text/javascript">
function confirmDelete(){
    return confirm('Are you sure want to delete this Patient?');
}
</script>