<?php

session_start();

require_once "../database/connection.php";

// Check if user is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

// Get approved gyms with owner details
$sql = "SELECT gyms.*, users.full_name
        FROM gyms
        INNER JOIN users ON gyms.owner_id = users.user_id
        WHERE gyms.status = 'approved'
        ORDER BY gyms.created_at DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Approved Gyms | GymConnect</title>

<link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

<div class="main-content">

    <h1>Approved Gyms</h1>

    <br>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">

        <tr>

            <th>Gym Name</th>
            <th>Owner</th>
            <th>City</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Approved Status</th>

        </tr>

<?php

if($result->num_rows > 0){

    while($row = $result->fetch_assoc()){

?>

<tr>

<td><?php echo htmlspecialchars($row["gym_name"]); ?></td>

<td><?php echo htmlspecialchars($row["full_name"]); ?></td>

<td><?php echo htmlspecialchars($row["city"]); ?></td>

<td><?php echo htmlspecialchars($row["phone"]); ?></td>

<td><?php echo htmlspecialchars($row["email"]); ?></td>

<td style="color:green;font-weight:bold;">
Approved
</td>

</tr>

<?php

    }

}else{

?>

<tr>

<td colspan="6" align="center">

No Approved Gyms Found

</td>

</tr>

<?php

}

?>

</table>

<br><br>

<a href="dashboard.php">
<button>← Back to Dashboard</button>
</a>

</div>

</body>

</html>