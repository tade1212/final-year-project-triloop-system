<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$student_id = $_SESSION['user_id'];

// 1. Fetch History for the Filter Dropdown
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

// 3. SUBJECT COUNT (Required for accurate Average calculation)
$sub_count_query = $conn->query("SELECT COUNT(allocation_id) as total_subs FROM allocations WHERE class_id = '$selected_class'");
$sub_count_data = $sub_count_query->fetch_assoc();
$num_subjects = $sub_count_data['total_subs'] > 0 ? $sub_count_data['total_subs'] : 1;

// 4. RANKING & SUM LOGIC
if ($selected_sem == '3') {
    // Annual Sum: Sum S1 + S2 then divide by 2 to keep it out of 100 per subject
    $rank_query = "SELECT student_id, (SUM(total_score) / 2) as final_sum 
                   FROM grades 
                   JOIN allocations ON grades.allocation_id = allocations.allocation_id 
                   WHERE allocations.class_id = '$selected_class' 
                   AND grades.academic_year = '$selected_year' 
                   AND grades.semester IN (1, 2)
                   GROUP BY student_id 
                   ORDER BY final_sum DESC";
} else {
    // Semester Sum
    $rank_query = "SELECT student_id, SUM(total_score) as final_sum 
                   FROM grades 
                   JOIN allocations ON grades.allocation_id = allocations.allocation_id 
                   WHERE allocations.class_id = '$selected_class' 
                   AND grades.academic_year = '$selected_year' 
                   AND grades.semester = '$selected_sem' 
                   GROUP BY student_id 
                   ORDER BY final_sum DESC";
}

$rank_res = $conn->query($rank_query);
$my_rank = "N/A";
$my_total_sum = 0;
$pos = 1;
while($row = $rank_res->fetch_assoc()) {
    if($row['student_id'] == $student_id) {
        $my_rank = $pos;
        $my_total_sum = $row['final_sum'];
        break;
    }
    $pos++;
}

// Calculate actual Average
$my_average = $my_total_sum / $num_subjects;

// 5. FETCH MARKS FOR TABLE
if ($selected_sem == '3') {
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

<style>
    /* Mobile Font Optimization */
    @media (max-width: 576px) {
        .stat-label { font-size: 0.6rem !important; }
        .stat-value { font-size: 1.1rem !important; }
        .score-table th, .score-table td { font-size: 0.7rem !important; padding: 4px !important; }
    }
    
    /* PRINT LOGIC */
    @media print {
        body * { visibility: hidden; }
        #printableArea, #printableArea * { visibility: visible; }
        #printableArea { position: absolute; left: 0; top: 0; width: 100%; color: black !important; }
        .no-print { display: none !important; }
        .table { border: 1px solid #dee2e6 !important; width: 100% !important; }
        #printOnlyHeader { display: block !important; }
    }
    #printOnlyHeader { display: none; text-align: center; border-bottom: 3px double #333; margin-bottom: 20px; padding-bottom: 10px; }
</style>

<div class="container-fluid px-2">

    <!-- 1. FILTERS (Hides during Print) -->
    <div class="card shadow-sm mb-3 no-print border-0 bg-light">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="view_mark.php" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="small fw-bold text-muted">Year & Grade</label>
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
                    <label class="small fw-bold text-muted">Semester</label>
                    <select name="sem" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="1" <?php if($selected_sem == '1') echo 'selected'; ?>>Semester 1</option>
                        <option value="2" <?php if($selected_sem == '2') echo 'selected'; ?>>Semester 2</option>
                        <option value="3" <?php if($selected_sem == '3') echo 'selected'; ?>>Annual Result</option>
                    </select>
                    <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                </div>
                <div class="col-6 col-md-4 text-end">
                    <?php if($selected_sem == '3'): ?>
                        <a href="generate_certificate.php?year=<?php echo $selected_year; ?>&class_id=<?php echo $selected_class; ?>" 
                           class="btn btn-sm btn-warning w-100 w-md-auto shadow-sm fw-bold">
                            <i class="fas fa-medal me-1"></i> Certificate
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-primary w-100 w-md-auto shadow-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print Report
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. PRINTABLE CONTENT -->
    <div id="printableArea">
        
        <div id="printOnlyHeader">
            <h2 class="mb-1">TSINSETA LEMARIAM SECONDARY SCHOOL</h2>
            <h4 class="text-uppercase border-top border-bottom py-1">Official Student Report Card</h4>
            <div class="row mt-3 text-start small">
                <div class="col-6"><strong>Student:</strong> <?php echo $_SESSION['full_name']; ?></div>
                <div class="col-6 text-end"><strong>ID:</strong> <?php echo $_SESSION['username']; ?></div>
            </div>
        </div>

        <!-- TRIPLE SUMMARY CARDS (Rank, Sum, Average) -->
        <div class="row g-2 mb-3">
            <!-- RANK -->
            <div class="col-4">
                <div class="card bg-primary text-white border-0 shadow-sm">
                    <div class="card-body p-2 p-md-3 text-center">
                        <small class="stat-label opacity-75 d-block text-uppercase fw-bold">Rank</small>
                        <h3 class="stat-value fw-bold mb-0"><?php echo $my_rank; ?></h3>
                    </div>
                </div>
            </div>
            <!-- SUM -->
            <div class="col-4">
                <div class="card bg-success text-white border-0 shadow-sm">
                    <div class="card-body p-2 p-md-3 text-center">
                        <small class="stat-label opacity-75 d-block text-uppercase fw-bold">Total Sum</small>
                        <h3 class="stat-value fw-bold mb-0"><?php echo number_format($my_total_sum, 1); ?></h3>
                    </div>
                </div>
            </div>
            <!-- AVERAGE -->
            <div class="col-4">
                <div class="card bg-info text-white border-0 shadow-sm">
                    <div class="card-body p-2 p-md-3 text-center">
                        <small class="stat-label opacity-75 d-block text-uppercase fw-bold">Average</small>
                        <h3 class="stat-value fw-bold mb-0"><?php echo number_format($my_average, 1); ?>%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- MARKS TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover m-0 text-center align-middle score-table">
                        <thead class="table-dark small text-uppercase">
                            <tr>
                                <th class="text-start ps-3">Subject</th>
                                <th>T1(5%)</th><th>T2(5%)</th><th>Ac(5%)</th><th>Ex(5%)</th><th>In(5%)</th><th>Gr(5%)</th><th>Mid(20%)</th><th>Fin(50%)</th>
                                <th class="bg-primary">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($marks_res->num_rows > 0): ?>
                                <?php while($m = $marks_res->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-start ps-3 fw-bold"><?php echo $m['subject_name']; ?></td>
                                    <td><?php echo ($m['test1'] !== null) ? floatval($m['test1']) : '-'; ?></td>
                                    <td><?php echo ($m['test2'] !== null) ? floatval($m['test2']) : '-'; ?></td>
                                    <td><?php echo ($m['activity'] !== null) ? floatval($m['activity']) : '-'; ?></td>
                                    <td><?php echo ($m['exercise'] !== null) ? floatval($m['exercise']) : '-'; ?></td>
                                    <td><?php echo ($m['indiv_work'] !== null) ? floatval($m['indiv_work']) : '-'; ?></td>
                                    <td><?php echo ($m['group_work'] !== null) ? floatval($m['group_work']) : '-'; ?></td>
                                    <td><?php echo ($m['mid_exam'] !== null) ? floatval($m['mid_exam']) : '-'; ?></td>
                                    <td><?php echo ($m['final_exam'] !== null) ? floatval($m['final_exam']) : '-'; ?></td>
                                    <td class="fw-bold text-primary bg-light"><?php echo number_format($m['total_score'], 1); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="py-5 text-muted">No academic records found for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <h3 class="<?php echo ($my_average >= 50) ? 'text-success' : 'text-danger'; ?> fw-bold text-uppercase" style="letter-spacing: 2px;">
                <?php echo ($my_average >= 50) ? 'Status: Promoted' : 'Status: Failed'; ?>
            </h3>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>