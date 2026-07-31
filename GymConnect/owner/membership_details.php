<?php

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


require_once "../database/connection.php";


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET MEMBERSHIP ID
========================================================= */

$membership_id = (int) (
    $_GET["id"] ?? 0
);


if ($membership_id <= 0) {

    die("Invalid membership selected.");

}


/* =========================================================
   GET MEMBERSHIP DETAILS
   AND VERIFY GYM BELONGS TO OWNER
========================================================= */

$stmt = $conn->prepare("
    SELECT

        m.membership_id,

        m.member_id,

        m.gym_id,

        m.plan_id,

        m.start_date,

        m.end_date,

        m.status,

        u.full_name AS member_name,

        u.email AS member_email,

        u.phone AS member_phone,

        g.gym_name,

        g.address AS gym_address,

        g.city AS gym_city,

        g.state AS gym_state,

        g.country AS gym_country,

        g.phone AS gym_phone,

        g.email AS gym_email,

        mp.plan_name,

        mp.duration_months,

        mp.price,

        mp.description AS plan_description

    FROM memberships m

    INNER JOIN users u
        ON m.member_id = u.user_id

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    INNER JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE m.membership_id = ?

    AND g.owner_id = ?

    LIMIT 1
");


if (!$stmt) {

    die(
        "Unable to load membership details."
    );

}


$stmt->bind_param(
    "ii",
    $membership_id,
    $owner_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    die(
        "Membership not found or you do not have permission to view it."
    );

}


$membership =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   AUTOMATIC DISPLAY STATUS
========================================================= */

$display_status =
    $membership["status"];


if (
    $display_status === "Active" &&
    strtotime(
        $membership["end_date"]
    ) < strtotime(
        date("Y-m-d")
    )
) {

    $display_status =
        "Expired";

}


/* =========================================================
   GET PAYMENT HISTORY
========================================================= */

$payments = [];


$payment_stmt = $conn->prepare("
    SELECT

        payment_id,

        amount,

        payment_type,

        payment_method,

        transaction_id,

        payment_status,

        payment_date,

        notes

    FROM payments

    WHERE membership_id = ?

    AND gym_id = ?

    AND member_id = ?

    ORDER BY payment_date DESC
");


if ($payment_stmt) {

    $payment_stmt->bind_param(
        "iii",
        $membership_id,
        $membership["gym_id"],
        $membership["member_id"]
    );

    $payment_stmt->execute();

    $payment_result =
        $payment_stmt->get_result();


    while (
        $payment_row =
        $payment_result->fetch_assoc()
    ) {

        $payments[] =
            $payment_row;

    }


    $payment_stmt->close();

}


/* =========================================================
   CALCULATE TOTAL PAYMENTS
========================================================= */

$total_paid = 0;


foreach (
    $payments
    as $payment
) {

    if (
        $payment["payment_status"] ===
        "Completed"
    ) {

        $total_paid +=
            (float) $payment["amount"];

    }

}

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
    Membership Details | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/membership_details.css"
>


</head>


<body>


<div class="details-page">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">


        <div>

            <h1>
                Membership Details
            </h1>

            <p>
                View complete membership information
            </p>

        </div>


        <div class="header-actions">


            <a
                href="memberships.php"
                class="back-btn"
            >

                ← Back to Memberships

            </a>


            <a
                href="dashboard.php"
                class="dashboard-btn"
            >

                Dashboard

            </a>


        </div>


    </div>



    <!-- =================================================
         MEMBERSHIP STATUS HEADER
    ================================================== -->

    <div class="membership-header-card">


        <div class="membership-title">


            <div class="membership-icon">

                M

            </div>


            <div>


                <h2>

                    <?php

                    echo htmlspecialchars(
                        $membership[
                            "plan_name"
                        ]
                    );

                    ?>

                </h2>


                <p>

                    Membership ID:

                    #

                    <?php

                    echo $membership[
                        "membership_id"
                    ];

                    ?>

                </p>


            </div>


        </div>



        <div>


            <span
                class="
                status-badge
                <?php
                echo strtolower(
                    $display_status
                );
                ?>
                "
            >

                <?php

                echo htmlspecialchars(
                    $display_status
                );

                ?>

            </span>


        </div>


    </div>



    <!-- =================================================
         MAIN GRID
    ================================================== -->

    <div class="details-grid">


        <!-- =================================================
             MEMBER INFORMATION
        ================================================== -->

        <div class="details-card">


            <div class="card-header">

                <h2>
                    Member Information
                </h2>

            </div>


            <div class="info-list">


                <div class="info-row">

                    <span>
                        Full Name
                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                "member_name"
                            ]
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        Email
                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                "member_email"
                            ]
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        Phone
                    </span>

                    <strong>

                        <?php

                        echo !empty(
                            $membership[
                                "member_phone"
                            ]
                        )
                        ? htmlspecialchars(
                            $membership[
                                "member_phone"
                            ]
                        )
                        : "Not available";

                        ?>

                    </strong>

                </div>


            </div>


        </div>



        <!-- =================================================
             GYM INFORMATION
        ================================================== -->

        <div class="details-card">


            <div class="card-header">

                <h2>
                    Gym Information
                </h2>

            </div>


            <div class="info-list">


                <div class="info-row">

                    <span>
                        Gym Name
                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                "gym_name"
                            ]
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        Address
                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                "gym_address"
                            ]
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        City
                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                "gym_city"
                            ]
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        Phone
                    </span>

                    <strong>

                        <?php

                        echo !empty(
                            $membership[
                                "gym_phone"
                            ]
                        )
                        ? htmlspecialchars(
                            $membership[
                                "gym_phone"
                            ]
                        )
                        : "Not available";

                        ?>

                    </strong>

                </div>


            </div>


        </div>



        <!-- =================================================
             MEMBERSHIP PLAN
        ================================================== -->

        <div class="details-card">


            <div class="card-header">

                <h2>
                    Membership Plan
                </h2>

            </div>


            <div class="info-list">


                <div class="info-row">

                    <span>
                        Plan Name
                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                "plan_name"
                            ]
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        Duration
                    </span>

                    <strong>

                        <?php

                        echo $membership[
                            "duration_months"
                        ];

                        ?>

                        Month(s)

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        Plan Price
                    </span>

                    <strong class="price">

                        ₹<?php

                        echo number_format(
                            (float)
                            $membership[
                                "price"
                            ],
                            2
                        );

                        ?>

                    </strong>

                </div>


                <?php if (
                    !empty(
                        $membership[
                            "plan_description"
                        ]
                    )
                ): ?>


                    <div class="description">

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $membership[
                                    "plan_description"
                                ]
                            )
                        );

                        ?>

                    </div>


                <?php endif; ?>


            </div>


        </div>



        <!-- =================================================
             MEMBERSHIP DATES
        ================================================== -->

        <div class="details-card">


            <div class="card-header">

                <h2>
                    Membership Period
                </h2>

            </div>


            <div class="info-list">


                <div class="info-row">

                    <span>
                        Start Date
                    </span>

                    <strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $membership[
                                    "start_date"
                                ]
                            )
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        End Date
                    </span>

                    <strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $membership[
                                    "end_date"
                                ]
                            )
                        );

                        ?>

                    </strong>

                </div>


                <div class="info-row">

                    <span>
                        Current Status
                    </span>


                    <span
                        class="
                        status-badge
                        <?php
                        echo strtolower(
                            $display_status
                        );
                        ?>
                        "
                    >

                        <?php

                        echo htmlspecialchars(
                            $display_status
                        );

                        ?>

                    </span>


                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         PAYMENT SUMMARY
    ================================================== -->

    <div class="payment-summary-card">


        <div>

            <span>
                Total Payments Received
            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $total_paid,
                    2
                );

                ?>

            </strong>

        </div>


        <div>

            <span>
                Payment Records
            </span>


            <strong>

                <?php

                echo count(
                    $payments
                );

                ?>

            </strong>

        </div>


    </div>



    <!-- =================================================
         PAYMENT HISTORY
    ================================================== -->

    <div class="payment-card">


        <div class="card-header">


            <div>

                <h2>
                    Payment History
                </h2>

                <p>
                    Payments associated with this membership
                </p>

            </div>


        </div>



        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Payment ID
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Payment Type
                        </th>

                        <th>
                            Method
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Payment Date
                        </th>

                        <th>
                            Transaction ID
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    count($payments) > 0
                ): ?>


                    <?php foreach (
                        $payments
                        as $payment
                    ): ?>


                        <tr>


                            <td>

                                #

                                <?php

                                echo $payment[
                                    "payment_id"
                                ];

                                ?>

                            </td>


                            <td class="amount">

                                ₹<?php

                                echo number_format(
                                    (float)
                                    $payment[
                                        "amount"
                                    ],
                                    2
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $payment[
                                        "payment_type"
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $payment[
                                        "payment_method"
                                    ]
                                );

                                ?>

                            </td>


                            <td>


                                <span
                                    class="
                                    payment-status
                                    <?php
                                    echo strtolower(
                                        $payment[
                                            "payment_status"
                                        ]
                                    );
                                    ?>
                                    "
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $payment[
                                            "payment_status"
                                        ]
                                    );

                                    ?>

                                </span>


                            </td>


                            <td>

                                <?php

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $payment[
                                            "payment_date"
                                        ]
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo !empty(
                                    $payment[
                                        "transaction_id"
                                    ]
                                )
                                ? htmlspecialchars(
                                    $payment[
                                        "transaction_id"
                                    ]
                                )
                                : "—";

                                ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>


                        <td
                            colspan="7"
                            class="no-records"
                        >

                            No payment records found
                            for this membership.

                        </td>


                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>

    <div>
        <!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>

</div>
<!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>

<!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>

<!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>

<!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>

<<!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>
<!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>
    <!-- =================================================
     UPDATE MEMBERSHIP STATUS
================================================== -->

<div class="status-update-card">

    <div class="card-header">

        <h2>
            Update Membership Status
        </h2>

    </div>


    <form
        action="update_membership_status.php"
        method="POST"
        onsubmit="return confirm('Are you sure you want to change this membership status?');"
    >

        <input
            type="hidden"
            name="membership_id"
            value="<?php
            echo $membership["membership_id"];
            ?>"
        >


        <div class="status-update-content">


            <div class="status-select-group">

                <label>
                    Select New Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option value="">
                        Select Status
                    </option>


                    <option value="Active">
                        Active
                    </option>


                    <option value="Expired">
                        Expired
                    </option>


                    <option value="Transferred">
                        Transferred
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-status-btn"
            >

                Update Status

            </button>


        </div>

    </form>




                </div>


    <!-- =================================================
         BOTTOM ACTION
    ================================================== -->

    <div class="bottom-actions">


        <a
            href="memberships.php"
            class="back-btn"
        >

            ← Back to All Memberships

        </a>


    </div>


</div>


</body>

</html>