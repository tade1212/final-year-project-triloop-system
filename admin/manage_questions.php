<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// 1. Handle Adding a New Question
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $question_text = $conn->real_escape_string($_POST['question_text']);
    
    if (!empty($question_text)) {
        $conn->query("INSERT INTO eval_questions (question_text) VALUES ('$question_text')");
        $success = "New evaluation criteria added!";
    }
}

// 2. Handle Deleting a Question
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM eval_questions WHERE question_id = $id");
    header("Location: manage_questions.php?msg=deleted");
    exit();
}

// 3. Fetch all existing questions
$questions = $conn->query("SELECT * FROM eval_questions ORDER BY question_id ASC");
?>

<div class="container mt-4">
    <div class="row">
        <!-- Part 1: Form to Add Question -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="m-0"><i class="fas fa-plus-circle me-2"></i> Add Criteria</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Question / Criteria</label>
                            <textarea name="question_text" class="form-control" rows="4" placeholder="e.g., Does the teacher provide clear examples during class?" required></textarea>
                        </div>
                        <button type="submit" name="add_question" class="btn btn-primary w-100">
                            Save Question
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Part 2: List of Existing Questions -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 fw-bold text-dark"><i class="fas fa-list me-2 text-primary"></i> Current Evaluation Questions</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">#ID</th>
                                <th>Evaluation Question / Criteria</th>
                                <th class="text-center" style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($questions->num_rows > 0): ?>
                                <?php while($row = $questions->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['question_id']; ?></td>
                                    <td class="fw-normal"><?php echo $row['question_text']; ?></td>
                                    <td class="text-center">
                                        <a href="manage_questions.php?delete_id=<?php echo $row['question_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Delete this question? This will affect future evaluations.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No evaluation questions found. Add one on the left.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="alert alert-info mt-3 small">
                <i class="fas fa-info-circle me-2"></i> These questions will appear for all students when the evaluation window is opened.
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>