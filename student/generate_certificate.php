<?php
session_start();
require '../includes/db_connect.php';

// 1. IDENTIFY TARGET STUDENT (Security Refinement)
if (isset($_GET['sid']) && $_SESSION['role'] == 'teacher') {
    // TEACHER ACCESSING A STUDENT'S CERTIFICATE
    $student_id = intval($_GET['sid']);
    $teacher_id = $_SESSION['user_id'];
    $class_id_check = intval($_GET['class_id']);

    // Check if this teacher is the homeroom teacher for this class
    $verify = $conn->query("SELECT * FROM classes WHERE class_id = $class_id_check AND class_teacher_id = $teacher_id");
    if ($verify->num_rows == 0) {
        die("Security Denied: You are not the homeroom teacher for this student.");
    }
} else {
    // STUDENT ACCESSING THEIR OWN CERTIFICATE
    $student_id = $_SESSION['user_id'];
}

$selected_year = $_GET['year'];
$selected_class = $_GET['class_id'];

// 2. Fetch Student and Class Details
$info_sql = "SELECT u.full_name, u.username, c.grade_level, c.section 
             FROM users u 
             JOIN classes c ON c.class_id = $selected_class
             WHERE u.user_id = $student_id";
$info_res = $conn->query($info_sql);
if($info_res->num_rows == 0) die("Student record not found.");
$info = $info_res->fetch_assoc();

// 3. Fetch Annual Rank and Average
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
    <title>Academic Certificate - <?php echo $info['full_name']; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f0f0; padding: 50px; font-family: 'Georgia', serif; }
        .certificate-container {
            width: 1000px; height: 700px; padding: 50px; text-align: center;
            border: 20px solid #1e3a8a; background-color: white;
            position: relative; margin: auto; box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .certificate-container::after {
            content: ""; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 2px solid #fbbf24; pointer-events: none;
        }
        .school-name { font-size: 38px; color: #1e3a8a; font-weight: bold; text-transform: uppercase; }
        .cert-title { font-size: 50px; color: #b45309; margin: 20px 0; }
        .statement { font-size: 22px; margin: 20px 0; color: #374151; }
        .student-name { font-size: 45px; font-weight: bold; text-decoration: underline; color: #000; margin: 10px 0; }
        .details { font-size: 20px; margin-top: 30px; line-height: 1.6; }
        .rank-box { display: inline-block; margin-top: 20px; padding: 15px 40px; border: 2px solid #1e3a8a; background: #f8fafc; }
        .footer-signatures { margin-top: 80px; display: flex; justify-content: space-between; padding: 0 50px; }
        .sig-line { border-top: 2px solid #000; width: 200px; padding-top: 5px; font-weight: bold; text-transform: capitalize; }
        @media print {
            body { background: none; padding: 0; }
            .certificate-container { box-shadow: none; border-width: 15px; margin: 0; width: 100%; height: 95vh; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-lg btn-success shadow">
            <i class="fas fa-print me-2"></i> Print Official Certificate
        </button>
        <?php if($_SESSION['role'] == 'student'): ?>
            <a href="view_mark.php" class="btn btn-lg btn-secondary shadow ms-2">Back to Portal</a>
        <?php else: ?>
            <button onclick="window.close()" class="btn btn-lg btn-secondary shadow ms-2">Close Tab</button>
        <?php endif; ?>
    </div>

    <div class="certificate-container">
        <div class="school-name">Tsinseta Lemariam Secondary School</div>
        <div class="cert-title">Certificate of Achievement</div>
        <p class="statement">This is to certify that</p>
        <div class="student-name"><?php echo $info['full_name']; ?></div>
        <p class="statement">Has successfully completed the academic requirements for</p>
        <div class="details">
            <strong>Grade <?php echo $info['grade_level'].$info['section']; ?></strong> | 
            Academic Year: <strong><?php echo $selected_year; ?></strong>
        </div>
        <div class="rank-box shadow-sm">
            <div class="row">
                <div class="col-6 border-end">
                    <small class="text-muted d-block">FinaL Rank</small>
                    <span class="fs-3 fw-bold text-primary"><?php echo $my_rank; ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Yearly Average</small>
                    <span class="fs-3 fw-bold text-success"><?php echo number_format($my_avg, 1); ?>%</span>
                </div>
            </div>
        </div>
        <div class="footer-signatures">
            <div><div class="sig-line">Director</div></div>
            <div><div style="font-size: 50px; color: rgba(30, 58, 138, 0.1); font-weight: bold; transform: rotate(-15deg);">OFFICIAL SEAL</div></div>
            <div><div class="sig-line">Class Teacher</div></div>
        </div>
        <div style="position: absolute; bottom: 20px; left: 0; right: 0;">
            <small class="text-muted">Generated by Triloop System on <?php echo date("d-M-Y"); ?> | Student ID: <?php echo $info['username']; ?></small>
        </div>
    </div>

</html>