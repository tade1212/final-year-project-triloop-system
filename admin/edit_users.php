<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_GET['id'])) { header("Location: manage_users.php"); exit(); }
$user_id = intval($_GET['id']);
$success = ""; $error = ""; $new_pin_display = "";

// --- HANDLE 1: UPDATE BASIC INFO (Name, Username, Role) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $role      = $_POST['role'];
    $class_id  = $_POST['class_id'] ?? null;

    $stmt = $conn->prepare("UPDATE users SET full_name=?, sex=?, username=?, role=? WHERE user_id=?");
$stmt->bind_param("ssssi", $full_name, $sex, $username, $role, $user_id);
    if ($stmt->execute()) {
        $success = "User details updated successfully.";
        // Handle Student Class Assignment
        if ($role == 'student') {
            $conn->query("INSERT INTO students (student_id, class_id) VALUES ($user_id, $class_id) ON DUPLICATE KEY UPDATE class_id=$class_id");
        }
    } else { $error = "Update failed: " . $conn->error; }
}

// --- HANDLE 2: GENERATE SYSTEM PIN (Inside the Edit Page) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_pin'])) {
    $admin_id = $_SESSION['user_id'];
    $pin = rand(100000, 999999); // System generates the PIN
    $hashed = password_hash($pin, PASSWORD_DEFAULT);

    $conn->begin_transaction();
    try {
        // Update user to the random PIN and force a reset
        $conn->query("UPDATE users SET password='$hashed', reset_required=1 WHERE user_id=$user_id");
        // Log the action for accountability
        $conn->query("INSERT INTO admin_activity_logs (admin_id, action_type, target_user_id) VALUES ($admin_id, 'Admin Generated PIN', $user_id)");
        $conn->commit();
        $new_pin_display = $pin; // Displayed only once on this refresh
    } catch (Exception $e) { $conn->rollback(); $error = "PIN generation failed."; }
}

// FETCH CURRENT DATA
$sql = "SELECT u.*, s.class_id FROM users u LEFT JOIN students s ON u.user_id = s.student_id WHERE u.user_id = $user_id";
$user = ($conn->query($sql))->fetch_assoc();
$classes = $conn->query("SELECT * FROM classes ORDER BY grade_level");

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6"> <!-- Optimized width for a centered form -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-primary"><i class="fas fa-user-cog me-2"></i> Edit User Profile</h5>
                    <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">Back to List</a>
                </div>
                <div class="card-body p-4">
                    
                    <?php if($success) echo "<div class='alert alert-success shadow-sm py-2'>$success</div>"; ?>
                    <?php if($error) echo "<div class='alert alert-danger shadow-sm py-2'>$error</div>"; ?>

                    <!-- PIN DISPLAY AREA -->
                    <?php if($new_pin_display): ?>
                        <div class="alert alert-warning border-warning text-center shadow-sm mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-key me-2"></i> New Temporary PIN Generated</h6>
                            <div class="display-6 fw-bold text-danger"><?php echo $new_pin_display; ?></div>
                            <small class="text-muted">Provide this PIN to the user. They will be forced to change it on login.</small>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <h6 class="fw-bold mb-4 text-secondary text-center">Account Details for <?php echo $user['full_name']; ?></h6>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username (ID)</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
    <label class="form-label small fw-bold">Sex / Gender</label>
    <select name="sex" class="form-select">
        <option value="Male" <?php if($user['sex']=='Male') echo 'selected'; ?>>Male</option>
        <option value="Female" <?php if($user['sex']=='Female') echo 'selected'; ?>>Female</option>
    </select>
</div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">System Role</label>
                            <select name="role" class="form-select" id="roleSelect" onchange="toggleEditClass()">
                                <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                                <option value="teacher" <?php if($user['role']=='teacher') echo 'selected'; ?>>Teacher</option>
                                <option value="student" <?php if($user['role']=='student') echo 'selected'; ?>>Student</option>
                            </select>
                        </div>

                        <div class="mb-4" id="editClassDiv" style="display: <?php echo ($user['role']=='student')?'block':'none'; ?>;">
                            <label class="form-label small text-success fw-bold">Assigned Class</label>
                            <select name="class_id" class="form-select">
                                <option value="">-- Select Class --</option>
                                <?php while($c = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo $c['class_id']; ?>" <?php if($user['class_id']==$c['class_id']) echo 'selected'; ?>>
                                        Grade <?php echo $c['grade_level'].'-'.$c['section']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="update_user" class="btn btn-primary shadow-sm">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                            
                            <hr class="my-3">
                            
                            <!-- <button type="submit" name="generate_pin" class="btn btn-outline-warning fw-bold" onclick="return confirm('Generate a new random PIN for this user?');">
                                <i class="fas fa-sync-alt me-2"></i> Generate New System PIN
                            </button> -->
                        </div>
                    </form>

                </div>
            </div>
            
            <div class="text-center mt-4 text-muted small">
                <i class="fas fa-info-circle me-1"></i> Passwords are encrypted using BCRYPT for maximum security.
            </div>
        </div>
    </div>
</div>

<script>
    function toggleEditClass() {
        var role = document.getElementById("roleSelect").value;
        document.getElementById("editClassDiv").style.display = (role === "student") ? "block" : "none";
    }
</script>

<?php include 'footer.php'; ?>