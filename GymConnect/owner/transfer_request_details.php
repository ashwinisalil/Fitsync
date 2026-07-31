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


$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET TRANSFER ID
========================================================= */

$transfer_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($transfer_id <= 0) {

    header(
        "Location: transfer_requests.php?error=" .
        urlencode("Invalid transfer request.")
    );

    exit();

}


/* =========================================================
   GET COMPLETE TRANSFER DETAILS
   AND VERIFY OWNER
========================================================= */

$stmt = $conn->prepare("
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
        mt.status,
        mt.requested_at,
        mt.approved_at,
        mt.completed_at,
        mt.approved_by,

        u.full_name AS member_name,
        u.email AS member_email,
        u.phone AS member_phone,

        from_gym.gym_name AS from_gym_name,
        from_gym.address AS from_gym_address,
        from_gym.city AS from_gym_city,
        from_gym.state AS from_gym_state,

        to_gym.gym_name AS to_gym_name,
        to_gym.address AS to_gym_address,
        to_gym.city AS to_gym_city,
        to_gym.state AS to_gym_state,

        mp.plan_name,
        mp.duration_months,
        mp.price,

        m.start_date AS membership_start_date,
        m.end_date AS membership_end_date,
        m.status AS membership_status

    FROM membership_transfers mt

    INNER JOIN users u
        ON mt.member_id = u.user_id

    INNER JOIN gyms from_gym
        ON mt.from_gym_id = from_gym.gym_id

    INNER JOIN gyms to_gym
        ON mt.to_gym_id = to_gym.gym_id

    LEFT JOIN memberships m
        ON mt.membership_id = m.membership_id

    LEFT JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE mt.transfer_id = ?

    AND from_gym.owner_id = ?

    LIMIT 1
");


if (!$stmt) {

    die(
        "Unable to load transfer request."
    );

}


$stmt->bind_param(
    "ii",
    $transfer_id,
    $owner_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    $stmt->close();


    header(
        "Location: transfer_requests.php?error=" .
        urlencode(
            "Transfer request not found or you are not authorized to view it."
        )
    );

    exit();

}


$transfer =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   STATUS CLASS
========================================================= */

$status_class =
    strtolower(
        $transfer["status"]
    );


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
    Transfer Request Details | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/transfer_requests.css"
>


</head>


<body>


<div class="transfer-page">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="page-header">


        <div>

            <span class="request-id">

                Transfer Request #

                <?php

                echo (int)
                    $transfer["transfer_id"];

                ?>

            </span>


            <h1>
                Transfer Request Details
            </h1>


            <p>

                Review the complete membership transfer
                request before making a decision.

            </p>

        </div>


        <a
            href="transfer_requests.php"
            class="back-btn"
        >

            ← Back to Requests

        </a>


    </div>



    <!-- =================================================
         STATUS
    ================================================== -->

    <div class="details-status-card">


        <span>
            Current Status
        </span>


        <strong
            class="status-badge
            <?php
            echo htmlspecialchars(
                $status_class
            );
            ?>"
        >

            <?php

            echo htmlspecialchars(
                $transfer["status"]
            );

            ?>

        </strong>


    </div>



    <!-- =================================================
         MEMBER INFORMATION
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">


            <h2>
                Member Information
            </h2>


        </div>


        <div class="details-grid">


            <div class="detail-item">


                <span>
                    Full Name
                </span>


                <strong>

                    <?php

                    echo htmlspecialchars(
                        $transfer["member_name"]
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
                        $transfer["member_email"]
                    );

                    ?>

                </strong>


            </div>



            <div class="detail-item">


                <span>
                    Phone
                </span>


                <strong>

                    <?php

                    echo htmlspecialchars(
                        $transfer["member_phone"]
                        ??
                        "Not provided"
                    );

                    ?>

                </strong>


            </div>


        </div>


    </div>



    <!-- =================================================
         MEMBERSHIP INFORMATION
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">


            <h2>
                Current Membership
            </h2>


        </div>


        <div class="details-grid">


            <div class="detail-item">


                <span>
                    Membership Plan
                </span>


                <strong>

                    <?php

                    echo htmlspecialchars(
                        $transfer["plan_name"]
                        ??
                        "Membership"
                    );

                    ?>

                </strong>


            </div>



            <div class="detail-item">


                <span>
                    Plan Price
                </span>


                <strong>

                    ₹<?php

                    echo number_format(
                        (float)
                        (
                            $transfer["price"]
                            ??
                            0
                        ),
                        2
                    );

                    ?>

                </strong>


            </div>



            <div class="detail-item">


                <span>
                    Duration
                </span>


                <strong>

                    <?php

                    echo (int)
                        (
                            $transfer[
                                "duration_months"
                            ]
                            ??
                            0
                        );

                    ?>

                    Month(s)

                </strong>


            </div>



            <div class="detail-item">


                <span>
                    Membership Status
                </span>


                <strong>

                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "membership_status"
                        ]
                        ??
                        "Unknown"
                    );

                    ?>

                </strong>


            </div>



            <div class="detail-item">


                <span>
                    Start Date
                </span>


                <strong>

                    <?php

                    echo date(
                        "d M Y",
                        strtotime(
                            $transfer[
                                "membership_start_date"
                            ]
                        )
                    );

                    ?>

                </strong>


            </div>



            <div class="detail-item">


                <span>
                    End Date
                </span>


                <strong>

                    <?php

                    echo date(
                        "d M Y",
                        strtotime(
                            $transfer[
                                "membership_end_date"
                            ]
                        )
                    );

                    ?>

                </strong>


            </div>


        </div>


    </div>



    <!-- =================================================
         TRANSFER ROUTE
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">


            <h2>
                Transfer Route
            </h2>


        </div>


        <div class="transfer-route">


            <div class="gym-box">


                <span>
                    Current Gym
                </span>


                <strong>

                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "from_gym_name"
                        ]
                    );

                    ?>

                </strong>


                <small>

                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "from_gym_address"
                        ]
                        ??
                        ""
                    );

                    ?>


                    <br>


                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "from_gym_city"
                        ]
                        ??
                        ""
                    );

                    ?>


                    ,


                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "from_gym_state"
                        ]
                        ??
                        ""
                    );

                    ?>

                </small>


            </div>



            <div class="route-arrow">

                →

            </div>



            <div class="gym-box">


                <span>
                    Destination Gym
                </span>


                <strong>

                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "to_gym_name"
                        ]
                    );

                    ?>

                </strong>


                <small>

                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "to_gym_address"
                        ]
                        ??
                        ""
                    );

                    ?>


                    <br>


                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "to_gym_city"
                        ]
                        ??
                        ""
                    );

                    ?>


                    ,


                    <?php

                    echo htmlspecialchars(
                        $transfer[
                            "to_gym_state"
                        ]
                        ??
                        ""
                    );

                    ?>

                </small>


            </div>


        </div>


    </div>



    <!-- =================================================
         TRANSFER REASON
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">


            <h2>
                Member's Reason
            </h2>


        </div>


        <div class="reason-box">


            <p>

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $transfer[
                            "reason"
                        ]
                    )
                );

                ?>

            </p>


        </div>


    </div>



    <!-- =================================================
         FINANCIAL INFORMATION
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">


            <h2>
                Transfer Fee Distribution
            </h2>


            <p>

                The transfer fee is distributed
                according to the GymConnect transfer policy.

            </p>


        </div>


        <div class="financial-grid">


            <div class="financial-item">


                <span>
                    Total Transfer Fee
                </span>


                <strong>

                    ₹<?php

                    echo number_format(
                        (float)
                        $transfer[
                            "transfer_fee"
                        ],
                        2
                    );

                    ?>

                </strong>


            </div>



            <div class="financial-item">


                <span>
                    Your Gym Share
                </span>


                <strong class="gym-share">

                    ₹<?php

                    echo number_format(
                        (float)
                        $transfer[
                            "original_gym_amount"
                        ],
                        2
                    );

                    ?>

                </strong>


            </div>



            <div class="financial-item">


                <span>
                    GymConnect Share
                </span>


                <strong>

                    ₹<?php

                    echo number_format(
                        (float)
                        $transfer[
                            "gymconnect_amount"
                        ],
                        2
                    );

                    ?>

                </strong>


            </div>



            <div class="financial-item">


                <span>
                    Destination Gym Share
                </span>


                <strong>

                    ₹0.00

                </strong>


            </div>


        </div>


    </div>



    <!-- =================================================
         REQUEST TIMELINE
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">


            <h2>
                Transfer Timeline
            </h2>


        </div>


        <div class="timeline">


            <div class="timeline-item completed">


                <div class="timeline-dot">

                    ✓

                </div>


                <div>


                    <strong>
                        Transfer Requested
                    </strong>


                    <p>

                        <?php

                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $transfer[
                                    "requested_at"
                                ]
                            )
                        );

                        ?>

                    </p>


                </div>


            </div>



            <?php if (
                $transfer[
                    "status"
                ] === "Approved"
                ||
                $transfer[
                    "status"
                ] === "Completed"
            ): ?>


                <div class="timeline-item completed">


                    <div class="timeline-dot">

                        ✓

                    </div>


                    <div>


                        <strong>
                            Approved by Current Gym
                        </strong>


                        <?php if (
                            !empty(
                                $transfer[
                                    "approved_at"
                                ]
                            )
                        ): ?>


                            <p>

                                <?php

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $transfer[
                                            "approved_at"
                                        ]
                                    )
                                );

                                ?>

                            </p>


                        <?php endif; ?>


                    </div>


                </div>


            <?php elseif (
                $transfer[
                    "status"
                ] === "Rejected"
            ): ?>


                <div class="timeline-item rejected">


                    <div class="timeline-dot">

                        ✕

                    </div>


                    <div>


                        <strong>
                            Transfer Rejected
                        </strong>


                        <p>
                            The transfer request was rejected.
                        </p>


                    </div>


                </div>


            <?php else: ?>


                <div class="timeline-item pending">


                    <div class="timeline-dot">

                        2

                    </div>


                    <div>


                        <strong>
                            Awaiting Current Gym Approval
                        </strong>


                        <p>

                            This request is waiting
                            for your decision.

                        </p>


                    </div>


                </div>


            <?php endif; ?>



            <?php if (
                $transfer[
                    "status"
                ] === "Completed"
            ): ?>


                <div class="timeline-item completed">


                    <div class="timeline-dot">

                        ✓

                    </div>


                    <div>


                        <strong>
                            Transfer Completed
                        </strong>


                        <?php if (
                            !empty(
                                $transfer[
                                    "completed_at"
                                ]
                            )
                        ): ?>


                            <p>

                                <?php

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $transfer[
                                            "completed_at"
                                        ]
                                    )
                                );

                                ?>

                            </p>


                        <?php endif; ?>


                    </div>


                </div>


            <?php endif; ?>


        </div>


    </div>



    <!-- =================================================
         ACTIONS
    ================================================== -->

    <?php if (
        $transfer[
            "status"
        ] === "Pending"
    ): ?>


        <div class="decision-card">


            <h2>
                Transfer Decision
            </h2>


            <p>

                Approving this request will allow the
                destination gym to review and accept
                the membership transfer.

                Your member's membership will remain
                active in your gym until the destination
                gym completes the transfer.

            </p>


            <div class="decision-actions">


                <a
                    href="approve_transfer.php?id=<?php
                    echo (int)
                        $transfer[
                            "transfer_id"
                        ];
                    ?>"
                    class="approve-btn"
                    onclick="return confirm('Are you sure you want to approve this membership transfer request?');"
                >

                    ✓ Approve Transfer

                </a>


                <a
                    href="reject_transfer.php?id=<?php
                    echo (int)
                        $transfer[
                            "transfer_id"
                        ];
                    ?>"
                    class="reject-btn"
                    onclick="return confirm('Are you sure you want to reject this membership transfer request?');"
                >

                    ✕ Reject Transfer

                </a>


            </div>


        </div>


    <?php elseif (
        $transfer[
            "status"
        ] === "Approved"
    ): ?>


        <div class="info-card approved-info">


            <strong>
                Transfer Approved
            </strong>


            <p>

                You have approved this transfer request.
                The destination gym must now accept
                the transfer before the membership is moved.

            </p>


        </div>


    <?php elseif (
        $transfer[
            "status"
        ] === "Rejected"
    ): ?>


        <div class="info-card rejected-info">


            <strong>
                Transfer Rejected
            </strong>


            <p>

                This transfer request has been rejected.
                The member's membership remains with
                your gym.

            </p>


        </div>


    <?php elseif (
        $transfer[
            "status"
        ] === "Completed"
    ): ?>


        <div class="info-card completed-info">


            <strong>
                Transfer Completed
            </strong>


            <p>

                This membership has successfully
                been transferred to the destination gym.

            </p>


        </div>


    <?php endif; ?>


</div>


</body>

</html>