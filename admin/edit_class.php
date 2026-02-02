<?php
session_start();
require '../includes/db_connect.php';

// Check if ID is set
if (!isset($_GET['id'])) {
    header("Location: manage_classes.php");
    exit();
}

$id = intval($_GET['id']);
$success = "";
$error = "";

// --- 1. HANDLE UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $grade = $_POST['grade'];
    $section = strtoupper(trim($_POST['section']));
    $teacher_id = $_POST['class_teacher_id'];

    $sql = "UPDATE classes SET grade_level=?, section=?, class_teacher_id=? WHERE class_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $grade, $section, $teacher_id, $id);

    if ($stmt->execute()) {
        // Redirect back to list with success message
        header("Location: manage_classes.php?msg=updated");
        exit();
    } else {
        $error = "Error updating: " . $conn->error;
    }
}

// --- 2. FETCH CURRENT DATA ---
$class_query = $conn->query("SELECT * FROM classes WHERE class_id = $id");
$class_data = $class_query->fetch_assoc();

// --- 3. FETCH TEACHERS LIST ---
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='teacher'");

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Edit Class</h5>
                </div>
                <div class="card-body">
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form method="POST">
                        
                        <div class="mb-3">
                            <label>Grade Level</label>
                            <select name="grade" class="form-select" required>
                                <!-- Pre-select the current grade -->
                                <option value="9" <?php if($class_data['grade_level']==9) echo 'selected'; ?>>Grade 9</option>
                                <option value="10" <?php if($class_data['grade_level']==10) echo 'selected'; ?>>Grade 10</option>
                                <option value="11" <?php if($class_data['grade_level']==11) echo 'selected'; ?>>Grade 11</option>
                                <option value="12" <?php if($class_data['grade_level']==12) echo 'selected'; ?>>Grade 12</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Section</label>
                            <input type="text" name="section" class="form-control" value="<?php echo $class_data['section']; ?>" maxlength="1" required>
                        </div>

                        <div class="mb-3">
                            <label>Homeroom Teacher</label>
                            <select name="class_teacher_id" class="form-select" required>
                                <option value="" disabled>Select Teacher...</option>
                                <?php while($t = $teachers->fetch_assoc()): ?>
                                    <option value="<?php echo $t['user_id']; ?>" 
                                        <?php if($class_data['class_teacher_id'] == $t['user_id']) echo 'selected'; ?>>
                                        <?php echo $t['full_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="manage_classes.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Update Class</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>