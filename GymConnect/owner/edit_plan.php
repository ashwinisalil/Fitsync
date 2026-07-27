<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

if (!isset($_GET["id"])) {
    die("Invalid Plan ID.");
}

$plan_id = intval($_GET["id"]);

// Get plan details
$stmt = $conn->prepare("SELECT * FROM membership_plans WHERE plan_id = ?");
$stmt->bind_param("i", $plan_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Membership plan not found.");
}

$plan = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Edit Membership Plan</title>

<link rel="stylesheet"
href="../css/add_plan.css">

</head>

<body>

<div class="container">

<div class="form-box">

<h1>Edit Membership Plan</h1>

<form action="../edit_plan_process.php"
method="POST">

<input
type="hidden"
name="plan_id"
value="<?php echo $plan["plan_id"]; ?>">

<div class="input-group">

<label>Plan Name</label>

<input
type="text"
name="plan_name"
value="<?php echo htmlspecialchars($plan["plan_name"]); ?>"
required>

</div>

<div class="row">

<div class="input-group">

<label>Duration (Months)</label>

<select name="duration" required>

<option value="1" <?php if($plan["duration_months"]==1) echo "selected"; ?>>1 Month</option>

<option value="3" <?php if($plan["duration_months"]==3) echo "selected"; ?>>3 Months</option>

<option value="6" <?php if($plan["duration_months"]==6) echo "selected"; ?>>6 Months</option>

<option value="12" <?php if($plan["duration_months"]==12) echo "selected"; ?>>12 Months</option>

</select>

</div>

<div class="input-group">

<label>Price (₹)</label>

<input
type="number"
step="0.01"
name="price"
value="<?php echo $plan["price"]; ?>"
required>

</div>

</div>

<div class="input-group">

<label>Description</label>

<textarea
name="description"
rows="5"
required><?php echo htmlspecialchars($plan["description"]); ?></textarea>

</div>

<button
type="submit"
class="btn">

Update Plan

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