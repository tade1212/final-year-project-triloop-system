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

// 3. LOGIC FOR PREVIEW (Step 1)
if (isset($_POST['start_preview'])) {
    $show_preview = true;
    $sql = "SELECT st.student_id, c.grade_level, AVG(g.total_score) as annual_avg
            FROM students st
            JOIN classes c ON st.class_id = c.class_id
            JOIN grades g ON st.student_id = g.student_id
            WHERE g.academic_year = '$curr_year'
            GROUP BY st.student_id";
    
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) {
        if ($row['annual_avg'] >= 50) {
            if ($row['grade_level'] == 12) $preview_data['graduating']++;
            else $preview_data['promoted']++;
        } else {
            $preview_data['repeating']++;
        }
    }
}

// 4. LOGIC FOR EXECUTION (The Actual Shifting)
if (isset($_POST['execute_promotion'])) {
    // A. Fetch all passing students below grade 12
    $to_move = $conn->query("SELECT st.student_id, c.grade_level, c.section
                             FROM students st
                             JOIN classes c ON st.class_id = c.class_id
                             JOIN grades g ON st.student_id = g.student_id
                             WHERE g.academic_year = '$curr_year'
                             GROUP BY st.student_id HAVING AVG(g.total_score) >= 50");

    while($student = $to_move->fetch_assoc()) {
        $sid = $student['student_id'];
        $next_grade = $student['grade_level'] + 1;
        $sect = $student['section'];

        if($student['grade_level'] < 12) {
            // Find class ID for same section in next grade
            $target = $conn->query("SELECT class_id FROM classes WHERE grade_level = $next_grade AND section = '$sect'")->fetch_assoc();
            if($target) {
                $new_cid = $target['class_id'];
                $conn->query("UPDATE students SET class_id = $new_cid WHERE student_id = $sid");
            }
        } else {
            // Graduate Grade 12s (Change role to alumni or clear class_id)
            $conn->query("UPDATE users SET role = 'student' WHERE user_id = $sid"); // Keeping role but removing from class later
        }
    }
    $process_complete = true;
}
?>

<style>
    :root {
        --primary-blue: #2c3e50; /* Deep Navy */
        --accent-teal: #16a085;  /* Soft Teal */
        --soft-bg: #f4f7f6;
    }
    .card-header-pro { background-color: var(--primary-blue); color: white; border-bottom: 4px solid var(--accent-teal); }
    .btn-pro { background-color: var(--primary-blue); color: white; transition: 0.3s; }
    .btn-pro:hover { background-color: #1a252f; color: white; }
    .stat-card { border: none; border-radius: 10px; transition: transform 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
</style>

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header card-header-pro py-3">
            <h5 class="m-0 fw-bold"><i class="fas fa-layer-group me-2"></i> Academic Transition Manager</h5>
        </div>
        <div class="card-body p-4">
            
            <?php if ($curr_sem != 2): ?>
                <!-- STATUS: LOCKED -->
                <div class="text-center py-5">
                    <div class="mb-4" style="color: #bdc3c7;"><i class="fas fa-calendar-check fa-5x"></i></div>
                    <h3 class="text-dark fw-bold">Promotion Period Closed</h3>
                    <p class="text-muted">Promotion logic is only accessible at the end of <strong>Semester 2</strong>.<br>The system is currently set to Academic Year: <?php echo $curr_year; ?>, Semester <?php echo $curr_sem; ?>.</p>
                    <a href="settings.php" class="btn btn-outline-primary px-4 mt-3">Go to System Settings</a>
                </div>

            <?php elseif (!$show_preview && !$process_complete): ?>
                <!-- STEP 1: WELCOME & START -->
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h4 class="fw-bold text-dark">Prepare for Year-End Transition</h4>
                        <p class="text-muted">This wizard will automatically identify students who achieved a 50% average or higher across both semesters and prepare them for promotion to the next grade level.</p>
                        <div class="p-3 bg-light border-start border-4 border-info mb-4">
                            <small class="text-dark d-block"><strong>Academic Year:</strong> <?php echo $curr_year; ?></small>
                            <small class="text-dark"><strong>Logic:</strong> (Semester 1 + Semester 2) / 2 &ge; 50%</small>
                        </div>
                        <form method="POST">
                            <button type="submit" name="start_preview" class="btn btn-pro btn-lg px-5 shadow-sm">
                                Run Result Analysis
                            </button>
                        </form>
                    </div>
                    <!-- <div class="col-md-5 d-none d-md-block text-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/3200/3200135.png" style="width: 200px; opacity: 0.6;">
                    </div> -->
                </div>

            <?php elseif ($show_preview && !$process_complete): ?>
                <!-- STEP 2: PREVIEW SUMMARY -->
                <h5 class="fw-bold mb-4">Analysis Summary for <?php echo $curr_year; ?></h5>
                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <div class="card stat-card shadow-sm" style="background-color: #e8f5e9;">
                            <div class="card-body text-center">
                                <h2 class="fw-bold text-success mb-0"><?php echo $preview_data['promoted']; ?></h2>
                                <small class="text-success text-uppercase fw-bold">To Grade +1</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card shadow-sm" style="background-color: #fff8e1;">
                            <div class="card-body text-center">
                                <h2 class="fw-bold text-warning mb-0"><?php echo $preview_data['repeating']; ?></h2>
                                <small class="text-warning text-uppercase fw-bold">Repeating Grade</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card shadow-sm" style="background-color: #e3f2fd;">
                            <div class="card-body text-center">
                                <h2 class="fw-bold text-primary mb-0"><?php echo $preview_data['graduating']; ?></h2>
                                <small class="text-primary text-uppercase fw-bold">Graduating (12)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 bg-light">
                    <div class="card-body p-4 border-start border-4 border-success">
                        <h6 class="fw-bold text-dark">Ready to finalize?</h6>
                        <p class="small text-muted">By clicking "Execute", the system will update the student records. This is a permanent action.</p>
                        <form method="POST">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="vCheck" required>
                                <label class="form-check-label small" for="vCheck">I confirm that the marks for both semesters have been verified.</label>
                            </div>
                            <button type="submit" name="execute_promotion" class="btn btn-success px-4 me-2 shadow-sm">Execute Shifting</button>
                            <a href="promote_students.php" class="btn btn-link text-muted small">Cancel</a>
                        </form>
                    </div>
                </div>

            <?php elseif ($process_complete): ?>
                <!-- STEP 3: FINISH -->
                <div class="text-center py-5">
                    <div class="mb-4 text-success"><i class="fas fa-check-circle fa-5x"></i></div>
                    <h2 class="fw-bold text-dark">Transition Successful!</h2>
                    <p class="text-muted">Database has been updated. The shifting process is now complete.</p>
                    <hr class="my-4 mx-auto w-50">
                    <p class="small fw-bold text-uppercase text-secondary">Mandatory Next Steps:</p>
                    <div class="d-grid gap-2 d-md-block">
                        <a href="settings.php" class="btn btn-primary px-4"><i class="fas fa-sync me-2"></i> 1. Update to New Academic Year</a>
                        <a href="manage_users.php" class="btn btn-outline-secondary px-4 ms-md-2"><i class="fas fa-user-plus me-2"></i> 2. Add New Students</a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>