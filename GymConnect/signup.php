<?php
// signup.php
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign Up | GymConnect</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="css/signup.css">

</head>

<body>

<div class="signup-container">

    <!-- Left Side -->
    <div class="left-panel">

        <h1>GYMCONNECT</h1>

        <h2>Join the Future of Fitness</h2>

        <p>
            Connect with gyms, manage memberships,
            track your progress, receive workout & diet plans,
            and transfer memberships whenever you move.
        </p>

        <img src="images/signup.png" alt="Signup Image">

    </div>

    <!-- Right Side -->
    <div class="right-panel">

        <form action="signup_process.php" method="POST">

            <h2>Create Your Account</h2>

            <!-- Full Name -->
            <div class="input-box">

                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Enter your full name"
                    required>

            </div>

            <!-- Email -->
            <div class="input-box">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required>

            </div>

            <!-- Phone -->
            <div class="input-box">

                <label>Phone Number</label>

                <input
                    type="text"
                    name="phone"
                    placeholder="Enter phone number"
                    required>

            </div>

            <!-- Password -->
            <div class="input-box">

                <label>Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create password"
                    required>

            </div>

            <!-- Confirm Password -->
            <div class="input-box">

                <label>Confirm Password</label>

                <input
                    type="password"
                    id="confirmPassword"
                    name="confirm_password"
                    placeholder="Confirm password"
                    required>

            </div>

            <!-- User Role -->
            <div class="input-box">

                <label>Register As</label>

                <select name="role" required>

                    <option value="">Select User Type</option>

                    <option value="member">
                        Gym Member
                    </option>

                    <option value="owner">
                        Gym Owner
                    </option>

                </select>

            </div>

            <!-- Terms -->
            <div class="checkbox">

                <input
                    type="checkbox"
                    required>

                <span>
                    I agree to the Terms &
                    Conditions
                </span>

            </div>

            <!-- Signup Button -->
            <button
                type="submit"
                class="signup-btn">

                Create Account

            </button>

            <div class="login-link">

                Already have an account?

                <a href="login.php">

                    Login

                </a>

            </div>

        </form>

    </div>

</div>

<script src="js/signup.js"></script>

</body>

</html>