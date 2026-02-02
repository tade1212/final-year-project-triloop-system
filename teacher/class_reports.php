<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$teacher_id = $_SESSION['user_id'];

// Fetch the class this teacher manages
$my_classes = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $teacher_id");
?>

<div class="container-fluid mt-4">
    <h3 class="fw-bold text-primary">Homeroom Management</h3>
    <p class="text-muted">Generate Master Rosters and Ranks for your assigned sections.</p>

    <div class="row mt-4">
        <?php if ($my_classes->num_rows > 0): ?>
            <?php while($cls = $my_classes->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body text-center">
                            <div class="mb-3 text-success">
                                <i class="fas fa-users fa-3x"></i>
                            </div>
                            <h4 class="card-title fw-bold">Grade <?php echo $cls['grade_level'].'-'.$cls['section']; ?></h4>
                            <p class="card-text text-muted">Master Roster & Ranking</p>
                            <a href="view_roster.php?class_id=<?php echo $cls['class_id']; ?>" class="btn btn-primary w-100">
                                Generate Report <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-warning">You are not assigned as a Homeroom Teacher for any class.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>