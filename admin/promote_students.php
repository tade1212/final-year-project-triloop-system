<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// 1. SECURITY: Ensure only Admin can access
if ($_SESSION['role'] !== 'admin') {
    die("<div class='alert alert-light border-danger text-danger mt-5'>Unauthorized Access. This action is restricted to Administrators only.</div>");
}

// 2. FETCH SYSTEM SETTINGS
$settings_res = $conn->query("SELECT * FROM system_settings");
$settings = [];
while($s = $settings_res->fetch_assoc()) $settings[$s['setting_key']] = $s['setting_value'];

$curr_year = $settings['current_academic_year'];
$curr_sem  = (int)$settings['current_semester'];

$show_preview = false;
$process_complete = false;
$preview_data = ['promoted' => 0, 'repeating' => 0, 'graduating' => 0];

// 3. LOGIC FOR PREVIEW (Analyze results without changing DB)
if (isset($_POST['start_preview'])) {
    $show_preview = true;
    $sql = "SELECT st.student_id, c.grade_level, AVG(g.total_score) as annual_avg
            FROM students st
            JOIN classes c ON st.class_id = c.class_id
            JOIN grades g ON st.student_id = g.student_id
            WHERE g.academic_year = '$curr_year'
            GROUP BY st.student_id";
    
    $res = $conn->query($sql);
    if($res){
        while($row = $res->fetch_assoc()) {
            if ($row['annual_avg'] >= 50) {
                if ($row['grade_level'] == 12) $preview_data['graduating']++;
                else $preview_data['promoted']++;
            } else {
                $preview_data['repeating']++;
            }
        }
    }
}

// 4. LOGIC FOR EXECUTION (The Actual Shifting and Archiving)
// 4. LOGIC FOR EXECUTION (The Actual Shifting and Archiving)
if (isset($_POST['execute_promotion'])) {
    $conn->begin_transaction();
    try {
        // Fetch all students and their averages for the current year
        $to_process = $conn->query("SELECT st.student_id, u.full_name, c.grade_level, c.section, AVG(g.total_score) as annual_avg
                                 FROM students st
                                 JOIN users u ON st.student_id = u.user_id
                                 JOIN classes c ON st.class_id = c.class_id
                                 JOIN grades g ON st.student_id = g.student_id
                                 WHERE g.academic_year = '$curr_year'
                                 GROUP BY st.student_id");

        while($student = $to_process->fetch_assoc()) {
            $sid = $student['student_id'];
            $avg = $student['annual_avg'];
            $current_grade = $student['grade_level'];
            $sect = $student['section'];

            if($avg >= 50) {
                if($current_grade < 12) {
                    // PASSING UNDERGRADE: Shift to Grade + 1
                    $next_grade = $current_grade + 1;
                    $target_res = $conn->query("SELECT class_id FROM classes WHERE grade_level = $next_grade AND section = '$sect'");
                    $target = $target_res->fetch_assoc();
                    
                    if($target) {
                        $new_cid = $target['class_id'];
                        $conn->query("UPDATE students SET class_id = $new_cid WHERE student_id = $sid");
                    } else {
                        // Error fallback: If Admin forgot to create the next grade class
                        throw new Exception("Promotion failed for student $sid. Target class Grade $next_grade-$sect does not exist.");
                    }
                } else {
                    // PASSING GRADE 12: Archive in graduated_students table
                    $stmt_grad = $conn->prepare("INSERT INTO graduated_students (student_id, full_name, section_at_grad, final_avg, academic_year) VALUES (?, ?, ?, ?, ?)");
                    $stmt_grad->bind_param("issds", $sid, $student['full_name'], $sect, $avg, $curr_year);
                    $stmt_grad->execute();

                    // FIXED: Set class_id to NULL for graduates (Alumni)
                    $conn->query("UPDATE students SET class_id = NULL WHERE student_id = $sid");
                }
            }
            // Fails stay in their current class_id (No update query needed)
        }
        
        $conn->commit();
        $process_complete = true;
    } catch (Exception $e) {
        $conn->rollback();
        // Friendly error message for the Admin
        $error = "Promotion Stopped: " . $e->getMessage();
    }
}
?>

<style>
    :root { --primary-blue: #2c3e50; --accent-teal: #16a085; }
    .card-header-pro { background-color: var(--primary-blue); color: white; border-bottom: 4px solid var(--accent-teal); }
    .btn-pro { background-color: var(--primary-blue); color: white; }
    .stat-card { border: none; border-radius: 10px; }
</style>

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header card-header-pro py-3">
            <h5 class="m-0 fw-bold"><i class="fas fa-layer-group me-2"></i> Year-End Transition Manager</h5>
        </div>
        <div class="card-body p-4">
            
            <?php if ($curr_sem != 2): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-check fa-5x mb-3 text-muted"></i>
                    <h3 class="fw-bold">Promotion Period Closed</h3>
                    <p class="text-muted">Promotion shifting can only be executed at the end of <strong>Semester 2</strong>.</p>
                </div>

            <?php elseif (!$show_preview && !$process_complete): ?>
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h4 class="fw-bold text-dark">Annual Shifting Wizard</h4>
                        <p class="text-muted">Analyze student performance for <strong><?php echo $curr_year; ?></strong>. Passing students will be moved to the next grade; Grade 12 graduates will be archived.</p>
                        <form method="POST">
                            <button type="submit" name="start_preview" class="btn btn-pro px-5">Analyze Results</button>
                        </form>
                    </div>
                </div>

            <?php elseif ($show_preview && !$process_complete): ?>
                <h5 class="fw-bold mb-4">Summary for <?php echo $curr_year; ?></h5>
                <div class="row g-3 mb-5">
                    <div class="col-md-4 text-center">
                        <div class="card stat-card shadow-sm p-3" style="background-color: #e8f5e9;">
                            <h2 class="text-success"><?php echo $preview_data['promoted']; ?></h2>
                            <small class="fw-bold">READY TO PROMOTE</small>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card stat-card shadow-sm p-3" style="background-color: #fff8e1;">
                            <h2 class="text-warning"><?php echo $preview_data['repeating']; ?></h2>
                            <small class="fw-bold">STAYING (FAIL)</small>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card stat-card shadow-sm p-3" style="background-color: #e3f2fd;">
                            <h2 class="text-primary"><?php echo $preview_data['graduating']; ?></h2>
                            <small class="fw-bold">GRADUATING (12)</small>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-light rounded border-start border-4 border-success">
                    <h6 class="fw-bold">Final Confirmation</h6>
                    <form method="POST">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" required id="c">
                            <label class="form-check-label small" for="c">I verify that the annual marklist is complete and correct.</label>
                        </div>
                        <button type="submit" name="execute_promotion" class="btn btn-success px-4">Execute Shifting Now</button>
                    </form>
                </div>

            <?php elseif ($process_complete): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <h2 class="fw-bold">Process Complete!</h2>
                    <p class="text-muted">Students shifted and Grade 12 archive updated. Please change the year in settings next.</p>
                    <a href="settings.php" class="btn btn-primary px-4 mt-3">Go to Settings</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php include 'footer.php'; ?>