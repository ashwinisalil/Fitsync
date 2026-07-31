<?php

session_start();


/* =========================================================
   ADMIN LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: ../login.php");
    exit();

}


require_once "../database/connection.php";


/* =========================================================
   GET ADMIN ID
========================================================= */

$admin_id =
    (int) $_SESSION["user_id"];


/* =========================================================
   SUMMARY VARIABLES
========================================================= */

$total_transfers = 0;

$pending_settlements = 0;

$paid_settlements = 0;

$completed_settlements = 0;

$total_settlement_amount = 0;

$total_transfer_fees = 0;

$gymconnect_share = 0;

$old_gym_share = 0;


/* =========================================================
   GET TRANSFER SUMMARY
========================================================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_transfers
    FROM membership_transfers
");

$stmt->execute();

$result =
    $stmt->get_result();

$row =
    $result->fetch_assoc();

$total_transfers =
    (int)
    ($row["total_transfers"] ?? 0);

$stmt->close();


/* =========================================================
   GET SETTLEMENT SUMMARY
========================================================= */

$stmt = $conn->prepare("
    SELECT

        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending_settlements,


        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'Paid'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS paid_settlements,


        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'Completed'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS completed_settlements,


        COALESCE(
            SUM(amount),
            0
        ) AS total_settlement_amount

    FROM membership_transfer_settlements
");


$stmt->execute();


$result =
    $stmt->get_result();


$row =
    $result->fetch_assoc();


$pending_settlements =
    (int)
    ($row["pending_settlements"] ?? 0);


$paid_settlements =
    (int)
    ($row["paid_settlements"] ?? 0);


$completed_settlements =
    (int)
    ($row["completed_settlements"] ?? 0);


$total_settlement_amount =
    (float)
    ($row["total_settlement_amount"] ?? 0);


$stmt->close();


/* =========================================================
   GET TRANSFER FEE SUMMARY
=========================================================

   IMPORTANT:

   This section assumes your membership_transfers table
   contains:

   transfer_fee
   gymconnect_share
   old_gym_share

   If your table uses different column names,
   tell me your exact structure and I will adjust it.
========================================================= */


$stmt = $conn->prepare("
    SELECT

        COALESCE(
            SUM(transfer_fee),
            0
        ) AS total_transfer_fees,


        COALESCE(
            SUM(gymconnect_share),
            0
        ) AS gymconnect_share,


        COALESCE(
            SUM(old_gym_share),
            0
        ) AS old_gym_share

    FROM membership_transfers
");


if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();


    $total_transfer_fees =
        (float)
        ($row["total_transfer_fees"] ?? 0);


    $gymconnect_share =
        (float)
        ($row["gymconnect_share"] ?? 0);


    $old_gym_share =
        (float)
        ($row["old_gym_share"] ?? 0);


    $stmt->close();

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

    Transfer Settlements | Admin

</title>


<link
    rel="stylesheet"
    href="../css/admin_transfer_settlements.css"
>


</head>


<body>


<div class="page-container">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->


    <div class="page-header">


        <div>


            <a
                href="dashboard.php"
                class="back-btn"
            >

                ← Back to Admin Dashboard

            </a>


            <h1>

                Membership Transfer Settlements

            </h1>


            <p>

                Monitor membership transfers,
                transfer fees and settlement payments
                across GymConnect.

            </p>


        </div>


    </div>



    <!-- =================================================
         TRANSFER SUMMARY
    ================================================== -->


    <div class="summary-grid">


        <div class="summary-card">


            <span>

                Total Transfers

            </span>


            <strong>

                <?php

                echo
                    $total_transfers;

                ?>

            </strong>


            <small>

                All membership transfer requests

            </small>


        </div>



        <div class="summary-card">


            <span>

                Pending Settlements

            </span>


            <strong>

                <?php

                echo
                    $pending_settlements;

                ?>

            </strong>


            <small>

                Waiting for old gym payment

            </small>


        </div>



        <div class="summary-card">


            <span>

                Paid Settlements

            </span>


            <strong>

                <?php

                echo
                    $paid_settlements;

                ?>

            </strong>


            <small>

                Waiting for new gym confirmation

            </small>


        </div>



        <div class="summary-card">


            <span>

                Completed Settlements

            </span>


            <strong>

                <?php

                echo
                    $completed_settlements;

                ?>

            </strong>


            <small>

                Successfully completed

            </small>


        </div>


    </div>



    <!-- =================================================
         FINANCIAL SUMMARY
    ================================================== -->


    <div class="financial-grid">


        <div class="financial-card">


            <span>

                Total Transfer Fees

            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $total_transfer_fees,
                    2
                );

                ?>

            </strong>


            <small>

                Total ₹500 transfer fees collected

            </small>


        </div>



        <div class="financial-card">


            <span>

                GymConnect Revenue

            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $gymconnect_share,
                    2
                );

                ?>

            </strong>


            <small>

                GymConnect's share from transfer fees

            </small>


        </div>



        <div class="financial-card">


            <span>

                Previous Gym Share

            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $old_gym_share,
                    2
                );

                ?>

            </strong>


            <small>

                Amount shared with previous gyms

            </small>


        </div>



        <div class="financial-card">


            <span>

                Settlement Value

            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $total_settlement_amount,
                    2
                );

                ?>

            </strong>


            <small>

                Total value payable between gyms

            </small>


        </div>


    </div>



    <!-- =================================================
         TRANSFER RECORDS
    ================================================== -->


    <div class="records-section">


        <div class="section-header">


            <div>


                <h2>

                    Transfer Settlement Records

                </h2>


                <p>

                    All financial settlement records
                    related to membership transfers.

                </p>


            </div>


        </div>



        <?php


        /* ================================================
           GET ALL SETTLEMENT RECORDS
        ================================================= */


        $stmt = $conn->prepare("

            SELECT

                mts.settlement_id,

                mts.transfer_id,

                mts.membership_id,

                mts.amount,

                mts.payment_status,

                mts.payment_method,

                mts.transaction_reference,

                mts.paid_at,

                mts.created_at,


                mt.member_id,

                mt.reason,


                u.full_name AS member_name,

                u.email AS member_email,


                fg.gym_name AS from_gym_name,

                tg.gym_name AS to_gym_name,


                mp.plan_name

            FROM membership_transfer_settlements mts


            INNER JOIN membership_transfers mt

                ON mts.transfer_id =
                   mt.transfer_id


            INNER JOIN users u

                ON mt.member_id =
                   u.user_id


            INNER JOIN gyms fg

                ON mts.from_gym_id =
                   fg.gym_id


            INNER JOIN gyms tg

                ON mts.to_gym_id =
                   tg.gym_id


            INNER JOIN memberships m

                ON mts.membership_id =
                   m.membership_id


            INNER JOIN membership_plans mp

                ON m.plan_id =
                   mp.plan_id


            ORDER BY
                mts.created_at DESC

        ");


        $stmt->execute();


        $result =
            $stmt->get_result();


        ?>


        <?php if (
            $result->num_rows === 0
        ): ?>


            <!-- =========================================
                 EMPTY STATE
            ========================================== -->


            <div class="empty-state">


                <div class="empty-icon">

                    ✓

                </div>


                <h3>

                    No Settlement Records

                </h3>


                <p>

                    No membership transfer settlements
                    have been created yet.

                </p>


            </div>


        <?php else: ?>


            <!-- =========================================
                 TABLE
            ========================================== -->


            <div class="table-wrapper">


                <table>


                    <thead>


                        <tr>


                            <th>

                                Transfer

                            </th>


                            <th>

                                Member

                            </th>


                            <th>

                                From Gym

                            </th>


                            <th>

                                To Gym

                            </th>


                            <th>

                                Plan

                            </th>


                            <th>

                                Settlement

                            </th>


                            <th>

                                Status

                            </th>


                            <th>

                                Payment

                            </th>


                            <th>

                                Date

                            </th>


                        </tr>


                    </thead>


                    <tbody>


                        <?php while (
                            $settlement =
                            $result->fetch_assoc()
                        ): ?>


                            <tr>


                                <!-- =====================
                                     TRANSFER
                                ====================== -->


                                <td>


                                    <strong>

                                        #<?php

                                        echo
                                            (int)
                                            $settlement[
                                                "transfer_id"
                                            ];

                                        ?>

                                    </strong>


                                </td>



                                <!-- =====================
                                     MEMBER
                                ====================== -->


                                <td>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $settlement[
                                                "member_name"
                                            ]
                                        );

                                        ?>

                                    </strong>


                                    <small>

                                        <?php

                                        echo htmlspecialchars(
                                            $settlement[
                                                "member_email"
                                            ]
                                        );

                                        ?>

                                    </small>


                                </td>



                                <!-- =====================
                                     FROM GYM
                                ====================== -->


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "from_gym_name"
                                        ]
                                    );

                                    ?>

                                </td>



                                <!-- =====================
                                     TO GYM
                                ====================== -->


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "to_gym_name"
                                        ]
                                    );

                                    ?>

                                </td>



                                <!-- =====================
                                     PLAN
                                ====================== -->


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "plan_name"
                                        ]
                                    );

                                    ?>

                                </td>



                                <!-- =====================
                                     AMOUNT
                                ====================== -->


                                <td>


                                    <strong>

                                        ₹<?php

                                        echo number_format(
                                            (float)
                                            $settlement[
                                                "amount"
                                            ],
                                            2
                                        );

                                        ?>

                                    </strong>


                                </td>



                                <!-- =====================
                                     STATUS
                                ====================== -->


                                <td>


                                    <?php

                                    $status =
                                        $settlement[
                                            "payment_status"
                                        ];

                                    ?>


                                    <span
                                        class="
                                            status-badge
                                            status-<?php

                                            echo strtolower(
                                                $status
                                            );

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



                                <!-- =====================
                                     PAYMENT
                                ====================== -->


                                <td>


                                    <?php if (
                                        !empty(
                                            $settlement[
                                                "transaction_reference"
                                            ]
                                        )
                                    ): ?>


                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $settlement[
                                                    "payment_method"
                                                ]
                                            );

                                            ?>

                                        </strong>


                                        <small>

                                            <?php

                                            echo htmlspecialchars(
                                                $settlement[
                                                    "transaction_reference"
                                                ]
                                            );

                                            ?>

                                        </small>


                                    <?php else: ?>


                                        <span class="not-paid">

                                            Not Paid

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- =====================
                                     DATE
                                ====================== -->


                                <td>


                                    <small>

                                        <?php

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $settlement[
                                                    "created_at"
                                                ]
                                            )
                                        );

                                        ?>

                                    </small>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>


                </table>


            </div>


        <?php endif; ?>


        <?php

        $stmt->close();

        ?>


    </div>


</div>


</body>


</html>