<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_GET['class_id'])) header("Location: dashboard.php");
$class_id = intval($_GET['class_id']);
$teacher_id = $_SESSION['user_id'];

// Fetch Settings
$settings_res = $conn->query("SELECT * FROM system_settings");
$settings = [];
while($s = $settings_res->fetch_assoc()) $settings[$s['setting_key']] = $s['setting_value'];

// Filters
$view_type = isset($_GET['view']) ? $_GET['view'] : 'sem'; // sem or annual
$selected_sem = isset($_GET['sem']) ? intval($_GET['sem']) : $settings['current_semester'];
$selected_year = isset($_GET['year']) ? $_GET['year'] : $settings['current_academic_year'];

// Security
$verify = $conn->query("SELECT * FROM classes WHERE class_id=$class_id AND class_teacher_id=$teacher_id");
if ($verify->num_rows == 0) die("Access Denied.");
$class_info = $verify->fetch_assoc();

// 1. GET SUBJECTS
$subjects = [];
$sub_query = $conn->query("SELECT s.subject_name, s.subject_id FROM allocations a JOIN subjects s ON a.subject_id = s.subject_id WHERE a.class_id = $class_id ORDER BY s.subject_name");
while($s = $sub_query->fetch_assoc()) $subjects[$s['subject_id']] = $s['subject_name'];

// 2. GET STUDENTS & GRADES
$data = [];
$semester_clause = ($view_type == 'annual') ? "AND g.semester IN (1,2)" : "AND g.semester = $selected_sem";

$sql = "SELECT st.student_id, u.full_name, u.username, g.total_score, g.semester, a.subject_id
        FROM students st
        JOIN users u ON st.student_id = u.user_id
        LEFT JOIN allocations a ON a.class_id = st.class_id
        LEFT JOIN grades g ON g.student_id = st.student_id 
            AND g.allocation_id = a.allocation_id 
            AND g.academic_year = '$selected_year' 
            $semester_clause
        WHERE st.class_id = $class_id";

$res = $conn->query($sql);
while($row = $res->fetch_assoc()) {
    $sid = $row['student_id'];
    $sub_id = $row['subject_id'];
    
    if (!isset($data[$sid])) {
        $data[$sid] = ['name' => $row['full_name'], 'username' => $row['username'], 'scores' => [], 'total' => 0, 'avg' => 0];
    }

    if ($view_type == 'annual') {
        // Accumulate scores from both semesters to average later
        if (!isset($data[$sid]['scores'][$sub_id])) $data[$sid]['scores'][$sub_id] = 0;
        $data[$sid]['scores'][$sub_id] += floatval($row['total_score']) / 2;
    } else {
        $data[$sid]['scores'][$sub_id] = $row['total_score'];
    }
}

// 3. CALCULATION & RANKING
foreach ($data as $sid => $student) {
    $sum = 0; $count = count($subjects);
    if ($count > 0) {
        foreach ($student['scores'] as $score) $sum += floatval($score);
        $data[$sid]['total'] = $sum;
        $data[$sid]['avg'] = $sum / $count;
    }
}
uasort($data, function($a, $b) { return $b['avg'] <=> $a['avg']; });

include 'header.php';
?>

<div class="container-fluid mt-3">
    <div class="card border-0 bg-light shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h4 class="fw-bold text-primary m-0">Roster: Grade <?php echo $class_info['grade_level'].$class_info['section']; ?></h4>
            
            <form method="GET" class="d-flex align-items-center">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                
                <select name="view" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                    <option value="sem" <?php if($view_type == 'sem') echo 'selected'; ?>>Semester Report</option>
                    <option value="annual" <?php if($view_type == 'annual') echo 'selected'; ?>>Annual Average</option>
                </select>

                <?php if($view_type == 'sem'): ?>
                <select name="sem" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                    <option value="1" <?php if($selected_sem == 1) echo 'selected'; ?>>Sem 1</option>
                    <option value="2" <?php if($selected_sem == 2) echo 'selected'; ?>>Sem 2</option>
                </select>
                <?php endif; ?>

                <input type="text" name="year" class="form-control form-control-sm w-auto" value="<?php echo $selected_year; ?>" readonly>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0 text-center align-middle small">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Rank</th><th>ID</th><th class="text-start">Name</th>
                            <?php foreach($subjects as $subName): ?>
                                <th><?php echo substr($subName, 0, 3); ?></th>
                            <?php endforeach; ?>
                            <th class="bg-primary text-white">Avg</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($data as $student): $avg = round($student['avg'], 1); ?>
                        <tr>
                            <td class="fw-bold"><?php echo $rank++; ?></td>
                            <td class="text-muted"><?php echo $student['username']; ?></td>
                            <td class="text-start fw-bold"><?php echo $student['name']; ?></td>
                            <?php foreach($subjects as $subId => $subName): 
                                $score = isset($student['scores'][$subId]) ? floatval($student['scores'][$subId]) : '-'; ?>
                                <td class="<?php echo ($score < 50 && is_numeric($score)) ? 'text-danger' : ''; ?>"><?php echo $score; ?></td>
                            <?php endforeach; ?>
                            <td class="bg-primary bg-opacity-10 fw-bold"><?php echo $avg; ?></td>
                            <td><?php echo ($avg >= 50) ? '<span class="badge bg-success">Pass</span>' : '<span class="badge bg-danger">Fail</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>