<?php
session_start();
require '../includes/db_connect.php';

// 1. Security: Only Admin can delete
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. Check if ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 3. Prevent Admin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        header("Location: manage_users.php?error=self_delete");
        exit();
    }

    // 4. Execution
    // Because we used "ON DELETE CASCADE" in our database setup, 
    // deleting the user will automatically remove their rows in 
    // the 'students', 'grades', and 'allocations' tables.
    $sql = "DELETE FROM users WHERE user_id = $id";

    if ($conn->query($sql)) {
        header("Location: manage_users.php?msg=deleted");
    } else {
        header("Location: manage_users.php?error=db_error");
    }
} else {
    header("Location: manage_users.php");
}
?>