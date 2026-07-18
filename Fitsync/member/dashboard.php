<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "member") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$user_id = $_SESSION["user_id"];

/* ===========================
   MEMBER DETAILS
=========================== */

$sql = "SELECT
            u.full_name,
            u.email,
            u.phone,

            m.membership_id,
            m.start_date,
            m.end_date,
            m.status,

            g.gym_id,
            g.gym_name,

            mp.plan_name

        FROM users u

        LEFT JOIN memberships m
            ON u.user_id = m.member_id

        LEFT JOIN gyms g
            ON m.gym_id = g.gym_id

        LEFT JOIN membership_plans mp
            ON m.plan_id = mp.plan_id

        WHERE u.user_id = ?

        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();

$member = $stmt->get_result()->fetch_assoc();

/* ===========================
   CHECK MEMBERSHIP
=========================== */

$hasMembership = false;

if(!empty($member["membership_id"])){
    $hasMembership = true;
}

/* ===========================
   ATTENDANCE
=========================== */

$sql2 = "SELECT COUNT(*) AS total
         FROM attendance
         WHERE member_id=?";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i",$user_id);
$stmt2->execute();

$attendance = $stmt2->get_result()->fetch_assoc();

/* ===========================
   NOTIFICATIONS
=========================== */

$sql3 = "SELECT *
         FROM notifications
         WHERE user_id=?
         ORDER BY created_at DESC
         LIMIT 5";

$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param("i",$user_id);
$stmt3->execute();

$notifications = $stmt3->get_result();

/* ===========================
   DAYS LEFT
=========================== */

$daysLeft = "-";

if($hasMembership && !empty($member["end_date"])){

    $today = new DateTime();

    $expiry = new DateTime($member["end_date"]);

    if($today <= $expiry){

        $daysLeft = $today->diff($expiry)->days;

    }else{

        $daysLeft = 0;

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Member Dashboard | GymConnect</title>

<link rel="stylesheet"
href="../css/member_dashboard.css">

<link rel="preconnect"
href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="sidebar">

    <div class="logo">

        <h2>GymConnect</h2>

    </div>

    <ul>

        <li class="active">

            <a href="dashboard.php">

                <i class="fas fa-house"></i>

                Dashboard

            </a>

        </li>

        <li>

            <a href="profile.php">

                <i class="fas fa-user"></i>

                My Profile

            </a>

        </li>

        <?php if($hasMembership){ ?>

        <li>

            <a href="membership.php">

                <i class="fas fa-id-card"></i>

                Membership

            </a>

        </li>

        <li>

            <a href="attendance.php">

                <i class="fas fa-calendar-check"></i>

                Attendance

            </a>

        </li>

        <li>

            <a href="workout_plan.php">

                <i class="fas fa-dumbbell"></i>

                Workout Plan

            </a>

        </li>

        <li>

            <a href="diet_plan.php">

                <i class="fas fa-bowl-food"></i>

                Diet Plan

            </a>

        </li>

        <li>

            <a href="transfer_membership.php">

                <i class="fas fa-right-left"></i>

                Transfer Membership

            </a>

        </li>

        <?php } ?>

        <li>

            <a href="../logout.php">

                <i class="fas fa-right-from-bracket"></i>

                Logout

            </a>

        </li>

    </ul>

</div>

<div class="main-content">

<div class="topbar">

<h1>Member Dashboard</h1>

<div class="member-name">

Welcome,

<strong>

<?php echo htmlspecialchars($member["full_name"]); ?>

</strong>

</div>

</div>
<div class="cards">

    <div class="card">

        <i class="fas fa-building"></i>

        <h3>Gym</h3>

        <p>
            <?php
            echo $hasMembership
                ? htmlspecialchars($member["gym_name"])
                : "Not Joined";
            ?>
        </p>

    </div>

    <div class="card">

        <i class="fas fa-medal"></i>

        <h3>Membership</h3>

        <p>
            <?php
            echo $hasMembership
                ? htmlspecialchars($member["plan_name"])
                : "No Active Plan";
            ?>
        </p>

    </div>

    <div class="card">

        <i class="fas fa-hourglass-half"></i>

        <h3>Days Left</h3>

        <p><?php echo $daysLeft; ?></p>

    </div>

    <div class="card">

        <i class="fas fa-calendar-check"></i>

        <h3>Attendance</h3>

        <p><?php echo $attendance["total"]; ?></p>

    </div>

</div>

<?php if(!$hasMembership){ ?>

<div class="notification-box">

    <h2>Join Your First Gym</h2>

    <p>

        You are currently not a member of any gym.
        Click the button below to browse approved gyms
        and purchase a membership.

    </p>

    <br>

    <a href="join_gym.php" class="btn">

        <i class="fas fa-plus-circle"></i>

        Join Gym

    </a>

</div>

<?php }else{ ?>

<div class="details-section">

<div class="details-card">

<h2>Membership Details</h2>

<table>

<tr>

<td><strong>Gym</strong></td>

<td><?php echo htmlspecialchars($member["gym_name"]); ?></td>

</tr>

<tr>

<td><strong>Plan</strong></td>

<td><?php echo htmlspecialchars($member["plan_name"]); ?></td>

</tr>

<tr>

<td><strong>Start Date</strong></td>

<td><?php echo $member["start_date"]; ?></td>

</tr>

<tr>

<td><strong>Expiry Date</strong></td>

<td><?php echo $member["end_date"]; ?></td>

</tr>

<tr>

<td><strong>Status</strong></td>

<td><?php echo $member["status"]; ?></td>

</tr>

</table>

</div>

<div class="details-card">

<h2>Quick Actions</h2>

<div class="action-buttons">

<a href="profile.php" class="btn">

<i class="fas fa-user"></i>

My Profile

</a>

<a href="attendance.php" class="btn">

<i class="fas fa-calendar-check"></i>

Attendance

</a>

<a href="workout_plan.php" class="btn">

<i class="fas fa-dumbbell"></i>

Workout Plan

</a>

<a href="diet_plan.php" class="btn">

<i class="fas fa-bowl-food"></i>

Diet Plan

</a>

<a href="transfer_membership.php" class="btn">

<i class="fas fa-right-left"></i>

Transfer Membership

</a>

</div>

</div>

</div>

<?php } ?>

<div class="notification-box">

<h2>Latest Notifications</h2>

<?php

if($notifications->num_rows > 0){

while($row = $notifications->fetch_assoc()){

?>

<div class="notification">

<p>

<?php echo htmlspecialchars($row["message"]); ?>

</p>

<small>

<?php echo $row["created_at"]; ?>

</small>

</div>

<?php

}

}else{

?>

<p>No notifications available.</p>

<?php

}

?>

</div>

</div>

<script src="../js/member_dashboard.js"></script>

</body>

</html>