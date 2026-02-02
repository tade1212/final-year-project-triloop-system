<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$student_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) { header("Location: evaluate_teacher.php"); exit(); }
$alloc_id = intval($_GET['id']);

// Fetch System Settings
$settings_res = $conn->query("SELECT * FROM system_settings");
$settings = [];
while($row = $settings_res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

$curr_sem  = $settings['current_semester'];
$curr_year = $settings['current_academic_year'];

// Fetch Subject and Teacher Details
$info_sql = "SELECT s.subject_name, u.full_name as teacher_name 
             FROM allocations a 
             JOIN subjects s ON a.subject_id = s.subject_id 
             JOIN users u ON a.teacher_id = u.user_id 
             WHERE a.allocation_id = $alloc_id";
$info = ($conn->query($info_sql))->fetch_assoc();

// HANDLE SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_scores'])) {
    $ratings = $_POST['rating']; 
    $optional_comment = !empty($_POST['comment']) ? $conn->real_escape_string($_POST['comment']) : null;

    $conn->begin_transaction();
    try {
        // 1. Mark voter and save optional comment
        $stmt_voter = $conn->prepare("INSERT INTO evaluation_voters (student_id, allocation_id, semester, academic_year, student_comment) VALUES (?, ?, ?, ?, ?)");
        $stmt_voter->bind_param("iisss", $student_id, $alloc_id, $curr_sem, $curr_year, $optional_comment);
        $stmt_voter->execute();

        // 2. Save individual anonymous ratings
        foreach ($ratings as $q_id => $score) {
            $q_id = intval($q_id);
            $score = intval($score);
            $conn->query("INSERT INTO evaluation_responses (allocation_id, question_id, rating, semester, academic_year) 
                          VALUES ($alloc_id, $q_id, $score, '$curr_sem', '$curr_year')");
        }

        $conn->commit();
        echo "<script>alert('Feedback submitted successfully!'); window.location='evaluate_teacher.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$questions = $conn->query("SELECT * FROM eval_questions ORDER BY question_id ASC");
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="m-0"><i class="fas fa-edit me-2"></i> Evaluate: <?php echo $info['teacher_name']; ?></h5>
            <small class="opacity-75"><?php echo $info['subject_name']; ?> | Semester <?php echo $curr_sem; ?> (<?php echo $curr_year; ?>)</small>
        </div>
        <div class="card-body">
            <div class="alert alert-light border small text-muted mb-4">
                <i class="fas fa-info-circle me-1"></i> Rate each criteria from 1 (Poor) to 5 (Excellent).
            </div>

            <form method="POST">
                <!-- MATRIX TABLE -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start" style="min-width: 250px;">Evaluation Criteria</th>
                                <th style="width: 80px;">1</th>
                                <th style="width: 80px;">2</th>
                                <th style="width: 80px;">3</th>
                                <th style="width: 80px;">4</th>
                                <th style="width: 80px;">5</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($q = $questions->fetch_assoc()): ?>
                            <tr>
                                <td class="text-start fw-bold"><?php echo $q['question_text']; ?></td>
                                <?php for($i=1; $i<=5; $i++): ?>
                                <td>
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input" type="radio" 
                                               name="rating[<?php echo $q['question_id']; ?>]" 
                                               value="<?php echo $i; ?>" required>
                                    </div>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- OPTIONAL COMMENT BOX -->
                <div class="mt-4">
                    <label class="form-label fw-bold"><i class="far fa-comment-dots me-2"></i>Additional Comments (Optional)</label>
                    <textarea name="comment" class="form-control" rows="3" placeholder="Is there anything else you would like to mention about this subject or teacher?"></textarea>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small d-none d-md-block">Your identity is hidden for all feedback.</span>
                    <div class="w-100 text-end">
                        <a href="evaluate_teacher.php" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" name="submit_scores" class="btn btn-primary px-5">
                            Submit Evaluation
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Styling to make it look attractive and clear */
    .table thead th { font-weight: 800; font-size: 0.9rem; }
    .form-check-input { cursor: pointer; width: 1.25rem; height: 1.25rem; }
    .form-check-input:checked { background-color: #0d6efd; border-color: #0d6efd; }
    @media (max-width: 768px) {
        .card-header h5 { font-size: 1rem; }
        .table { font-size: 0.85rem; }
    }
</style>

<?php include 'footer.php'; ?>