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

    <title>Register Gym | GymConnect</title>

    <link rel="stylesheet" href="../css/register_gym.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="container">

    <div class="form-box">

        <h1>Register Your Gym</h1>

        <p>Complete the details below to register your gym.</p>

        <form action="../register_gym_process.php"
              method="POST"
              enctype="multipart/form-data">

            <div class="input-group">

                <label>Gym Name</label>

                <input type="text"
                       name="gym_name"
                       required>

            </div>

            <div class="input-group">

                <label>Description</label>

                <textarea
                    name="description"
                    rows="4"
                    required></textarea>

            </div>

            <div class="input-group">

                <label>Address</label>

                <textarea
                    name="address"
                    rows="3"
                    required></textarea>

            </div>

            <div class="row">

                <div class="input-group">

                    <label>City</label>

                    <input type="text"
                           name="city"
                           required>

                </div>

                <div class="input-group">

                    <label>State</label>

                    <input type="text"
                           name="state"
                           required>

                </div>

            </div>

            <div class="row">

                <div class="input-group">

                    <label>Country</label>

                    <input type="text"
                           name="country"
                           value="India"
                           required>

                </div>

                <div class="input-group">

                    <label>Pincode</label>

                    <input type="text"
                           name="pincode"
                           required>

                </div>

            </div>

            <div class="row">

                <div class="input-group">

                    <label>Phone</label>

                    <input type="text"
                           name="phone"
                           required>

                </div>

                <div class="input-group">

                    <label>Email</label>

                    <input type="email"
                           name="email"
                           required>

                </div>

            </div>

            <div class="row">

                <div class="input-group">

                    <label>Opening Time</label>

                    <input type="time"
                           name="opening_time"
                           required>

                </div>

                <div class="input-group">

                    <label>Closing Time</label>

                    <input type="time"
                           name="closing_time"
                           required>

                </div>

            </div>

            <div class="input-group">

                <label>Gym Image</label>

                <input type="file"
                       name="profile_image"
                       accept="image/*">

            </div>

            <button type="submit" class="btn">

                Register Gym

            </button>

        </form>

    </div>

</div>

</body>

</html>