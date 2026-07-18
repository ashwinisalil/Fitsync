<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: login.php");
    exit();
}

require_once "database/connection.php";

$owner_id = $_SESSION["user_id"];

$plan_name = trim($_POST["plan_name"]);
$duration_months = intval($_POST["duration"]);
$price = floatval($_POST["price"]);
$description = trim($_POST["description"]);

// Get owner's gym
$stmt = $conn->prepare("SELECT gym_id FROM gyms WHERE owner_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("No gym found.");
}

$gym = $result->fetch_assoc();
$gym_id = $gym["gym_id"];

// Insert membership plan
$stmt = $conn->prepare("
INSERT INTO membership_plans
(gym_id, plan_name, duration_months, price, description)
VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isids",
    $gym_id,
    $plan_name,
    $duration_months,
    $price,
    $description
);

if ($stmt->execute()) {

    header("Location: owner/membership_plans.php?success=1");
    exit();

} else {

    echo "Error: " . $conn->error;

}

$stmt->close();
$conn->close();

?>