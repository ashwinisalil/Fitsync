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


/* =========================================================
   GET OWNER'S GYMS
========================================================= */

$gymRevenue = [];

$stmt = $conn->prepare("
    SELECT
        g.gym_id,
        g.gym_name,

        COALESCE(
            SUM(
                CASE
                    WHEN p.payment_status = 'Completed'
                    THEN p.amount
                    ELSE 0
                END
            ),
            0
        ) AS total_revenue,

        COUNT(
            CASE
                WHEN p.payment_status = 'Completed'
                THEN p.payment_id
            END
        ) AS total_payments

    FROM gyms g

    LEFT JOIN payments p
        ON g.gym_id = p.gym_id

    WHERE g.owner_id = ?

    GROUP BY
        g.gym_id,
        g.gym_name

    ORDER BY
        total_revenue DESC
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $gymRevenue[] = $row;

}

$stmt->close();


/* =========================================================
   TOTAL REVENUE
========================================================= */

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(p.amount), 0) AS total_revenue

    FROM payments p

    INNER JOIN gyms g
        ON p.gym_id = g.gym_id

    WHERE g.owner_id = ?

    AND p.payment_status = 'Completed'
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_revenue =
    (float) ($row["total_revenue"] ?? 0);

$stmt->close();


/* =========================================================
   ONLINE REVENUE

   Everything except Cash
========================================================= */

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(p.amount), 0) AS online_revenue

    FROM payments p

    INNER JOIN gyms g
        ON p.gym_id = g.gym_id

    WHERE g.owner_id = ?

    AND p.payment_status = 'Completed'

    AND p.payment_method != 'Cash'
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$online_revenue =
    (float) ($row["online_revenue"] ?? 0);

$stmt->close();


/* =========================================================
   CASH REVENUE
========================================================= */

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(p.amount), 0) AS cash_revenue

    FROM payments p

    INNER JOIN gyms g
        ON p.gym_id = g.gym_id

    WHERE g.owner_id = ?

    AND p.payment_status = 'Completed'

    AND p.payment_method = 'Cash'
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$cash_revenue =
    (float) ($row["cash_revenue"] ?? 0);

$stmt->close();


/* =========================================================
   THIS MONTH REVENUE
========================================================= */

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(p.amount), 0) AS monthly_revenue

    FROM payments p

    INNER JOIN gyms g
        ON p.gym_id = g.gym_id

    WHERE g.owner_id = ?

    AND p.payment_status = 'Completed'

    AND MONTH(p.payment_date) =
        MONTH(CURRENT_DATE())

    AND YEAR(p.payment_date) =
        YEAR(CURRENT_DATE())
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$monthly_revenue =
    (float) ($row["monthly_revenue"] ?? 0);

$stmt->close();


/* =========================================================
   RECENT PAYMENTS
========================================================= */

$stmt = $conn->prepare("
    SELECT

        p.payment_id,

        p.amount,

        p.payment_type,

        p.payment_method,

        p.payment_status,

        p.payment_date,

        u.full_name AS member_name,

        g.gym_name,

        mp.plan_name

    FROM payments p

    INNER JOIN gyms g
        ON p.gym_id = g.gym_id

    LEFT JOIN users u
        ON p.member_id = u.user_id

    LEFT JOIN memberships m
        ON p.membership_id = m.membership_id

    LEFT JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE g.owner_id = ?

    ORDER BY
        p.payment_date DESC

    LIMIT 10
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$recentPayments =
    $stmt->get_result();

$stmt->close();

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
    Payments & Revenue | GymConnect
</title>

<link
    rel="stylesheet"
    href="../css/payments.css"
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

</head>


<body>


<div class="payment-container">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="payment-header">

        <div>

            <h1>
                Payments & Revenue
            </h1>

            <p>
                Manage payments and revenue from all your gyms.
            </p>

        </div>


        <div class="header-actions">

            <a
                href="payment_history.php"
                class="history-btn"
            >
                Payment History
            </a>

        </div>

    </div>



    <!-- =====================================================
         REVENUE CARDS
    ====================================================== -->

    <section class="revenue-cards">


        <div class="revenue-card">

            <span>
                Total Revenue
            </span>

            <h2>

                ₹<?php

                echo number_format(
                    $total_revenue,
                    2
                );

                ?>

            </h2>

        </div>



        <div class="revenue-card">

            <span>
                Online Payments
            </span>

            <h2>

                ₹<?php

                echo number_format(
                    $online_revenue,
                    2
                );

                ?>

            </h2>

        </div>



        <div class="revenue-card">

            <span>
                Cash Payments
            </span>

            <h2>

                ₹<?php

                echo number_format(
                    $cash_revenue,
                    2
                );

                ?>

            </h2>

        </div>



        <div class="revenue-card">

            <span>
                This Month
            </span>

            <h2>

                ₹<?php

                echo number_format(
                    $monthly_revenue,
                    2
                );

                ?>

            </h2>

        </div>


    </section>



    <!-- =====================================================
         REVENUE BY GYM
    ====================================================== -->

    <section class="payment-section">


        <div class="section-header">

            <h2>
                Revenue by Gym
            </h2>

        </div>


        <div class="gym-revenue-grid">


<?php

if (
    count($gymRevenue) > 0
) {

    foreach (
        $gymRevenue as $gym
    ) {

?>


            <div class="gym-revenue-card">


                <h3>

                    <?php

                    echo htmlspecialchars(
                        $gym["gym_name"]
                    );

                    ?>

                </h3>


                <p>
                    Total Collection
                </p>


                <strong>

                    ₹<?php

                    echo number_format(
                        $gym["total_revenue"],
                        2
                    );

                    ?>

                </strong>


                <p>

                    <?php

                    echo $gym["total_payments"];

                    ?>

                    Completed Payments

                </p>


                <a
                    href="payment_history.php?gym_id=<?php
                    echo $gym["gym_id"];
                    ?>"
                >

                    View Payment History →

                </a>


            </div>


<?php

    }

} else {

?>


            <div class="empty-message">

                No gyms found.

            </div>


<?php

}

?>


        </div>


    </section>



    <!-- =====================================================
         RECENT PAYMENTS
    ====================================================== -->

    <section class="payment-section">


        <div class="section-header">

            <h2>
                Recent Payments
            </h2>


            <a
                href="payment_history.php"
            >

                View All →

            </a>

        </div>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Member
                        </th>

                        <th>
                            Gym
                        </th>

                        <th>
                            Plan
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Method
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Date
                        </th>

                    </tr>

                </thead>


                <tbody>


<?php

if (
    $recentPayments->num_rows > 0
) {

    while (
        $payment =
        $recentPayments->fetch_assoc()
    ) {

?>


                    <tr>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment["member_name"]
                                ?? "Unknown"
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment["gym_name"]
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment["plan_name"]
                                ?? "N/A"
                            );

                            ?>

                        </td>


                        <td>

                            ₹<?php

                            echo number_format(
                                $payment["amount"],
                                2
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment["payment_type"]
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment["payment_method"]
                            );

                            ?>

                        </td>


                        <td>

                            <span class="status-badge">

                                <?php

                                echo htmlspecialchars(
                                    $payment["payment_status"]
                                );

                                ?>

                            </span>

                        </td>


                        <td>

                            <?php

                            echo date(
                                "d M Y, h:i A",
                                strtotime(
                                    $payment["payment_date"]
                                )
                            );

                            ?>

                        </td>


                    </tr>


<?php

    }

} else {

?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty-table"
                        >

                            No payments recorded yet.

                        </td>

                    </tr>


<?php

}

?>


                </tbody>


            </table>


        </div>


    </section>



    <div class="back-link">

        <a href="dashboard.php">

            ← Back to Dashboard

        </a>

    </div>


</div>


</body>

</html>

<?php

$conn->close();

?>