<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triloop Admin</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
</style>
</head>
<body>

<div id="wrapper">
    
    <!-- SIDEBAR -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="fas fa-school me-2"></i> TRILOOP
        </div>
        <div class="list-group list-group-flush my-3">
            <a href="dashboard.php" class="list-group-item"><i class="fas fa-tachometer-alt me-3"></i> Dashboard</a>
            <a href="manage_users.php" class="list-group-item"><i class="fas fa-users me-3"></i> Manage Users</a>
            <a href="manage_classes.php" class="list-group-item"><i class="fas fa-chalkboard me-3"></i> Manage Classes</a>
            <a href="manage_subjects.php" class="list-group-item"><i class="fas fa-book me-3"></i> Manage Subjects</a>
            <a href="allocate_subjects.php" class="list-group-item"><i class="fas fa-link me-3"></i> Assign Courses</a>
            <a href="create_schedule.php" class="list-group-item"><i class="fas fa-calendar-alt me-3"></i> Scheduling</a>
            <a href="manage_periods.php" class="list-group-item"><i class="fas fa-clock me-3"></i> Time Slots</a>
            <a href="view_evaluation.php" class="list-group-item"><i class="fas fa-star me-3"></i> Evaluations</a>
            <a href="settings.php" class="list-group-item"><i class="fas fa-cog me-3"></i> Settings</a>
            <a href="manage_questions.php" class="list-group-item"><i class="fas fa-question-circle me-3"></i> Eval. Questions</a>
            <a href="promote_students.php" class="list-group-item"><i class="fas fa-arrow-up me-3"></i> Promote Students</a>
            <a href="evaluate_staff.php" class="list-group-item"><i class="fas fa-user-check me-3"></i> Evaluate Staff</a>
            <a href="view_graduates.php" class="list-group-item"><i class="fas fa-user-graduate me-3"></i> Graduation Archive</a>
        </div>
    </div>

    <!-- CONTENT WRAPPER -->
    <div id="page-content-wrapper">
        
        <!-- HEADER BAR -->
        <nav class="navbar d-flex justify-content-between align-items-center">
            
            <div class="d-flex align-items-center">
                <!-- Mobile Toggle -->
                <button class="btn btn-primary d-md-none me-3" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <!-- Page Title -->
                <h4 class="m-0 fw-bold text-secondary">Admin Dashboard</h4>
            </div>

            <!-- User Profile & Logout -->
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block">
                    <small class="text-muted d-block">Welcome,</small>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
                <a href="../logout.php" class="btn btn-danger btn-sm px-3 rounded-pill">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Page Content Starts Here -->
        <div class="container-fluid">