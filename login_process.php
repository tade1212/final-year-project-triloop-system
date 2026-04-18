<?php
session_start();
require 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // KEPT ORIGINAL NAMES AS REQUESTED
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT user_id, password, full_name, role, reset_required FROM users WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id, $hashed_password, $fullname, $role, $reset_required);
        
        if ($stmt->fetch()) {
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $fullname;
                $_SESSION['role'] = $role;

                // CHECK FOR FORCED PASSWORD CHANGE
                if ($reset_required == 1) {
                    $_SESSION['force_change'] = true;
                    header("Location: change_password_required.php");
                    exit();
                }

                switch ($role) {
                    case 'admin': header("Location: admin/dashboard.php"); break;
                    case 'teacher': header("Location: teacher/dashboard.php"); break;
                    case 'student': header("Location: student/dashboard.php"); break;
                }
                exit();
            }
        }
        header("Location: index.php?error=1");
    }
}