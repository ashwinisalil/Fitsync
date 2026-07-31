<?php

/* =========================================================
   GYMCONNECT - OWNER DASHBOARD
   Dynamic Multi-Gym Dashboard
========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();


/* =========================================================
   OWNER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "owner"
) {
    header("Location: ../login.php");
    exit();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../database/connection.php";


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = intval($_SESSION["user_id"]);

$userName = $_SESSION["full_name"] ?? "Gym Owner";


/* =========================================================
   TOTAL GYMS
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_gyms
    FROM gyms
    WHERE owner_id = ?
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_gyms = intval(
    $row["total_gyms"]
);

$stmt->close();


/* =========================================================
   TOTAL MEMBERS
   Count unique members across all owner's gyms
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT m.member_id) AS total_members

    FROM memberships m

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    WHERE g.owner_id = ?
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_members = intval(
    $row["total_members"]
);

$stmt->close();


/* =========================================================
   ACTIVE MEMBERSHIPS
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS active_memberships

    FROM memberships m

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    WHERE g.owner_id = ?

    AND m.status = 'Active'
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$active_memberships = intval(
    $row["active_memberships"]
);

$stmt->close();


/* =========================================================
   EXPIRED MEMBERSHIPS

   We check both:
   1. status = Expired
   2. end_date is before today
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS expired_memberships

    FROM memberships m

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    WHERE g.owner_id = ?

    AND (
        m.status = 'Expired'
        OR m.end_date < CURDATE()
    )
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$expired_memberships = intval(
    $row["expired_memberships"]
);

$stmt->close();


/* =========================================================
   TODAY'S ATTENDANCE
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS today_attendance

    FROM attendance a

    INNER JOIN gyms g
        ON a.gym_id = g.gym_id

    WHERE g.owner_id = ?

    AND DATE(a.attendance_date) = CURDATE()
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$today_attendance = intval(
    $row["today_attendance"]
);

$stmt->close();


/* =========================================================
   RECENT MEMBERS

   Shows the latest members who joined
   any gym owned by this owner.
========================================================= */

$stmt = $conn->prepare("
    SELECT
        u.full_name,
        g.gym_name,
        mp.plan_name,
        m.start_date,
        m.end_date,
        m.status

    FROM memberships m

    INNER JOIN users u
        ON m.member_id = u.user_id

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    LEFT JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE g.owner_id = ?

    ORDER BY m.membership_id DESC

    LIMIT 10
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$recentMembers =
    $stmt->get_result();

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Owner Dashboard | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/dashboard.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

</head>


<body>


<div class="dashboard">


<!-- =====================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar">


    <div class="logo">

        <h2>
            GYMCONNECT
        </h2>

    </div>


    <ul>


        <li class="active">

            <a href="dashboard.php">

                🏠 Dashboard

            </a>

        </li>


        <li>

            <a href="my_gym.php">

                🏢 My Gyms

            </a>

        </li>


        <li>

            <a href="membership_plans.php">

                💳 Membership Plans

            </a>

        </li>


        <li>

            <a href="register_gym.php">

                🏢 Register Gym

            </a>

        </li>


        <li>

            <a href="members.php">

                👥 Members

            </a>

        </li>


        <li>

            <a href="membership.php">

                💳 Memberships

            </a>

        </li>


        <li>

            <a href="attendance.php">

                📅 Attendance

            </a>

        </li>


        <li>

            <a href="transfer_requests.php">

                🔄 Transfer Requests

            </a>

        </li>


        <li>

            <a href="profile.php">

                👤 My Profile

            </a>

        </li>

        <li>
            <a href="payments.php">💰 Payments & Revenue</a>
        </li>

        <li>
            <a href="transfer_settlements.php">
            Transfer Settlements
            </a>
        </li>


        <li>

            <a href="settings.php">

                ⚙ Settings

            </a>

        </li>


        <li>

            <a href="../logout.php">

                🚪 Logout

            </a>

        </li>


    </ul>


</aside>



<!-- =====================================================
     MAIN CONTENT
====================================================== -->

<main class="main-content">


    <!-- =================================================
         TOP BAR
    ================================================== -->

    <header class="topbar">


        <div>

            <h1>
                Dashboard
            </h1>

            <p>

                Welcome,
                <?php
                echo htmlspecialchars(
                    $userName
                );
                ?>

            </p>

        </div>


        <div class="profile">

            👤 Gym Owner

        </div>


    </header>



    <!-- =================================================
         STATISTICS CARDS
    ================================================== -->

    <section class="cards">


        <!-- TOTAL GYMS -->

        <div class="card">

            <h2>

                <?php
                echo $total_gyms;
                ?>

            </h2>

            <p>
                Total Gyms
            </p>

        </div>



        <!-- TOTAL MEMBERS -->

        <div class="card">

            <h2>

                <?php
                echo $total_members;
                ?>

            </h2>

            <p>
                Total Members
            </p>

        </div>



        <!-- ACTIVE MEMBERSHIPS -->

        <div class="card">

            <h2>

                <?php
                echo $active_memberships;
                ?>

            </h2>

            <p>
                Active Memberships
            </p>

        </div>



        <!-- EXPIRED MEMBERSHIPS -->

        <div class="card">

            <h2>

                <?php
                echo $expired_memberships;
                ?>

            </h2>

            <p>
                Expired Memberships
            </p>

        </div>



        <!-- TODAY'S ATTENDANCE -->

        <div class="card">

            <h2>

                <?php
                echo $today_attendance;
                ?>

            </h2>

            <p>
                Today's Attendance
            </p>

        </div>


    </section>



    <!-- =================================================
         RECENT MEMBERS
    ================================================== -->

    <section class="table-section">


        <h2>
            Recent Members
        </h2>


        <table>


            <thead>

                <tr>

                    <th>
                        Name
                    </th>

                    <th>
                        Gym
                    </th>

                    <th>
                        Membership Plan
                    </th>

                    <th>
                        Start Date
                    </th>

                    <th>
                        End Date
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>


<?php

if (
    $recentMembers->num_rows > 0
) {

    while (
        $member =
        $recentMembers->fetch_assoc()
    ) {

?>


                <tr>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $member["full_name"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $member["gym_name"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $member["plan_name"]
                            ?? "N/A"
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $member["start_date"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $member["end_date"]
                        );

                        ?>

                    </td>


                    <td>


<?php

$status =
    $member["status"];

$statusClass =
    strtolower(
        $status
    );

?>


                        <span
                            class="status
                            <?php
                            echo $statusClass;
                            ?>-status"
                        >

                            <?php

                            echo htmlspecialchars(
                                $status
                            );

                            ?>

                        </span>


                    </td>


                </tr>


<?php

    }

} else {

?>


                <tr>

                    <td
                        colspan="6"
                        style="text-align:center;"
                    >

                        No members have joined
                        your gyms yet.

                    </td>

                </tr>


<?php

}

?>


            </tbody>


        </table>


    </section>


</main>


</div>


<script
    src="../js/dashboard.js"
></script>


</body>

</html>

<?php

$stmt->close();

?>