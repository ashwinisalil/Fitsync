<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "owner") {
    header("Location: ../login.php");
    exit();
}

$userName = $_SESSION["full_name"];
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Owner Dashboard | GymConnect</title>

<link rel="stylesheet" href="../css/dashboard.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="dashboard">

    <!-- Sidebar -->

    <aside class="sidebar">

        <div class="logo">

            <h2>GYMCONNECT</h2>

        </div>

        <ul>

            <li class="active">
                <a href="dashboard.php">🏠 Dashboard</a>
            </li>

            <li>
                <a href="my_gym.php">🏢 My Gym</a>
            </li>

            <li>
                <a href="profile.php">👤 My Profile</a>
            </li>

            <li>
                <a href="membership_plans.php">💳 Membership Plans</a>
            </li>

            <li>
                <a href="register_gym.php">🏢 Register Gym</a>
            </li>

            <li>
                <a href="members.php">👥 Members</a>
            </li>

            <li>
                <a href="membership.php">💳 Memberships</a>
            </li>

            <li>
                <a href="workout.php">🏋 Workout Plans</a>
            </li>

            <li>
                <a href="diet.php">🥗 Diet Plans</a>
            </li>

            <li>
                <a href="attendance.php">📅 Attendance</a>
            </li>

            <li>
                <a href="transfers.php">🔄 Transfer Requests</a>
            </li>

            <li>
                <a href="settings.php">⚙ Settings</a>
            </li>

            <li>
                <a href="../logout.php">🚪 Logout</a>
            </li>

        </ul>

    </aside>

    <!-- Main Content -->

    <main class="main-content">

        <!-- Top Bar -->

        <header class="topbar">

            <div>

                <h1>Dashboard</h1>

                <p>Welcome, <?php echo htmlspecialchars($userName); ?></p>

            </div>

            <div class="profile">

                👤 Gym Owner

            </div>

        </header>

        <!-- Cards -->

        <section class="cards">

            <div class="card">

                <h2>0</h2>

                <p>Total Members</p>

            </div>

            <div class="card">

                <h2>0</h2>

                <p>Active Memberships</p>

            </div>

            <div class="card">

                <h2>0</h2>

                <p>Expired Memberships</p>

            </div>

            <div class="card">

                <h2>0</h2>

                <p>Today's Attendance</p>

            </div>

        </section>

        <!-- Recent Members -->

        <section class="table-section">

            <h2>Recent Members</h2>

            <table>

                <tr>

                    <th>Name</th>

                    <th>Membership</th>

                    <th>Status</th>

                </tr>

                <tr>

                    <td>No members yet</td>

                    <td>--</td>

                    <td>--</td>

                </tr>

            </table>

        </section>

    </main>

</div>

<script src="../js/dashboard.js"></script>

</body>

</html>