<?php

session_start();

require_once "database/connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        die("Please fill all fields.");
    }

    // Search user by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user["password"])) {

            // Store session variables
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            // Redirect according to role
            if ($user["role"] == "admin") {

                header("Location: admin/dashboard.php");
                exit();

            } elseif ($user["role"] == "owner") {

                header("Location: owner/dashboard.php");
                exit();

            } elseif ($user["role"] == "member") {

                header("Location: member/dashboard.php");
                exit();

            } else {

                session_destroy();
                die("Invalid user role.");

            }

        } else {

            die("Incorrect password.");

        }

    } else {

        die("No account found with this email.");

    }

    $stmt->close();
}

$conn->close();

?>