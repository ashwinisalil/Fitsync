<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"]!="member"){
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

if(!isset($_GET["plan_id"])){
    header("Location: join_gym.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$plan_id = intval($_GET["plan_id"]);

/* ==========================
   GET PLAN DETAILS
========================== */

$sql = "SELECT
            mp.plan_id,
            mp.plan_name,
            mp.duration_months,
            mp.price,
            mp.description,

            g.gym_id,
            g.gym_name

        FROM membership_plans mp

        INNER JOIN gyms g
        ON mp.gym_id=g.gym_id

        WHERE mp.plan_id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$plan_id);
$stmt->execute();

$plan = $stmt->get_result()->fetch_assoc();

if(!$plan){
    die("Membership plan not found.");
}
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Payment | GymConnect</title>

<link rel="stylesheet"
href="../css/payment.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

<div class="payment-box">

<h1>Membership Payment</h1>

<p>

Complete your payment to activate membership.

</p>

<form action="payment_process.php" method="POST">

<input
type="hidden"
name="plan_id"
value="<?php echo $plan["plan_id"]; ?>">

<input
type="hidden"
name="gym_id"
value="<?php echo $plan["gym_id"]; ?>">
<!-- Membership Summary -->

<div class="summary">

    <h2>Membership Details</h2>

    <table>

        <tr>

            <td><strong>Gym</strong></td>

            <td><?php echo htmlspecialchars($plan["gym_name"]); ?></td>

        </tr>

        <tr>

            <td><strong>Plan</strong></td>

            <td><?php echo htmlspecialchars($plan["plan_name"]); ?></td>

        </tr>

        <tr>

            <td><strong>Duration</strong></td>

            <td><?php echo $plan["duration_months"]; ?> Month(s)</td>

        </tr>

        <tr>

            <td><strong>Price</strong></td>

            <td>₹<?php echo number_format($plan["price"],2); ?></td>

        </tr>

        <tr>

            <td><strong>Description</strong></td>

            <td>

            <?php

            if(!empty($plan["description"])){

                echo htmlspecialchars($plan["description"]);

            }else{

                echo "No description available.";

            }

            ?>

            </td>

        </tr>

    </table>

</div>

<br>

<div class="payment-method">

    <h2>Select Payment Method</h2>

    <label>

        <input
        type="radio"
        name="payment_method"
        value="UPI"
        required>

        UPI

    </label>

    <label>

        <input
        type="radio"
        name="payment_method"
        value="Card">

        Debit / Credit Card

    </label>

    <label>

        <input
        type="radio"
        name="payment_method"
        value="Cash">

        Cash at Gym

    </label>

</div>

<br>

<button
type="submit"
class="pay-btn">

<i class="fas fa-credit-card"></i>

Pay ₹<?php echo number_format($plan["price"],2); ?>

</button>

</form>

</div>

</div>

<script src="../js/payment.js"></script>

</body>

</html>