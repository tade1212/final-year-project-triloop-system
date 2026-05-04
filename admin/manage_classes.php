<?php
session_start();
require '../includes/db_connect.php';

$success = "";
$error = "";

// --- 1. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM classes WHERE class_id=$id");
    header("Location: manage_classes.php?msg=deleted");
    exit();
}

// --- 2. HANDLE SUCCESS MESSAGES ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') $success = "Class updated successfully!";
    if ($_GET['msg'] == 'deleted') $success = "Class deleted successfully!";
}

// --- 3. HANDLE FORM SUBMISSION (Add Class) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $grade = $_POST['grade'];
    $section = strtoupper(trim($_POST['section']));
    $teacher_id = $_POST['class_teacher_id'];

    // REFINEMENT 1: Check if Class exists
    $check_class = $conn->query("SELECT * FROM classes WHERE grade_level='$grade' AND section='$section'");
    
    // REFINEMENT 2: Check if Teacher is already a Homeroom Teacher (New Logic)
    $check_teacher = $conn->query("SELECT * FROM classes WHERE class_teacher_id='$teacher_id'");

    if ($check_class->num_rows > 0) {
        $error = "Error: Class $grade-$section already exists!";
    } elseif ($check_teacher->num_rows > 0) {
        $error = "Error: This teacher is already assigned as a Homeroom Teacher to another section!";
    } else {
        $sql = "INSERT INTO classes (grade_level, section, class_teacher_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $grade, $section, $teacher_id);
        
        if ($stmt->execute()) {
            $success = "Class $grade-$section Created Successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// --- 4. FETCH DATA ---
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='teacher'");
$classes = $conn->query("SELECT c.*, u.full_name as teacher_name FROM classes c LEFT JOIN users u ON c.class_teacher_id = u.user_id ORDER BY grade_level, section");

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Add Class Form -->
        <div class="col-md-4">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 text-primary fw-bold">Create Class</h5>
                </div>
                <div class="card-body">
                    <?php if($error) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
                    <?php if($success) echo "<div class='alert alert-success py-2 small'>$success</div>"; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="small fw-bold">Grade Level</label>
                            <select name="grade" class="form-select" required>
                                <option value="9">Grade 9</option>
                                <option value="10">Grade 10</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Section</label>
                            <input type="text" name="section" class="form-control" placeholder="A, B, C..." maxlength="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Homeroom Teacher</label>
                            <select name="class_teacher_id" class="form-select" required>
                                <option value="" selected disabled>Select Teacher...</option>
                                <?php while($t = $teachers->fetch_assoc()): ?>
                                    <option value="<?php echo $t['user_id']; ?>"><?php echo $t['full_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">Add Class</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Class List Table (Preserved Design) -->
        <div class="col-md-8">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 text-primary fw-bold">Existing Classes</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Class</th>
                                <th>Homeroom Teacher</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $classes->fetch_assoc()): ?>
                            <tr>
                                <td>Grade <?php echo $row['grade_level'] . '-' . $row['section']; ?></td>
                                <td>
                                    <?php 
                                    if ($row['teacher_name']) {
                                        echo "<span class='badge bg-success'>" . $row['teacher_name'] . "</span>";
                                    } else {
                                        echo "<span class='badge bg-warning text-dark'>Unassigned</span>";
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="edit_class.php?id=<?php echo $row['class_id']; ?>" class="btn btn-sm btn-warning text-white"><i class="fas fa-edit"></i></a>
                                    <a href="manage_classes.php?delete=<?php echo $row['class_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this class?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>