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

// 2. Check if student is ALREADY registered for this specific Semester/Year
$check_sql = "SELECT * FROM semester_registrations 
              WHERE student_id = $student_id 
              AND semester = '$curr_sem' 
              AND academic_year = '$curr_year'";
$check_res = $conn->query($check_sql);
$is_already_registered = ($check_res->num_rows > 0);

// 3. Handle Registration Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_reg'])) {
    $selected_class = $_POST['class_id'];

    // Insert into history table
    $ins_query = "INSERT INTO semester_registrations (student_id, class_id, semester, academic_year) 
                  VALUES ($student_id, $selected_class, '$curr_sem', '$curr_year')";
    
    // Update current class in the main students table
    $upd_query = "UPDATE students SET class_id = $selected_class WHERE student_id = $student_id";

    if ($conn->query($ins_query) && $conn->query($upd_query)) {
        echo "<script>alert('Registration Successful for Semester $curr_sem!'); window.location='dashboard.php';</script>";
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h5 class="m-0 text-uppercase">Semester Registration</h5>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($reg_status == 'closed'): ?>
                        <!-- Case 1: Registration is CLOSED by Admin -->
                        <div class="text-center py-4">
                            <i class="fas fa-lock fa-4x text-muted mb-3"></i>
                            <h4 class="text-secondary">Registration is Closed</h4>
                            <p class="text-muted">The administrator has not opened the registration period yet. Please check back later.</p>
                            <a href="dashboard.php" class="btn btn-secondary px-4">Back to Dashboard</a>
                        </div>

                    <?php elseif ($is_already_registered): ?>
                        <!-- Case 2: Student is ALREADY registered -->
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-success">You are Registered!</h4>
                            <p class="text-muted">You have already completed registration for:<br>
                            <strong>Semester <?php echo $curr_sem; ?> (<?php echo $curr_year; ?>)</strong></p>
                            <a href="dashboard.php" class="btn btn-primary px-4">Go to Dashboard</a>
                        </div>

                    <?php else: ?>
                        <!-- Case 3: Registration is OPEN and Student needs to register -->
                        <form method="POST">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> 
                                You are registering for <strong>Semester <?php echo $curr_sem; ?></strong> of the <strong><?php echo $curr_year; ?></strong> Academic Year.
                            </div>

                            <div class="mb-4">
                                <label class="fw-bold mb-2">Confirm Your Class/Section</label>
                                <select name="class_id" class="form-select form-select-lg" required>
                                    <option value="">-- Select Your Class --</option>
                                    <?php
                                    $classes = $conn->query("SELECT * FROM classes ORDER BY grade_level, section");
                                    while($c = $classes->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $c['class_id']; ?>">
                                        Grade <?php echo $c['grade_level'].'-'.$c['section']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-danger">* Ensure you select the correct grade level assigned to you.</small>
                            </div>

                            <button type="submit" name="confirm_reg" class="btn btn-success btn-lg w-100 shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> Complete Registration
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>