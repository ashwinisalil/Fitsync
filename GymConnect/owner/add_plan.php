<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: ../login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Membership Plan | GymConnect</title>

<link rel="stylesheet" href="../css/add_plan.css">

</head>

<body>

<div class="container">

<div class="form-box">

<h1>Add Membership Plan</h1>

<form action="../add_plan_process.php" method="POST">

<div class="input-group">

<label>Plan Name</label>

<input
type="text"
name="plan_name"
placeholder="Enter Plan Name"
required>

</div>

<div class="row">

<div class="input-group">

<label>Duration (Months)</label>

<select name="duration" required>

<option value="">Select Duration</option>

<option value="1">1 Month</option>

<option value="3">3 Months</option>

<option value="6">6 Months</option>

<option value="12">12 Months</option>

</select>

</div>

<div class="input-group">

<label>Price (₹)</label>

<input
type="number"
name="price"
placeholder="Enter Price"
required>

</div>

</div>

<div class="input-group">

<label>Description</label>

<textarea
name="description"
rows="5"
placeholder="Describe this membership plan..."
required></textarea>

</div>

<button
type="submit"
class="btn">

Save Plan

</button>

<br><br>

<a href="membership_plans.php">

← Back to Membership Plans

</a>

</form>

</div>

</div>

</body>

</html>