<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$admin_id = $_SESSION['user_id'];

// 1. Fetch System Settings (Semester/Year)
$settings = $conn->query("SELECT * FROM system_settings");
$set = []; while($row = $settings->fetch_assoc()) $set[$row['setting_key']] = $row['setting_value'];

// 2. Fetch All Teachers to be evaluated
$teachers_res = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name");

// 3. Handle selection and form display
$target_tid = isset($_GET['tid']) ? intval($_GET['tid']) : null;
$questions = $conn->query("SELECT * FROM eval_questions WHERE target_role = 'admin' ORDER BY question_id");

// 4. Handle Submission
if (isset($_POST['submit_admin_eval'])) {
    $tid = $_POST['target_teacher_id'];
    $ratings = $_POST['rating'];
    
    // Find a valid allocation_id for this teacher to link the data correctly
    $alloc_res = $conn->query("SELECT allocation_id FROM allocations WHERE teacher_id = $tid LIMIT 1");
    $alloc_row = $alloc_res->fetch_assoc();
    $alloc_id = $alloc_row['allocation_id'] ?? 0;

    $conn->begin_transaction();
    try {
        // Record that Admin has voted for this teacher
        $conn->query("INSERT INTO evaluation_voters (student_id, allocation_id, semester, academic_year, evaluator_role) 
                      VALUES ($admin_id, $alloc_id, '{$set['current_semester']}', '{$set['current_academic_year']}', 'admin')");

        // Save anonymous responses
        foreach($ratings as $qid => $score) {
            $qid = intval($qid);
            $score = intval($score);
            $conn->query("INSERT INTO evaluation_responses (allocation_id, question_id, rating, semester, academic_year, evaluator_role) 
                          VALUES ($alloc_id, $qid, $score, '{$set['current_semester']}', '{$set['current_academic_year']}', 'admin')");
        }
        $conn->commit();
        echo "<script>alert('Staff evaluation saved successfully!'); window.location='evaluate_staff.php';</script>";
    } catch(Exception $e) { 
        $conn->rollback(); 
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-primary"><i class="fas fa-user-check me-2"></i> Staff Performance Review</h4>
        <span class="badge bg-danger">ADMINISTRATIVE ROLE</span>
    </div>

    <?php if(!$target_tid): ?>
        <!-- STEP 1: SELECT TEACHER -->
        <div class="row g-3">
            <?php while($t = $teachers_res->fetch_assoc()): 
                // Check if admin already evaluated this teacher
                $check = $conn->query("SELECT * FROM evaluation_voters 
                                       WHERE student_id=$admin_id 
                                       AND evaluator_role='admin' 
                                       AND allocation_id IN (SELECT allocation_id FROM allocations WHERE teacher_id={$t['user_id']})");
            ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="mb-2 text-muted"><i class="fas fa-user-tie fa-2x"></i></div>
                        <h6 class="fw-bold mb-3"><?php echo $t['full_name']; ?></h6>
                        <?php if($check->num_rows > 0): ?>
                            <button class="btn btn-sm btn-secondary w-100 disabled"><i class="fas fa-check-circle me-1"></i> Evaluated</button>
                        <?php else: ?>
                            <a href="evaluate_staff.php?tid=<?php echo $t['user_id']; ?>" class="btn btn-sm btn-primary w-100">Evaluate Staff Member</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

    <?php else: 
        // STEP 2: SHOW FORM
        $t_res = $conn->query("SELECT full_name FROM users WHERE user_id=$target_tid");
        $t_data = $t_res->fetch_assoc();
    ?>
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between">
                <h5 class="m-0">Evaluation Form: <?php echo $t_data['full_name']; ?></h5>
                <a href="evaluate_staff.php" class="btn-close btn-close-white"></a>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="target_teacher_id" value="<?php echo $target_tid; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Administrative Criteria</th>
                                    <th class="text-center" style="width: 200px;">Rating (1-5)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($questions->num_rows > 0): ?>
                                    <?php while($q = $questions->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $q['question_text']; ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <input type="radio" class="btn-check" name="rating[<?php echo $q['question_id']; ?>]" id="q<?php echo $q['question_id'].'_'.$i; ?>" value="<?php echo $i; ?>" required autocomplete="off">
                                                    <label class="btn btn-outline-primary btn-sm" for="q<?php echo $q['question_id'].'_'.$i; ?>"><?php echo $i; ?></label>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center py-4">No admin questions found. Please add them in <a href="manage_questions.php">Questions Manager</a>.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-end">
                        <a href="evaluate_staff.php" class="btn btn-light px-4 me-2">Cancel</a>
                        <button type="submit" name="submit_admin_eval" class="btn btn-success px-5 shadow-sm" <?php if($questions->num_rows == 0) echo 'disabled'; ?>>
                            Submit Performance Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>