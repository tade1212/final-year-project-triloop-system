<?php 
require 'includes/db_connect.php'; 
$request_msg = ""; $show_token = "";

// 1. Handle Reset Request Logic
if(isset($_POST['request_reset'])) {
    $uname = $conn->real_escape_string($_POST['reset_username']);
    $check = $conn->query("SELECT user_id FROM users WHERE username = '$uname'");
    if($check->num_rows > 0) {
        $token = (string)rand(1111, 9999);
        $conn->query("UPDATE users SET reset_status='REQUESTED', request_token='$token' WHERE username='$uname'");
        $show_token = $token;
    } else { 
        $request_msg = "Username not found."; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triloop | Secure Portal</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --midnight: #08090a;
            --card-bg: #111214;
            --accent-cobalt: #0052cc;
            --bright-sky: #4dabff;
            --error-red: #3d1212;
            --error-border: #ff4d4d;
        }

        body {
            background-color: var(--midnight);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, sans-serif;
            color: #ffffff;
            overflow: hidden;
            position: relative;
        }

        /* --- New Rotating Text Styles --- */
        .marquee-container {
            position: absolute;
            top: 20px;
            left: 0;
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
            z-index: 100;
        }

        .marquee-text {
            display: inline-block;
            padding-left: 100%;
            animation: scroll-right 45s linear infinite;
            font-size: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #4dabff, #0052cc, #ffffff, #4dabff);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(77, 171, 255, 0.4);
        }

        @keyframes scroll-right {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        /* -------------------------------- */

        .orb {
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.15;
        }
        .orb-1 { top: -10%; left: -5%; background: var(--accent-cobalt); }
        .orb-2 { bottom: -10%; right: -5%; background: var(--bright-sky); }

        .portal-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            background: var(--card-bg);
            border: 2px solid white;
            border-radius: 32px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
        }

        .brand-title {
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 0;
            background: linear-gradient(to bottom, #fff, #a5a5a5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: var(--bright-sky);
            margin-bottom: 40px;
            opacity: 0.8;
            font-weight: 600;
        }

        .auth-error-box {
            background: var(--error-red);
            border: 1px solid var(--error-border);
            color: #ffcccc;
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .input-wrapper {
            position: relative;
            margin-bottom: 18px;
        }

        .input-wrapper i.field-icon {
            position: absolute;
            left: 18px; top: 50%;
            transform: translateY(-50%);
            color: #555;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .input-wrapper input {
            width: 100%;
            background: #000;
            border: 2px solid gray;
            border-radius: 14px;
            padding: 15px 45px 15px 50px;
            color: #fff;
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--bright-sky);
            box-shadow: 0 0 0 4px rgba(77, 171, 255, 0.1);
        }

        .eye-btn-bright {
            position: absolute;
            right: 18px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #fff !important;
            font-size: 1rem;
            z-index: 10;
            opacity: 0.9;
        }
        .eye-btn-bright:hover { opacity: 1; color: var(--bright-sky) !important; }

        .btn-primary-action {
            width: 100%;
            background: var(--accent-cobalt);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-primary-action:hover {
            background: #0043a4;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 82, 204, 0.3);
        }

        .reset-link {
            display: inline-block;
            margin-top: 25px;
            color: #666;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .reset-link:hover { color: #fff; }

        .token-matrix {
            background: #000;
            border: 2px dashed gray;
            color: var(--bright-sky);
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: 8px;
            padding: 20px;
            border-radius: 16px;
            margin: 20px 0;
        }
    </style>
</head>
<body>

    <!-- Rotating Text Header -->
    <div class="marquee-container">
        <div class="marquee-text">
            Tsinseta lemariam comprehensive secondary school  Academic Portal
        </div>
    </div>

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="portal-card">
    
        <h1 class="brand-title">TRILOOP</h1>
        <div class="brand-subtitle">TsinsetaLemariam Academy Portal </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="auth-error-box" id="errorDiv">
                <i class="fas fa-shield-halved"></i> 
                Invalid ID or Password
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success bg-success bg-opacity-20 border-success text-white small py-2 rounded-3 mb-4">
                <i class="fas fa-check-circle me-1"></i> Account Secured
            </div>
        <?php endif; ?>

        <form action="login_process.php" method="POST" autocomplete="off">
            <div class="input-wrapper">
                <i class="fas fa-id-badge field-icon"></i>
                <input type="text" name="username" placeholder="Username / ID" required readonly 
                       onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly', true);">
            </div>

            <div class="input-wrapper">
                <i class="fas fa-lock field-icon"></i>
                <input type="password" name="password" id="passInput" placeholder="Password" required readonly 
                       onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly', true);">
                <i class="fas fa-eye eye-btn-bright" id="eyeIcon" onclick="togglePassword()"></i>
            </div>

            <button type="submit" class="btn-primary-action shadow-lg"> Login</button>
        </form>

        <a href="#" class="reset-link" data-bs-toggle="modal" data-bs-target="#forgotModal">
            reset password?
        </a>
    </div>

    <!-- Handshake Reset Modal -->
    <div class="modal fade" id="forgotModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #000; border-radius: 24px; border: 1px solid #333;">
                <div class="modal-body p-5 text-center">
                    <?php if($show_token): ?>
                        <div class="text-success mb-3"><i class="fas fa-circle-check fa-4x"></i></div>
                        <h4 class="fw-bold">Auth Code</h4>
                        <p class="text-muted small">Provide this code to the Admin to authorize a reset:</p>
                        <div class="token-matrix"><?php echo $show_token; ?></div>
                        <button class="btn btn-outline-light w-100 py-3" style="border-radius: 12px;" data-bs-dismiss="modal">Close</button>
                    <?php else: ?>
                        <h4 class="fw-bold mb-4">Reset Request</h4>
                        <?php if($request_msg) echo "<div class='text-danger mb-3 small'>$request_msg</div>"; ?>
                        <form method="POST">
                            <div class="input-wrapper text-start">
                                <i class="fas fa-user-shield field-icon"></i>
                                <input type="text" name="reset_username" placeholder="Your Registered ID" required>
                            </div>
                            <button type="submit" name="request_reset" class="btn-primary-action">Request Permission</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const pass = document.getElementById("passInput");
            const icon = document.getElementById("eyeIcon");
            if (pass.type === "password") {
                pass.type = "text";
                icon.className = "fas fa-eye-slash eye-btn-bright";
            } else {
                pass.type = "password";
                icon.className = "fas fa-eye eye-btn-bright";
            }
        }

        window.onload = function() {
            if (window.location.search.includes('error')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            <?php if(!empty($show_token) || !empty($request_msg)): ?>
                var myModal = new bootstrap.Modal(document.getElementById('forgotModal'));
                myModal.show();
            <?php endif; ?>
        };
    </script>
</body>
</html>