<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$member_id = intval($_POST["member_id"]);

$full_name = trim($_POST["full_name"]);
$gender = $_POST["gender"];
$date_of_birth = $_POST["date_of_birth"];
$phone = trim($_POST["phone"]);
$email = trim($_POST["email"]);
$address = trim($_POST["address"]);
$emergency_contact = trim($_POST["emergency_contact"]);
$plan_id = intval($_POST["plan_id"]);
$joining_date = $_POST["joining_date"];
$membership_end = $_POST["membership_end"];

// Get current profile image

$stmt = $conn->prepare("SELECT profile_image FROM members WHERE member_id=?");
$stmt->bind_param("i", $member_id);
$stmt->execute();

$result = $stmt->get_result();
$member = $result->fetch_assoc();

$profile_image = $member["profile_image"];

// Upload new image

if(isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"]==0){

    $filename = time() . "_" . basename($_FILES["profile_image"]["name"]);

    move_uploaded_file(
        $_FILES["profile_image"]["tmp_name"],
        "../uploads/members/" . $filename
    );

    $profile_image = $filename;
}

// Update member

$stmt = $conn->prepare("
UPDATE members
SET
full_name=?,
gender=?,
date_of_birth=?,
phone=?,
email=?,
address=?,
emergency_contact=?,
plan_id=?,
joining_date=?,
membership_end=?,
profile_image=?
WHERE member_id=?
");

$stmt->bind_param(
    "sssssssisssi",
    $full_name,
    $gender,
    $date_of_birth,
    $phone,
    $email,
    $address,
    $emergency_contact,
    $plan_id,
    $joining_date,
    $membership_end,
    $profile_image,
    $member_id
);

if($stmt->execute()){

    header("Location: members.php?updated=1");
    exit();

}else{

    echo "Error: " . $conn->error;

}

$stmt->close();
$conn->close();

?>