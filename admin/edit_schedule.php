<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: create_schedule.php");
    exit();
}

$id = intval($_GET['id']);
$success = "";
$error = "";

// 1. FETCH CURRENT DATA
$query = $conn->query("SELECT * FROM schedule WHERE schedule_id = $id");
$data = $query->fetch_assoc();
if (!$data) die("Schedule not found.");

// 2. FETCH ALLOCATIONS (For Dropdown)
$alloc_list = $conn->query("
    SELECT a.allocation_id, s.subject_name, u.full_name, c.grade_level, c.section 
    FROM allocations a
    JOIN subjects s ON a.subject_id = s.subject_id
    JOIN users u ON a.teacher_id = u.user_id
    JOIN classes c ON a.class_id = c.class_id
    ORDER BY c.grade_level, c.section, s.subject_name
");

// 3. HANDLE UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id      = $_POST['class_id']; // Usually readonly, but we keep it for checking
    $allocation_id = $_POST['allocation_id'];
    $day           = $_POST['day'];
    $start         = $_POST['start_time'];
    $end           = $_POST['end_time'];
    $room          = trim($_POST['room']);

    // Get Teacher ID
    $alloc_query = $conn->query("SELECT teacher_id FROM allocations WHERE allocation_id = $allocation_id");
    $teacher_row = $alloc_query->fetch_assoc();
    $teacher_id  = $teacher_row['teacher_id'];

    // CONFLICT CHECK (EXCLUDING CURRENT ID)
    $sql_check = "
        SELECT s.* FROM schedule s
        JOIN allocations a ON s.allocation_id = a.allocation_id
        WHERE s.day_of_week = '$day'
        AND s.schedule_id != $id  -- IMPORTANT: Don't conflict with self
        AND (('$start' < s.end_time) AND ('$end' > s.start_time))
        AND (
            s.room_number = '$room' OR s.class_id = '$class_id' OR a.teacher_id = '$teacher_id'
        )
    ";

    if ($conn->query($sql_check)->num_rows > 0) {
        $error = "<b>Conflict Detected!</b> This time slot is busy for the Teacher, Class, or Room.";
    } else {
        $stmt = $conn->prepare("UPDATE schedule SET allocation_id=?, day_of_week=?, start_time=?, end_time=?, room_number=? WHERE schedule_id=?");
        $stmt->bind_param("issssi", $allocation_id, $day, $start, $end, $room, $id);
        
        if ($stmt->execute()) {
            header("Location: create_schedule.php?msg=updated");
            exit();
        } else {
            $error = "DB Error: " . $conn->error;
        }
    }
}

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="m-0 fw-bold">Edit Schedule Slot</h5>
                </div>
                <div class="card-body">
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form method="POST">
                        <input type="hidden" name="class_id" value="<?php echo $data['class_id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Day</label>
                            <select name="day" class="form-select">
                                <option value="Monday" <?php if($data['day_of_week']=='Monday') echo 'selected'; ?>>Monday</option>
                                <option value="Tuesday" <?php if($data['day_of_week']=='Tuesday') echo 'selected'; ?>>Tuesday</option>
                                <option value="Wednesday" <?php if($data['day_of_week']=='Wednesday') echo 'selected'; ?>>Wednesday</option>
                                <option value="Thursday" <?php if($data['day_of_week']=='Thursday') echo 'selected'; ?>>Thursday</option>
                                <option value="Friday" <?php if($data['day_of_week']=='Friday') echo 'selected'; ?>>Friday</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject & Teacher</label>
                            <select name="allocation_id" class="form-select">
                                <?php while($a = $alloc_list->fetch_assoc()): ?>
                                    <option value="<?php echo $a['allocation_id']; ?>" 
                                        <?php if($data['allocation_id'] == $a['allocation_id']) echo 'selected'; ?>>
                                        <?php echo $a['subject_name'] . " (" . $a['teacher_name'] . ") - G" . $a['grade_level'] . $a['section']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Start</label>
                                <input type="time" name="start_time" class="form-control" value="<?php echo $data['start_time']; ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">End</label>
                                <input type="time" name="end_time" class="form-control" value="<?php echo $data['end_time']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Room</label>
                            <input type="text" name="room" class="form-control" value="<?php echo $data['room_number']; ?>" required>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="create_schedule.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Update Slot</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>