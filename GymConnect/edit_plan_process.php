<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: login.php");
    exit();
}

require_once "database/connection.php";

$plan_id = intval($_POST["plan_id"]);
$plan_name = trim($_POST["plan_name"]);
$duration_months = intval($_POST["duration"]);
$price = floatval($_POST["price"]);
$description = trim($_POST["description"]);

// Update membership plan
$stmt = $conn->prepare("
UPDATE membership_plans
SET
plan_name = ?,
duration_months = ?,
price = ?,
description = ?
WHERE plan_id = ?
");

$stmt->bind_param(
    "sidsi",
    $plan_name,
    $duration_months,
    $price,
    $description,
    $plan_id
);

if ($stmt->execute()) {

    header("Location: owner/membership_plans.php?updated=1");
    exit();

} else {

    echo "Error updating membership plan: " . $conn->error;

}

$stmt->close();
$conn->close();

?>