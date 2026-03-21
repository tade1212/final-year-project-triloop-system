<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$student_id = $_SESSION['user_id'];

// 1. Fetch Global System Settings
$settings = [];
$res = $conn->query("SELECT * FROM system_settings");
while($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$reg_status = $settings['registration_status'];
$curr_year  = $settings['current_academic_year'];
$curr_sem   = $settings['current_semester'];

// 2. FETCH THE STUDENT'S ASSIGNED CLASS (Refined Logic)
// We get the class the Admin assigned them to in the master 'students' table
$assigned_sql = "SELECT c.class_id, c.grade_level, c.section 
                 FROM students s 
                 JOIN classes c ON s.class_id = c.class_id 
                 WHERE s.student_id = $student_id";
$assigned_res = $conn->query($assigned_sql);
$assigned_data = $assigned_res->fetch_assoc();

// 3. Check if student is ALREADY registered for this specific Semester/Year
$check_sql = "SELECT * FROM semester_registrations 
              WHERE student_id = $student_id 
              AND semester = '$curr_sem' 
              AND academic_year = '$curr_year'";
$check_res = $conn->query($check_sql);
$is_already_registered = ($check_res->num_rows > 0);

// 4. Handle Registration Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_reg'])) {
    $class_to_register = $assigned_data['class_id']; // Take it directly from the assigned data

    // Insert into historical archive
    $ins_query = "INSERT INTO semester_registrations (student_id, class_id, semester, academic_year) 
                  VALUES ($student_id, $class_to_register, '$curr_sem', '$curr_year')";

    if ($conn->query($ins_query)) {
        echo "<script>alert('Registration Successful!'); window.location='dashboard.php';</script>";
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header p-4" style="background-color: #2c3e50; border-bottom: 4px solid #16a085;">
                    <h4 class="m-0 text-white fw-bold"><i class="fas fa-id-card me-2"></i> Semester Enrollment</h4>
                </div>
                <div class="card-body p-4 bg-white">
                    
                    <?php if ($reg_status == 'closed'): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-4x text-muted mb-3"></i>
                            <h4 class="text-secondary">Enrollment Period Closed</h4>
                            <p class="text-muted">The self-registration window is currently locked by the administrator.</p>
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4">Return to Dashboard</a>
                        </div>

                    <?php elseif ($is_already_registered): ?>
                        <div class="text-center py-4">
                            <div class="mb-3 text-success"><i class="fas fa-check-circle fa-4x"></i></div>
                            <h4 class="fw-bold">Already Enrolled</h4>
                            <p class="text-muted">You have successfully registered for:<br>
                            <span class="badge bg-light text-dark border p-2 mt-2">
                                 <?php echo $assigned_data['grade_level'].'-'.$assigned_data['section']; ?> Semester (<?php echo $curr_sem; ?>) 
                            </span></p>
                            <a href="dashboard.php" class="btn btn-primary px-5 shadow-sm mt-3">Go to Student Portal</a>
                        </div>

                    <?php else: ?>
                        <!-- THE REFINED FORM -->
                        <form method="POST">
                            <div class="mb-4">
                                <p class="text-muted mb-4">The system has identified your assigned grade based on your academic progress. Please confirm the details below to begin the semester.</p>
                                
                                <div class="p-3 mb-3 bg-light rounded border-start border-4 border-success">
                                    <label class="small text-uppercase fw-bold text-muted d-block mb-1">Target Semester</label>
                                    <span class="fs-5 fw-bold text-dark">Semester <?php echo $curr_sem; ?> (<?php echo $curr_year; ?>)</span>
                                </div>

                                <div class="p-3 bg-light rounded border-start border-4 border-primary">
                                    <label class="small text-uppercase fw-bold text-muted d-block mb-1">Assigned Class Level</label>
                                    <!-- DISPLAY AS PLAIN TEXT - NO DROPDOWN -->
                                    <span class="fs-4 fw-bold text-primary">Grade <?php echo $assigned_data['grade_level'].'-'.$assigned_data['section']; ?></span>
                                </div>
                            </div>

                            <div class="alert alert-info small py-2">
                                <i class="fas fa-info-circle me-1"></i> If your grade level is incorrect, please contact the Registrar Office immediately.
                            </div>

                            <!-- Hidden field to send the correct ID -->
                            <input type="hidden" name="class_id" value="<?php echo $assigned_data['class_id']; ?>">

                            <button type="submit" name="confirm_reg" class="btn btn-success btn-lg w-100 shadow-sm mt-3 py-3 fw-bold">
                                <i class="fas fa-file-signature me-2"></i> Confirm Enrollment
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>