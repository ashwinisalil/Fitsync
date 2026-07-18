<?php

session_start();
require_once "../database/connection.php";

// Check admin login
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

// Get all pending gyms with owner details
$sql = "SELECT gyms.*, users.full_name
        FROM gyms
        INNER JOIN users ON gyms.owner_id = users.user_id
        WHERE gyms.status = 'pending'
        ORDER BY gyms.created_at DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pending Gym Approvals</title>

<link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

<div class="main-content">

    <h1>Pending Gym Approval Requests</h1>

    <br>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">

        <tr>

            <th>Gym Name</th>
            <th>Owner</th>
            <th>City</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Registered On</th>
            <th>Action</th>

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

<td><?php echo htmlspecialchars($row["created_at"]); ?></td>

<td>

<a href="approve_gym.php?id=<?php echo $row["gym_id"]; ?>">

<button>Approve</button>

</a>

<a href="reject_gym.php?id=<?php echo $row["gym_id"]; ?>">

<button>Reject</button>

</a>

</td>

</tr>

<?php

    }

}else{

?>

<tr>

<td colspan="7" align="center">

No Pending Gym Requests

</td>

</tr>

<?php

}

?>

</table>

</div>

</body>

</html>