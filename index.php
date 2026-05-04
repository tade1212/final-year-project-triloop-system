<?php 
require 'includes/db_connect.php'; 
$request_msg = ""; $show_token = "";

// --- 1. KEEP PREVIOUS HANDSHAKE LOGIC ---
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triloop Login</title>
    
    <!-- Local Bootstrap & Custom CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        /* KEEP BACKGROUND IMAGE */
        body { 
            background-image:url('assets/images/bground.jpg'); 
            background-size: cover; 
            background-position: center; 
            border: 15px solid transparent; 
            min-height: 100vh; 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card { 
            max-width: 400px; 
            width: 100%;
            background: white; 
            padding: 35px; 
            border-radius: 15px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
        }

        /* REFINEMENT: Unified Focus Logic */
        .input-group-refined {
            display: flex;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        /* The magic blue ring logic */
        .input-group-refined:focus-within {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .input-group-refined .form-control {
            border: none !important;
            box-shadow: none !important;
        }

        .input-group-refined .input-group-text {
            border: none !important;
            background-color: transparent !important;
            cursor: pointer;
        }
        
        .brand-title { color: #0d6efd; font-weight: 800; letter-spacing: 2px; }
    </style>
</head>
<body>

<div class="login-card">
    <h2 class="text-center brand-title m-0">TRILOOP</h2>
    <p class="text-center text-muted small mb-4">Tsinseta Lemariam School</p>
    
    <form action="login_process.php" method="POST" autocomplete="off">
        
        <?php if(isset($_GET['error'])) echo '<div class="alert alert-danger text-center p-2 small">Invalid Username or Password</div>'; ?>
        <?php if(isset($_GET['msg'])) echo '<div class="alert alert-success text-center p-2 small">Account Secured. Please Login.</div>'; ?>

        <div class="mb-3">
            <label class="form-label small fw-bold">Username</label>
            <input type="text" class="form-control" name="username" required autofocus>
        </div>
        
        <div class="mb-3">
            <label class="form-label small fw-bold">Password</label>
            <!-- REFINED UNIFIED INPUT GROUP -->
            <div class="input-group-refined">
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       required>
                <span class="input-group-text" onclick="togglePassword()">
                    <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm mb-3">Login</button>
    </form>
    
    <!-- KEEP FORGOT PASSWORD FEATURE -->
    <div class="text-center border-top pt-3">
        <button class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotModal">
            Forgot Password? Request Reset
        </button>
    </div>
</div>

<!-- HANDSHAKE TOKEN MODAL -->
<div class="modal fade" id="forgotModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-center">
            <div class="modal-body p-4">
                <?php if($show_token): ?>
                    <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                    <h6 class="fw-bold">Auth Code Generated</h6>
                    <div class="display-6 fw-bold text-primary my-3"><?php echo $show_token; ?></div>
                    <p class="small text-muted">Provide this code to the Admin to authorize your reset.</p>
                    <button class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">Close</button>
                <?php else: ?>
                    <h6 class="fw-bold mb-3">Request Reset Permission</h6>
                    <?php if($request_msg) echo "<p class='text-danger small'>$request_msg</p>"; ?>
                    <form method="POST">
                        <input type="text" name="reset_username" class="form-control mb-3" placeholder="Enter Username/ID" required>
                        <button type="submit" name="request_reset" class="btn btn-primary btn-sm w-100">Get Code</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT: Logic and Auto-Modal -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
// 1. Toggle Password Functionality
function togglePassword() {
    const passInput = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    
    if (passInput.type === "password") {
        passInput.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        passInput.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// 2. Keep Modal open after refresh if token was generated
window.onload = function() {
    <?php if($show_token || $request_msg): ?>
        var myModal = new bootstrap.Modal(document.getElementById('forgotModal'));
        myModal.show();
    <?php endif; ?>
};
</script>

</body>
</html>