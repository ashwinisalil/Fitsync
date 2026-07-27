
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "owner"
) {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$owner_id = (int) $_SESSION["user_id"];

$userName = $_SESSION["full_name"] ?? "Gym Owner";


/* =========================================================
   GET OWNER'S MEMBERS
========================================================= */

$sql = "
    SELECT
        m.membership_id,
        m.start_date,
        m.end_date,
        m.status,

        u.user_id,
        u.full_name,
        u.email,
        u.phone,

        g.gym_id,
        g.gym_name,

        mp.plan_name,
        mp.duration_months,
        mp.price

    FROM memberships m

    INNER JOIN users u
        ON m.member_id = u.user_id

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    LEFT JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE g.owner_id = ?

    ORDER BY m.membership_id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $owner_id);

$stmt->execute();

$result = $stmt->get_result();


/* =========================================================
   COUNT MEMBERS
========================================================= */

$total_members = 0;
$active_members = 0;
$expired_members = 0;
$transferred_members = 0;

$members = [];

while ($row = $result->fetch_assoc()) {

    $members[] = $row;

    $total_members++;

    if ($row["status"] === "Active") {
        $active_members++;
    }

    if ($row["status"] === "Expired") {
        $expired_members++;
    }

    if ($row["status"] === "Transferred") {
        $transferred_members++;
    }
}

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Members | GymConnect</title>

<link rel="stylesheet"
      href="../css/owner_members.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet">

</head>


<body>


<div class="page-container">


    <!-- =========================================
         SIDEBAR
    ========================================== -->

    <aside class="sidebar">


        <div class="logo">

            <h2>GYMCONNECT</h2>

        </div>


        <ul>

            <li>

                <a href="dashboard.php">
                    🏠 Dashboard
                </a>

            </li>


            <li>

                <a href="my_gym.php">
                    🏢 My Gym
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


            <li class="active">

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

                <a href="workout.php">
                    🏋 Workout Plans
                </a>

            </li>


            <li>

                <a href="diet.php">
                    🥗 Diet Plans
                </a>

            </li>


            <li>

                <a href="attendance.php">
                    📅 Attendance
                </a>

            </li>


            <li>

                <a href="transfers.php">
                    🔄 Transfer Requests
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



    <!-- =========================================
         MAIN CONTENT
    ========================================== -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">


            <div>

                <h1>Members</h1>

                <p>
                    Manage members of your gyms
                </p>

            </div>


            <div class="profile">

                👤
                <?php echo htmlspecialchars($userName); ?>

            </div>


        </header>



        <!-- =========================================
             SUMMARY CARDS
        ========================================== -->

        <section class="stats">


            <div class="stat-card">

                <h2>
                    <?php echo $total_members; ?>
                </h2>

                <p>
                    Total Memberships
                </p>

            </div>


            <div class="stat-card active-card">

                <h2>
                    <?php echo $active_members; ?>
                </h2>

                <p>
                    Active Members
                </p>

            </div>


            <div class="stat-card expired-card">

                <h2>
                    <?php echo $expired_members; ?>
                </h2>

                <p>
                    Expired Memberships
                </p>

            </div>


            <div class="stat-card transfer-card">

                <h2>
                    <?php echo $transferred_members; ?>
                </h2>

                <p>
                    Transferred
                </p>

            </div>


        </section>



        <!-- =========================================
             ACTION BAR
        ========================================== -->

        <section class="action-bar">


            <div class="search-box">

                <input
                    type="text"
                    id="memberSearch"
                    placeholder="Search member, email, phone or gym..."
                >

            </div>


            <a
                href="add_member.php"
                class="add-member-btn"
            >

                + Add Member Manually

            </a>


        </section>



        <!-- =========================================
             MEMBERS TABLE
        ========================================== -->

        <section class="members-section">


            <div class="section-header">

                <h2>
                    Gym Members
                </h2>

                <span>

                    <?php echo $total_members; ?>
                    Records

                </span>

            </div>


            <div class="table-container">


                <table id="membersTable">


                    <thead>

                        <tr>

                            <th>Member</th>

                            <th>Contact</th>

                            <th>Gym</th>

                            <th>Plan</th>

                            <th>Start Date</th>

                            <th>End Date</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (count($members) > 0): ?>


                        <?php foreach ($members as $member): ?>


                            <tr>


                                <td>

                                    <div class="member-info">

                                        <div class="member-avatar">

                                            <?php

                                            echo strtoupper(
                                                substr(
                                                    $member["full_name"],
                                                    0,
                                                    1
                                                )
                                            );

                                            ?>

                                        </div>


                                        <div>

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $member["full_name"]
                                                );

                                                ?>

                                            </strong>


                                            <small>

                                                ID:
                                                <?php

                                                echo $member["user_id"];

                                                ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <div>

                                        <?php

                                        echo htmlspecialchars(
                                            $member["email"]
                                        );

                                        ?>

                                    </div>


                                    <small>

                                        <?php

                                        echo htmlspecialchars(
                                            $member["phone"] ?? "N/A"
                                        );

                                        ?>

                                    </small>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $member["gym_name"]
                                    );

                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $member["plan_name"]
                                            ?? "N/A"
                                        );

                                        ?>

                                    </strong>


                                    <small>

                                        ₹<?php

                                        echo number_format(
                                            $member["price"]
                                            ?? 0,
                                            2
                                        );

                                        ?>

                                    </small>

                                </td>


                                <td>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $member["start_date"]
                                        )
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $member["end_date"]
                                        )
                                    );

                                    ?>

                                </td>


                                <td>


                                    <?php

                                    $status =
                                        $member["status"];

                                    $status_class =
                                        strtolower(
                                            $status
                                        );

                                    ?>


                                    <span
                                        class="
                                        status
                                        <?php
                                        echo $status_class;
                                        ?>
                                        "
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $status
                                        );

                                        ?>

                                    </span>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="no-members"
                            >

                                <div>

                                    <h3>
                                        No Members Yet
                                    </h3>

                                    <p>

                                        Members who join your
                                        gym online or are added
                                        manually will appear here.

                                    </p>

                                    <a
                                        href="add_member.php"
                                        class="add-member-btn"
                                    >

                                        + Add Your First Member

                                    </a>

                                </div>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>


    </main>


</div>


<script src="../js/owner_members.js"></script>


</body>

</html>
