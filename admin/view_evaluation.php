<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// 1. Fetch Current Settings
$res = $conn->query("SELECT * FROM system_settings");
$settings = [];
while($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
$curr_year = $settings['current_academic_year'];

// 2. Query: Get Average Rating per Teacher across all their subjects
$summary_sql = "SELECT u.user_id, u.full_name, AVG(er.rating) as overall_avg, COUNT(DISTINCT ev.voter_id) as total_voters
                FROM users u
                JOIN allocations a ON u.user_id = a.teacher_id
                LEFT JOIN evaluation_responses er ON a.allocation_id = er.allocation_id
                LEFT JOIN evaluation_voters ev ON a.allocation_id = ev.allocation_id
                WHERE u.role = 'teacher'
                GROUP BY u.user_id
                ORDER BY overall_avg DESC";
$summary_res = $conn->query($summary_sql);

// 3. Handle Detailed View (If a specific teacher is selected)
$selected_teacher = isset($_GET['view_id']) ? intval($_GET['view_id']) : null;
?>

<div class="container mt-4">
    <h3 class="fw-bold text-primary mb-4"><i class="fas fa-chart-bar me-2"></i> Teacher Performance Analytics</h3>

    <div class="row">
        <!-- Part 1: Overall Leaderboard -->
        <div class="<?php echo $selected_teacher ? 'col-md-5' : 'col-md-12'; ?>">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 fw-bold">Teacher Rankings</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Teacher Name</th>
                                <th class="text-center">Avg Rating</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($t = $summary_res->fetch_assoc()): ?>
                            <tr class="<?php echo ($selected_teacher == $t['user_id']) ? 'table-primary' : ''; ?>">
                                <td class="fw-bold"><?php echo $t['full_name']; ?></td>
                                <td class="text-center">
                                    <?php if($t['overall_avg']): ?>
                                        <span class="badge rounded-pill <?php echo ($t['overall_avg'] >= 4) ? 'bg-success' : (($t['overall_avg'] >= 2.5) ? 'bg-warning' : 'bg-danger'); ?> fs-6">
                                            <?php echo number_format($t['overall_avg'], 2); ?> / 5.0
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">No Data</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="view_evaluation.php?view_id=<?php echo $t['user_id']; ?>" class="btn btn-sm btn-primary">
                                        Analyze Details
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Part 2: Detailed Weakness/Strength Analysis -->
        <?php if($selected_teacher): 
            // Fetch name
            $name_res = $conn->query("SELECT full_name FROM users WHERE user_id = $selected_teacher");
            $t_name = ($name_res->fetch_assoc())['full_name'];

            // Fetch average per question for this teacher
            $detail_sql = "SELECT q.question_text, AVG(er.rating) as q_avg
                           FROM eval_questions q
                           JOIN evaluation_responses er ON q.question_id = er.question_id
                           JOIN allocations a ON er.allocation_id = a.allocation_id
                           WHERE a.teacher_id = $selected_teacher
                           GROUP BY q.question_id";
            $detail_res = $conn->query($detail_sql);
        ?>
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <h5 class="m-0">Details: <?php echo $t_name; ?></h5>
                    <a href="view_evaluation.php" class="btn-close btn-close-white"></a>
                </div>
                <div class="card-body">
                    <h6>Criteria Breakdown (Weaknesses & Strengths)</h6>
                    <hr>
                    <?php if($detail_res->num_rows > 0): ?>
                        <?php while($q = $detail_res->fetch_assoc()): 
                            $percentage = ($q['q_avg'] / 5) * 100;
                            $color = ($q['q_avg'] >= 4) ? 'bg-success' : (($q['q_avg'] >= 2.5) ? 'bg-warning' : 'bg-danger');
                        ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold small"><?php echo $q['question_text']; ?></span>
                                <span class="fw-bold text-primary"><?php echo number_format($q['q_avg'], 1); ?>/5</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar <?php echo $color; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center py-5 text-muted">No detailed feedback scores available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>