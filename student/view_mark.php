<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$student_id = $_SESSION['user_id'];

// 1. Fetch History
$history_sql = "SELECT DISTINCT r.academic_year, c.grade_level, c.section, r.class_id 
                FROM semester_registrations r 
                JOIN classes c ON r.class_id = c.class_id 
                WHERE r.student_id = $student_id 
                ORDER BY r.academic_year DESC, c.grade_level DESC";
$history_res = $conn->query($history_sql);

// 2. Filters
$selected_year = isset($_GET['year']) ? $_GET['year'] : '';
$selected_sem  = isset($_GET['sem']) ? $_GET['sem'] : '1';
$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';

if ($selected_year == '' && $history_res->num_rows > 0) {
    $history_res->data_seek(0);
    $latest = $history_res->fetch_assoc();
    $selected_year = $latest['academic_year'];
    $selected_class = $latest['class_id'];
}

// 3. RANKING LOGIC (Modified for Annual)
if ($selected_sem == '3') {
    // Annual Rank: Average of S1 and S2
    $rank_query = "SELECT student_id, (SUM(total_score) / 2) as grand_total 
                   FROM grades 
                   JOIN allocations ON grades.allocation_id = allocations.allocation_id 
                   WHERE allocations.class_id = '$selected_class' 
                   AND grades.academic_year = '$selected_year' 
                   AND grades.semester IN (1, 2)
                   GROUP BY student_id 
                   ORDER BY grand_total DESC";
} else {
    $rank_query = "SELECT student_id, SUM(total_score) as grand_total 
                   FROM grades 
                   JOIN allocations ON grades.allocation_id = allocations.allocation_id 
                   WHERE allocations.class_id = '$selected_class' 
                   AND grades.academic_year = '$selected_year' 
                   AND grades.semester = '$selected_sem' 
                   GROUP BY student_id 
                   ORDER BY grand_total DESC";
}

$rank_res = $conn->query($rank_query);
$my_rank = "N/A";
$my_total_points = 0;
$pos = 1;
while($row = $rank_res->fetch_assoc()) {
    if($row['student_id'] == $student_id) {
        $my_rank = $pos;
        $my_total_points = $row['grand_total'];
        break;
    }
    $pos++;
}

// 4. FETCH MARKS (Modified for Annual)
if ($selected_sem == '3') {
    // Annual: Average components of S1 and S2 per subject
    $marks_sql = "SELECT sub.subject_name, 
                  AVG(g.test1) as test1, AVG(g.test2) as test2, AVG(g.exercise) as exercise, 
                  AVG(g.activity) as activity, AVG(g.group_work) as group_work, 
                  AVG(g.indiv_work) as indiv_work, AVG(g.mid_exam) as mid_exam, 
                  AVG(g.final_exam) as final_exam, AVG(g.total_score) as total_score
                  FROM grades g
                  JOIN allocations a ON g.allocation_id = a.allocation_id
                  JOIN subjects sub ON a.subject_id = sub.subject_id
                  WHERE g.student_id = $student_id 
                  AND g.academic_year = '$selected_year' 
                  AND g.semester IN (1, 2)
                  GROUP BY sub.subject_id";
} else {
    $marks_sql = "SELECT sub.subject_name, g.* 
                  FROM grades g
                  JOIN allocations a ON g.allocation_id = a.allocation_id
                  JOIN subjects sub ON a.subject_id = sub.subject_id
                  WHERE g.student_id = $student_id 
                  AND g.academic_year = '$selected_year' 
                  AND g.semester = '$selected_sem'";
}
$marks_res = $conn->query($marks_sql);
?>

<div class="container-fluid px-2">
    <!-- FILTER HEADER -->
    <div class="card shadow-sm border-0 mb-3 bg-light">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="view_mark.php" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <label class="small fw-bold text-muted d-block">Academic Year & Grade</label>
                    <select name="year_class" class="form-select form-select-sm" onchange="location.href='view_mark.php?year=' + this.value.split('|')[0] + '&class_id=' + this.value.split('|')[1] + '&sem=<?php echo $selected_sem; ?>'">
                        <?php 
                        $history_res->data_seek(0);
                        while($h = $history_res->fetch_assoc()): 
                            $val = $h['academic_year'] . "|" . $h['class_id'];
                            $selected = ($selected_year == $h['academic_year'] && $selected_class == $h['class_id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $val; ?>" <?php echo $selected; ?>>
                            <?php echo $h['academic_year']; ?> (Grade <?php echo $h['grade_level'].$h['section']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="small fw-bold text-muted d-block">Semester</label>
                    <select name="sem" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="1" <?php if($selected_sem == '1') echo 'selected'; ?>>Semester 1</option>
                        <option value="2" <?php if($selected_sem == '2') echo 'selected'; ?>>Semester 2</option>
                        <option value="3" <?php if($selected_sem == '3') echo 'selected'; ?>>Annual Result</option>
                    </select>
                    <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                </div>
                <div class="col-6 col-md-4 text-end pt-3 pt-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100 w-md-auto" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SUMMARY CARDS (Optimized for Mobile) -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card bg-primary text-white border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <small class="opacity-75 d-block text-uppercase" style="font-size: 0.7rem;">Rank</small>
                    <h2 class="fw-bold mb-0"><?php echo $my_rank; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-success text-white border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <small class="opacity-75 d-block text-uppercase" style="font-size: 0.7rem;">Total Score</small>
                    <h2 class="fw-bold mb-0"><?php echo number_format($my_total_points, 1); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- MARKS TABLE -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover m-0 text-center align-middle" style="font-size: 0.85rem;">
                    <thead class="table-dark small text-uppercase">
                        <tr>
                            <th class="text-start">Subject</th>
                            <th>Test1(5)</th><th>Test2(5)</th><th>Activity(5)</th><th>Exercisebook(5)</th><th>Individual Work(5)</th><th>Group Work(5)</th><th>Mid Exam(20)</th><th>Final Exam(50)</th>
                            <th class="bg-primary">Tot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($marks_res->num_rows > 0): ?>
                            <?php while($m = $marks_res->fetch_assoc()): ?>
                            <tr>
                                <td class="text-start fw-bold"><?php echo $m['subject_name']; ?></td>
                                <td><?php echo floatval($m['test1']); ?></td>
                                <td><?php echo floatval($m['test2']); ?></td>
                                <td><?php echo floatval($m['activity']); ?></td>
                                <td><?php echo floatval($m['exercise']); ?></td>
                                <td><?php echo floatval($m['indiv_work']); ?></td>
                                <td><?php echo floatval($m['group_work']); ?></td>
                                <td><?php echo floatval($m['mid_exam']); ?></td>
                                <td><?php echo floatval($m['final_exam']); ?></td>
                                <td class="fw-bold text-primary bg-light"><?php echo number_format($m['total_score'], 1); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="py-5 text-muted">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling for mobile - keeps table headers readable */
    @media (max-width: 576px) {
        .table th, .table td { padding: 0.4rem 0.2rem !important; font-size: 0.75rem; }
        .display-4 { font-size: 2rem; }
    }
</style>

<?php include 'footer.php'; ?>