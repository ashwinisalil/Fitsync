<?php

session_start();

require_once "database/connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Check empty fields
    if (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirm_password) ||
        empty($role)
    ) {

        die("Please fill all fields.");

    }

    // Password Match
    if ($password != $confirm_password) {

        die("Passwords do not match.");

    }

    // Check Email

    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email=?");

    $checkEmail->bind_param("s", $email);

    $checkEmail->execute();

    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {

        die("Email already exists.");

    }

    // Check Phone

    $checkPhone = $conn->prepare("SELECT user_id FROM users WHERE phone=?");

    $checkPhone->bind_param("s", $phone);

    $checkPhone->execute();

    $result = $checkPhone->get_result();

    if ($result->num_rows > 0) {

        die("Phone number already exists.");

    }

    // Encrypt Password

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert User

    $stmt = $conn->prepare("INSERT INTO users(full_name,email,phone,password,role)
    VALUES(?,?,?,?,?)");

    $stmt->bind_param(
        "sssss",
        $full_name,
        $email,
        $phone,
        $hashedPassword,
        $role
    );

    if ($stmt->execute()) {

        header("Location: login.php");

        exit();

    } else {

        echo "Registration Failed.";

    }

    $stmt->close();

    $conn->close();

}

?>