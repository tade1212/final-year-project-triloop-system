<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$my_id = $_SESSION['user_id'];

// 1. Fetch System Settings
$settings = $conn->query("SELECT * FROM system_settings");
$set = []; while($row = $settings->fetch_assoc()) $set[$row['setting_key']] = $row['setting_value'];

// 2. Fetch Colleagues (All teachers except ME)
$colleagues = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'teacher' AND user_id != $my_id");

// 3. Handle specific teacher selection for evaluation
$target_teacher_id = isset($_GET['evaluate_id']) ? intval($_GET['evaluate_id']) : null;
$questions = $conn->query("SELECT * FROM eval_questions WHERE target_role = 'teacher' ORDER BY question_id");

// 4. Handle Submission
if (isset($_POST['submit_peer_eval'])) {
    $target_tid = $_POST['target_teacher_id'];
    $ratings = $_POST['rating'];
    
    // We use a placeholder allocation_id to fit existing schema
    $alloc_res = $conn->query("SELECT allocation_id FROM allocations WHERE teacher_id = $target_tid LIMIT 1");
    $alloc_id = ($alloc_res->fetch_assoc())['allocation_id'] ?? 0;

    $conn->begin_transaction();
    try {
        $conn->query("INSERT INTO evaluation_voters (student_id, allocation_id, semester, academic_year, evaluator_role) 
                      VALUES ($my_id, $alloc_id, '{$set['current_semester']}', '{$set['current_academic_year']}', 'teacher')");
        foreach($ratings as $qid => $score) {
            $conn->query("INSERT INTO evaluation_responses (allocation_id, question_id, rating, semester, academic_year, evaluator_role) 
                          VALUES ($alloc_id, $qid, $score, '{$set['current_semester']}', '{$set['current_academic_year']}', 'teacher')");
        }
        $conn->commit();
        echo "<script>alert('Peer evaluation submitted!'); window.location='peer_evaluation.php';</script>";
    } catch(Exception $e) { $conn->rollback(); }
}
?>

<div class="container mt-4">
    <h4 class="fw-bold text-primary mb-4"><i class="fas fa-users-cog me-2"></i> Peer Performance Review</h4>

    <?php if(!$target_teacher_id): ?>
        <div class="row g-3">
            <?php while($t = $colleagues->fetch_assoc()): 
                // Check if already evaluated
                $check = $conn->query("SELECT * FROM evaluation_voters WHERE student_id=$my_id AND evaluator_role='teacher' AND allocation_id IN (SELECT allocation_id FROM allocations WHERE teacher_id={$t['user_id']})");
            ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="fw-bold"><?php echo $t['full_name']; ?></h6>
                        <?php if($check->num_rows > 0): ?>
                            <span class="badge bg-light text-success">Completed</span>
                        <?php else: ?>
                            <a href="peer_evaluation.php?evaluate_id=<?php echo $t['user_id']; ?>" class="btn btn-sm btn-outline-primary">Evaluate Colleague</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: 
        $t_name = ($conn->query("SELECT full_name FROM users WHERE user_id=$target_teacher_id")->fetch_assoc())['full_name'];
    ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">Evaluate: <?php echo $t_name; ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="target_teacher_id" value="<?php echo $target_teacher_id; ?>">
                    <table class="table align-middle">
                        <?php while($q = $questions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $q['question_text']; ?></td>
                                <td class="text-end">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <input type="radio" name="rating[<?php echo $q['question_id']; ?>]" value="<?php echo $i; ?>" required class="mx-1"> <?php echo $i; ?>
                                    <?php endfor; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                    <button type="submit" name="submit_peer_eval" class="btn btn-primary w-100">Submit Review</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>