<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member'){
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$user_id = $_SESSION['user_id'];

/* ==========================
   CHECK ACTIVE MEMBERSHIP
========================== */

$checkMembership = $conn->prepare("
SELECT membership_id
FROM memberships
WHERE member_id = ?
AND status='Active'
LIMIT 1
");

$checkMembership->bind_param("i",$user_id);
$checkMembership->execute();

$resultMembership = $checkMembership->get_result();

if($resultMembership->num_rows > 0){
    header("Location: dashboard.php");
    exit();
}

/* ==========================
   FETCH APPROVED GYMS
========================== */

$sql = "
SELECT *
FROM gyms
WHERE status='approved'
ORDER BY gym_name ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Join Gym | GymConnect</title>

<link rel="stylesheet"
href="../css/join_gym.css">

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

<h1>Join Your Gym</h1>

<p>Select any approved gym to view membership plans.</p>

</div>

<div class="gym-container">
    <?php

if($result->num_rows > 0){

    while($gym = $result->fetch_assoc()){

?>

<div class="gym-card">

    <div class="gym-header">

        <h2><?php echo htmlspecialchars($gym['gym_name']); ?></h2>

    </div>

    <div class="gym-body">

        <p>
            <i class="fas fa-location-dot"></i>
            <?php
            echo htmlspecialchars($gym['address']) . ", " .
                 htmlspecialchars($gym['city']) . ", " .
                 htmlspecialchars($gym['state']);
            ?>
        </p>

        <p>
            <i class="fas fa-phone"></i>
            <?php echo htmlspecialchars($gym['phone']); ?>
        </p>

        <p>
            <i class="fas fa-envelope"></i>
            <?php echo htmlspecialchars($gym['email']); ?>
        </p>

        <p>
            <i class="fas fa-clock"></i>
            <?php echo $gym['opening_time']; ?>
            -
            <?php echo $gym['closing_time']; ?>
        </p>

        <p>
            <i class="fas fa-users"></i>
            Capacity :
            <?php echo $gym['gym_capacity']; ?>
        </p>

    </div>

    <div class="gym-footer">

        <a href="view_plans.php?gym_id=<?php echo $gym['gym_id']; ?>" class="join-btn">

            View Membership Plans

        </a>

    </div>

</div>

<?php

    }

}else{

?>

<div class="no-gym">

    <i class="fas fa-dumbbell"></i>

    <h2>No Approved Gyms Available</h2>

    <p>Please check again later.</p>

</div>

<?php

}

?>

</div>

</div>

<script src="../js/join_gym.js"></script>

</body>

</html>