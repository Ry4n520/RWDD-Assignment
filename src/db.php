<?php
$host = "localhost";     // XAMPP runs MySQL locally
$user = "root";          // default XAMPP user
$pass = "";              // default password is empty (unless you set one in phpMyAdmin)
$dbname = "linkmosaic"; // replace with your DB name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
