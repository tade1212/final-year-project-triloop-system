<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triloop Login</title>
    
    <!-- 1. Bootstrap CSS (Local) -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- 2. Our Custom CSS (Local) -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="login-card">
    <h2 class="brand-title">TRILOOP</h2>
    <p class="brand-subtitle">Integrated Academic System</p>
    
    <!-- Login Form -->
    <form action="login_process.php" method="POST" autocomplete="off">
        
        <?php 
        if(isset($_GET['error'])) {
            echo '<div class="alert alert-danger text-center p-2" style="font-size:0.9rem;">Invalid Username or Password</div>';
        }
        ?>

        <div class="mb-3">
            <label for="username" class="form-label" autocomplete="off">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>
        
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 btn-login">Login</button>
    </form>
    
    <div class="text-center mt-4">
        <small class="text-muted">Tsinseta Lemariam School &copy; 2026</small>
    </div>
</div>

<!-- Bootstrap Bundle (for future popups/dropdowns) -->
<script src="assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>