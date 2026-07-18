<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "member") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: join_gym.php");
    exit();
}

$member_id = $_SESSION["user_id"];

$plan_id = intval($_POST["plan_id"]);
$gym_id = intval($_POST["gym_id"]);
$payment_method = trim($_POST["payment_method"]);

/* ==========================
   GET PLAN DETAILS
========================== */

$sql = "SELECT duration_months, price
        FROM membership_plans
        WHERE plan_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $plan_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid membership plan.");
}

$plan = $result->fetch_assoc();

$duration = $plan["duration_months"];
$amount = $plan["price"];

/* ==========================
   CHECK IF MEMBER ALREADY HAS
   AN ACTIVE MEMBERSHIP
========================== */

$check = $conn->prepare("
    SELECT membership_id
    FROM memberships
    WHERE member_id = ?
    AND status = 'Active'
");

$check->bind_param("i", $member_id);
$check->execute();
$activeMembership = $check->get_result();

if ($activeMembership->num_rows > 0) {

    die("You already have an active membership.");

}

/* ==========================
   CALCULATE DATES
========================== */

$start_date = date("Y-m-d");

$end_date = date(
    "Y-m-d",
    strtotime("+$duration months")
);
/* ==========================
   SAVE PAYMENT
========================== */

$payment = $conn->prepare("
    INSERT INTO payments
    (member_id, gym_id, plan_id, amount, payment_method, payment_status)
    VALUES (?, ?, ?, ?, ?, 'Completed')
");

$payment->bind_param(
    "iiids",
    $member_id,
    $gym_id,
    $plan_id,
    $amount,
    $payment_method
);

if (!$payment->execute()) {
    die("Payment could not be processed.");
}

/* ==========================
   CREATE MEMBERSHIP
========================== */

$membership = $conn->prepare("
    INSERT INTO memberships
    (member_id, gym_id, plan_id, start_date, end_date, status)
    VALUES (?, ?, ?, ?, ?, 'Active')
");

$membership->bind_param(
    "iiiss",
    $member_id,
    $gym_id,
    $plan_id,
    $start_date,
    $end_date
);

if (!$membership->execute()) {
    die("Membership could not be created.");
}

/* ==========================
   CREATE NOTIFICATION
========================== */

$message = "Your membership has been activated successfully.";

$notification = $conn->prepare("
    INSERT INTO notifications
    (user_id, message)
    VALUES (?, ?)
");

$notification->bind_param(
    "is",
    $member_id,
    $message
);

$notification->execute();

/* ==========================
   REDIRECT
========================== */

header("Location: dashboard.php?payment=success");
exit();
?>