<?php
session_start();
require '../includes/db_connect.php';

// --- 1. HANDLE ADD USER (Form Submission) ---
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];
    $class_id  = isset($_POST['class_id']) ? $_POST['class_id'] : null;

    // Check duplicate username
    $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Error: Username '$username' already exists!";
    } else {
        // 1. Create User in 'users' table
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $username, $hashed, $role);
        
        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id; // Get the ID of the user we just made
            
            // 2. If Student, assign to Class immediately
            if ($role == 'student' && !empty($class_id)) {
                $stmt_stud = $conn->prepare("INSERT INTO students (student_id, class_id) VALUES (?, ?)");
                $stmt_stud->bind_param("ii", $new_user_id, $class_id);
                $stmt_stud->execute();
                $stmt_stud->close();
            }

            $success = "User added successfully!";
        } else {
            $error = "DB Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// --- 2. FETCH DATA FOR UI ---

// A. Get List of Classes (For the Dropdown)
$class_list = $conn->query("SELECT class_id, grade_level, section FROM classes ORDER BY grade_level, section");
$classes_options = [];
while ($c = $class_list->fetch_assoc()) {
    $classes_options[] = $c;
}

// B. Handle Search & Filter
$search_text = "";
$filter_role = "";
$where_clauses = ["1=1"];

if (isset($_GET['search'])) {
    if (!empty($_GET['search_text'])) {
        $s = $conn->real_escape_string($_GET['search_text']);
        $where_clauses[] = "(u.full_name LIKE '%$s%' OR u.username LIKE '%$s%')";
    }
    if (!empty($_GET['role'])) {
        $r = $conn->real_escape_string($_GET['role']);
        $where_clauses[] = "u.role = '$r'";
    }
}

// C. Execute Main Query
$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT u.*, c.grade_level, c.section 
        FROM users u 
        LEFT JOIN students s ON u.user_id = s.student_id 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE $where_sql 
        ORDER BY u.user_id DESC LIMIT 50";
$result = $conn->query($sql);

include 'header.php';
?>

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <h3 class="text-primary fw-bold">Manage Users</h3>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i> Add New User
        </button>
    </div>

    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

    <!-- SEARCH BAR -->
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search_text" class="form-control" placeholder="Search Name or ID..." value="<?php echo htmlspecialchars($search_text); ?>">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" <?php if($filter_role=='admin') echo 'selected'; ?>>Admin</option>
                        <option value="teacher" <?php if($filter_role=='teacher') echo 'selected'; ?>>Teacher</option>
                        <option value="student" <?php if($filter_role=='student') echo 'selected'; ?>>Student</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- USERS TABLE -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID / Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Class Enrolled</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($row['role']=='admin'?'danger':($row['role']=='teacher'?'success':'info')); ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    if ($row['role'] == 'student') {
                                        if ($row['grade_level']) {
                                            echo "<span class='badge bg-secondary'>Grade " . $row['grade_level'] . "-" . $row['section'] . "</span>";
                                        } else {
                                            echo "<span class='text-danger small'>Not Assigned</span>";
                                        }
                                    } else {
                                        echo "-";
                                    }
                                ?>
                            </td>
                            <td>
                                 <a href="edit_users.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Register New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Username (ID)</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Default Password</label>
                        <input type="text" name="password" class="form-control" value="123456" required>
                    </div>
                    
                    <!-- ROLE SELECTION -->
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" id="modalRoleSelect" class="form-select" onchange="toggleModalClassField()" required>
                            <option value="teacher">Subject Teacher</option>
                            <option value="admin">Administrator</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <!-- CLASS SELECTION (Hidden by default, shown only for Students) -->
                    <div class="mb-3" id="classSelectionDiv" style="display:none;">
                        <label class="fw-bold text-success">Assign Class (For Students)</label>
                        <select name="class_id" class="form-select">
                            <option value="">Select Grade & Section...</option>
                            <?php foreach($classes_options as $cls): ?>
                                <option value="<?php echo $cls['class_id']; ?>">
                                    Grade <?php echo $cls['grade_level'] . " - " . $cls['section']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Select the class this student belongs to.</div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JAVASCRIPT FOR MODAL -->
<script>
    function toggleModalClassField() {
        var role = document.getElementById("modalRoleSelect").value;
        var classDiv = document.getElementById("classSelectionDiv");
        
        if (role === "student") {
            classDiv.style.display = "block";
        } else {
            classDiv.style.display = "none";
        }
    }
</script>

<?php include 'footer.php'; ?>