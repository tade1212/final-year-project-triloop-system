<?php
session_start();
require '../includes/db_connect.php';

// Check if ID is set
if (!isset($_GET['id'])) {
    header("Location: allocate_subjects.php");
    exit();
}

$id = intval($_GET['id']);
$success = "";
$error = "";

// --- 1. HANDLE UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_POST['teacher_id'];
    $subject_id = $_POST['subject_id'];
    $class_id   = $_POST['class_id'];

    // Check Duplicate: specific subject in specific class (excluding current record)
    $check = $conn->query("SELECT * FROM allocations 
                           WHERE class_id='$class_id' 
                           AND subject_id='$subject_id' 
                           AND allocation_id != $id");
    
    if ($check->num_rows > 0) {
        $error = "Error: This subject is already assigned to someone for this class!";
    } else {
        $stmt = $conn->prepare("UPDATE allocations SET teacher_id=?, subject_id=?, class_id=? WHERE allocation_id=?");
        $stmt->bind_param("iiii", $teacher_id, $subject_id, $class_id, $id);

        if ($stmt->execute()) {
            header("Location: allocate_subjects.php?msg=updated");
            exit();
        } else {
            $error = "Error updating: " . $conn->error;
        }
    }
}

// --- 2. FETCH CURRENT DATA ---
$query = $conn->query("SELECT * FROM allocations WHERE allocation_id = $id");
$data = $query->fetch_assoc();

if (!$data) {
    die("Allocation not found.");
}

// --- 3. FETCH DROPDOWNS ---
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='teacher' ORDER BY full_name");
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
$classes  = $conn->query("SELECT * FROM classes ORDER BY grade_level, section");

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="m-0 fw-bold"><i class="fas fa-edit me-2"></i> Edit Allocation</h5>
                </div>
                <div class="card-body">
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form method="POST">
                        
                        <!-- Class -->
                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select" required>
                                <option value="" disabled>Select Class...</option>
                                <?php while($c = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo $c['class_id']; ?>" 
                                        <?php if($data['class_id'] == $c['class_id']) echo 'selected'; ?>>
                                        Grade <?php echo $c['grade_level'] . '-' . $c['section']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Subject -->
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="" disabled>Select Subject...</option>
                                <?php while($s = $subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $s['subject_id']; ?>" 
                                        <?php if($data['subject_id'] == $s['subject_id']) echo 'selected'; ?>>
                                        <?php echo $s['subject_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Teacher -->
                        <div class="mb-3">
                            <label class="form-label">Teacher</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="" disabled>Select Teacher...</option>
                                <?php while($t = $teachers->fetch_assoc()): ?>
                                    <option value="<?php echo $t['user_id']; ?>" 
                                        <?php if($data['teacher_id'] == $t['user_id']) echo 'selected'; ?>>
                                        <?php echo $t['full_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="allocate_subjects.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Assignment</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>