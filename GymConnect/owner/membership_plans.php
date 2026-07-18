<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$owner_id = $_SESSION["user_id"];

// Get owner's gym
$stmt = $conn->prepare("SELECT gym_id FROM gyms WHERE owner_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$gymResult = $stmt->get_result();

if ($gymResult->num_rows == 0) {
    die("No gym registered.");
}

$gym = $gymResult->fetch_assoc();
$gym_id = $gym["gym_id"];

// Get membership plans
$stmt = $conn->prepare("SELECT * FROM membership_plans WHERE gym_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $gym_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Membership Plans | GymConnect</title>

<link rel="stylesheet" href="../css/membership_plans.css">

</head>

<body>

<div class="container">

    <div class="header">

        <h1>Membership Plans</h1>

        <a href="add_plan.php" class="add-btn">+ Add New Plan</a>

    </div>

    <table>

        <tr>

            <th>Plan Name</th>
            <th>Duration</th>
            <th>Price</th>
            <th>Description</th>
            <th>Actions</th>

        </tr>

<?php

if($result->num_rows > 0){

while($row = $result->fetch_assoc()){

?>

<tr>

<td><?php echo htmlspecialchars($row["plan_name"]); ?></td>

<td><?php echo $row["duration_months"]; ?> Month(s)</td>

<td>₹<?php echo $row["price"]; ?></td>

<td><?php echo htmlspecialchars($row["description"]); ?></td>

<td>

<a href="edit_plan.php?id=<?php echo $row["plan_id"]; ?>">Edit</a>

|

<a href="delete_plan.php?id=<?php echo $row["plan_id"]; ?>"
onclick="return confirm('Delete this plan?');">

Delete

</a>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="5" align="center">

No Membership Plans Found

</td>

</tr>

<?php

}

?>

</table>

</div>

</body>

</html>