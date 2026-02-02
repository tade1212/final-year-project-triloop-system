<?php
session_start();
require '../includes/db_connect.php';

$success = "";
$error = "";

// --- 1. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subjects WHERE subject_id=$id");
    header("Location: manage_subjects.php?msg=deleted");
    exit();
}

// --- 2. HANDLE SUCCESS MESSAGES ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') $success = "Subject updated successfully!";
    if ($_GET['msg'] == 'deleted') $success = "Subject deleted successfully!";
}

// --- 3. HANDLE ADD FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['subject_name']);
    $code = strtoupper(trim($_POST['subject_code'])); // Force Uppercase (e.g. MATH)

    // Check duplicate
    $check = $conn->query("SELECT * FROM subjects WHERE subject_code='$code' OR subject_name='$name'");
    
    if ($check->num_rows > 0) {
        $error = "Error: Subject already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $code);
        
        if ($stmt->execute()) {
            $success = "Subject '$name' added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// --- 4. FETCH SUBJECTS ---
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        
        <!-- Left Side: Add Subject Form -->
        <div class="col-md-4">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 text-primary fw-bold"><i class="fas fa-book me-2"></i> Add Subject</h5>
                </div>
                <div class="card-body">
                    
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" placeholder="e.g. Mathematics" required>
                        </div>
                        <div class="mb-3">
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" class="form-control" placeholder="e.g. MATH01" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Subject</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side: Subject List -->
        <div class="col-md-8">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 text-primary fw-bold">Existing Subjects</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th style="width: 150px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($subjects->num_rows > 0): ?>
                                <?php while($row = $subjects->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $row['subject_code']; ?></span></td>
                                    <td><?php echo $row['subject_name']; ?></td>
                                    <td>
                                        <a href="edit_subject.php?id=<?php echo $row['subject_id']; ?>" class="btn btn-sm btn-warning text-white">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage_subjects.php?delete=<?php echo $row['subject_id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this subject?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center">No subjects found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>