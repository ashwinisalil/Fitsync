<?php
// login.php
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | GymConnect</title>

    <link rel="stylesheet" href="css/login.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

</head>

<body>

<div class="auth-container">

    <!-- Left Side -->

    <div class="left-panel">

        <h1>GYMCONNECT</h1>

        <h2>Welcome Back!</h2>

        <p>

            Login to manage your gym,
            track workouts,
            monitor memberships,
            and continue your fitness journey.

        </p>

        <img src="images/login.png"
             alt="Login">

    </div>

    <!-- Right Side -->

    <div class="right-panel">

        <form action="login_process.php"
              method="POST">

            <h2>Login</h2>

            <div class="input-box">

                <label>Email</label>

                <input
                        type="email"
                        name="email"
                        placeholder="Enter Email"
                        required>

            </div>

            <div class="input-box">

                <label>Password</label>

                <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter Password"
                        required>

            </div>

            <div class="options">

                <label>

                    <input
                            type="checkbox"
                            name="remember">

                    Remember Me

                </label>

                <a href="#">

                    Forgot Password?

                </a>

            </div>

            <button
                    class="login-btn"
                    type="submit">

                Login

            </button>

            <div class="signup-link">

                Don't have an account?

                <a href="signup.php">

                    Create Account

                </a>

            </div>

        </form>

    </div>

</div>

<script src="js/login.js"></script>

</body>

</html>