<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// 1. Handle Adding a New Question with Role Targeting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $question_text = $conn->real_escape_string($_POST['question_text']);
    $target_role = $_POST['target_role'];
    
    if (!empty($question_text)) {
        $conn->query("INSERT INTO eval_questions (question_text, target_role) VALUES ('$question_text', '$target_role')");
        $success = "New evaluation criteria added for " . ucfirst($target_role) . "s!";
    }
}

// 2. Handle Deleting
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM eval_questions WHERE question_id = $id");
    header("Location: manage_questions.php?msg=deleted");
    exit();
}

$questions = $conn->query("SELECT * FROM eval_questions ORDER BY target_role, question_id ASC");
?>

<div class="container mt-4">
    <div class="row">
        <!-- Part 1: Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="m-0"><i class="fas fa-plus-circle me-2"></i> Add Question</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($success)) echo "<div class='alert alert-success small'>$success</div>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Target Audience</label>
                            <select name="target_role" class="form-select border-primary" required>
                                <option value="student">Students (Evaluate Teachers)</option>
                                <option value="teacher">Teachers (Peer Evaluation)</option>
                                <option value="admin">Admin (Staff Performance)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Question Text</label>
                            <textarea name="question_text" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="add_question" class="btn btn-primary w-100">Save Question</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Part 2: Table -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3"><h5 class="m-0 fw-bold">Evaluation Question Bank</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Target</th>
                                <th>Question</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $questions->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo ($row['target_role'] == 'admin') ? 'bg-danger' : (($row['target_role'] == 'teacher') ? 'bg-info' : 'bg-success'); ?>">
                                        <?php echo strtoupper($row['target_role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['question_text']; ?></td>
                                <td class="text-center">
                                    <a href="manage_questions.php?delete_id=<?php echo $row['question_id']; ?>" class="text-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>