<?php
$servername = "localhost";
$username   = "root";      // Default XAMPP username
$password   = "";  
$dbname     = "triloop_db"; // The database we just created
$port    = 3307;        // Default XAMPP port is 3306

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
?>