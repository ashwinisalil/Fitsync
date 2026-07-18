<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

// Statistics
$totalGyms = $conn->query("SELECT COUNT(*) AS total FROM gyms")->fetch_assoc()['total'];
$pendingGyms = $conn->query("SELECT COUNT(*) AS total FROM gyms WHERE status='pending'")->fetch_assoc()['total'];
$approvedGyms = $conn->query("SELECT COUNT(*) AS total FROM gyms WHERE status='approved'")->fetch_assoc()['total'];
$totalOwners = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='owner'")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard | GymConnect</title>

<link rel="stylesheet" href="../css/dashboard.css">

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
                <a href="pending_gyms.php">⏳ Pending Gyms</a>
            </li>

            <li>
                <a href="approved_gyms.php">✅ Approved Gyms</a>
            </li>

            <li>
                <a href="../logout.php">🚪 Logout</a>
            </li>

        </ul>

    </aside>

    <!-- Main Content -->

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Admin Dashboard</h1>

                <p>Welcome,
                    <?php echo $_SESSION["full_name"]; ?>
                </p>

            </div>

        </header>

        <section class="cards">

            <div class="card">
                <h2><?php echo $totalGyms; ?></h2>
                <p>Total Gyms</p>
            </div>

            <div class="card">
                <h2><?php echo $pendingGyms; ?></h2>
                <p>Pending Gyms</p>
            </div>

            <div class="card">
                <h2><?php echo $approvedGyms; ?></h2>
                <p>Approved Gyms</p>
            </div>

            <div class="card">
                <h2><?php echo $totalOwners; ?></h2>
                <p>Gym Owners</p>
            </div>

        </section>

        <section class="table-section">

            <h2>Quick Actions</h2>

            <br>

            <a href="pending_gyms.php" class="btn btn-primary">
                View Pending Gym Requests
            </a>

        </section>

    </main>

</div>

</body>

</html>