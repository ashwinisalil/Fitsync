<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: login.php");
    exit();
}

require_once "database/connection.php";

$gym_id = $_POST["gym_id"];
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

// Get current image
$stmt = $conn->prepare("SELECT profile_image FROM gyms WHERE gym_id = ?");
$stmt->bind_param("i", $gym_id);
$stmt->execute();
$result = $stmt->get_result();
$gym = $result->fetch_assoc();

$profile_image = $gym["profile_image"];

// Upload new image if selected
if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {

    $filename = time() . "_" . basename($_FILES["profile_image"]["name"]);

    $target = "uploads/gyms/" . $filename;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target)) {

        $profile_image = $filename;

    }

}

// Update gym
$stmt = $conn->prepare("UPDATE gyms SET
gym_name=?,
description=?,
address=?,
city=?,
state=?,
country=?,
pincode=?,
phone=?,
email=?,
opening_time=?,
closing_time=?,
profile_image=?
WHERE gym_id=?");

$stmt->bind_param(
    "ssssssssssssi",
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
    $profile_image,
    $gym_id
);

if ($stmt->execute()) {

    header("Location: owner/my_gym.php?updated=1");
    exit();

} else {

    echo "Error updating gym: " . $conn->error;

}

$stmt->close();
$conn->close();

?>