<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// 1. Fetch Weights from Settings
$set_res = $conn->query("SELECT * FROM system_settings");
$set = [];
while($row = $set_res->fetch_assoc()) $set[$row['setting_key']] = $row['setting_value'];

$w_std = $set['weight_student'] / 100;
$w_tea = $set['weight_teacher'] / 100;
$w_adm = $set['weight_admin'] / 100;

// 2. Query: Calculate Weighted Average for each teacher
$summary_sql = "SELECT u.user_id, u.full_name,
                AVG(CASE WHEN er.evaluator_role = 'student' THEN er.rating END) as avg_std,
                AVG(CASE WHEN er.evaluator_role = 'teacher' THEN er.rating END) as avg_tea,
                AVG(CASE WHEN er.evaluator_role = 'admin' THEN er.rating END) as avg_adm
                FROM users u
                JOIN allocations a ON u.user_id = a.teacher_id
                LEFT JOIN evaluation_responses er ON a.allocation_id = er.allocation_id
                WHERE u.role = 'teacher'
                GROUP BY u.user_id";
$summary_res = $conn->query($summary_sql);

// Store processed data for sorting
$leaderboard = [];
while($t = $summary_res->fetch_assoc()){
    $s = $t['avg_std'] ?? 0;
    $p = $t['avg_tea'] ?? 0;
    $a = $t['avg_adm'] ?? 0;
    
    // THE AGGREGATE FORMULA
    $final_score = ($s * $w_std) + ($p * $w_tea) + ($a * $w_adm);
    
    $t['final_weighted_score'] = $final_score;
    $leaderboard[] = $t;
}

// Sort leaderboard by final score
usort($leaderboard, function($a, $b) { return $b['final_weighted_score'] <=> $a['final_weighted_score']; });

$selected_teacher = isset($_GET['view_id']) ? intval($_GET['view_id']) : null;
?>

<div class="container mt-4">
    <h3 class="fw-bold text-primary mb-4"><i class="fas fa-chart-pie me-2"></i> 360° Aggregate Teacher Performance</h3>

    <div class="row">
        <div class="<?php echo $selected_teacher ? 'col-md-5' : 'col-md-12'; ?>">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3"><h5 class="m-0 fw-bold">Aggregate Leaderboard</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th class="text-center small">Student (<?php echo $set['weight_student']; ?>%)</th>
                                <th class="text-center small">Peer (<?php echo $set['weight_teacher']; ?>%)</th>
                                <th class="text-center small">Admin (<?php echo $set['weight_admin']; ?>%)</th>
                                <th class="text-center bg-primary text-white">FINAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaderboard as $t): ?>
                            <tr class="<?php echo ($selected_teacher == $t['user_id']) ? 'table-primary' : ''; ?>">
                                <td class="fw-bold small"><?php echo $t['full_name']; ?></td>
                                <td class="text-center small"><?php echo number_format($t['avg_std'], 1); ?></td>
                                <td class="text-center small"><?php echo number_format($t['avg_tea'], 1); ?></td>
                                <td class="text-center small"><?php echo number_format($t['avg_adm'], 1); ?></td>
                                <td class="text-center fw-bold text-primary"><?php echo number_format($t['final_weighted_score'], 2); ?></td>
                                <td class="text-center">
                                    <a href="view_evaluation.php?view_id=<?php echo $t['user_id']; ?>" class="btn btn-sm btn-link p-0"><i class="fas fa-search-plus"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if($selected_teacher): 
            $detail_sql = "SELECT q.question_text, q.target_role, AVG(er.rating) as q_avg
                           FROM eval_questions q
                           JOIN evaluation_responses er ON q.question_id = er.question_id
                           JOIN allocations a ON er.allocation_id = a.allocation_id
                           WHERE a.teacher_id = $selected_teacher
                           GROUP BY q.question_id ORDER BY q.target_role";
            $detail_res = $conn->query($detail_sql);
        ?>
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <h5 class="m-0 small">Analysis: Detailed Angle Breakdown</h5>
                    <a href="view_evaluation.php" class="btn-close btn-close-white"></a>
                </div>
                <div class="card-body">
                    <?php while($q = $detail_res->fetch_assoc()): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><span class="badge bg-secondary me-2"><?php echo strtoupper($q['target_role']); ?></span> <?php echo $q['question_text']; ?></span>
                                <span class="fw-bold"><?php echo number_format($q['q_avg'], 1); ?>/5</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar <?php echo ($q['target_role'] == 'admin') ? 'bg-danger' : (($q['target_role'] == 'teacher') ? 'bg-info' : 'bg-success'); ?>" 
                                     style="width: <?php echo ($q['q_avg']/5)*100; ?>%"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>