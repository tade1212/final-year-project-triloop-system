<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$teacher_id = $_SESSION['user_id'];

// 1. Fetch Current Settings (Year/Semester)
$res = $conn->query("SELECT * FROM system_settings");
$settings = [];
while($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
$curr_year = $settings['current_academic_year'];

// 2. Query: Overall Average Score for this Teacher
$overall_sql = "SELECT AVG(er.rating) as total_avg, COUNT(er.res_id) as total_feedback_points
                FROM evaluation_responses er
                JOIN allocations a ON er.allocation_id = a.allocation_id
                WHERE a.teacher_id = $teacher_id";
$overall_res = $conn->query($overall_sql);
$overall_data = $overall_res->fetch_assoc();

// 3. Query: Detailed Breakdown per Question (To see Weaknesses)
$detail_sql = "SELECT q.question_text, AVG(er.rating) as q_avg
               FROM eval_questions q
               JOIN evaluation_responses er ON q.question_id = er.question_id
               JOIN allocations a ON er.allocation_id = a.allocation_id
               WHERE a.teacher_id = $teacher_id
               GROUP BY q.question_id";
$detail_res = $conn->query($detail_sql);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary"><i class="fas fa-chart-line me-2"></i> My Performance Analysis</h3>
        <span class="text-muted">Academic Year: <?php echo $curr_year; ?></span>
    </div>

    <div class="row">
        <!-- Summary Card -->
        <div class="col-md-4 mb-4">
            <div class="card bg-dark text-white shadow border-0 h-100">
                <div class="card-body text-center p-4 d-flex flex-column justify-content-center">
                    <h6 class="text-uppercase opacity-75 mb-3">Overall Satisfaction Index</h6>
                    <?php if($overall_data['total_avg']): ?>
                        <h1 class="display-2 fw-bold mb-0 text-warning">
                            <?php echo number_format($overall_data['total_avg'], 1); ?>
                        </h1>
                        <p class="fs-5">out of 5.0</p>
                        <hr class="bg-white">
                        <small class="opacity-75">Based on <?php echo $overall_data['total_feedback_points']; ?> individual rating points from students.</small>
                    <?php else: ?>
                        <h2 class="text-muted">No Data Yet</h2>
                        <p class="small">Students haven't submitted evaluations for your classes this term.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 fw-bold"><i class="fas fa-tasks me-2 text-primary"></i> Criteria Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php if($detail_res->num_rows > 0): ?>
                        <p class="text-muted mb-4">This breakdown helps you identify specific areas for pedagogical improvement.</p>
                        
                        <?php while($row = $detail_res->fetch_assoc()): 
                            $val = $row['q_avg'];
                            $percent = ($val / 5) * 100;
                            
                            // Color Logic
                            $bar_class = "bg-success"; // Strength
                            if($val < 4) $bar_class = "bg-info";    // Good
                            if($val < 3) $bar_class = "bg-warning"; // Warning
                            if($val < 2) $bar_class = "bg-danger";  // Weakness
                        ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-dark"><?php echo $row['question_text']; ?></span>
                                <span class="badge <?php echo $bar_class; ?>"><?php echo number_format($val, 1); ?> / 5</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 10px;">
                                <div class="progress-bar <?php echo $bar_class; ?> progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: <?php echo $percent; ?>%">
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>

                

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fa-3x text-light mb-3"></i>
                            <p class="text-muted">Detailed analytics will appear once the evaluation window closes.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>