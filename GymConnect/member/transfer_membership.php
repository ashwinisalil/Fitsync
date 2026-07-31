<?php

session_start();

require_once "../database/connection.php";


/* =========================================================
   MEMBER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'member'
) {
    header("Location: ../login.php");
    exit();
}


$member_id = (int) $_SESSION['user_id'];


/* =========================================================
   VARIABLES
========================================================= */

$membership = null;
$transfer = null;

$error = "";
$success = "";


/* =========================================================
   GET ACTIVE MEMBERSHIP
========================================================= */

$membership_stmt = $conn->prepare("
    SELECT

        m.membership_id,
        m.member_id,
        m.gym_id,
        m.plan_id,
        m.start_date,
        m.end_date,
        m.status,

        g.gym_name,
        g.address,
        g.city,
        g.state,

        mp.plan_name,
        mp.duration_months,
        mp.price

    FROM memberships m

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    LEFT JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE m.member_id = ?

    AND m.status = 'Active'

    ORDER BY m.membership_id DESC

    LIMIT 1
");


if (!$membership_stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}


$membership_stmt->bind_param(
    "i",
    $member_id
);


if (!$membership_stmt->execute()) {

    die(
        "Unable to load membership: " .
        htmlspecialchars(
            $membership_stmt->error
        )
    );

}


$membership_result =
    $membership_stmt->get_result();


if (
    $membership_result->num_rows > 0
) {

    $membership =
        $membership_result->fetch_assoc();

}


$membership_stmt->close();


/* =========================================================
   GET EXISTING TRANSFER REQUEST
========================================================= */

if ($membership) {

    $membership_id =
        (int) $membership['membership_id'];


    $transfer_stmt = $conn->prepare("
        SELECT

            mt.transfer_id,
            mt.membership_id,
            mt.member_id,
            mt.from_gym_id,
            mt.to_gym_id,
            mt.reason,
            mt.transfer_fee,
            mt.original_gym_amount,
            mt.gymconnect_amount,
            mt.remaining_membership_value,
            mt.settlement_status,
            mt.member_payment_id,
            mt.paid_at,
            mt.status,
            mt.payment_status,
            mt.requested_at,
            mt.approved_at,
            mt.completed_at,

            fg.gym_name
                AS from_gym_name,

            tg.gym_name
                AS to_gym_name

        FROM membership_transfers mt

        INNER JOIN gyms fg
            ON mt.from_gym_id = fg.gym_id

        INNER JOIN gyms tg
            ON mt.to_gym_id = tg.gym_id

        WHERE mt.membership_id = ?

        AND mt.member_id = ?

        ORDER BY mt.transfer_id DESC

        LIMIT 1
    ");


    if (!$transfer_stmt) {

        die(
            "Transfer query error: " .
            htmlspecialchars(
                $conn->error
            )
        );

    }


    $transfer_stmt->bind_param(
        "ii",
        $membership_id,
        $member_id
    );


    if (
        !$transfer_stmt->execute()
    ) {

        die(
            "Unable to load transfer request: " .
            htmlspecialchars(
                $transfer_stmt->error
            )
        );

    }


    $transfer_result =
        $transfer_stmt->get_result();


    if (
        $transfer_result->num_rows > 0
    ) {

        $transfer =
            $transfer_result->fetch_assoc();

    }


    $transfer_stmt->close();

}


/* =========================================================
   NORMALIZE STATUS VALUES
========================================================= */

$transfer_status = "";

$payment_status = "";

$settlement_status = "";


if ($transfer) {

    $transfer_status =
        strtolower(
            trim(
                $transfer['status'] ?? ''
            )
        );


    $payment_status =
        strtolower(
            trim(
                $transfer['payment_status'] ?? ''
            )
        );


    $settlement_status =
        strtolower(
            trim(
                $transfer['settlement_status'] ?? ''
            )
        );

}


/* =========================================================
   DETERMINE WHETHER TRANSFER IS ACTIVE
========================================================= */

$active_transfer = false;


if ($transfer) {

    $active_statuses = [

        'pending',

        'approved',

        'awaiting payment',

        'settlement pending',

        'completed'

    ];


    if (
        in_array(
            $transfer_status,
            $active_statuses,
            true
        )
    ) {

        $active_transfer = true;

    }

}


/* =========================================================
   HANDLE CANCEL REQUEST
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset(
        $_POST['cancel_transfer']
    )
) {

    if (!$transfer) {

        $error =
            "No transfer request found.";

    } else {

        if (
            $payment_status === 'paid'
        ) {

            $error =
                "You cannot cancel the transfer after the ₹500 transfer fee has been paid.";

        } elseif (
            $transfer_status === 'completed'
        ) {

            $error =
                "This transfer has already been completed.";

        } else {

            $cancel_stmt =
                $conn->prepare("
                    DELETE FROM membership_transfers

                    WHERE transfer_id = ?

                    AND member_id = ?
                ");


            if (!$cancel_stmt) {

                $error =
                    "Unable to cancel request: " .
                    $conn->error;

            } else {

                $cancel_stmt->bind_param(
                    "ii",
                    $transfer['transfer_id'],
                    $member_id
                );


                if (
                    $cancel_stmt->execute()
                ) {

                    $success =
                        "Transfer request cancelled successfully.";

                    $transfer = null;

                    $active_transfer = false;

                } else {

                    $error =
                        "Unable to cancel transfer request: " .
                        $cancel_stmt->error;

                }


                $cancel_stmt->close();

            }

        }

    }

}


/* =========================================================
   GET OTHER GYMS FOR NEW TRANSFER
========================================================= */

$gyms = [];


if (
    $membership
    &&
    !$active_transfer
) {

    $current_gym_id =
        (int) $membership['gym_id'];


    $gym_stmt = $conn->prepare("
        SELECT

            gym_id,
            gym_name,
            description,
            address,
            city,
            state,
            phone,
            email

        FROM gyms

        WHERE gym_id != ?

        AND status = 'approved'

        AND is_active = 1

        ORDER BY gym_name ASC
    ");


    if ($gym_stmt) {

        $gym_stmt->bind_param(
            "i",
            $current_gym_id
        );


        if (
            $gym_stmt->execute()
        ) {

            $gym_result =
                $gym_stmt->get_result();


            while (
                $gym_row =
                $gym_result->fetch_assoc()
            ) {

                $gyms[] =
                    $gym_row;

            }

        }


        $gym_stmt->close();

    }

}


/* =========================================================
   PROCESS NEW TRANSFER REQUEST
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset(
        $_POST['submit_transfer']
    )
) {

    if (!$membership) {

        $error =
            "You do not have an active membership.";

    } elseif ($active_transfer) {

        $error =
            "You already have an active transfer request.";

    } else {

        $to_gym_id =
            isset(
                $_POST['to_gym_id']
            )
            ? (int)
                $_POST['to_gym_id']
            : 0;


        $reason =
            isset(
                $_POST['reason']
            )
            ? trim(
                $_POST['reason']
            )
            : "";


        if (
            $to_gym_id <= 0
        ) {

            $error =
                "Please select a gym.";

        } elseif (
            $to_gym_id ==
            (int)
            $membership['gym_id']
        ) {

            $error =
                "You cannot transfer to your current gym.";

        } else {


            /* =========================================
               VERIFY DESTINATION GYM
            ========================================== */

            $gym_check =
                $conn->prepare("
                    SELECT gym_id

                    FROM gyms

                    WHERE gym_id = ?

                    AND status = 'approved'

                    AND is_active = 1

                    LIMIT 1
                ");


            if (!$gym_check) {

                $error =
                    "Database error: " .
                    $conn->error;

            } else {

                $gym_check->bind_param(
                    "i",
                    $to_gym_id
                );


                $gym_check->execute();


                $gym_check_result =
                    $gym_check->get_result();


                if (
                    $gym_check_result->num_rows === 0
                ) {

                    $error =
                        "Selected gym is not available.";

                }


                $gym_check->close();

            }


            /* =========================================
               CREATE TRANSFER REQUEST
            ========================================== */

            if (
                $error === ""
            ) {


                /* =====================================
                   TRANSFER FEE
                ====================================== */

                $transfer_fee =
                    500.00;


                /* =====================================
                   GET PLAN PRICE
                ====================================== */

                $plan_price =
                    isset(
                        $membership['price']
                    )
                    ? (float)
                        $membership['price']
                    : 0.00;


                /* =====================================
                   GET PLAN DURATION
                ====================================== */

                $duration_months =
                    isset(
                        $membership[
                            'duration_months'
                        ]
                    )
                    ? (int)
                        $membership[
                            'duration_months'
                        ]
                    : 0;


                /* =====================================
                   GET MEMBERSHIP DATES
                ====================================== */

                $start_date =
                    new DateTime(
                        $membership[
                            'start_date'
                        ]
                    );


                $end_date =
                    new DateTime(
                        $membership[
                            'end_date'
                        ]
                    );


                $today =
                    new DateTime();


                /* =====================================
                   CALCULATE REMAINING MONTHS
                ====================================== */

                $remaining_months = 0;


                /*
                 * Membership has not started.
                 */

                if (
                    $today < $start_date
                ) {

                    $remaining_months =
                        $duration_months;

                }


                /*
                 * Membership has expired.
                 */

                elseif (
                    $today >= $end_date
                ) {

                    $remaining_months =
                        0;

                }


                /*
                 * Membership is currently active.
                 */

                else {

                    $interval =
                        $today->diff(
                            $end_date
                        );


                    $remaining_months =

                        (
                            $interval->y
                            * 12
                        )

                        +

                        $interval->m;


                    /*
                     * Count remaining days
                     * as an additional month.
                     */

                    if (
                        $interval->d > 0
                    ) {

                        $remaining_months++;

                    }

                }


                /* =====================================
                   VALIDATE REMAINING MONTHS
                ====================================== */

                if (
                    $remaining_months < 0
                ) {

                    $remaining_months =
                        0;

                }


                if (
                    $remaining_months >
                    $duration_months
                ) {

                    $remaining_months =
                        $duration_months;

                }


                /* =====================================
                   CALCULATE MONTHLY MEMBERSHIP VALUE
                ====================================== */

                $monthly_value =
                    0.00;


                if (
                    $duration_months > 0
                ) {

                    $monthly_value =

                        $plan_price
                        /
                        $duration_months;

                }


                /* =====================================
                   CALCULATE REMAINING MEMBERSHIP VALUE
                ====================================== */

                $remaining_membership_value =

                    $monthly_value
                    *
                    $remaining_months;


                $remaining_membership_value =

                    round(
                        $remaining_membership_value,
                        2
                    );


                /* =====================================
                   ORIGINAL MEMBERSHIP AMOUNT
                ====================================== */

                $original_gym_amount =

                    $plan_price;


                /* =====================================
                   GYMCONNECT SHARE
                ====================================== */

                $gymconnect_amount =

                    $transfer_fee;


                /* =====================================
                   INSERT TRANSFER REQUEST
                ====================================== */

                $insert =
                    $conn->prepare("
                        INSERT INTO membership_transfers

                        (
                            membership_id,

                            member_id,

                            from_gym_id,

                            to_gym_id,

                            reason,

                            transfer_fee,

                            original_gym_amount,

                            gymconnect_amount,

                            remaining_membership_value,

                            settlement_status,

                            status,

                            payment_status,

                            requested_at

                        )

                        VALUES

                        (
                            ?,

                            ?,

                            ?,

                            ?,

                            ?,

                            ?,

                            ?,

                            ?,

                            ?,

                            'Pending',

                            'Pending',

                            'Pending',

                            NOW()
                        )
                    ");


                if (!$insert) {

                    $error =
                        "Unable to create transfer request: " .
                        $conn->error;

                } else {


                    $insert->bind_param(

                        "iiiisdddd",

                        $membership_id,

                        $member_id,

                        $membership['gym_id'],

                        $to_gym_id,

                        $reason,

                        $transfer_fee,

                        $original_gym_amount,

                        $gymconnect_amount,

                        $remaining_membership_value

                    );


                    if (
                        $insert->execute()
                    ) {

                        $success =

                            "Transfer request submitted successfully. " .

                            "Remaining membership value: ₹" .

                            number_format(
                                $remaining_membership_value,
                                2
                            );


                        header(
                            "Location: transfer_membership.php?success="
                            .
                            urlencode(
                                $success
                            )
                        );

                        exit();

                    } else {

                        $error =
                            "Unable to create transfer request: " .
                            $insert->error;

                    }


                    $insert->close();

                }

            }

        }

    }

}


/* =========================================================
   SUCCESS MESSAGE FROM REDIRECT
========================================================= */

if (
    isset(
        $_GET['success']
    )
) {

    $success =
        $_GET['success'];

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

        Transfer Membership | GymConnect

    </title>


    <style>

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

            font-family: Arial, sans-serif;

        }


        body {

            background: #0b0f14;

            color: #ffffff;

            min-height: 100vh;

            padding: 40px 20px;

        }


        .container {

            max-width: 1100px;

            margin: auto;

        }


        .back-btn {

            display: inline-block;

            background: #171d25;

            color: #ffffff;

            padding: 11px 18px;

            border-radius: 7px;

            text-decoration: none;

            margin-bottom: 30px;

            border: 1px solid #29323e;

        }


        .back-btn:hover {

            background: #202833;

        }


        h1 {

            font-size: 34px;

            margin-bottom: 10px;

        }


        .subtitle {

            color: #91a0b3;

            margin-bottom: 30px;

        }


        .message {

            padding: 15px 20px;

            border-radius: 8px;

            margin-bottom: 25px;

        }


        .success-message {

            background: #12271b;

            border: 1px solid #247544;

            color: #65d88b;

        }


        .error-message {

            background: #321719;

            border: 1px solid #793238;

            color: #ff8888;

        }


        .card {

            background: #11161d;

            border: 1px solid #252d38;

            border-radius: 14px;

            padding: 30px;

            margin-bottom: 25px;

        }


        .card h2 {

            margin-bottom: 10px;

        }


        .card-subtitle {

            color: #91a0b3;

            margin-bottom: 25px;

        }


        .membership-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(220px, 1fr)
                );

            gap: 20px;

        }


        .info-box {

            background: #0c1117;

            padding: 20px;

            border-radius: 10px;

        }


        .info-box span {

            display: block;

            color: #91a0b3;

            font-size: 14px;

            margin-bottom: 8px;

        }


        .info-box strong {

            font-size: 18px;

        }


        .transfer-card {

            border-color: #4b3b16;

        }


        .transfer-route {

            display: grid;

            grid-template-columns:
                1fr 80px 1fr;

            gap: 20px;

            align-items: center;

            margin: 25px 0;

        }


        .gym-box {

            background: #0c1117;

            padding: 25px;

            border-radius: 10px;

        }


        .gym-box span {

            display: block;

            color: #91a0b3;

            margin-bottom: 10px;

        }


        .gym-box strong {

            font-size: 20px;

        }


        .arrow {

            text-align: center;

            color: #20d47a;

            font-size: 35px;

        }


        .status-badge {

            display: inline-block;

            padding: 10px 18px;

            border-radius: 30px;

            font-weight: bold;

            margin: 15px 0;

        }


        .status-pending {

            background: #3b3016;

            color: #ffd866;

        }


        .status-approved {

            background: #292045;

            color: #c4a8ff;

        }


        .status-paid {

            background: #123521;

            color: #65e493;

        }


        .status-completed {

            background: #123521;

            color: #65e493;

        }


        .status-rejected {

            background: #39191c;

            color: #ff8585;

        }


        .payment-success {

            background: #10251a;

            border: 1px solid #237345;

            padding: 20px;

            border-radius: 10px;

            color: #70df95;

            margin-top: 20px;

        }


        .pay-btn {

            display: inline-block;

            background: #20ce7a;

            color: #04150d;

            padding: 15px 28px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 18px;

            font-weight: bold;

            margin-top: 15px;

        }


        .pay-btn:hover {

            background: #28e28a;

        }


        .cancel-btn {

            background: #481d22;

            color: #ff8b91;

            border: 1px solid #75353c;

            padding: 11px 20px;

            border-radius: 7px;

            cursor: pointer;

            margin-top: 20px;

        }


        .cancel-btn:hover {

            background: #60242b;

        }


        .form-group {

            margin-bottom: 20px;

        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            color: #b4c0cf;

        }


        select,

        textarea {

            width: 100%;

            background: #0c1117;

            color: #ffffff;

            border: 1px solid #303a47;

            border-radius: 8px;

            padding: 14px;

            outline: none;

        }


        select:focus,

        textarea:focus {

            border-color: #20ce7a;

        }


        textarea {

            min-height: 120px;

            resize: vertical;

        }


        .submit-btn {

            background: #20ce7a;

            color: #04150d;

            border: none;

            padding: 14px 25px;

            border-radius: 8px;

            font-weight: bold;

            cursor: pointer;

            font-size: 16px;

        }


        .submit-btn:hover {

            background: #28e28a;

        }


        .empty {

            text-align: center;

            padding: 40px;

            color: #91a0b3;

        }


        @media (
            max-width: 700px
        ) {

            body {

                padding: 25px 15px;

            }


            .transfer-route {

                grid-template-columns: 1fr;

            }


            .arrow {

                transform:
                    rotate(90deg);

            }

        }

    </style>

</head>


<body>


<div class="container">


    <a
        href="dashboard.php"
        class="back-btn"
    >

        ← Back to Dashboard

    </a>


    <h1>

        Transfer Membership

    </h1>


    <p class="subtitle">

        Transfer your existing membership
        from your current gym to another
        GymConnect gym.

    </p>



    <?php if (
        $success !== ""
    ): ?>

        <div class="
            message
            success-message
        ">

            ✓

            <?php

            echo htmlspecialchars(
                $success
            );

            ?>

        </div>

    <?php endif; ?>



    <?php if (
        $error !== ""
    ): ?>

        <div class="
            message
            error-message
        ">

            ✕

            <?php

            echo htmlspecialchars(
                $error
            );

            ?>

        </div>

    <?php endif; ?>



    <?php if (!$membership): ?>


        <div class="card empty">

            <h2>

                No Active Membership

            </h2>

            <br>

            You need an active membership
            to request a transfer.

        </div>


    <?php else: ?>



        <div class="card">


            <h2>

                Your Current Membership

            </h2>


            <p class="card-subtitle">

                Your active membership details.

            </p>



            <div class="membership-grid">


                <div class="info-box">

                    <span>

                        Current Gym

                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                'gym_name'
                            ]
                        );

                        ?>

                    </strong>

                </div>



                <div class="info-box">

                    <span>

                        Membership Plan

                    </span>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                'plan_name'
                            ]
                            ??
                            'Membership Plan'
                        );

                        ?>

                    </strong>

                </div>



                <div class="info-box">

                    <span>

                        Start Date

                    </span>

                    <strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $membership[
                                    'start_date'
                                ]
                            )
                        );

                        ?>

                    </strong>

                </div>



                <div class="info-box">

                    <span>

                        End Date

                    </span>

                    <strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $membership[
                                    'end_date'
                                ]
                            )
                        );

                        ?>

                    </strong>

                </div>


            </div>


        </div>



        <?php if (
            $transfer
        ): ?>


            <div class="card transfer-card">


                <h2>

                    Your Transfer Request

                </h2>


                <p class="card-subtitle">

                    You already have a transfer
                    request in progress.

                </p>



                <div class="transfer-route">


                    <div class="gym-box">

                        <span>

                            Current Gym

                        </span>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $transfer[
                                    'from_gym_name'
                                ]
                            );

                            ?>

                        </strong>

                    </div>



                    <div class="arrow">

                        →

                    </div>



                    <div class="gym-box">

                        <span>

                            Transfer To

                        </span>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $transfer[
                                    'to_gym_name'
                                ]
                            );

                            ?>

                        </strong>

                    </div>


                </div>



                <?php if (
                    $transfer_status === 'pending'
                ): ?>


                    <div class="
                        status-badge
                        status-pending
                    ">

                        Transfer Request Pending

                    </div>


                    <p class="card-subtitle">

                        Your transfer request is
                        waiting for approval from
                        the current gym owner.

                    </p>



                <?php elseif (

                    (
                        $transfer_status ===
                        'awaiting payment'
                    )

                    &&

                    (
                        $payment_status ===
                        'pending'
                    )

                ): ?>


                    <div class="
                        status-badge
                        status-approved
                    ">

                        Transfer Approved —
                        Payment Required

                    </div>


                    <p class="card-subtitle">

                        The current gym owner has
                        approved your transfer.
                        Please pay the ₹500 transfer
                        fee to continue.

                    </p>


                    <a
                        href="
                            pay_transfer.php?id=<?php
                            echo (int)
                                $transfer[
                                    'transfer_id'
                                ];
                            ?>
                        "
                        class="pay-btn"
                    >

                        Pay ₹500.00 & Continue Transfer

                    </a>



                <?php elseif (

                    (
                        $transfer_status ===
                        'awaiting payment'
                    )

                    &&

                    (
                        $payment_status ===
                        'paid'
                    )

                ): ?>


                    <div class="
                        status-badge
                        status-paid
                    ">

                        ✓ Transfer Fee Paid

                    </div>


                    <div class="payment-success">

                        <strong>

                            ₹500 transfer fee paid
                            successfully.

                        </strong>


                        <br><br>


                        Your payment has been recorded.


                        <br><br>


                        The transfer is now waiting
                        for the remaining transfer
                        process to be completed.


                        <?php if (
                            !empty(
                                $transfer[
                                    'paid_at'
                                ]
                            )
                        ): ?>

                            <br><br>

                            Paid At:

                            <?php

                            echo htmlspecialchars(
                                $transfer[
                                    'paid_at'
                                ]
                            );

                            ?>

                        <?php endif; ?>


                    </div>



                <?php elseif (

                    $transfer_status ===
                    'settlement pending'

                ): ?>


                    <div class="
                        status-badge
                        status-paid
                    ">

                        ✓ Transfer Fee Paid

                    </div>


                    <div class="payment-success">

                        <strong>

                            Your ₹500 transfer fee
                            has been paid successfully.

                        </strong>

                        <br><br>

                        Your membership transfer is
                        now waiting for settlement
                        between the gyms.

                        <br><br>

                        Remaining Membership Value:

                        <strong>

                            ₹<?php

                            echo number_format(
                                (float)
                                $transfer[
                                    'remaining_membership_value'
                                ],
                                2
                            );

                            ?>

                        </strong>

                    </div>



                <?php elseif (

                    $transfer_status ===
                    'completed'

                ): ?>


                    <div class="
                        status-badge
                        status-completed
                    ">

                        ✓ Membership Transfer Completed

                    </div>


                    <div class="payment-success">

                        Your membership has been
                        successfully transferred
                        to:

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $transfer[
                                    'to_gym_name'
                                ]
                            );

                            ?>

                        </strong>

                    </div>



                <?php elseif (

                    $transfer_status ===
                    'rejected'

                ): ?>


                    <div class="
                        status-badge
                        status-rejected
                    ">

                        Transfer Request Rejected

                    </div>


                    <p class="card-subtitle">

                        Your transfer request was
                        rejected by the gym owner.

                    </p>



                <?php else: ?>


                    <div class="
                        status-badge
                        status-pending
                    ">

                        <?php

                        echo htmlspecialchars(
                            $transfer[
                                'status'
                            ]
                        );

                        ?>

                    </div>


                <?php endif; ?>



                <div
                    style="
                        margin-top:25px;
                        padding-top:20px;
                        border-top:
                            1px solid #252d38;
                    "
                >


                    <p>

                        <strong>

                            Transfer Status:

                        </strong>


                        <?php

                        echo htmlspecialchars(
                            $transfer[
                                'status'
                            ]
                        );

                        ?>

                    </p>


                    <br>


                    <p>

                        <strong>

                            Payment Status:

                        </strong>


                        <?php

                        echo htmlspecialchars(
                            $transfer[
                                'payment_status'
                            ]
                        );

                        ?>

                    </p>


                    <?php if (
                        !empty(
                            $transfer[
                                'paid_at'
                            ]
                        )
                    ): ?>


                        <br>


                        <p>

                            <strong>

                                Paid At:

                            </strong>


                            <?php

                            echo htmlspecialchars(
                                $transfer[
                                    'paid_at'
                                ]
                            );

                            ?>

                        </p>


                    <?php endif; ?>


                </div>



                <?php if (

                    $payment_status !==
                    'paid'

                    &&

                    $transfer_status !==
                    'completed'

                    &&

                    $transfer_status !==
                    'rejected'

                ): ?>


                    <form

                        method="POST"

                        onsubmit="
                            return confirm(
                                'Are you sure you want to cancel this transfer request?'
                            );
                        "

                    >


                        <input

                            type="hidden"

                            name="cancel_transfer"

                            value="1"

                        >


                        <button

                            type="submit"

                            class="cancel-btn"

                        >

                            Cancel Transfer Request

                        </button>


                    </form>


                <?php endif; ?>


            </div>



        <?php else: ?>


            <div class="card">


                <h2>

                    Request Membership Transfer

                </h2>


                <p class="card-subtitle">

                    Select another GymConnect gym
                    where you want to transfer
                    your membership.

                </p>



                <?php if (
                    count($gyms) === 0
                ): ?>


                    <div class="empty">

                        No other approved gyms
                        are currently available
                        for transfer.

                    </div>


                <?php else: ?>


                    <form

                        method="POST"

                    >


                        <div class="form-group">


                            <label>

                                Select Gym

                            </label>


                            <select

                                name="to_gym_id"

                                required

                            >


                                <option value="">

                                    Select a gym

                                </option>


                                <?php foreach (
                                    $gyms
                                    as $gym
                                ): ?>


                                    <option

                                        value="<?php

                                        echo (int)
                                            $gym[
                                                'gym_id'
                                            ];

                                        ?>"

                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $gym[
                                                'gym_name'
                                            ]
                                        );

                                        ?>


                                        <?php if (
                                            !empty(
                                                $gym[
                                                    'city'
                                                ]
                                            )
                                        ): ?>

                                            -

                                            <?php

                                            echo htmlspecialchars(
                                                $gym[
                                                    'city'
                                                ]
                                            );

                                            ?>

                                        <?php endif; ?>


                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>




                        <div class="form-group">


                            <label>

                                Reason for Transfer
                                (Optional)

                            </label>


                            <textarea

                                name="reason"

                                placeholder="
                                    Enter your reason
                                    for transferring
                                    membership...
                                "

                            ></textarea>


                        </div>



                        <div class="info-box">

                            <span>

                                Transfer Fee

                            </span>


                            <strong>

                                ₹500.00

                            </strong>


                            <br><br>


                            The ₹500 transfer fee
                            will be payable after
                            your current gym owner
                            approves the transfer.

                        </div>


                        <br>



                        <button

                            type="submit"

                            name="submit_transfer"

                            class="submit-btn"

                        >

                            Submit Transfer Request

                        </button>


                    </form>


                <?php endif; ?>


            </div>


        <?php endif; ?>


    <?php endif; ?>


</div>


</body>

</html>