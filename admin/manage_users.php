<?php
session_start();
require '../includes/db_connect.php';

$success = ""; $error = ""; $temp_pin = "";


// --- 1. HANDLE SYSTEM PIN RESET (Keep Existing Handshake Logic) ---
if (isset($_POST['finalize_reset'])) {
    $target_id = intval($_POST['target_id']);
    $user_token = trim($_POST['auth_token']);
    $stmt = $conn->prepare("SELECT request_token FROM users WHERE user_id = ? AND reset_status = 'REQUESTED'");
    $stmt->bind_param("i", $target_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $db_data = $res->fetch_assoc();

    if ($db_data && $db_data['request_token'] == $user_token) {
        $pin = rand(100000, 999999);
        $hashed = password_hash($pin, PASSWORD_DEFAULT);
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE users SET password='$hashed', reset_required=1, reset_status='NONE', request_token=NULL WHERE user_id=$target_id");
            $log = $conn->prepare("INSERT INTO admin_activity_logs (admin_id, action_type, target_user_id, ip_address) VALUES (?, 'HANDSHAKE_RESET', ?, ?)");
            $log->bind_param("iis", $_SESSION['user_id'], $target_id, $_SERVER['REMOTE_ADDR']);
            $log->execute();
            $conn->commit();
            $temp_pin = $pin;
        } catch (Exception $e) { $conn->rollback(); $error = "Reset Error."; }
    } else { $error = "Invalid Handshake Token!"; }
}

// --- 2. KEEP PREVIOUS ADD USER LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $sex = trim($_POST['sex']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];
    $class_id  = $_POST['class_id'] ?? null;
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) { $error = "Username already exists!"; } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, sex, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $sex, $username, $hashed, $role);
        if ($stmt->execute()) {
            if ($role == 'student' && !empty($class_id)) {
                $conn->query("INSERT INTO students (student_id, class_id) VALUES ({$stmt->insert_id}, $class_id)");
            }
            $success = "User created successfully!";
        }
    }
}

// --- 3. FILTER & FETCH LOGIC (Layout Preserved) ---
$search_text = $_GET['search_text'] ?? "";
$filter_role = $_GET['role'] ?? "";
$filter_grade = $_GET['grade_level'] ?? "";
$filter_section = $_GET['section'] ?? "";
$where_clauses = ["1=1"];
if (!empty($search_text)) { $s = $conn->real_escape_string($search_text); $where_clauses[] = "(u.full_name LIKE '%$s%' OR u.username LIKE '%$s%')"; }
if (!empty($filter_role)) $where_clauses[] = "u.role = '$filter_role'";
if (!empty($filter_grade)) $where_clauses[] = "c.grade_level = " . intval($filter_grade);
if (!empty($filter_section)) $where_clauses[] = "c.section = '{$conn->real_escape_string($filter_section)}'";

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT u.*, c.grade_level, c.section FROM users u LEFT JOIN students s ON u.user_id = s.student_id LEFT JOIN classes c ON s.class_id = c.class_id WHERE $where_sql ORDER BY u.user_id DESC";
$result = $conn->query($sql);

include 'header.php';
?>

<style>
    /* Custom Stylings for an attractive table */
    .user-table thead { background-color: #f8f9fa; color: #333; border-bottom: 2px solid #dee2e6; }
    .user-table tbody tr { transition: all 0.2s; border-bottom: 1px solid #eee; }
    .user-table tbody tr:hover { background-color: #f1f8ff !important; transform: scale(1.002); }
    
    .avatar-circle {
        width: 35px; height: 35px; background-color: #6c757d; color: white;
        border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 0.8rem; margin-right: 10px;
    }
    .role-badge { font-size: 0.75rem; font-weight: 600; padding: 5px 10px; border-radius: 50px; }
    .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
    .card-pro { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark m-0">Directory <span class="text-muted fw-light"></span></h3>
        <button class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i> Add New User
        </button>
    </div>

    <?php if($error) echo "<div class='alert alert-danger border-0 shadow-sm'>$error</div>"; ?>
    <?php if($success) echo "<div class='alert alert-success border-0 shadow-sm'>$success</div>"; ?>
    <?php if($temp_pin) echo "<div class='alert alert-info border-0 shadow-sm'>PIN Reset! Temporary Access Code: <strong class='fs-4'>$temp_pin</strong></div>"; ?>

    <!-- FILTER CARD -->
    <div class="card card-pro mb-4 border-start border-4 border-primary">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Search Name or ID</label>
                    <input type="text" name="search_text" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search_text); ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Filter Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" <?php if($filter_role=='admin') echo 'selected'; ?>>Admin</option>
                        <option value="teacher" <?php if($filter_role=='teacher') echo 'selected'; ?>>Teacher</option>
                        <option value="student" <?php if($filter_role=='student') echo 'selected'; ?>>Student</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Grade</label>
                    <select name="grade_level" class="form-select">
                        <option value="">All Grades</option>
                        <?php for($i=9; $i<=12; $i++) echo "<option value='$i' ".($filter_grade==$i?'selected':'').">Grade $i</option>"; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Section</label>
                    <select name="section" class="form-select">
                        <option value="">All Sections</option>
                        <?php foreach(['A','B','C','D'] as $s) echo "<option value='$s' ".($filter_section==$s?'selected':'').">$s</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="search" class="btn btn-dark w-100"><i class="fas fa-filter me-2"></i> Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MAIN TABLE CARD -->
    <div class="card card-pro border-0 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table user-table table-borderless mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Full Name & ID</th>
                            <th>Role</th>
                            <th>Class</th>
                            <th class="text-center">Reset Auth</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): 
                            // Get initials for avatar
                            $names = explode(" ", $row['full_name']);
                            $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ""));
                            $avatar_colors = ['#1abc9c', '#3498db', '#9b59b6', '#e67e22', '#e74c3c'];
                            $bg_color = $avatar_colors[$row['user_id'] % 5];
                        ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle" style="background-color: <?php echo $bg_color; ?>;"><?php echo $initials; ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo $row['full_name']; ?></div>
                                        <div class="small text-muted font-monospace"><?php echo $row['username']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                    $role_class = ($row['role']=='admin'?'bg-danger bg-opacity-10 text-danger':($row['role']=='teacher'?'bg-success bg-opacity-10 text-success':'bg-info bg-opacity-10 text-info'));
                                ?>
                                <span class="role-badge <?php echo $role_class; ?>"><?php echo strtoupper($row['role']); ?></span>
                            </td>
                            <td>
                                <?php if($row['role'] == 'student'): ?>
                                    <span class="text-dark small fw-bold"><?php echo $row['grade_level'] ? "G-".$row['grade_level'].$row['section'] : "-"; ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['reset_status'] == 'REQUESTED'): ?>
                                    <span class="badge rounded-pill bg-warning text-dark"><i class="fas fa-key me-1"></i> Requested</span>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="fas fa-lock me-1"></i> Locked</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                                    <a href="edit_users.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-white border-end" title="Edit Profile"><i class="fas fa-edit text-primary"></i></a>
                                    
                                    <?php if($row['reset_status'] == 'REQUESTED'): ?>
                                        <button class="btn btn-sm btn-white border-end" onclick="openHandshake(<?php echo $row['user_id']; ?>, '<?php echo $row['full_name']; ?>')" title="Verify Handshake"><i class="fas fa-hand-holding-heart text-warning"></i></button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light border-end" disabled><i class="fas fa-shield-alt text-muted"></i></button>
                                    <?php endif; ?>

                                    <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-white" onclick="return confirm('Permanently delete user?');" title="Delete Account"><i class="fas fa-trash-alt text-danger"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- HANDSHAKE MODAL -->
<div class="modal fade" id="handshakeModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content border-0 shadow-lg">
        <div class="modal-body p-4 text-center">
            <div class="mb-3 text-warning"><i class="fas fa-user-lock fa-3x"></i></div>
            <h6 id="hsName" class="fw-bold">Handshake Auth</h6>
            <form method="POST">
                <input type="hidden" name="target_id" id="hsId">
                <input type="text" name="auth_token" class="form-control text-center fs-4 mb-3" placeholder="0000" maxlength="4" required>
                <button type="submit" name="finalize_reset" class="btn btn-warning w-100 fw-bold">Complete Reset</button>
            </form>
        </div>
    </div></div>
</div>

<!-- MODAL ADD USER (Previous Logic Kept) -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-primary text-white border-0"><h5 class="modal-title fw-bold">New User Account</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <form method="POST"><div class="modal-body p-4">
            <div class="mb-3"><label class="small fw-bold text-muted">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
            <div class="mb-3"><label class="small fw-bold text-muted">Username (ID)</label><input type="text" name="username" class="form-control" required></div>
            <div class="mb-3"><label class="small fw-bold text-muted">Temporary Password</label><input type="text" name="password" class="form-control" value="123456" required></div>
            <div class="mb-3">
    <label class="small fw-bold text-muted"> Gender</label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="sex" id="sexMale" value="Male" required>
        <label class="form-check-label" for="sexMale">Male</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="sex" id="sexFemale" value="Female" required>
        <label class="form-check-label" for="sexFemale">Female</label>
    </div>
</div>
            <div class="mb-3"><label class="small fw-bold text-muted">Account Role</label><select name="role" id="modalRoleSelect" class="form-select" onchange="toggleModalClassField()"><option value="teacher">Teacher</option><option value="admin">Admin</option><option value="student">Student</option></select></div>
            <div class="mb-3" id="classSelectionDiv" style="display:none;"><label class="small fw-bold text-success">Initial Class Placement</label><select name="class_id" class="form-select"><option value="">Select Class...</option><?php 
                $c_q = $conn->query("SELECT * FROM classes ORDER BY grade_level");
                while($c = $c_q->fetch_assoc()){ echo "<option value='{$c['class_id']}'>Grade {$c['grade_level']}-{$c['section']}</option>"; }
            ?></select></div>
        </div><div class="modal-footer border-0 px-4 pb-4"><button type="submit" name="add_user" class="btn btn-primary w-100">Create Account</button></div></form>
    </div></div>
</div>

<script>
    function toggleModalClassField() { 
        var role = document.getElementById("modalRoleSelect").value;
        document.getElementById("classSelectionDiv").style.display = (role === "student") ? "block" : "none";
    }
    function openHandshake(id, name) {
        document.getElementById('hsId').value = id;
        document.getElementById('hsName').innerText = name;
        new bootstrap.Modal(document.getElementById('handshakeModal')).show();
    }
</script>
<?php include 'footer.php'; ?>