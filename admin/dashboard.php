<?php
session_start();
require '../includes/db_connect.php'; // Get Database Connection
include 'header.php'; // Load Sidebar

// -- LOGIC: Count Data for the Dashboard --
$count_students = $conn->query("SELECT count(*) FROM users WHERE role='student'")->fetch_row()[0];
$count_teachers = $conn->query("SELECT count(*) FROM users WHERE role='teacher'")->fetch_row()[0];
$count_classes  = $conn->query("SELECT count(*) FROM classes")->fetch_row()[0];
$count_subjects = $conn->query("SELECT count(*) FROM subjects")->fetch_row()[0];
?>

<div class="container-fluid">
    <!-- <h2 class="mb-4">Admin Dashboard</h2> -->
    
    <div class="row g-4">
        <!-- Card 1: Students -->
        <div class="col-md-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <h1 class="display-4 fw-bold"><?php echo $count_students; ?></h1>
                </div>
            </div>
        </div>

        <!-- Card 2: Teachers -->
        <div class="col-md-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Teachers</h5>
                    <h1 class="display-4 fw-bold"><?php echo $count_teachers; ?></h1>
                </div>
            </div>
        </div>

        <!-- Card 3: Classes -->
        <div class="col-md-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h5 class="card-title">Active Classes</h5>
                    <h1 class="display-4 fw-bold"><?php echo $count_classes; ?></h1>
                </div>
            </div>
        </div>

        <!-- Card 4: Subjects -->
        <div class="col-md-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <h5 class="card-title">Subjects</h5>
                    <h1 class="display-4 fw-bold"><?php echo $count_subjects; ?></h1>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>