<?php

session_start();

require_once "database/connection.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $owner_id = $_SESSION["user_id"];

    $gym_name = trim($_POST["gym_name"]);
    $description = trim($_POST["description"]);
    $address = trim($_POST["address"]);
    $city = trim($_POST["city"]);
    $state = trim($_POST["state"]);
    $country = trim($_POST["country"]);
    $pincode = trim($_POST["pincode"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $opening_time = $_POST["opening_time"];
    $closing_time = $_POST["closing_time"];

    // Default image value
    $image_name = NULL;

    // Upload Image
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {

        $uploadDir = "uploads/gyms/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);

        $targetFile = $uploadDir . $image_name;

        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile);
    }

    // Insert Gym
    $stmt = $conn->prepare("INSERT INTO gyms
    (owner_id, gym_name, description, address, city, state, country,
    pincode, phone, email, opening_time, closing_time, profile_image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "issssssssssss",
        $owner_id,
        $gym_name,
        $description,
        $address,
        $city,
        $state,
        $country,
        $pincode,
        $phone,
        $email,
        $opening_time,
        $closing_time,
        $image_name
    );

    if ($stmt->execute()) {

        echo "<script>
                alert('Gym Registered Successfully! Waiting for Admin Approval.');
                window.location='owner/dashboard.php';
              </script>";

    } else {

        echo "<script>
                alert('Registration Failed!');
                window.history.back();
              </script>";

    }

    $stmt->close();
    $conn->close();

}

?>