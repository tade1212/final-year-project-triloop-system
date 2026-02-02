<?php
require '../includes/db_connect.php';
include 'header.php';

$student_id = $_SESSION['user_id'];

// 1. Get Student Class Info
$info_sql = "SELECT c.grade_level, c.section, c.class_id 
             FROM students s 
             JOIN classes c ON s.class_id = c.class_id 
             WHERE s.student_id = $student_id";
$info_res = $conn->query($info_sql);
$student_info = $info_res->fetch_assoc();

// 2. Count Subjects
$subj_sql = "SELECT COUNT(*) as total FROM allocations WHERE class_id = " . $student_info['class_id'];
$subj_count = $conn->query($subj_sql)->fetch_assoc()['total'];

// 3. Count Pending Evaluations
// (This checks how many teachers teach this student but haven't been evaluated by them yet)
// We will refine this once we build the evaluation logic, but for now, it's a placeholder.
$pending_eval = 0; 
?>

<div class="row g-4">
    <!-- Marks Summary -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center">
                <div class="display-6 text-primary mb-2"><i class="fas fa-graduation-cap"></i></div>
                <h5 class="card-title">My Performance</h5>
                <p class="text-muted small">Check your grades, totals, and class rank.</p>
                <a href="view_mark.php" class="btn btn-primary w-100">View Report Card</a>
            </div>
        </div>
    </div>

    <!-- Schedule Summary -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center">
                <div class="display-6 text-success mb-2"><i class="fas fa-clock"></i></div>
                <h5 class="card-title">Class Schedule</h5>
                <p class="text-muted small">You have <strong><?php echo $subj_count; ?></strong> subjects this semester.</p>
                <a href="view_schedule.php" class="btn btn-success w-100">View Timetable</a>
            </div>
        </div>
    </div>

    <!-- Evaluation Summary -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center">
                <div class="display-6 text-warning mb-2"><i class="fas fa-star"></i></div>
                <h5 class="card-title">Teacher Evaluation</h5>
                <p class="text-muted small">Help improve your school by providing anonymous feedback.</p>
                <a href="evaluate_teacher.php" class="btn btn-warning w-100 text-white">Start Evaluation</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>