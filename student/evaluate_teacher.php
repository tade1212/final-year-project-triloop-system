<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$student_id = $_SESSION['user_id'];

// 1. Fetch System Settings (Is evaluation open?)
$settings = [];
$s_res = $conn->query("SELECT * FROM system_settings");
while($row = $s_res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

$eval_open = ($settings['evaluation_status'] == 'open');
$curr_sem  = $settings['current_semester'];
$curr_year = $settings['current_academic_year'];

// 2. Get Student's Class ID
$cls_query = $conn->query("SELECT class_id FROM students WHERE student_id = $student_id");
$class_id = ($cls_query->fetch_assoc())['class_id'];

// 3. Get all Subjects & Teachers for this Student's class
$sql = "SELECT a.allocation_id, s.subject_name, u.full_name as teacher_name
        FROM allocations a
        JOIN subjects s ON a.subject_id = s.subject_id
        JOIN users u ON a.teacher_id = u.user_id
        WHERE a.class_id = $class_id";
$allocations = $conn->query($sql);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary"><i class="fas fa-user-check me-2"></i> Teacher Evaluation</h3>
        <span class="badge <?php echo $eval_open ? 'bg-success' : 'bg-danger'; ?> p-2">
            Status: <?php echo strtoupper($settings['evaluation_status']); ?>
        </span>
    </div>

    <?php if (!$eval_open): ?>
        <div class="alert alert-warning py-5 text-center shadow-sm border-0">
            <i class="fas fa-clock fa-4x mb-3 opacity-50"></i>
            <h4>Evaluation Period is Currently Closed</h4>
            <p>Please wait for the administrator to open the feedback window (usually at the end of the term).</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while($row = $allocations->fetch_assoc()): 
                $alloc_id = $row['allocation_id'];
                
                // Check if this student ALREADY voted for this allocation
                $voted_check = $conn->query("SELECT * FROM evaluation_voters 
                                            WHERE student_id = $student_id 
                                            AND allocation_id = $alloc_id 
                                            AND semester = '$curr_sem'");
                $has_voted = ($voted_check->num_rows > 0);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0 <?php echo $has_voted ? 'opacity-75' : ''; ?>">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1"><?php echo $row['subject_name']; ?></h5>
                        <p class="text-muted mb-3"><i class="fas fa-user-tie me-2"></i><?php echo $row['teacher_name']; ?></p>
                        
                        <?php if($has_voted): ?>
                            <button class="btn btn-secondary w-100 disabled">
                                <i class="fas fa-check-circle me-1"></i> Feedback Submitted
                            </button>
                        <?php else: ?>
                            <a href="submit_evaluation.php?id=<?php echo $alloc_id; ?>" class="btn btn-primary w-100">
                                <i class="fas fa-star-half-alt me-1"></i> Start Evaluation
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>