<?php
// login_process.php

// 1. Start the Session (To remember the user across pages)
session_start();

// 2. Connect to the Database
require 'includes/db_connect.php';

// 3. Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get inputs and sanitize them (Security)
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 4. Prepare SQL Statement (Prevents SQL Injection)
    $sql = "SELECT user_id, password, full_name, role FROM users WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters (s = string)
        $stmt->bind_param("s", $username);
        
        // Execute the query
        $stmt->execute();
        
        // Store result
        $stmt->store_result();

        // 5. Check if user exists
        if ($stmt->num_rows == 1) {
            // Bind the results to variables
            $stmt->bind_result($id, $hashed_password, $fullname, $role);
            $stmt->fetch();

            // 6. Verify Password
            if (password_verify($password, $hashed_password)) {
                
                // --- SUCCESS! ---
                
                // Store data in Session variables
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $fullname;
                $_SESSION['role'] = $role;

                // 7. Redirect based on Role
                switch ($role) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'teacher':
                        header("Location: teacher/dashboard.php");
                        break;
                    case 'student':
                        header("Location: student/dashboard.php");
                        break;
                    default:
                        // Unknown role? Send back to login.
                        header("Location: index.php?error=1");
                }
                exit();

            } else {
                // Password wrong
                header("Location: index.php?error=1");
                exit();
            }
        } else {
            // Username not found
            header("Location: index.php?error=1");
            exit();
        }

        $stmt->close();
    }
}
$conn->close();
?>