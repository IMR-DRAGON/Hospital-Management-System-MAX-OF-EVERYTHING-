<?php
require_once __DIR__ . '/includes/init.php';
if (empty($_SESSION['name'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}
include hms_path('includes/header.php');
$is_patient = ((int)$_SESSION['role'] === 3);
$is_admin = ((int)$_SESSION['role'] === 1);

function normalize_name($name) {
    $name = trim(preg_replace('/\s+/', ' ', (string)$name));
    return strtolower($name);
}

function get_patient_name_filter($connection, $patient_id) {
    $patient_id = (int)$patient_id;
    $result = mysqli_query($connection, "SELECT first_name, last_name FROM tbl_patient WHERE id = $patient_id LIMIT 1");
    if (!$result || mysqli_num_rows($result) === 0) {
        return "1=0";
    }
    $patient = mysqli_fetch_assoc($result);
    $full_name = normalize_name(trim($patient['first_name']) . ' ' . trim($patient['last_name']));
    $first = mysqli_real_escape_string($connection, trim($patient['first_name']));
    $last = mysqli_real_escape_string($connection, trim($patient['last_name']));
    $full_esc = mysqli_real_escape_string($connection, $full_name);

    return "(
        LOWER(REPLACE(TRIM(SUBSTRING_INDEX(patient_name, ',', 1)), '  ', ' ')) = '$full_esc'
        OR LOWER(REPLACE(TRIM(patient_name), '  ', ' ')) = '$full_esc'
        OR (patient_name LIKE '%$first%' AND patient_name LIKE '%$last%')
    )";
}

function build_queue_map($connection) {
    $queue_map = [];
    $groups = [];
    $result = mysqli_query(
        $connection,
        "SELECT id, doctor, date, time FROM tbl_appointment WHERE status = 1"
    );
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $key = $row['doctor'] . '||' . $row['date'];
            $groups[$key][] = $row;
        }
    }

    foreach ($groups as $key => $appointments) {
        usort($appointments, function ($a, $b) {
            $ta = strtotime($a['time']);
            $tb = strtotime($b['time']);
            $ta = ($ta === false) ? PHP_INT_MAX : $ta;
            $tb = ($tb === false) ? PHP_INT_MAX : $tb;
            if ($ta === $tb) {
                return (int)$a['id'] - (int)$b['id'];
            }
            return $ta - $tb;
        });

        $serial = 1;
        foreach ($appointments as $apt) {
            $queue_map[$key][(int)$apt['id']] = $serial++;
        }
    }

    return $queue_map;
}

if (isset($_GET['ids']) && !$is_patient) {
    $id = (int)$_GET['ids'];
    mysqli_query($connection, "DELETE FROM tbl_appointment WHERE id = $id");
}

$where_clause = '';
if ($is_patient) {
    $where_clause = 'WHERE ' . get_patient_name_filter($connection, $_SESSION['user_id']);
}

$fetch_query = mysqli_query($connection, "SELECT * FROM tbl_appointment $where_clause ORDER BY date DESC, time ASC, id ASC");
$queue_map = $is_patient ? build_queue_map($connection) : [];
?>
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-4 col-3">
                        <h4 class="page-title"><?php echo $is_patient ? 'My Appointments' : 'Appointments'; ?></h4>
                    </div>
                    <?php if ($is_admin): ?>
                    <div class="col-sm-8 col-9 text-right m-b-20">
                        <a href="<?php echo hms_url('modules/admin/add-appointment.php'); ?>" class="btn btn-primary btn-rounded float-right"><i class="fa fa-plus"></i> Add Appointment</a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="datatable table table-stripped">
                        <thead>
                            <tr>
                                <?php if ($is_patient): ?>
                                <th>Serial</th>
                                <?php endif; ?>
                                <th>Appointment ID</th>
                                <?php if (!$is_patient): ?>
                                <th>Patient Name</th>
                                <th>Age</th>
                                <?php endif; ?>
                                <th>Doctor Name</th>
                                <th>Department</th>
                                <th>Appointment Date</th>
                                <th>Appointment Time</th>
                                <th>Status</th>
                                <?php if (!$is_patient): ?>
                                <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($fetch_query && mysqli_num_rows($fetch_query) > 0):
                                while ($row = mysqli_fetch_array($fetch_query)):
                                    $time_raw = $row['time'];
                                    $time_parsed = strtotime($time_raw);
                                    if ($time_parsed !== false) {
                                        $time_display = date('h:i A', $time_parsed);
                                        $timeout_display = date('h:i A', $time_parsed + 3600);
                                        $time_cell = $time_display . ' - ' . $timeout_display;
                                    } else {
                                        $time_cell = htmlspecialchars($time_raw);
                                    }

                                    $name_parts = explode(',', $row['patient_name']);
                                    $name = trim($name_parts[0]);
                                    $age_part = $name_parts[1] ?? null;
                                    $year = '-';
                                    if ($age_part) {
                                        $date = str_replace('/', '-', trim($age_part));
                                        $dob_ts = strtotime($date);
                                        if ($dob_ts !== false) {
                                            $year = date('Y') - date('Y', $dob_ts);
                                        }
                                    }

                                    $queue_key = $row['doctor'] . '||' . $row['date'];
                                    $serial = $queue_map[$queue_key][(int)$row['id']] ?? '-';
                            ?>
                            <tr>
                                <?php if ($is_patient): ?>
                                <td><span class="badge badge-primary"><?php echo (int)$serial; ?></span></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($row['appointment_id']); ?></td>
                                <?php if (!$is_patient): ?>
                                <td><?php echo htmlspecialchars($name); ?></td>
                                <td><?php echo htmlspecialchars($year); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($row['doctor']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><?php echo $time_cell; ?></td>
                                <?php if ((int)$row['status'] === 1): ?>
                                <td><span class="custom-badge status-green">Active</span></td>
                                <?php else: ?>
                                <td><span class="custom-badge status-red">Inactive</span></td>
                                <?php endif; ?>
                                <?php if (!$is_patient): ?>
                                <td class="text-right">
                                    <div class="dropdown dropdown-action">
                                        <a href="#" class="action-icon dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="<?php echo hms_url('modules/admin/edit-appointment.php'); ?>"?id=<?php echo $row['id']; ?>"><i class="fa fa-pencil m-r-5"></i> Edit</a>
                                            <a class="dropdown-item" href="<?php echo hms_url('modules/shared/appointments.php'); ?>"?ids=<?php echo $row['id']; ?>" onclick="return confirmDelete()"><i class="fa fa-trash-o m-r-5"></i> Delete</a>
                                        </div>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="<?php echo $is_patient ? '7' : '9'; ?>" class="text-center text-muted">
                                    <?php echo $is_patient ? 'You have no appointments yet.' : 'No appointments found.'; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($is_patient): ?>
                <p class="text-muted mt-2">
                    <i class="fa fa-info-circle"></i>
                    Serial number shows your position in the queue for that doctor on the appointment date.
                </p>
                <?php endif; ?>
            </div>
        </div>

<?php include hms_path('includes/footer.php'); ?>
<script language="JavaScript" type="text/javascript">
function confirmDelete(){
    return confirm('Are you sure want to delete this Appointments?');
}
</script>
