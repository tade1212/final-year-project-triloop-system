<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $status = $_POST['reg_status'];
    $year = $_POST['acad_year'];
    $sem = $_POST['semester'];

    $conn->query("UPDATE system_settings SET setting_value = '$status' WHERE setting_key = 'registration_status'");
    $conn->query("UPDATE system_settings SET setting_value = '$year' WHERE setting_key = 'current_academic_year'");
    $conn->query("UPDATE system_settings SET setting_value = '$sem' WHERE setting_key = 'current_semester'");
    
    $success = "System settings updated successfully!";
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
                    <!-- Registration Toggle -->
                    <div class="col-md-4 mb-3">
                        <label class="fw-bold">Student Self-Registration</label>
                        <select name="reg_status" class="form-select border-primary">
                            <option value="open" <?php if($settings['registration_status'] == 'open') echo 'selected'; ?>>OPEN (Allow Registration)</option>
                            <option value="closed" <?php if($settings['registration_status'] == 'closed') echo 'selected'; ?>>CLOSED (Block Registration)</option>
                        </select>
                        <small class="text-muted">When closed, students cannot register for the new semester.</small>
                    </div>
                    

                    <!-- Current Year -->
                    <div class="col-md-4 mb-3">
                        <label class="fw-bold">Current Academic Year</label>
                        <input type="text" name="acad_year" class="form-control" value="<?php echo $settings['current_academic_year']; ?>" placeholder="e.g. 2025/2026">
                    </div>

                    <!-- Current Semester -->
                    <div class="col-md-4 mb-3">
                        <label class="fw-bold">Active Semester</label>
                        <select name="semester" class="form-select">
                            <option value="1" <?php if($settings['current_semester'] == '1') echo 'selected'; ?>>Semester 1</option>
                            <option value="2" <?php if($settings['current_semester'] == '2') echo 'selected'; ?>>Semester 2</option>
                        </select>
                    </div>
                </div>
                <!-- Add this column inside the row in your admin/settings.php -->
<div class="col-md-4 mb-3">
    <label class="fw-bold">Evaluation Window</label>
    <select name="eval_status" class="form-select border-warning">
        <option value="open" <?php if($settings['evaluation_status'] == 'open') echo 'selected'; ?>>OPEN (Allow Feedback)</option>
        <option value="closed" <?php if($settings['evaluation_status'] == 'closed') echo 'selected'; ?>>CLOSED (Locked)</option>
    </select>
    <small class="text-muted">Students can only evaluate teachers when this is OPEN.</small>
</div>

<!-- Remember to update the PHP logic at the top of settings.php to save 'eval_status' -->
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    // ... your other updates ...
    $eval_status = $_POST['eval_status'];
    $conn->query("UPDATE system_settings SET setting_value = '$eval_status' WHERE setting_key = 'evaluation_status'");
    // ...
}
?>

                <div class="mt-3 text-end">
                    <button type="submit" name="update_settings" class="btn btn-primary px-5">Save Global Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>