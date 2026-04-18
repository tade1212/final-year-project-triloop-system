<?php
session_start();
if (!isset($_SESSION['force_change'])) { header("Location: index.php"); exit(); }
require 'includes/db_connect.php';
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $p1 = $_POST['p1']; $p2 = $_POST['p2'];
    if (strlen($p1) < 6) { $error = "Minimum 6 characters."; }
    elseif ($p1 !== $p2) { $error = "Passwords do not match."; }
    else {
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash', reset_required=0 WHERE user_id={$_SESSION['user_id']}");
        unset($_SESSION['force_change']);
        header("Location: index.php?msg=secured"); exit();
    }
}
?>
<!DOCTYPE html><html><head><title>Secure Account</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-4">
    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white text-center"><h5>Secure Your Account</h5></div>
        <div class="card-body">
            <p class="small text-muted">A system PIN was used. Please choose your own private password to continue.</p>
            <?php if($error) echo "<div class='alert alert-danger py-1 small'>$error</div>"; ?>
            <form method="POST">
                <input type="password" name="p1" class="form-control mb-2" placeholder="New Password" required>
                <input type="password" name="p2" class="form-control mb-3" placeholder="Confirm Password" required>
                <button type="submit" class="btn btn-primary w-100">Update and Login</button>
            </form>
        </div>
    </div>
</div></div></div></body></html>