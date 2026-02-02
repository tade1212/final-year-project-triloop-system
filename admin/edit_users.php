<?php
session_start();
require '../includes/db_connect.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = intval($_GET['id']);
$success = "";
$error = "";

// --- 1. HANDLE UPDATE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $role      = $_POST['role'];
    $new_pass  = $_POST['password'];
    $class_id  = isset($_POST['class_id']) ? $_POST['class_id'] : null;

    // A. Update Basic Info
    $sql = "UPDATE users SET full_name=?, username=?, role=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $full_name, $username, $role, $user_id);
    
    if ($stmt->execute()) {
        $success = "User updated successfully!";

        // B. Update Password (Only if typed)
        if (!empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hashed' WHERE user_id=$user_id");
            $success .= " Password changed.";
        }

        // C. Update Class (If Student)
        if ($role == 'student') {
            // Check if entry exists in 'students' table
            $check = $conn->query("SELECT * FROM students WHERE student_id=$user_id");
            if ($check->num_rows > 0) {
                // Update existing
                $update_class = $conn->prepare("UPDATE students SET class_id=? WHERE student_id=?");
                $update_class->bind_param("ii", $class_id, $user_id);
                $update_class->execute();
            } else {
                // Insert new (if they were changed from Teacher to Student)
                $insert_class = $conn->prepare("INSERT INTO students (student_id, class_id) VALUES (?, ?)");
                $insert_class->bind_param("ii", $user_id, $class_id);
                $insert_class->execute();
            }
        }
    } else {
        $error = "Error updating: " . $conn->error;
    }
}

// --- 2. FETCH EXISTING USER DATA ---
// We get user info AND class info (if they are a student)
$sql = "SELECT u.*, s.class_id 
        FROM users u 
        LEFT JOIN students s ON u.user_id = s.student_id 
        WHERE u.user_id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

// --- 3. FETCH CLASSES FOR DROPDOWN ---
$classes = $conn->query("SELECT * FROM classes ORDER BY grade_level, section");

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="m-0 fw-bold"><i class="fas fa-edit me-2"></i> Edit User</h5>
                </div>
                <div class="card-body">
                    
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <?php if($success) echo "<div class='alert alert-success'>$success <a href='manage_users.php'>Go Back</a></div>"; ?>

                    <form method="POST">
                        
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label class="form-label">Username (ID)</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="roleSelect" class="form-select" onchange="toggleStudentOptions()">
                                <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                                <option value="teacher" <?php if($user['role']=='teacher') echo 'selected'; ?>>Teacher</option>
                                <option value="student" <?php if($user['role']=='student') echo 'selected'; ?>>Student</option>
                            </select>
                        </div>

                        <!-- Class Selection (Hidden unless Student) -->
                        <div class="mb-3" id="classDiv" style="display: <?php echo ($user['role']=='student') ? 'block' : 'none'; ?>;">
                            <label class="form-label fw-bold text-success">Assign Class</label>
                            <select name="class_id" class="form-select">
                                <option value="">Select Class...</option>
                                <?php while($c = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo $c['class_id']; ?>" 
                                        <?php if($user['class_id'] == $c['class_id']) echo 'selected'; ?>>
                                        Grade <?php echo $c['grade_level'] . '-' . $c['section']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Password Reset -->
                        <div class="mb-4">
                            <label class="form-label">New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                            <input type="text" name="password" class="form-control" placeholder="Type new password...">
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Update User</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleStudentOptions() {
        var role = document.getElementById("roleSelect").value;
        var div = document.getElementById("classDiv");
        if(role === 'student') {
            div.style.display = 'block';
        } else {
            div.style.display = 'none';
        }
    }
</script>

<?php include 'footer.php'; ?>