<?php 
require 'includes/db_connect.php'; 
$request_msg = ""; $show_token = "";

if(isset($_POST['request_reset'])) {
    $uname = $conn->real_escape_string($_POST['reset_username']);
    $check = $conn->query("SELECT user_id FROM users WHERE username = '$uname'");
    if($check->num_rows > 0) {
        $token = (string)rand(1111, 9999);
        $conn->query("UPDATE users SET reset_status='REQUESTED', request_token='$token' WHERE username='$uname'");
        $show_token = $token;
    } else { $request_msg = "Username not found."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Triloop Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body{ background-image:url('assets/images/bground.jpg'); background-size: cover; background-position: center; border: 15px; min-height: 100vh; }
        .input-group-text { cursor: pointer; background-color: #fff; }
    </style>
</head>
<body>

<div class="login-card">
    <h2 class="brand-title">TRILOOP</h2>
    <p class="brand-subtitle">Integrated Academic System</p>
    
    <form action="login_process.php" method="POST" autocomplete="off">
        <?php if(isset($_GET['error'])) echo '<div class="alert alert-danger text-center p-2 small">Invalid Username or Password</div>'; ?>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required>
                <span class="input-group-text" onclick="togglePassword('password', 'eyeIcon')">
                    <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                </span>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 btn-login">Login</button>
    </form>
    
    <div class="text-center mt-3 border-top pt-2">
        <button class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot Password?</button>
    </div>
</div>

<!-- Auto-showing Token Modal -->
<div class="modal fade" id="forgotModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content text-center"><div class="modal-body p-4">
        <?php if($show_token): ?>
            <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
            <h6>Permission Code</h6>
            <div class="display-6 fw-bold text-primary mb-3"><?php echo $show_token; ?></div>
            <p class="small text-muted">Provide this code to the Admin to reset your password.</p>
            <button class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">Close</button>
        <?php else: ?>
            <h6>Request Reset</h6>
            <form method="POST">
                <input type="text" name="reset_username" class="form-control mb-2" placeholder="Your ID" required>
                <button type="submit" name="request_reset" class="btn btn-primary btn-sm w-100">Get Code</button>
            </form>
        <?php endif; ?>
    </div></div></div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === "password") { input.type = "text"; icon.classList.replace("fa-eye", "fa-eye-slash"); } 
    else { input.type = "password"; icon.classList.replace("fa-eye-slash", "fa-eye"); }
}
window.onload = function() { <?php if($show_token) echo "new bootstrap.Modal(document.getElementById('forgotModal')).show();"; ?> };
</script>
</body>
</html>