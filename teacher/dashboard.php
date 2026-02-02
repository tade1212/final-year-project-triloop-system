<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$teacher_id = $_SESSION['user_id'];

// Fetch assigned subjects
$sql = "SELECT a.allocation_id, s.subject_name, c.grade_level, c.section
        FROM allocations a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN classes c ON a.class_id = c.class_id
        WHERE a.teacher_id = $teacher_id
        ORDER BY c.grade_level, c.section";

$result = $conn->query($sql);
?>

<h3 class="mt-4 mb-4">My Assigned Classes</h3>

<div class="row g-4">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card shadow-sm h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-dark">
                            Grade <?php echo $row['grade_level'] . '-' . $row['section']; ?>
                        </h5>
                        <p class="card-text text-muted">
                            <i class="fas fa-book me-2"></i> <?php echo $row['subject_name']; ?>
                        </p>
                        <hr>
                        <a href="enter_marks.php?alloc_id=<?php echo $row['allocation_id']; ?>" 
                           class="btn btn-primary w-100">
                           <i class="fas fa-edit me-1"></i> Enter Marks
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">
            You have not been assigned any classes yet. Please contact the Administrator.
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>