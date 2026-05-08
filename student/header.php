<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Security: Check if user is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Triloop</title>
    <!-- Same CSS links as Teacher Portal -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* Sidebar Icon Refinement */
#sidebar-wrapper .list-group-item i {
    width: 25px;
    text-align: center;
    margin-right: 10px;
    font-size: 1.1rem;
    filter: drop-shadow(0px 1px 1px rgba(0,0,0,0.1)); /* Improves sharpness */
}

/* Specific Colors to remove blur and add vibrancy */
.fa-tachometer-alt { color: #3498db; } /* Blue */
.fa-users { color: #e67e22; }          /* Orange */
.fa-chalkboard { color: #1abc9c; }     /* Teal */
.fa-book { color: #9b59b6; }           /* Purple */
.fa-calendar-alt { color: #e74c3c; }   /* Red */
.fa-star { color: #f1c40f; }           /* Yellow */
.fa-cog { color: #95a5a6; }            /* Grey */
.fa-user-graduate { color: #2ecc71; }  /* Green */
.fa-school { color: #3498db; } /* Dark Blue for Logo */
.fa-chart-line { color: #2ecc71; } /* Green for Marks & Rank */
.fa-user-check { color: #1abc9c; } /* Teal for Evaluate Teachers */
.fa-key { color: #e67e22; } /* Orange for Change Password */
.sidebar-heading { color: #3498db; font-weight: 800; font-size: 1.25rem; }
</style>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar Wrapper -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">STUDENT</div>
        <div class="list-group list-group-flush my-3">
            <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent text-white">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a href="view_schedule.php" class="list-group-item list-group-item-action bg-transparent text-white">
                <i class="fas fa-calendar-alt me-2"></i> My Schedule
            </a>
            <a href="view_mark.php" class="list-group-item list-group-item-action bg-transparent text-white">
                <i class="fas fa-chart-line me-2"></i> Marks & Rank
                <a href="register_semester.php" class="list-group-item list-group-item-action bg-transparent text-white">
                <i class="fas fa-chart-line me-2"></i> register
            </a>
            <a href="evaluate_teacher.php" class="list-group-item list-group-item-action bg-transparent text-white">
                <i class="fas fa-user-check me-2"></i> Evaluate Teachers
            </a>
            <a href="change_password.php" class="list-group-item list-group-item-action bg-transparent text-white">
                <i class="fas fa-key me-2"></i> Change Password </a>
            
        </div>
    </div>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 sticky-top">
            <button class="btn btn-outline-primary d-md-none me-3" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="m-0 text-primary">Student Portal</h4>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <small class="text-muted d-block"><b>Welcome:</b></small>
                    <span class="fw-bold"><?php echo $_SESSION['full_name']; ?></span>
                </div>
                <a href="../logout.php" class="btn btn-danger btn-sm shadow-sm">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="container-fluid py-4">