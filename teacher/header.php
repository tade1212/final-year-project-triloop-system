<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet"> <!-- Reusing Admin CSS for layout -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">TEACHER</div>
        <div class="list-group list-group-flush my-3">
            <a href="dashboard.php" class="list-group-item"><i class="fas fa-home me-2"></i> My Classes</a>
            <a href="view_schedule.php" class="list-group-item"><i class="fas fa-calendar-alt me-2"></i> My Schedule</a>
            <a href="my_performance.php" class="list-group-item"><i class="fas fa-star me-2"></i> My Performance</a> 
            <a href="change_password.php" class="list-group-item"><i class="fas fa-key me-3"></i> Change Password</a>   
        <a href="peer_evaluation.php" class="list-group-item"><i class="fas fa-users-cog me-2"></i> Peer Evaluation</a>
        </div>
        <!-- Inside sidebar .list-group -->
<?php
// Check if this teacher is a Homeroom Teacher for ANY class
require_once '../includes/db_connect.php'; // Ensure connection exists
$tid = $_SESSION['user_id'];
$check_homeroom = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $tid");
if ($check_homeroom->num_rows > 0): 
?>
    <a href="class_reports.php" class="list-group-item list-group-item-action bg-transparent text-white">
        <i class="fas fa-chart-line me-2"></i> Class Reports (Rank)
    </a>
    
<?php endif; ?>
    </div>

    <!-- Content Wrapper -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3">
            <button class="btn btn-outline-primary d-md-none me-3" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <h4 class="m-0 text-primary">Teacher Portal</h4>
            <div class="ms-auto">
                <span class="me-3 fw-bold text-muted"><?php echo $_SESSION['full_name']; ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
                
            </div>
        </nav>
        <div class="container-fluid">