<?php
require_once __DIR__ . '/includes/init.php';
if(empty($_SESSION['name']))
{
    header('Location: ' . hms_url('login.php'));
}
include hms_path('includes/header.php');
if(isset($_REQUEST['add-schedule']))
    {
      $doctor_name = $_REQUEST['doctor'];
      $daysArray = isset($_REQUEST['days']) ? $_REQUEST['days'] : array();
      $days = implode(", ", $daysArray);
      $start_time = $_REQUEST['start_time'];
      $end_time = $_REQUEST['end_time'];
      $message = $_REQUEST['message'];
      $status = $_REQUEST['status'];

      // Basic validation: start time should be before end time
      if (strtotime($start_time) >= strtotime($end_time)) {
          $msg = "Start time must be before end time.";
      } else {
          // Build day match condition
          $dayConds = array();
          foreach ($daysArray as $d) {
              $dEsc = mysqli_real_escape_string($connection, $d);
              $dayConds[] = "available_days LIKE '%".$dEsc."%'";
          }
          $dayWhere = count($dayConds) ? '('.implode(' OR ', $dayConds).')' : '0';

          // Overlap check: existing slot overlaps if NOT (existing.end <= new.start OR existing.start >= new.end)
          $doctorEsc = mysqli_real_escape_string($connection, $doctor_name);
          $startEsc = mysqli_real_escape_string($connection, $start_time);
          $endEsc = mysqli_real_escape_string($connection, $end_time);
          $overlapSql = "SELECT id FROM tbl_schedule WHERE doctor_name='$doctorEsc' AND $dayWhere AND NOT (end_time <= '$startEsc' OR start_time >= '$endEsc') AND status=1 LIMIT 1";
          $overlapRes = mysqli_query($connection, $overlapSql);

          if ($overlapRes && mysqli_num_rows($overlapRes) > 0) {
              $msg = "This schedule overlaps an existing slot for the selected days.";
          } else {
              $insert_query = mysqli_query($connection, "INSERT INTO tbl_schedule SET doctor_name='$doctorEsc', available_days='$days', start_time='$startEsc', end_time='$endEsc', message='".mysqli_real_escape_string($connection,$message)."', status='$status'");
              if($insert_query>0)
              {
                  $msg = "Schedule created successfully";
              }
              else
              {
                  $msg = "Error!";
              }
          }
      }
    }
?>
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-4 ">
                        <h4 class="page-title">Add Schedule</h4>
                         
                    </div>
                    <div class="col-sm-8  text-right m-b-20">
                        <a href="<?php echo hms_url('modules/shared/schedule.php'); ?>" class="btn btn-primary btn-rounded float-right">Back</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Doctor Name</label>
                                        <select class="select" name="doctor" required>
                                            <option value="">Select</option>
                                            <?php
                                        $fetch_query = mysqli_query($connection, "select concat(first_name,' ',last_name) as name from tbl_employee where role=2 and status=1");
                                        while($row = mysqli_fetch_array($fetch_query)){
                                        ?>
                                            <option><?php echo $row['name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Available Days</label>
                                        <select class="select" multiple name="days[]" required>
                                            <option value="">Select Days</option>
                                            <option>Sunday</option>
                                            <option>Monday</option>
                                            <option>Tuesday</option>
                                            <option>Wednesday</option>
                                            <option>Thursday</option>
                                            <option>Friday</option>
                                            <option>Saturday</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Start Time</label>
                                        <div class="time-icon">
                                            <input type="text" class="form-control" id="datetimepicker3" name="start_time" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>End Time</label>
                                        <div class="time-icon">
                                            <input type="text" class="form-control" id="datetimepicker4" name="end_time" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea cols="30" rows="4" class="form-control" name="message" required></textarea>
                            </div>
                            <div class="form-group">
                                <label class="display-block">Schedule Status</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status" id="product_active" value="1" checked>
                                    <label class="form-check-label" for="product_active">
                                    Active
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status" id="product_inactive" value="0">
                                    <label class="form-check-label" for="product_inactive">
                                    Inactive
                                    </label>
                                </div>
                            </div>
                            <div class="m-t-20 text-center">
                                <button class="btn btn-primary submit-btn" name="add-schedule">Create Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
		</div>
    
<?php
    include hms_path('includes/footer.php');
?>
<script type="text/javascript">
     <?php
        if(isset($msg)) {
            echo 'swal("' . $msg . '");';
        }
    ?>
</script>