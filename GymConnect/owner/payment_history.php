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
   GET FILTER VALUES
========================================================= */

$gym_id = isset($_GET["gym_id"])
    ? (int) $_GET["gym_id"]
    : 0;

$payment_method =
    trim($_GET["payment_method"] ?? "");

$payment_type =
    trim($_GET["payment_type"] ?? "");

$payment_status =
    trim($_GET["payment_status"] ?? "");

$from_date =
    trim($_GET["from_date"] ?? "");

$to_date =
    trim($_GET["to_date"] ?? "");

$search =
    trim($_GET["search"] ?? "");


/* =========================================================
   GET OWNER'S GYMS
========================================================= */

$gyms = [];

$stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name

    FROM gyms

    WHERE owner_id = ?

    ORDER BY gym_name ASC
");

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

while (
    $row = $result->fetch_assoc()
) {

    $gyms[] = $row;

}

$stmt->close();


/* =========================================================
   BUILD PAYMENT QUERY
========================================================= */

$sql = "

    SELECT

        p.payment_id,

        p.membership_id,

        p.amount,

        p.payment_type,

        p.payment_method,

        p.transaction_id,

        p.payment_status,

        p.payment_date,

        p.notes,

        p.recorded_by,

        u.full_name AS member_name,

        u.email AS member_email,

        g.gym_name,

        mp.plan_name

    FROM payments p


    INNER JOIN gyms g

        ON p.gym_id = g.gym_id


    LEFT JOIN users u

        ON p.member_id = u.user_id


    LEFT JOIN memberships m

        ON p.membership_id =
           m.membership_id


    LEFT JOIN membership_plans mp

        ON m.plan_id =
           mp.plan_id


    WHERE g.owner_id = ?

";


$params = [
    $owner_id
];

$types = "i";


/* =========================================================
   FILTER BY GYM
========================================================= */

if (
    $gym_id > 0
) {

    $sql .= "
        AND p.gym_id = ?
    ";

    $types .= "i";

    $params[] =
        $gym_id;

}


/* =========================================================
   FILTER BY PAYMENT METHOD
========================================================= */

if (
    $payment_method !== ""
) {

    $sql .= "
        AND p.payment_method = ?
    ";

    $types .= "s";

    $params[] =
        $payment_method;

}


/* =========================================================
   FILTER BY PAYMENT TYPE
========================================================= */

if (
    $payment_type !== ""
) {

    $sql .= "
        AND p.payment_type = ?
    ";

    $types .= "s";

    $params[] =
        $payment_type;

}


/* =========================================================
   FILTER BY PAYMENT STATUS
========================================================= */

if (
    $payment_status !== ""
) {

    $sql .= "
        AND p.payment_status = ?
    ";

    $types .= "s";

    $params[] =
        $payment_status;

}


/* =========================================================
   FILTER FROM DATE
========================================================= */

if (
    $from_date !== ""
) {

    $sql .= "
        AND DATE(p.payment_date) >= ?
    ";

    $types .= "s";

    $params[] =
        $from_date;

}


/* =========================================================
   FILTER TO DATE
========================================================= */

if (
    $to_date !== ""
) {

    $sql .= "
        AND DATE(p.payment_date) <= ?
    ";

    $types .= "s";

    $params[] =
        $to_date;

}


/* =========================================================
   SEARCH
========================================================= */

if (
    $search !== ""
) {

    $sql .= "

        AND (

            u.full_name LIKE ?

            OR u.email LIKE ?

            OR p.transaction_id LIKE ?

        )

    ";

    $search_value =
        "%" . $search . "%";

    $types .= "sss";

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;

}


/* =========================================================
   ORDER
========================================================= */

$sql .= "

    ORDER BY
        p.payment_date DESC

";


/* =========================================================
   PREPARE QUERY
========================================================= */

$stmt = $conn->prepare(
    $sql
);


/* =========================================================
   BIND PARAMETERS
========================================================= */

$stmt->bind_param(
    $types,
    ...$params
);


/* =========================================================
   EXECUTE
========================================================= */

$stmt->execute();

$payments =
    $stmt->get_result();

$stmt->close();


/* =========================================================
   TOTAL FILTERED REVENUE
========================================================= */

$total_filtered_revenue = 0;

$payment_count = 0;


/* =========================================================
   STORE PAYMENT ROWS

   We need the rows for displaying the table
   and calculating the total.
========================================================= */

$payment_rows = [];


while (
    $row =
    $payments->fetch_assoc()
) {

    $payment_rows[] =
        $row;


    if (
        $row["payment_status"]
        === "Completed"
    ) {

        $total_filtered_revenue +=
            (float) $row["amount"];

    }


    $payment_count++;

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
    Payment History | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/payment_history.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

</head>


<body>


<div class="payment-history-container">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="page-header">


        <div>

            <h1>
                Payment History
            </h1>

            <p>
                View and manage payment records
                from all your gyms.
            </p>

        </div>


        <div class="header-actions">


            <a
                href="payments.php"
                class="back-btn"
            >

                ← Payments Dashboard

            </a>


        </div>


    </div>



    <!-- =================================================
         SUMMARY
    ================================================== -->

    <div class="history-summary">


        <div class="summary-card">


            <span>
                Total Records
            </span>


            <strong>

                <?php

                echo $payment_count;

                ?>

            </strong>


        </div>



        <div class="summary-card">


            <span>
                Completed Revenue
            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $total_filtered_revenue,
                    2
                );

                ?>

            </strong>


        </div>


    </div>



    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="filter-card">


        <form
            method="GET"
            action="payment_history.php"
        >


            <!-- SEARCH -->

            <div class="filter-group">


                <label>
                    Search
                </label>


                <input
                    type="text"
                    name="search"
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                    placeholder="Member name, email or transaction ID"
                >


            </div>



            <!-- GYM -->

            <div class="filter-group">


                <label>
                    Gym
                </label>


                <select
                    name="gym_id"
                >


                    <option value="">

                        All Gyms

                    </option>


<?php

foreach (
    $gyms as $gym
) {

?>


                    <option
                        value="<?php
                        echo $gym["gym_id"];
                        ?>"
                        <?php

                        if (
                            $gym_id ==
                            $gym["gym_id"]
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        <?php

                        echo htmlspecialchars(
                            $gym["gym_name"]
                        );

                        ?>

                    </option>


<?php

}

?>


                </select>


            </div>



            <!-- PAYMENT METHOD -->

            <div class="filter-group">


                <label>
                    Payment Method
                </label>


                <select
                    name="payment_method"
                >


                    <option value="">

                        All Methods

                    </option>


                    <option
                        value="Cash"
                        <?php

                        if (
                            $payment_method
                            === "Cash"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Cash

                    </option>


                    <option
                        value="UPI"
                        <?php

                        if (
                            $payment_method
                            === "UPI"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        UPI

                    </option>


                    <option
                        value="Card"
                        <?php

                        if (
                            $payment_method
                            === "Card"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Card

                    </option>


                    <option
                        value="Net Banking"
                        <?php

                        if (
                            $payment_method
                            === "Net Banking"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Net Banking

                    </option>


                    <option
                        value="Online"
                        <?php

                        if (
                            $payment_method
                            === "Online"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Online

                    </option>


                </select>


            </div>



            <!-- PAYMENT TYPE -->

            <div class="filter-group">


                <label>
                    Payment Type
                </label>


                <select
                    name="payment_type"
                >


                    <option value="">

                        All Types

                    </option>


                    <option
                        value="Membership"
                        <?php

                        if (
                            $payment_type
                            === "Membership"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Membership

                    </option>


                    <option
                        value="Renewal"
                        <?php

                        if (
                            $payment_type
                            === "Renewal"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Renewal

                    </option>


                    <option
                        value="Transfer"
                        <?php

                        if (
                            $payment_type
                            === "Transfer"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Transfer

                    </option>


                </select>


            </div>



            <!-- STATUS -->

            <div class="filter-group">


                <label>
                    Status
                </label>


                <select
                    name="payment_status"
                >


                    <option value="">

                        All Status

                    </option>


                    <option
                        value="Completed"
                        <?php

                        if (
                            $payment_status
                            === "Completed"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Completed

                    </option>


                    <option
                        value="Pending"
                        <?php

                        if (
                            $payment_status
                            === "Pending"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Pending

                    </option>


                    <option
                        value="Failed"
                        <?php

                        if (
                            $payment_status
                            === "Failed"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Failed

                    </option>


                    <option
                        value="Refunded"
                        <?php

                        if (
                            $payment_status
                            === "Refunded"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Refunded

                    </option>


                </select>


            </div>



            <!-- FROM DATE -->

            <div class="filter-group">


                <label>
                    From Date
                </label>


                <input
                    type="date"
                    name="from_date"
                    value="<?php
                    echo htmlspecialchars(
                        $from_date
                    );
                    ?>"
                >


            </div>



            <!-- TO DATE -->

            <div class="filter-group">


                <label>
                    To Date
                </label>


                <input
                    type="date"
                    name="to_date"
                    value="<?php
                    echo htmlspecialchars(
                        $to_date
                    );
                    ?>"
                >


            </div>



            <!-- BUTTONS -->

            <div class="filter-actions">


                <button
                    type="submit"
                    class="filter-btn"
                >

                    Apply Filters

                </button>


                <a
                    href="payment_history.php"
                    class="clear-btn"
                >

                    Clear

                </a>


            </div>


        </form>


    </div>



    <!-- =================================================
         PAYMENT TABLE
    ================================================== -->

    <div class="payment-table-card">


        <div class="table-header">


            <h2>
                Payment Records
            </h2>


            <span>

                <?php

                echo $payment_count;

                ?>

                Records

            </span>


        </div>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            #
                        </th>

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
                            Transaction ID
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Payment Date
                        </th>

                    </tr>

                </thead>


                <tbody>


<?php

if (
    count($payment_rows) > 0
) {


    $counter = 1;


    foreach (
        $payment_rows as $payment
    ) {


?>


                    <tr>


                        <!-- NUMBER -->

                        <td>

                            <?php

                            echo $counter++;

                            ?>

                        </td>



                        <!-- MEMBER -->

                        <td>


                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $payment[
                                        "member_name"
                                    ]
                                    ??
                                    "Unknown"
                                );

                                ?>

                            </strong>


                            <small>

                                <?php

                                echo htmlspecialchars(
                                    $payment[
                                        "member_email"
                                    ]
                                    ??
                                    ""
                                );

                                ?>

                            </small>


                        </td>



                        <!-- GYM -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment[
                                    "gym_name"
                                ]
                            );

                            ?>

                        </td>



                        <!-- PLAN -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment[
                                    "plan_name"
                                ]
                                ??
                                "N/A"
                            );

                            ?>

                        </td>



                        <!-- AMOUNT -->

                        <td>

                            <strong>

                                ₹<?php

                                echo number_format(
                                    $payment[
                                        "amount"
                                    ],
                                    2
                                );

                                ?>

                            </strong>

                        </td>



                        <!-- TYPE -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment[
                                    "payment_type"
                                ]
                            );

                            ?>

                        </td>



                        <!-- METHOD -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $payment[
                                    "payment_method"
                                ]
                            );

                            ?>

                        </td>



                        <!-- TRANSACTION ID -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $payment[
                                        "transaction_id"
                                    ]
                                )
                            ) {

                                echo htmlspecialchars(
                                    $payment[
                                        "transaction_id"
                                    ]
                                );

                            } else {

                                echo "—";

                            }

                            ?>

                        </td>



                        <!-- STATUS -->

                        <td>


                            <span
                                class="status
                                status-<?php

                                echo strtolower(
                                    $payment[
                                        "payment_status"
                                    ]
                                );

                                ?>"
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



                        <!-- DATE -->

                        <td>

                            <?php

                            echo date(
                                "d M Y",
                                strtotime(
                                    $payment[
                                        "payment_date"
                                    ]
                                )
                            );

                            ?>

                            <small>

                                <?php

                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $payment[
                                            "payment_date"
                                        ]
                                    )
                                );

                                ?>

                            </small>

                        </td>


                    </tr>


<?php

    }


} else {


?>


                    <tr>

                        <td
                            colspan="10"
                            class="no-records"
                        >

                            No payment records found.

                        </td>

                    </tr>


<?php

}

?>


                </tbody>


            </table>


        </div>


    </div>



    <!-- =================================================
         BACK
    ================================================== -->

    <div class="bottom-actions">


        <a
            href="payments.php"
        >

            ← Back to Payments Dashboard

        </a>


        <a
            href="dashboard.php"
        >

            Back to Dashboard

        </a>


    </div>


</div>


</body>

</html>


<?php

$conn->close();

?>