<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$teacher_id = $_SESSION['user_id'];

// 1. Fetch Periods (Columns) including both start and end times
$periods_result = $conn->query("SELECT * FROM time_slots ORDER BY start_time ASC");
$periods = [];
while($p = $periods_result->fetch_assoc()) $periods[] = $p;

// 2. Days (Rows)
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// 3. Fetch specific teacher's schedule
$schedule_map = [];
$sql = "SELECT s.*, sub.subject_name, c.grade_level, c.section 
        FROM schedule s
        JOIN allocations a ON s.allocation_id = a.allocation_id
        JOIN subjects sub ON a.subject_id = sub.subject_id
        JOIN classes c ON a.class_id = c.class_id
        WHERE a.teacher_id = $teacher_id";

$res = $conn->query($sql);
while($row = $res->fetch_assoc()) {
    $schedule_map[$row['day_of_week']][$row['period_id']] = $row;
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h3 class="fw-bold text-primary">My Weekly Teaching Schedule</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle shadow-sm bg-white">
            <thead class="table-dark">
                <tr>
                    <th style="width: 120px;">Day</th>
                    <?php foreach($periods as $p): ?>
                        <th>
                            <?php echo $p['period_name']; ?><br>
                            <!-- FIXED: Now showing Start and End Time -->
                            <small class="fw-light">
                                <?php echo date("g:i A", strtotime($p['start_time'])); ?> - 
                                <?php echo date("g:i A", strtotime($p['end_time'])); ?>
                            </small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($days as $day): ?>
                <tr>
                    <td class="fw-bold bg-light"><?php echo $day; ?></td>
                    <?php foreach($periods as $p): 
                        $pid = $p['period_id'];
                        if ($p['type'] == 'break'): 
                            echo "<td class='bg-light text-muted small'>Break</td>";
                        else:
                            $cell = isset($schedule_map[$day][$pid]) ? $schedule_map[$day][$pid] : null;
                    ?>
                        <td>
                            <?php if($cell): ?>
                                <div class="p-2 border rounded bg-success bg-opacity-10 border-success">
                                    <strong class="text-success"><?php echo $cell['subject_name']; ?></strong><br>
                                    <span>Grade <?php echo $cell['grade_level'].'-'.$cell['section']; ?></span><br>
                                    <small class="text-muted">Room: <?php echo $cell['room_number']; ?></small>
                                </div>
                            <?php else: ?>
                                <span class="text-white-50">-</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>