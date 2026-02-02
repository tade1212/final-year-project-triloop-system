<?php
require '../includes/db_connect.php';
include 'header.php'; // This includes session_start and Sidebar

$student_id = $_SESSION['user_id'];

// 1. Get the Class ID for this student
$stmt = $conn->prepare("SELECT class_id FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$student_info = $res->fetch_assoc();
$class_id = $student_info['class_id'];

// 2. Fetch Periods (Columns)
$periods_result = $conn->query("SELECT * FROM time_slots ORDER BY start_time ASC");
$periods = [];
while($p = $periods_result->fetch_assoc()) $periods[] = $p;

// 3. Days (Rows)
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// 4. Fetch the schedule for the student's class
$schedule_map = [];
$sql = "SELECT s.*, sub.subject_name, u.full_name as teacher_name 
        FROM schedule s
        JOIN allocations a ON s.allocation_id = a.allocation_id
        JOIN subjects sub ON a.subject_id = sub.subject_id
        JOIN users u ON a.teacher_id = u.user_id
        WHERE s.class_id = $class_id";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()) {
    $schedule_map[$row['day_of_week']][$row['period_id']] = $row;
}
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="m-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i> My Weekly Timetable</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead class="bg-dark text-white">
                    <tr>
                        <th style="width: 120px;">Day / Time</th>
                        <?php foreach($periods as $p): ?>
                            <th>
                                <?php echo $p['period_name']; ?><br>
                                <small class="fw-light"><?php echo date("g:i A", strtotime($p['start_time'])) . ' - ' . date("g:i A", strtotime($p['end_time'])); ?></small>
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
                                    <div class="p-2 border rounded bg-primary bg-opacity-10 border-primary">
                                        <strong class="text-primary d-block"><?php echo $cell['subject_name']; ?></strong>
                                        <small class="d-block text-dark"><?php echo $cell['teacher_name']; ?></small>
                                        <span class="badge bg-secondary">Room: <?php echo $cell['room_number']; ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>