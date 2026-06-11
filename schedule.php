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
                        <h4 class="page-title">Schedule</h4>
                    </div>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <?php if($_SESSION['role'] == 1) { ?>
                        <a href="<?php echo hms_url('modules/admin/add-schedule.php'); ?>" class="btn btn-primary btn-rounded float-right"><i class="fa fa-plus"></i> Add Schedule</a>
                        <?php } ?>
                    </div>
                </div>
                <div class="table-responsive">
                                    <table class="datatable table table-stripped ">
                                    <thead>
                                        <tr>
                                            <th>Doctor Name</th>
                                            <th>Available Days</th>
                                            <th>Available Time</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <?php if($_SESSION['role'] == 1) { ?>
                                            <th>Action</th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if(isset($_GET['ids'])){
                                        $id = $_GET['ids'];
                                        $delete_query = mysqli_query($connection, "delete from tbl_schedule where id='$id'");
                                        }
                                        $fetch_query = mysqli_query($connection, "select * from tbl_schedule order by doctor_name, id desc");
                                        while($row = mysqli_fetch_array($fetch_query))
                                        {
                                        ?>
                                        <tr>
                                            <td><?php echo $row['doctor_name']; ?></td>
                                            <td><?php echo $row['available_days']; ?></td>
                                            <td><?php echo $row['start_time'].' - '.$row['end_time']; ?></td>
                                            <td><?php echo htmlspecialchars($row['message']); ?></td>
                                            <?php if($row['status']==1) { ?>
                                            <td><span class="custom-badge status-green">Active</span></td>
                                        <?php } else {?>
                                            <td><span class="custom-badge status-red">Inactive</span></td>
                                        <?php } ?>
                                            <?php if($_SESSION['role'] == 1) { ?>
                                            <td class="text-right">
                                            <div class="dropdown dropdown-action">
                                                <a href="#" class="action-icon dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="<?php echo hms_url('modules/admin/edit-schedule.php?id=' . (int)$row['id']); ?>"><i class="fa fa-pencil m-r-5"></i> Edit</a>
                                                    <a class="dropdown-item" href="<?php echo hms_url('modules/shared/schedule.php?ids=' . (int)$row['id']); ?>" onclick="return confirmDelete()"><i class="fa fa-trash-o m-r-5"></i> Delete</a>
                                                </div>
                                            </div>
                                        </td>
                                        <?php } ?>
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
    return confirm('Are you sure want to delete this Schedule?');
}
</script>