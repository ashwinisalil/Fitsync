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


$owner_id =
    (int) $_SESSION["user_id"];


/* =========================================================
   GET OWNER'S GYMS
========================================================= */

$owner_gyms = [];


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


$result =
    $stmt->get_result();


while (
    $row =
    $result->fetch_assoc()
) {

    $owner_gyms[] =
        $row;

}


$stmt->close();


/* =========================================================
   CHECK OWNER HAS GYM
========================================================= */

if (
    count($owner_gyms) === 0
) {

    die(
        "You do not have any gym associated with your account."
    );

}


/* =========================================================
   CREATE GYM ID ARRAY
========================================================= */

$gym_ids = [];


foreach (
    $owner_gyms
    as $gym
) {

    $gym_ids[] =
        (int)
        $gym["gym_id"];

}


/* =========================================================
   CREATE PLACEHOLDERS
========================================================= */

$placeholders =
    implode(
        ",",
        array_fill(
            0,
            count($gym_ids),
            "?"
        )
    );


/* =========================================================
   GET RECEIVED SETTLEMENTS
========================================================= */

$sql = "

    SELECT

        mts.settlement_id,

        mts.transfer_id,

        mts.membership_id,

        mts.from_gym_id,

        mts.to_gym_id,

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


    WHERE mts.to_gym_id
          IN ($placeholders)


    ORDER BY mts.created_at DESC

";


$stmt =
    $conn->prepare(
        $sql
    );


if (!$stmt) {

    die(
        "Database error: "
        . htmlspecialchars(
            $conn->error
        )
    );

}


/* =========================================================
   BIND GYM IDS
========================================================= */

$types =
    str_repeat(
        "i",
        count($gym_ids)
    );


$stmt->bind_param(
    $types,
    ...$gym_ids
);


$stmt->execute();


$result =
    $stmt->get_result();


$settlements = [];


while (
    $row =
    $result->fetch_assoc()
) {

    $settlements[] =
        $row;

}


$stmt->close();


/* =========================================================
   SUMMARY
========================================================= */

$total_pending =
    0;


$total_paid =
    0;


$total_completed =
    0;


foreach (
    $settlements
    as $settlement
) {

    $amount =
        (float)
        $settlement["amount"];


    if (
        $settlement[
            "payment_status"
        ]
        === "Paid"
    ) {

        $total_paid +=
            $amount;

    }


    elseif (
        $settlement[
            "payment_status"
        ]
        === "Completed"
    ) {

        $total_completed +=
            $amount;

    }


    elseif (
        $settlement[
            "payment_status"
        ]
        === "Pending"
    ) {

        $total_pending +=
            $amount;

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

    Received Settlements | GymConnect

</title>


<link
    rel="stylesheet"
    href="../css/received_settlements.css"
>


</head>


<body>


<div class="page-container">


    <!-- =================================================
         HEADER
    ================================================== -->


    <div class="page-header">


        <div>


            <a
                href="dashboard.php"
                class="back-btn"
            >

                ← Back to Dashboard

            </a>


            <h1>

                Received Settlements

            </h1>


            <p>

                View payments received from previous
                gyms for transferred memberships.

            </p>


        </div>


    </div>



    <!-- =================================================
         SUMMARY CARDS
    ================================================== -->


    <div class="summary-grid">


        <div class="summary-card">


            <span>

                Awaiting Confirmation

            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $total_paid,
                    2
                );

                ?>

            </strong>


            <small>

                Payments waiting for you
                to confirm receipt.

            </small>


        </div>



        <div class="summary-card">


            <span>

                Completed Received

            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $total_completed,
                    2
                );

                ?>

            </strong>


            <small>

                Total settlement payments
                confirmed by your gym.

            </small>


        </div>



        <div class="summary-card">


            <span>

                Pending

            </span>


            <strong>

                ₹<?php

                echo number_format(
                    $total_pending,
                    2
                );

                ?>

            </strong>


            <small>

                Settlements that the old gym
                has not paid yet.

            </small>


        </div>


    </div>



    <!-- =================================================
         SETTLEMENT SECTION
    ================================================== -->


    <div class="settlement-section">


        <div class="section-header">


            <h2>

                Settlement History

            </h2>


            <p>

                Review payment details before
                confirming receipt.

            </p>


        </div>



        <?php if (
            count(
                $settlements
            )
            === 0
        ): ?>


            <!-- =========================================
                 EMPTY STATE
            ========================================== -->


            <div class="empty-state">


                <div class="empty-icon">

                    ✓

                </div>


                <h3>

                    No Settlements Found

                </h3>


                <p>

                    Your gyms currently have
                    no membership transfer settlements.

                </p>


            </div>


        <?php else: ?>


            <!-- =========================================
                 SETTLEMENT LIST
            ========================================== -->


            <div class="settlement-list">


                <?php foreach (
                    $settlements
                    as $settlement
                ): ?>


                    <?php

                    $status =
                        $settlement[
                            "payment_status"
                        ];

                    ?>


                    <div class="settlement-card">


                        <!-- =================================
                             HEADER
                        ================================== -->


                        <div class="card-header">


                            <div>


                                <h3>

                                    Transfer #<?php

                                    echo
                                        (int)
                                        $settlement[
                                            "transfer_id"
                                        ];

                                    ?>

                                </h3>


                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "member_name"
                                        ]
                                    );

                                    ?>

                                </span>


                            </div>



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


                        </div>



                        <!-- =================================
                             GYM ROUTE
                        ================================== -->


                        <div class="transfer-route">


                            <div class="gym-box">


                                <span>

                                    From Previous Gym

                                </span>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "from_gym_name"
                                        ]
                                    );

                                    ?>

                                </strong>


                            </div>



                            <div class="route-arrow">

                                →

                            </div>



                            <div class="gym-box">


                                <span>

                                    Your Gym

                                </span>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "to_gym_name"
                                        ]
                                    );

                                    ?>

                                </strong>


                            </div>


                        </div>



                        <!-- =================================
                             DETAILS
                        ================================== -->


                        <div class="settlement-details">


                            <div class="detail-item">


                                <span>

                                    Member

                                </span>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "member_name"
                                        ]
                                    );

                                    ?>

                                </strong>


                            </div>



                            <div class="detail-item">


                                <span>

                                    Email

                                </span>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "member_email"
                                        ]
                                    );

                                    ?>

                                </strong>


                            </div>



                            <div class="detail-item">


                                <span>

                                    Membership Plan

                                </span>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $settlement[
                                            "plan_name"
                                        ]
                                    );

                                    ?>

                                </strong>


                            </div>



                            <div class="detail-item amount-item">


                                <span>

                                    Settlement Amount

                                </span>


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


                            </div>


                        </div>



                        <!-- =================================
                             PAYMENT INFORMATION
                        ================================== -->


                        <?php if (
                            $status === "Paid" ||
                            $status === "Completed"
                        ): ?>


                            <div class="payment-info">


                                <div>


                                    <span>

                                        Payment Method

                                    </span>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $settlement[
                                                "payment_method"
                                            ]
                                            ??
                                            "Not Available"
                                        );

                                        ?>

                                    </strong>


                                </div>



                                <div>


                                    <span>

                                        Transaction Reference

                                    </span>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $settlement[
                                                "transaction_reference"
                                            ]
                                            ??
                                            "Not Available"
                                        );

                                        ?>

                                    </strong>


                                </div>



                                <div>


                                    <span>

                                        Paid At

                                    </span>


                                    <strong>

                                        <?php

                                        if (
                                            !empty(
                                                $settlement[
                                                    "paid_at"
                                                ]
                                            )
                                        ) {

                                            echo date(
                                                "d M Y, h:i A",
                                                strtotime(
                                                    $settlement[
                                                        "paid_at"
                                                    ]
                                                )
                                            );

                                        } else {

                                            echo
                                                "Not Available";

                                        }

                                        ?>

                                    </strong>


                                </div>


                            </div>


                        <?php endif; ?>



                        <!-- =================================
                             FOOTER
                        ================================== -->


                        <div class="card-footer">


                            <span class="created-date">

                                Created:

                                <?php

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $settlement[
                                            "created_at"
                                        ]
                                    )
                                );

                                ?>

                            </span>



                            <?php if (
                                $status === "Pending"
                            ): ?>


                                <span class="pending-text">

                                    Waiting for previous gym
                                    to make payment.

                                </span>


                            <?php elseif (
                                $status === "Paid"
                            ): ?>


                                <form
                                    action="confirm_settlement.php"
                                    method="POST"
                                    onsubmit="
                                        return confirm(
                                            'Have you verified and received this payment?'
                                        );
                                    "
                                >


                                    <input
                                        type="hidden"
                                        name="settlement_id"
                                        value="<?php

                                        echo
                                            (int)
                                            $settlement[
                                                "settlement_id"
                                            ];

                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="confirm-btn"
                                    >

                                        ✓ Confirm Payment Received

                                    </button>


                                </form>


                            <?php elseif (
                                $status === "Completed"
                            ): ?>


                                <span class="completed-text">

                                    ✓ Payment Received
                                    & Settlement Completed

                                </span>


                            <?php endif; ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>


</html>