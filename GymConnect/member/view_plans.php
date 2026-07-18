<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "member") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

if (!isset($_GET["gym_id"])) {
    header("Location: join_gym.php");
    exit();
}

$gym_id = intval($_GET["gym_id"]);

/* ==========================
   FETCH GYM DETAILS
========================== */

$sqlGym = "SELECT gym_name
           FROM gyms
           WHERE gym_id=?";

$stmtGym = $conn->prepare($sqlGym);
$stmtGym->bind_param("i", $gym_id);
$stmtGym->execute();

$gym = $stmtGym->get_result()->fetch_assoc();

if (!$gym) {
    die("Gym not found.");
}

/* ==========================
   FETCH MEMBERSHIP PLANS
========================== */

$sqlPlans = "SELECT *
             FROM membership_plans
             WHERE gym_id=?
             ORDER BY price ASC";

$stmtPlans = $conn->prepare($sqlPlans);
$stmtPlans->bind_param("i", $gym_id);
$stmtPlans->execute();

$plans = $stmtPlans->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Membership Plans | GymConnect</title>

<link rel="stylesheet"
href="../css/view_plans.css">

<link rel="preconnect"
href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

<div class="header">

<h1>

<?php echo htmlspecialchars($gym["gym_name"]); ?>

</h1>

<p>

Choose the membership plan that suits you.

</p>

</div>

<div class="plans-container">
    <?php

if($plans->num_rows > 0){

    while($plan = $plans->fetch_assoc()){

?>

<div class="plan-card">

    <div class="plan-header">

        <h2><?php echo htmlspecialchars($plan["plan_name"]); ?></h2>

    </div>

    <div class="plan-body">

        <p>

            <i class="fas fa-calendar-alt"></i>

            Duration :
            <strong>

            <?php echo $plan["duration_months"]; ?>

            Month(s)

            </strong>

        </p>

        <p>

            <i class="fas fa-indian-rupee-sign"></i>

            Price :
            <strong>

            ₹<?php echo number_format($plan["price"],2); ?>

            </strong>

        </p>

        <p>

            <i class="fas fa-circle-info"></i>

            Description :

        </p>

        <p class="description">

            <?php

            if(!empty($plan["description"])){

                echo htmlspecialchars($plan["description"]);

            }else{

                echo "No description available.";

            }

            ?>

        </p>

    </div>

    <div class="plan-footer">

        <a href="payment.php?plan_id=<?php echo $plan["plan_id"]; ?>" class="choose-btn">

            Choose Plan

        </a>

    </div>

</div>

<?php

    }

}else{

?>

<div class="no-plan">

    <i class="fas fa-dumbbell"></i>

    <h2>No Membership Plans Available</h2>

    <p>

        This gym has not added any membership plans yet.

    </p>

    <br>

    <a href="join_gym.php" class="choose-btn">

        Back to Gyms

    </a>

</div>

<?php

}

?>

</div>

</div>

<script src="../js/view_plans.js"></script>

</body>

</html>