<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"]!="member"){
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$user_id=$_SESSION["user_id"];

$sql="SELECT

g.gym_name,
mp.plan_name,
mp.duration_months,
mp.price,

m.start_date,
m.end_date,
m.status,

u.full_name

FROM memberships m

INNER JOIN gyms g
ON m.gym_id=g.gym_id

INNER JOIN membership_plans mp
ON m.plan_id=mp.plan_id

INNER JOIN users u
ON m.member_id=u.user_id

WHERE m.member_id=?
AND m.status='Active'

LIMIT 1";

$stmt=$conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();

$result=$stmt->get_result();

if($result->num_rows==0){

die("No active membership found.");

}

$membership=$result->fetch_assoc();

$today=new DateTime();

$expiry=new DateTime($membership["end_date"]);

$daysLeft=0;

if($expiry>$today){

$daysLeft=$today->diff($expiry)->days;

}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Membership | GymConnect</title>

<link rel="stylesheet" href="../css/membership.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

    <div class="membership-card">

        <h1>My Membership</h1>

        <p class="subtitle">
            Active Membership Details
        </p>

        <table>

            <tr>
                <td><strong>Member Name</strong></td>
                <td><?php echo htmlspecialchars($membership["full_name"]); ?></td>
            </tr>

            <tr>
                <td><strong>Gym Name</strong></td>
                <td><?php echo htmlspecialchars($membership["gym_name"]); ?></td>
            </tr>

            <tr>
                <td><strong>Membership Plan</strong></td>
                <td><?php echo htmlspecialchars($membership["plan_name"]); ?></td>
            </tr>

            <tr>
                <td><strong>Duration</strong></td>
                <td><?php echo $membership["duration_months"]; ?> Month(s)</td>
            </tr>

            <tr>
                <td><strong>Start Date</strong></td>
                <td><?php echo $membership["start_date"]; ?></td>
            </tr>

            <tr>
                <td><strong>Expiry Date</strong></td>
                <td><?php echo $membership["end_date"]; ?></td>
            </tr>

            <tr>
                <td><strong>Days Remaining</strong></td>
                <td><?php echo $daysLeft; ?> Days</td>
            </tr>

            <tr>
                <td><strong>Status</strong></td>
                <td class="status">
                    <?php echo $membership["status"]; ?>
                </td>
            </tr>

            <tr>
                <td><strong>Membership Fee</strong></td>
                <td>₹<?php echo number_format($membership["price"],2); ?></td>
            </tr>

        </table>

        <div class="buttons">

            <a href="dashboard.php" class="btn">
                <i class="fas fa-house"></i>
                Dashboard
            </a>

            <a href="transfer_membership.php" class="btn transfer">
                <i class="fas fa-right-left"></i>
                Transfer Membership
            </a>

        </div>

    </div>

</div>

</body>

</html>