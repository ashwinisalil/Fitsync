<?php

session_start();

require_once "../database/connection.php";

// Check if user is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

// Check if gym ID is provided
if (isset($_GET["id"])) {

    $gym_id = intval($_GET["id"]);

    // Approve the gym
    $stmt = $conn->prepare("UPDATE gyms SET status = 'approved' WHERE gym_id = ?");
    $stmt->bind_param("i", $gym_id);

    if ($stmt->execute()) {

        header("Location: pending_gyms.php?success=approved");
        exit();

    } else {

        echo "Error approving gym.";

    }

    $stmt->close();

} else {

    echo "Invalid Gym ID.";

}

$conn->close();

?>