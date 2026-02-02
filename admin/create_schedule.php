<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// 1. Get Selected Class
$selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// 2. Fetch Columns (Periods)
$periods_result = $conn->query("SELECT * FROM time_slots ORDER BY start_time ASC");
$periods = [];
while($p = $periods_result->fetch_assoc()) $periods[] = $p;

// 3. Define Rows (Days)
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// 4. Fetch Schedule Data if class is selected
$schedule_map = [];
if ($selected_class_id > 0) {
    $sql = "SELECT s.*, sub.subject_name, u.full_name as teacher_name 
            FROM schedule s
            JOIN allocations a ON s.allocation_id = a.allocation_id
            JOIN subjects sub ON a.subject_id = sub.subject_id
            JOIN users u ON a.teacher_id = u.user_id
            WHERE s.class_id = $selected_class_id";
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) {
        // Map data like: $schedule_map['Monday'][Period_ID] = Data
        $schedule_map[$row['day_of_week']][$row['period_id']] = $row;
    }
}

// 5. Handle Form Submission (Add Slot)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_slot'])) {
    $p_id = $_POST['period_id'];
    $day_name = $_POST['day'];
    $alloc_id = $_POST['allocation_id'];
    $room = $_POST['room'];

    // CONFLICT CHECK (Simplified for Grid)
    // Check if Teacher or Room is busy at this specific Period + Day
    // (We don't need complex time overlap logic anymore because Periods are fixed!)
    
    // Get Teacher ID
    $t_res = $conn->query("SELECT teacher_id FROM allocations WHERE allocation_id=$alloc_id");
    $t_id = $t_res->fetch_assoc()['teacher_id'];

    $check = $conn->query("SELECT s.* FROM schedule s 
                           JOIN allocations a ON s.allocation_id = a.allocation_id
                           WHERE s.day_of_week = '$day_name' 
                           AND s.period_id = $p_id
                           AND (s.room_number = '$room' OR a.teacher_id = $t_id)");

    if ($check->num_rows > 0) {
        echo "<script>alert('Conflict! Teacher or Room is busy.');</script>";
    } else {
        $conn->query("INSERT INTO schedule (class_id, allocation_id, day_of_week, room_number, period_id, start_time, end_time) 
                      VALUES ($selected_class_id, $alloc_id, '$day_name', '$room', $p_id, '00:00', '00:00')");
        // Note: We used dummy 00:00 for start/end because we rely on period_id now. 
        // If you want strict data, fetch start/end from time_slots table before inserting.
        echo "<script>window.location='create_schedule.php?class_id=$selected_class_id';</script>";
    }
}

// 6. Handle Delete
if (isset($_GET['delete_id'])) {
    $del = intval($_GET['delete_id']);
    $cls = intval($_GET['cls']);
    $conn->query("DELETE FROM schedule WHERE schedule_id=$del");
    echo "<script>window.location='create_schedule.php?class_id=$cls';</script>";
}
?>

<div class="container-fluid">
    <h3 class="mt-3 text-primary fw-bold">Master Schedule Matrix</h3>

    <!-- Step 1: Select Class -->
    <div class="card p-3 mb-3 bg-light border-0">
        <form method="GET" class="d-flex align-items-center">
            <label class="fw-bold me-3">Select Class:</label>
            <select name="class_id" class="form-select w-auto me-3" onchange="this.form.submit()">
                <option value="">-- Choose --</option>
                <?php
                $classes = $conn->query("SELECT * FROM classes ORDER BY grade_level, section");
                while($c = $classes->fetch_assoc()):
                ?>
                <option value="<?php echo $c['class_id']; ?>" <?php if($selected_class_id==$c['class_id']) echo 'selected'; ?>>
                    Grade <?php echo $c['grade_level'].'-'.$c['section']; ?>
                </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <?php if($selected_class_id > 0): ?>
    
    <!-- Step 2: The Grid -->
    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle shadow-sm">
            <thead class="bg-dark text-white">
                <tr>
                    <th style="width: 100px;">Day / Time</th>
                    <?php foreach($periods as $p): ?>
                        <th>
                            <?php echo $p['period_name']; ?><br>
                            <small class="fw-light"><?php echo date("g:i", strtotime($p['start_time'])) . '-' . date("g:i", strtotime($p['end_time'])); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php foreach($days as $day): ?>
                <tr>
                    <td class="fw-bold bg-light"><?php echo $day; ?></td>
                    
                    <?php foreach($periods as $p): 
                        $pid = $p['period_id'];
                        
                        // Check if Break
                        if ($p['type'] == 'break') {
                            echo "<td class='bg-secondary bg-opacity-10 text-muted'><em>Break</em></td>";
                            continue;
                        }

                        // Check if Class Scheduled
                        $cell_data = isset($schedule_map[$day][$pid]) ? $schedule_map[$day][$pid] : null;
                    ?>
                        <td>
                            <?php if($cell_data): ?>
                                <!-- SHOW CLASS -->
                                <div class="p-2 border rounded bg-primary bg-opacity-10 border-primary">
                                    <strong class="text-primary"><?php echo $cell_data['subject_name']; ?></strong><br>
                                    <small><?php echo $cell_data['teacher_name']; ?></small><br>
                                    <small class="text-muted">Room: <?php echo $cell_data['room_number']; ?></small>
                                    <div class="mt-1">
                                        <a href="create_schedule.php?delete_id=<?php echo $cell_data['schedule_id']; ?>&cls=<?php echo $selected_class_id; ?>" 
                                           class="text-danger" onclick="return confirm('Remove?');"><i class="fas fa-times-circle"></i></a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- SHOW ADD BUTTON -->
                                <button class="btn btn-sm btn-outline-secondary rounded-circle" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#addSlotModal" 
                                        onclick="setModalData('<?php echo $day; ?>', <?php echo $pid; ?>, '<?php echo $p['period_name']; ?>')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-info">Please select a class to view/edit the schedule.</div>
    <?php endif; ?>

</div>

<!-- ADD SLOT MODAL -->
<div class="modal fade" id="addSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                    <input type="hidden" name="day" id="modalDay">
                    <input type="hidden" name="period_id" id="modalPeriodId">

                    <p class="fw-bold">
                        Adding for: <span id="modalDayDisplay" class="text-primary"></span> - 
                        <span id="modalPeriodDisplay" class="text-primary"></span>
                    </p>

                    <div class="mb-3">
                        <label>Subject & Teacher</label>
                        <select name="allocation_id" class="form-select" required>
                            <option value="">Choose...</option>
                            <?php 
                            // Fetch Allocations for selected class
                            if ($selected_class_id) {
                                $allocs = $conn->query("SELECT a.allocation_id, s.subject_name, u.full_name 
                                                        FROM allocations a 
                                                        JOIN subjects s ON a.subject_id=s.subject_id 
                                                        JOIN users u ON a.teacher_id=u.user_id 
                                                        WHERE a.class_id=$selected_class_id");
                                while($al = $allocs->fetch_assoc()) {
                                    echo "<option value='{$al['allocation_id']}'>{$al['subject_name']} ({$al['full_name']})</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Room Number</label>
                        <input type="text" name="room" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_slot" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setModalData(day, pid, pname) {
        document.getElementById('modalDay').value = day;
        document.getElementById('modalPeriodId').value = pid;
        document.getElementById('modalDayDisplay').innerText = day;
        document.getElementById('modalPeriodDisplay').innerText = pname;
    }
</script>

<?php include 'footer.php'; ?>