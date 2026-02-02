<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_GET['alloc_id'])) { header("Location: dashboard.php"); exit(); }
$alloc_id = intval($_GET['alloc_id']);
$teacher_id = $_SESSION['user_id'];
$success = "";

// 1. Fetch Global Settings
$settings_res = $conn->query("SELECT * FROM system_settings");
$settings = [];
while($s = $settings_res->fetch_assoc()) $settings[$s['setting_key']] = $s['setting_value'];

$selected_sem = isset($_GET['sem']) ? intval($_GET['sem']) : $settings['current_semester'];
$selected_year = isset($_GET['year']) ? $_GET['year'] : $settings['current_academic_year'];

// 2. Fetch Allocation Info
$check_res = $conn->query("SELECT a.*, c.grade_level, c.section, s.subject_name 
                           FROM allocations a
                           JOIN classes c ON a.class_id = c.class_id
                           JOIN subjects s ON a.subject_id = s.subject_id
                           WHERE a.allocation_id = $alloc_id AND a.teacher_id = $teacher_id");
$info = $check_res->fetch_assoc();
if (!$info) die("Access Denied.");

// 3. HANDLE POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("INSERT INTO grades (student_id, allocation_id, semester, academic_year, test1, test2, exercise, activity, group_work, indiv_work, mid_exam, final_exam) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE test1=VALUES(test1), test2=VALUES(test2), exercise=VALUES(exercise), activity=VALUES(activity), group_work=VALUES(group_work), indiv_work=VALUES(indiv_work), mid_exam=VALUES(mid_exam), final_exam=VALUES(final_exam)");

    foreach ($_POST['test1'] as $sid => $val) {
        // If the input is an empty string, we save it as NULL in the database
        $v1 = ($_POST['test1'][$sid] === "") ? null : floatval($_POST['test1'][$sid]);
        $v2 = ($_POST['test2'][$sid] === "") ? null : floatval($_POST['test2'][$sid]);
        $v3 = ($_POST['exercise'][$sid] === "") ? null : floatval($_POST['exercise'][$sid]);
        $v4 = ($_POST['activity'][$sid] === "") ? null : floatval($_POST['activity'][$sid]);
        $v5 = ($_POST['group'][$sid] === "") ? null : floatval($_POST['group'][$sid]);
        $v6 = ($_POST['indiv'][$sid] === "") ? null : floatval($_POST['indiv'][$sid]);
        $v7 = ($_POST['mid'][$sid] === "") ? null : floatval($_POST['mid'][$sid]);
        $v8 = ($_POST['final'][$sid] === "") ? null : floatval($_POST['final'][$sid]);
        
        $stmt->bind_param("iissdddddddd", $sid, $alloc_id, $selected_sem, $selected_year, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8);
        $stmt->execute();
    }
    $success = "Grades updated for registered students.";
}

// 4. FETCH REGISTERED STUDENTS ONLY (Requirement #3)
// We join with semester_registrations to ensure we only see students who "Checked In"
$sql_students = "SELECT u.user_id, u.username, u.full_name, g.*
                 FROM semester_registrations sr
                 JOIN users u ON sr.student_id = u.user_id
                 LEFT JOIN grades g ON sr.student_id = g.student_id 
                    AND g.allocation_id = $alloc_id 
                    AND g.semester = $selected_sem 
                    AND g.academic_year = '$selected_year'
                 WHERE sr.class_id = {$info['class_id']}
                 AND sr.semester = $selected_sem
                 AND sr.academic_year = '$selected_year'
                 ORDER BY u.full_name";
$students = $conn->query($sql_students);

include 'header.php';
?>

<div class="container-fluid mt-3">
    <div class="card bg-light border-0 mb-3 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold text-primary m-0"><?php echo $info['subject_name']; ?></h4>
                <small class="text-muted">Grade <?php echo $info['grade_level'].$info['section']; ?> | <?php echo $selected_year; ?></small>
            </div>
            <form method="GET" class="d-flex align-items-center">
                <input type="hidden" name="alloc_id" value="<?php echo $alloc_id; ?>">
                <select name="sem" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" <?php if($selected_sem == 1) echo 'selected'; ?>>Semester 1</option>
                    <option value="2" <?php if($selected_sem == 2) echo 'selected'; ?>>Semester 2</option>
                </select>
            </form>
            <a href="dashboard.php" class="btn btn-sm btn-secondary">Back</a>
        </div>
    </div>

    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

    <form method="POST">
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-bordered table-hover align-middle text-center table-sm mb-0" style="font-size: 0.85rem;">
                <thead class="table-dark">
                    <tr>
                        <th class="text-start ps-3">Student Name</th>
                        <th>T1(5)</th><th>T2(5)</th><th>Ex(5)</th><th>Ac(5)</th><th>Gr(5)</th><th>In(5)</th>
                        <th class="table-info text-dark">Mid(20)</th>
                        <th class="table-primary text-dark">Final(50)</th>
                        <th class="bg-primary">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students->num_rows > 0): ?>
                    <?php while ($row = $students->fetch_assoc()): $sid = $row['user_id']; ?>
                    <tr>
                        <td class="text-start ps-3 fw-bold"><?php echo $row['full_name']; ?></td>
                        
                        <!-- Requirement #5: Blank instead of 0 using Ternary Operator -->
                        <td><input type="number" step="0.1" name="test1[<?php echo $sid; ?>]" value="<?php echo ($row['test1'] === null) ? '' : floatval($row['test1']); ?>" class="form-control form-control-sm text-center score-input" data-max="5"></td>
                        <td><input type="number" step="0.1" name="test2[<?php echo $sid; ?>]" value="<?php echo ($row['test2'] === null) ? '' : floatval($row['test2']); ?>" class="form-control form-control-sm text-center score-input" data-max="5"></td>
                        <td><input type="number" step="0.1" name="exercise[<?php echo $sid; ?>]" value="<?php echo ($row['exercise'] === null) ? '' : floatval($row['exercise']); ?>" class="form-control form-control-sm text-center score-input" data-max="5"></td>
                        <td><input type="number" step="0.1" name="activity[<?php echo $sid; ?>]" value="<?php echo ($row['activity'] === null) ? '' : floatval($row['activity']); ?>" class="form-control form-control-sm text-center score-input" data-max="5"></td>
                        <td><input type="number" step="0.1" name="group[<?php echo $sid; ?>]" value="<?php echo ($row['group_work'] === null) ? '' : floatval($row['group_work']); ?>" class="form-control form-control-sm text-center score-input" data-max="5"></td>
                        <td><input type="number" step="0.1" name="indiv[<?php echo $sid; ?>]" value="<?php echo ($row['indiv_work'] === null) ? '' : floatval($row['indiv_work']); ?>" class="form-control form-control-sm text-center score-input" data-max="5"></td>
                        <td><input type="number" step="0.1" name="mid[<?php echo $sid; ?>]" value="<?php echo ($row['mid_exam'] === null) ? '' : floatval($row['mid_exam']); ?>" class="form-control form-control-sm text-center score-input border-info" data-max="20"></td>
                        <td><input type="number" step="0.1" name="final[<?php echo $sid; ?>]" value="<?php echo ($row['final_exam'] === null) ? '' : floatval($row['final_exam']); ?>" class="form-control form-control-sm text-center score-input border-primary" data-max="50"></td>
                        
                        <td class="fw-bold text-primary total-cell" id="total_<?php echo $sid; ?>">0</td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="py-4 text-muted">No students have registered for this semester yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 text-end">
            <button type="submit" class="btn btn-primary px-5 shadow-sm">Save All Marks</button>
        </div>
    </form>
</div>

<script>
// Logic to handle auto-sum and display blank if all inputs are empty
function calculateTotals() {
    document.querySelectorAll('tbody tr').forEach(row => {
        let total = 0;
        let hasValue = false;
        row.querySelectorAll('.score-input').forEach(input => {
            if(input.value !== "") {
                hasValue = true;
                let val = parseFloat(input.value) || 0;
                let max = parseFloat(input.dataset.max);
                if (val > max) { input.value = max; val = max; }
                total += val;
            }
        });
        row.querySelector('.total-cell').innerText = hasValue ? total.toFixed(1) : "";
    });
}
window.onload = calculateTotals;
document.querySelectorAll('.score-input').forEach(i => i.addEventListener('input', calculateTotals));
</script>
<?php include 'footer.php'; ?>