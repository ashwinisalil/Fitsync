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

if ($result->num_rows == 0) {
    die("No gym found.");
}

$gym = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Gym | GymConnect</title>

<link rel="stylesheet" href="../css/edit_gym.css">

</head>

<body>

<div class="container">

<div class="form-box">

<h1>Edit Gym Details</h1>

<form action="../edit_gym_process.php"
      method="POST"
      enctype="multipart/form-data">

<input type="hidden"
       name="gym_id"
       value="<?php echo $gym["gym_id"]; ?>">

<div class="input-group">

<label>Gym Name</label>

<input type="text"
       name="gym_name"
       value="<?php echo htmlspecialchars($gym["gym_name"]); ?>"
       required>

</div>

<div class="input-group">

<label>Description</label>

<textarea
name="description"
rows="4"
required><?php echo htmlspecialchars($gym["description"]); ?></textarea>

</div>

<div class="input-group">

<label>Address</label>

<textarea
name="address"
rows="3"
required><?php echo htmlspecialchars($gym["address"]); ?></textarea>

</div>
<div class="row">

    <div class="input-group">

        <label>City</label>

        <input type="text"
               name="city"
               value="<?php echo htmlspecialchars($gym["city"]); ?>"
               required>

    </div>

    <div class="input-group">

        <label>State</label>

        <input type="text"
               name="state"
               value="<?php echo htmlspecialchars($gym["state"]); ?>"
               required>

    </div>

</div>

<div class="row">

    <div class="input-group">

        <label>Country</label>

        <input type="text"
               name="country"
               value="<?php echo htmlspecialchars($gym["country"]); ?>"
               required>

    </div>

    <div class="input-group">

        <label>Pincode</label>

        <input type="text"
               name="pincode"
               value="<?php echo htmlspecialchars($gym["pincode"]); ?>"
               required>

    </div>

</div>

<div class="row">

    <div class="input-group">

        <label>Phone</label>

        <input type="text"
               name="phone"
               value="<?php echo htmlspecialchars($gym["phone"]); ?>"
               required>

    </div>

    <div class="input-group">

        <label>Email</label>

        <input type="email"
               name="email"
               value="<?php echo htmlspecialchars($gym["email"]); ?>"
               required>

    </div>

</div>

<div class="row">

    <div class="input-group">

        <label>Opening Time</label>

        <input type="time"
               name="opening_time"
               value="<?php echo $gym["opening_time"]; ?>"
               required>

    </div>

    <div class="input-group">

        <label>Closing Time</label>

        <input type="time"
               name="closing_time"
               value="<?php echo $gym["closing_time"]; ?>"
               required>

    </div>

</div>

<div class="input-group">

    <label>Current Gym Image</label>

    <br><br>

    <?php if(!empty($gym["profile_image"])) { ?>

        <img src="../uploads/gyms/<?php echo $gym["profile_image"]; ?>"
             width="180"
             style="border-radius:10px;">

    <?php } ?>

</div>

<div class="input-group">

    <label>Upload New Image</label>

    <input type="file"
           name="profile_image"
           accept="image/*">

</div>

<button type="submit" class="btn">

    Update Gym Details

</button>
<div class="row">

    <div class="input-group">

        <label>City</label>

        <input type="text"
               name="city"
               value="<?php echo htmlspecialchars($gym["city"]); ?>"
               required>

    </div>

    <div class="input-group">

        <label>State</label>

        <input type="text"
               name="state"
               value="<?php echo htmlspecialchars($gym["state"]); ?>"
               required>

    </div>

</div>

<div class="row">

    <div class="input-group">

        <label>Country</label>

        <input type="text"
               name="country"
               value="<?php echo htmlspecialchars($gym["country"]); ?>"
               required>

    </div>

    <div class="input-group">

        <label>Pincode</label>

        <input type="text"
               name="pincode"
               value="<?php echo htmlspecialchars($gym["pincode"]); ?>"
               required>

    </div>

</div>

<div class="row">

    <div class="input-group">

        <label>Phone</label>

        <input type="text"
               name="phone"
               value="<?php echo htmlspecialchars($gym["phone"]); ?>"
               required>

    </div>

    <div class="input-group">

        <label>Email</label>

        <input type="email"
               name="email"
               value="<?php echo htmlspecialchars($gym["email"]); ?>"
               required>

    </div>

</div>

<div class="row">

    <div class="input-group">

        <label>Opening Time</label>

        <input type="time"
               name="opening_time"
               value="<?php echo $gym["opening_time"]; ?>"
               required>

    </div>

    <div class="input-group">

        <label>Closing Time</label>

        <input type="time"
               name="closing_time"
               value="<?php echo $gym["closing_time"]; ?>"
               required>

    </div>

</div>

<div class="input-group">

    <label>Current Gym Image</label>

    <br><br>

    <?php if(!empty($gym["profile_image"])) { ?>

        <img src="../uploads/gyms/<?php echo $gym["profile_image"]; ?>"
             width="180"
             style="border-radius:10px;">

    <?php } ?>

</div>

<div class="input-group">

    <label>Upload New Image</label>

    <input type="file"
           name="profile_image"
           accept="image/*">

</div>

<button type="submit" class="btn">

    Update Gym Details

</button>
</form>

</div>

</div>

</body>

</html>