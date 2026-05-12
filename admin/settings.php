<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $status = $_POST['reg_status'];
    $year = $_POST['acad_year'];
    $sem = $_POST['semester'];
    $eval_status = $_POST['eval_status'];
    
    // NEW: Weights
    $w_std = $_POST['w_student'];
    $w_tea = $_POST['w_teacher'];
    $w_adm = $_POST['w_admin'];

    $conn->query("UPDATE system_settings SET setting_value = '$status' WHERE setting_key = 'registration_status'");
    $conn->query("UPDATE system_settings SET setting_value = '$year' WHERE setting_key = 'current_academic_year'");
    $conn->query("UPDATE system_settings SET setting_value = '$sem' WHERE setting_key = 'current_semester'");
    $conn->query("UPDATE system_settings SET setting_value = '$eval_status' WHERE setting_key = 'evaluation_status'");
    
    // Update Weights
    $conn->query("UPDATE system_settings SET setting_value = '$w_std' WHERE setting_key = 'weight_student'");
    $conn->query("UPDATE system_settings SET setting_value = '$w_tea' WHERE setting_key = 'weight_teacher'");
    $conn->query("UPDATE system_settings SET setting_value = '$w_adm' WHERE setting_key = 'weight_admin'");
    
    $success = "All system settings and evaluation weights updated!";
}

// Fetch Current Settings
$settings = [];
$res = $conn->query("SELECT * FROM system_settings");
while($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0"><i class="fas fa-cogs me-2"></i> Global System Control</h5>
        </div>
        <div class="card-body">
            <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold">Registration Window</label>
                        <select name="reg_status" class="form-select">
                            <option value="open" <?php if($settings['registration_status'] == 'open') echo 'selected'; ?>>OPEN</option>
                            <option value="closed" <?php if($settings['registration_status'] == 'closed') echo 'selected'; ?>>CLOSED</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold">Evaluation Window</label>
                        <select name="eval_status" class="form-select">
                            <option value="open" <?php if($settings['evaluation_status'] == 'open') echo 'selected'; ?>>OPEN</option>
                            <option value="closed" <?php if($settings['evaluation_status'] == 'closed') echo 'selected'; ?>>CLOSED</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold">Academic Year</label>
                        <input type="text" name="acad_year" class="form-control" value="<?php echo $settings['current_academic_year']; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-bold">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="1" <?php if($settings['current_semester'] == '1') echo 'selected'; ?>>Semester 1</option>
                            <option value="2" <?php if($settings['current_semester'] == '2') echo 'selected'; ?>>Semester 2</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold text-secondary"><i class="fas fa-balance-scale me-2"></i> 360° Evaluation Weights (%)</h6>
                <div class="row bg-light p-3 rounded">
                    <div class="col-md-4">
                        <label class="small fw-bold">Student Contribution</label>
                        <div class="input-group">
                            <input type="number" name="w_student" class="form-control" value="<?php echo $settings['weight_student']; ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Peer (Teacher) Contribution</label>
                        <div class="input-group">
                            <input type="number" name="w_teacher" class="form-control" value="<?php echo $settings['weight_teacher']; ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Admin Contribution</label>
                        <div class="input-group">
                            <input type="number" name="w_admin" class="form-control" value="<?php echo $settings['weight_admin']; ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <!-- <div class="col-12 mt-2">
                        <small class="text-danger">* Ensure the sum of all weights equals 100.</small>
                    </div> -->
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" name="update_settings" class="btn btn-primary px-5 shadow-sm">Save All Configurations</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>