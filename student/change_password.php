<?php
session_start();
require '../includes/db_connect.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_pass = $_POST['current_pass'];
    $new_pass     = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    // 1. Fetch current hashed password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // 2. Validation
    if (!password_verify($current_pass, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // 3. Success: Hash and Update
        $hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $hashed_pass, $user_id);
        
        if ($update_stmt->execute()) {
            $success = "Password updated successfully!";
        } else {
            $error = "Database error. Please try again.";
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-primary"><i class="fas fa-key me-2"></i> Security Settings</h5>
                </div>
                <div class="card-body p-4">
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger small py-2"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if($success): ?>
                        <div class="alert alert-success small py-2"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Current Password</label>
                            <input type="password" name="current_pass" class="form-control" required 
                                   placeholder="Enter old password">
                        </div>

                        <hr>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">New Password</label>
                            <input type="password" name="new_pass" class="form-control" required 
                                   placeholder="Minimum 6 characters" autocomplete="new-password">
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_pass" class="form-control" required 
                                   placeholder="Re-type new password">
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dashboard.php" class="text-muted small text-decoration-none">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded border">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i> 
                    <strong>Tip:</strong> Use a combination of letters and numbers to make your account more secure.
                </small>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>