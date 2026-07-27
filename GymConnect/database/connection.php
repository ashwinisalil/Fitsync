<?php

// Database Configuration

$servername = "localhost";
$username = "root";
$password = "";
$database = "gymconnect";

// Create Connection

$conn = new mysqli($servername, $username, $password, $database);

// Check Connection

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set Character Encoding

$conn->set_charset("utf8");

?>