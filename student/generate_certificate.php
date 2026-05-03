<?php
session_start();
require '../includes/db_connect.php';

// 1. IDENTIFY TARGET STUDENT (Security Refinement)
if (isset($_GET['sid']) && $_SESSION['role'] == 'teacher') {
    $student_id = intval($_GET['sid']);
    $teacher_id = $_SESSION['user_id'];
    $class_id_check = intval($_GET['class_id']);

    $verify = $conn->query("SELECT * FROM classes WHERE class_id = $class_id_check AND class_teacher_id = $teacher_id");
    if ($verify->num_rows == 0) {
        die("Security Denied: You are not the homeroom teacher for this student.");
    }
} else {
    $student_id = $_SESSION['user_id'];
}

$selected_year = $_GET['year'];
$selected_class = $_GET['class_id'];

// 2. Fetch Student and Class Details
$info_sql = "SELECT u.full_name, u.username, c.grade_level, c.section 
             FROM users u 
             JOIN classes c ON c.class_id = $selected_class
             WHERE u.user_id = $student_id";
$info = ($conn->query($info_sql))->fetch_assoc();

// 3. NEW: Fetch Subject-wise Marks for S1, S2 and Annual Average
$subjects_marks_sql = "SELECT s.subject_name, 
       MAX(CASE WHEN g.semester = 1 THEN g.total_score END) as sem1_total,
       MAX(CASE WHEN g.semester = 2 THEN g.total_score END) as sem2_total
       FROM allocations a
       JOIN subjects s ON a.subject_id = s.subject_id
       LEFT JOIN grades g ON g.allocation_id = a.allocation_id 
            AND g.student_id = $student_id 
            AND g.academic_year = '$selected_year'
       WHERE a.class_id = $selected_class
       GROUP BY s.subject_id
       ORDER BY s.subject_name ASC";
$subjects_res = $conn->query($subjects_marks_sql);

// 4. Fetch Annual Rank and Total Average
$rank_query = "SELECT student_id, (SUM(total_score) / 2) as final_points 
               FROM grades 
               JOIN allocations ON grades.allocation_id = allocations.allocation_id 
               WHERE allocations.class_id = '$selected_class' 
               AND grades.academic_year = '$selected_year' 
               AND grades.semester IN (1, 2)
               GROUP BY student_id 
               ORDER BY final_points DESC";
$rank_res = $conn->query($rank_query);

$my_rank = "N/A";
$my_avg = 0;
$pos = 1;
while($row = $rank_res->fetch_assoc()) {
    if($row['student_id'] == $student_id) {
        $my_rank = $pos;
        $my_avg = $row['final_points'];
        break;
    }
    $pos++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript Certificate - <?php echo $info['full_name']; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; padding: 30px; font-family: 'Times New Roman', serif; }
        .certificate-container {
            width: 1050px; min-height: 800px; padding: 40px; text-align: center;
            border: 15px solid #1e3a8a; background-color: white;
            position: relative; margin: auto; box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .school-name { font-size: 32px; color: #1e3a8a; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;}
        .cert-title { font-size: 40px; color: #b45309; font-weight: bold; margin-bottom: 20px; text-decoration: underline;}
        
        /* Transcript Table Style */
        .transcript-table { width: 100%; margin-top: 25px; border-collapse: collapse; }
        .transcript-table th, .transcript-table td { border: 1px solid #333; padding: 8px; font-size: 16px; }
        .transcript-table th { background-color: #f1f5f9; text-transform: uppercase; font-weight: bold; }
        
        .student-info-box { text-align: left; margin-bottom: 20px; font-size: 18px; display: flex; justify-content: space-between;}
        
        .footer-signatures { margin-top: 50px; display: flex; justify-content: space-between; padding: 0 50px; }
        .sig-line { border-top: 2px solid #000; width: 220px; padding-top: 5px; font-weight: bold; font-size: 16px; }

        @media print {
            body { background: none; padding: 0; }
            .certificate-container { border-width: 10px; margin: 0; width: 100%; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow"><i class="fas fa-print me-2"></i> Print Transcript</button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg ms-2">Back</button>
    </div>

    <div class="certificate-container">
        <div class="school-name">Tsinseta Lemariam Secondary School</div>
        <p class="mb-1">Adigrat, Tigray, Ethiopia</p>
        <div class="cert-title">Official Student Transcript</div>
        
        <div class="student-info-box">
            <div>
                <strong>Student Name:</strong> <?php echo strtoupper($info['full_name']); ?><br>
                <strong>Grade:</strong> <?php echo $info['grade_level'].'-'.$info['section']; ?>
            </div>
            <div class="text-end">
                <strong>Student ID:</strong> <?php echo $info['username']; ?><br>
                <strong>Academic Year:</strong> <?php echo $selected_year; ?>
            </div>
        </div>

        <!-- TRANSCRIPT TABLE -->
        <table class="transcript-table">
            <thead>
                <tr>
                    <th class="text-start">Subject Name</th>
                    <th>Semester 1 (100)</th>
                    <th>Semester 2 (100)</th>
                    <th>Annual Average</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $s1_sum = 0; $s2_sum = 0;
                while($sub = $subjects_res->fetch_assoc()): 
                    $s1 = floatval($sub['sem1_total']);
                    $s2 = floatval($sub['sem2_total']);
                    $ann = ($s1 + $s2) / 2;
                    $s1_sum += $s1; $s2_sum += $s2;
                ?>
                <tr>
                    <td class="text-start fw-bold"><?php echo $sub['subject_name']; ?></td>
                    <td><?php echo ($sub['sem1_total'] !== null) ? $s1 : '-'; ?></td>
                    <td><?php echo ($sub['sem2_total'] !== null) ? $s2 : '-'; ?></td>
                    <td class="fw-bold"><?php echo number_format($ann, 1); ?></td>
                </tr>
                <?php endwhile; ?>
                <!-- TOTAL ROW -->
                <tr style="background-color: #f8fafc;">
                    <td class="text-start fw-bold">TOTAL SCORE / AVERAGE</td>
                    <td class="fw-bold"><?php echo $s1_sum; ?></td>
                    <td class="fw-bold"><?php echo $s2_sum; ?></td>
                    <td class="fw-bold bg-warning bg-opacity-25"><?php echo number_format($my_avg, 1); ?>%</td>
                </tr>
            </tbody>
        </table>

        <!-- RANKING BOX -->
        <div class="mt-4 p-3 border d-inline-block" style="background-color: #f1f5f9;">
            <span class="fs-5">Final Annual Rank: <strong><?php echo $my_rank; ?></strong></span>
        </div>

        <p class="mt-4" style="font-size: 16px;">This transcript is an official record of the student's academic performance for the year mentioned above.</p>

        <div class="footer-signatures">
            <div>
                <div class="sig-line">School Director</div>
            </div>
            <div>
                <div style="font-size: 40px; color: rgba(30, 58, 138, 0.08); font-weight: bold; transform: rotate(-15deg);">OFFICIAL SCHOOL STAMP</div>
            </div>
            <div>
                <div class="sig-line">Homeroom Teacher</div>
            </div>
        </div>

        <div style="position: absolute; bottom: 15px; left: 0; right: 0;">
            <small class="text-muted">Document Verified by Triloop Security Logic on <?php echo date("d-M-Y"); ?></small>
        </div>
    </div>

</body>
</html>