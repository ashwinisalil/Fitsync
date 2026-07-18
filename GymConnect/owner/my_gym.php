<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$owner_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT * FROM gyms WHERE owner_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Gym | GymConnect</title>

<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="../css/my_gym.css">

<style>

.gym-card{

    background:#ffffff;
    border-radius:15px;
    padding:30px;
    box-shadow:0 5px 20px rgba(0,0,0,.15);
    max-width:900px;
    margin:auto;

}

.gym-card img{

    width:220px;
    height:220px;
    object-fit:cover;
    border-radius:15px;
    margin-bottom:20px;

}

.gym-card h2{

    margin-bottom:15px;

}

.info{

    margin:10px 0;
    font-size:17px;

}

.status{

    display:inline-block;
    padding:8px 18px;
    border-radius:20px;
    font-weight:bold;
    color:white;

}

.pending{

    background:orange;

}

.approved{

    background:green;

}

.rejected{

    background:red;

}

.btn{

    display:inline-block;
    margin-top:25px;
    padding:12px 25px;
    background:#007bff;
    color:white;
    text-decoration:none;
    border-radius:8px;

}

.btn:hover{

    background:#0056b3;

}

</style>

</head>

<body>

<div class="main-content">

<h1>My Gym</h1>

<br>

<?php

if($result->num_rows==0){

?>

<div class="gym-card">

<h2>No Gym Registered</h2>

<p>You haven't registered any gym yet.</p>

<br>

<a class="btn" href="register_gym.php">

Register Gym

</a>

</div>

<?php

}else{

$gym=$result->fetch_assoc();

?>

<div class="gym-card">

<?php

if(!empty($gym["profile_image"])){

?>

<img src="../uploads/gyms/<?php echo $gym["profile_image"]; ?>">

<?php

}

?>

<h2><?php echo htmlspecialchars($gym["gym_name"]); ?></h2>

<div class="info">

<strong>Description:</strong>

<?php echo htmlspecialchars($gym["description"]); ?>

</div>

<div class="info">

<strong>Address:</strong>

<?php

echo htmlspecialchars($gym["address"]);

echo ", ";

echo htmlspecialchars($gym["city"]);

echo ", ";

echo htmlspecialchars($gym["state"]);

echo ", ";

echo htmlspecialchars($gym["country"]);

echo " - ";

echo htmlspecialchars($gym["pincode"]);

?>

</div>

<div class="info">

<strong>Phone:</strong>

<?php echo htmlspecialchars($gym["phone"]); ?>

</div>

<div class="info">

<strong>Email:</strong>

<?php echo htmlspecialchars($gym["email"]); ?>

</div>

<div class="info">

<strong>Working Hours:</strong>

<?php

echo $gym["opening_time"];

echo " - ";

echo $gym["closing_time"];

?>

</div>

<div class="info">

<strong>Status:</strong>

<span class="status <?php echo $gym["status"]; ?>">

<?php echo ucfirst($gym["status"]); ?>

</span>

</div>

<a href="edit_gym.php?id=<?php echo $gym["gym_id"]; ?>" class="btn">

Edit Gym

</a>

</div>

<?php

}

?>

</div>

</body>

</html>