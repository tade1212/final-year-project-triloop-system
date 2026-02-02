<?php
session_start();
require '../includes/db_connect.php';

$success = "";
$error = "";

// --- 1. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM allocations WHERE allocation_id=$id");
    header("Location: allocate_subjects.php?msg=deleted");
    exit();
}

// --- 2. HANDLE SUCCESS MESSAGES ---
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "Allocation removed successfully!";
    if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $success = "Allocation updated successfully!";
}
}

// --- 3. HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_POST['teacher_id'];
    $subject_id = $_POST['subject_id'];
    $class_id   = $_POST['class_id'];

    // Check if this specific link already exists
    // (e.g., Mr. Abebe is ALREADY assigned Math for Grade 9A)
    $check = $conn->query("SELECT * FROM allocations WHERE class_id='$class_id' AND subject_id='$subject_id'");
    
    if ($check->num_rows > 0) {
        $error = "Error: This subject is already assigned to a teacher for this class!";
    } else {
        $stmt = $conn->prepare("INSERT INTO allocations (teacher_id, subject_id, class_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $teacher_id, $subject_id, $class_id);
        
        if ($stmt->execute()) {
            $success = "Course Assigned Successfully!";
        } else {
            $error = "DB Error: " . $conn->error;
        }
    }
}

// --- 4. FETCH DATA FOR DROPDOWNS ---
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='teacher' ORDER BY full_name");
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
$classes  = $conn->query("SELECT * FROM classes ORDER BY grade_level, section");

// --- 5. FETCH EXISTING ALLOCATIONS (With JOINS to get Names) ---
$allocations = $conn->query("
    SELECT a.allocation_id, 
           u.full_name as teacher_name, 
           s.subject_name, 
           c.grade_level, c.section
    FROM allocations a
    JOIN users u ON a.teacher_id = u.user_id
    JOIN subjects s ON a.subject_id = s.subject_id
    JOIN classes c ON a.class_id = c.class_id
    ORDER BY c.grade_level, c.section, s.subject_name
");

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        
        <!-- LEFT: ASSIGNMENT FORM -->
        <div class="col-md-4">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="m-0 fw-bold"><i class="fas fa-link me-2"></i> Assign Course</h5>
                </div>
                <div class="card-body">
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

                    <form method="POST">
                        
                        <!-- 1. Select Class -->
                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select" required>
                                <option value="" selected disabled>Select Class...</option>
                                <?php while($c = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo $c['class_id']; ?>">
                                        Grade <?php echo $c['grade_level'] . '-' . $c['section']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- 2. Select Subject -->
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="" selected disabled>Select Subject...</option>
                                <?php while($s = $subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $s['subject_id']; ?>">
                                        <?php echo $s['subject_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- 3. Select Teacher -->
                        <div class="mb-3">
                            <label class="form-label">Teacher</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="" selected disabled>Select Teacher...</option>
                                <?php while($t = $teachers->fetch_assoc()): ?>
                                    <option value="<?php echo $t['user_id']; ?>">
                                        <?php echo $t['full_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Assign Teacher</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: ALLOCATION LIST -->
        <div class="col-md-8">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 text-primary fw-bold">Course Allocations</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Assigned Teacher</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($allocations->num_rows > 0): ?>
                                <?php while($row = $allocations->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">
                                            Grade <?php echo $row['grade_level'] . '-' . $row['section']; ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?php echo $row['subject_name']; ?></td>
                                    <td><?php echo $row['teacher_name']; ?></td>
                                    <td>
    <a href="edit_allocation.php?id=<?php echo $row['allocation_id']; ?>" class="btn btn-sm btn-outline-warning me-1">
        <i class="fas fa-edit"></i>
    </a>

    <!-- Delete Button -->
    <a href="allocate_subjects.php?delete=<?php echo $row['allocation_id']; ?>" 
       class="btn btn-sm btn-outline-danger"
       onclick="return confirm('Remove this assignment?');">
        <i class="fas fa-trash"></i> 
    </a>
                                        
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-3">No courses assigned yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>