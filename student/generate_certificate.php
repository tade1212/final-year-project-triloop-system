<?php
session_start();
require '../includes/db_connect.php';

// 1. IDENTIFY TARGET STUDENT (Security)
if (isset($_GET['sid']) && $_SESSION['role'] == 'teacher') {
    $student_id = intval($_GET['sid']);
    $teacher_id = $_SESSION['user_id'];
    $class_id_check = intval($_GET['class_id']);
    $verify = $conn->query("SELECT * FROM classes WHERE class_id = $class_id_check AND class_teacher_id = $teacher_id");
    if ($verify->num_rows == 0) die("Access Denied.");
} else {
    $student_id = $_SESSION['user_id'];
}

$selected_year = $_GET['year'];
$selected_class = $_GET['class_id'];

// 2. Fetch Student/Class Info
$info_sql = "SELECT u.full_name, u.username, c.grade_level, c.section 
             FROM users u 
             JOIN classes c ON c.class_id = $selected_class
             WHERE u.user_id = $student_id";
$info = ($conn->query($info_sql))->fetch_assoc();

// 3. Fetch Transcript Table Data (S1, S2, Annual)
$subjects_marks_sql = "SELECT s.subject_name, 
       MAX(CASE WHEN g.semester = 1 THEN g.total_score END) as sem1_total,
       MAX(CASE WHEN g.semester = 2 THEN g.total_score END) as sem2_total
       FROM allocations a
       JOIN subjects s ON a.subject_id = s.subject_id
       LEFT JOIN grades g ON g.allocation_id = a.allocation_id 
            AND g.student_id = $student_id 
            AND g.academic_year = '$selected_year'
       WHERE a.class_id = $selected_class
       GROUP BY s.subject_id ORDER BY s.subject_name ASC";
$subjects_res = $conn->query($subjects_marks_sql);
$num_subjects = $subjects_res->num_rows;

// 4. Fetch Annual Rank 
$rank_query = "SELECT student_id, (SUM(total_score) / 2) as final_points 
               FROM grades 
               JOIN allocations ON grades.allocation_id = allocations.allocation_id 
               WHERE allocations.class_id = '$selected_class' 
               AND grades.academic_year = '$selected_year' 
               AND grades.semester IN (1, 2)
               GROUP BY student_id ORDER BY final_points DESC";
$rank_res = $conn->query($rank_query);

$my_rank = "N/A"; $my_total_points = 0; $pos = 1;
while($row = $rank_res->fetch_assoc()) {
    if($row['student_id'] == $student_id) { $my_rank = $pos; $my_total_points = $row['final_points']; break; }
    $pos++;
}

// Precise Grand Math
$grand_sum = $my_total_points * 2; // Total S1 + S2 points
$grand_average = ($num_subjects > 0) ? ($my_total_points / $num_subjects) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript - <?php echo $info['full_name']; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; padding: 20px; font-family: 'Times New Roman', serif; }
        .certificate-container { width: 1050px; min-height: 850px; padding: 50px; text-align: center; border: 15px solid #1e3a8a; background: white; margin: auto; position: relative; }
        .school-name { font-size: 32px; color: #1e3a8a; font-weight: bold; text-transform: uppercase; }
        .cert-title { font-size: 38px; color: #b45309; font-weight: bold; text-decoration: underline; margin-bottom: 20px; }
        .transcript-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .transcript-table th, .transcript-table td { border: 1px solid #333; padding: 10px; font-size: 16px; }
        .transcript-table th { background-color: #f1f5f9; }
        .student-info { text-align: left; margin-bottom: 20px; display: flex; justify-content: space-between; font-size: 18px; }
        .summary-card { border: 2px solid #1e3a8a; padding: 15px; background: #f8fafc; }
        .sig-line { border-top: 2px solid #000; width: 220px; margin-top: 60px; font-weight: bold; }
        @media print { .no-print { display: none; } body { padding: 0; } .certificate-container { width: 100%; border-width: 10px; } }
    </style>
</head>
<body>

    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow-sm">Print Official Transcript</button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg ms-2">Back</button>
    </div>

    <div class="certificate-container">
        <div class="school-name">Tsinseta Lemariam Secondary School</div>
        <p>Adigrat, Tigray, Ethiopia</p>
        <div class="cert-title">Official Academic Transcript</div>

        <div class="student-info">
            <div><strong>Name:</strong> <?php echo strtoupper($info['full_name']); ?><br><strong>ID:</strong> <?php echo $info['username']; ?></div>
            <div class="text-end"><strong>Grade:</strong> <?php echo $info['grade_level'].'-'.$info['section']; ?><br><strong>Year:</strong> <?php echo $selected_year; ?></div>
        </div>

        <table class="transcript-table">
            <thead>
                <tr>
                    <th class="text-start">Subject</th>
                    <th>Semester 1 (100)</th>
                    <th>Semester 2 (100)</th>
                    <th>Annual (100)</th>
                </tr>
            </thead>
            <tbody>
                <?php while($sub = $subjects_res->fetch_assoc()): 
                    $s1 = $sub['sem1_total']; $s2 = $sub['sem2_total'];
                    $ann = (floatval($s1) + floatval($s2)) / 2;
                ?>
                <tr>
                    <td class="text-start fw-bold"><?php echo $sub['subject_name']; ?></td>
                    <td><?php echo ($s1 !== null) ? floatval($s1) : '-'; ?></td>
                    <td><?php echo ($s2 !== null) ? floatval($s2) : '-'; ?></td>
                    <td class="fw-bold bg-light"><?php echo number_format($ann, 1); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- REFINED SUMMARY: SUM AND AVERAGE SEPARATED -->
        <div class="row mt-4 justify-content-center">
            <div class="col-4 px-1">
                <div class="summary-card">
                    <small class="text-muted fw-bold">ANNUAL RANK</small>
                    <h3 class="m-0 text-primary fw-bold"><?php echo $my_rank; ?></h3>
                </div>
            </div>
            <div class="col-4 px-1">
                <div class="summary-card">
                    <small class="text-muted fw-bold">TOTAL POINTS (SUM)</small>
                    <h3 class="m-0 text-dark fw-bold"><?php echo number_format($grand_sum, 1); ?></h3>
                </div>
            </div>
            <div class="col-4 px-1">
                <div class="summary-card">
                    <small class="text-muted fw-bold">GENERAL AVERAGE</small>
                    <h3 class="m-0 text-success fw-bold"><?php echo number_format($grand_average, 1); ?>%</h3>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between px-5 mt-5">
            <div><div class="sig-line">Director</div></div>
            <div style="font-size: 40px; color: rgba(30,58,138,0.1); font-weight: bold; transform: rotate(-15deg); margin-top: 30px;">SCHOOL SEAL</div>
            <div><div class="sig-line">Homeroom Teacher</div></div>
        </div>

        <div style="position: absolute; bottom: 20px; left: 0; right: 0; font-size: 12px; color: #777;">
            Document Generated: <?php echo date("d-M-Y H:i"); ?> | Triloop Integrated Logic
        </div>
    </div>

</body>
</html>