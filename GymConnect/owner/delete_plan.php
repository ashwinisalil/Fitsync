<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

if (!isset($_GET["id"])) {
    die("Invalid Plan ID.");
}

$plan_id = intval($_GET["id"]);

// Verify that the plan belongs to the logged-in owner's gym
$owner_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
SELECT mp.plan_id
FROM membership_plans mp
JOIN gyms g ON mp.gym_id = g.gym_id
WHERE mp.plan_id = ? AND g.owner_id = ?
");

$stmt->bind_param("ii", $plan_id, $owner_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Plan not found or access denied.");
}

// Delete the plan
$stmt = $conn->prepare("DELETE FROM membership_plans WHERE plan_id = ?");
$stmt->bind_param("i", $plan_id);

if ($stmt->execute()) {

    header("Location: membership_plans.php?deleted=1");
    exit();

} else {

    echo "Error deleting membership plan.";

}

$stmt->close();
$conn->close();

?>