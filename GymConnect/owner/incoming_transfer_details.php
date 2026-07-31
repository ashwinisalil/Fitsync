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
        "Location: incoming_transfer_requests.php?error=" .
        urlencode("Invalid transfer request.")
    );

    exit();

}


/* =========================================================
   GET TRANSFER DETAILS
   VERIFY DESTINATION GYM BELONGS TO OWNER
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

    AND to_gym.owner_id = ?

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
        "Location: incoming_transfer_requests.php?error=" .
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
    Incoming Transfer Details | GymConnect
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
                Incoming Transfer Details
            </h1>


            <p>

                Review the membership transfer before
                accepting it into your gym.

            </p>

        </div>


        <a
            href="incoming_transfer_requests.php"
            class="back-btn"
        >

            ← Back to Incoming Transfers

        </a>


    </div>



    <!-- =================================================
         STATUS
    ================================================== -->

    <div class="details-status-card">


        <span>
            Current Transfer Status
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
                Membership Information
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
                    Original Plan Price
                </span>


                <strong>

                    $<?php

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
                    Membership Start Date
                </span>


                <strong>

                    <?php

                    if (
                        !empty(
                            $transfer[
                                "membership_start_date"
                            ]
                        )
                    ) {

                        echo date(
                            "d M Y",
                            strtotime(
                                $transfer[
                                    "membership_start_date"
                                ]
                            )
                        );

                    } else {

                        echo "N/A";

                    }

                    ?>

                </strong>

            </div>



            <div class="detail-item">

                <span>
                    Membership End Date
                </span>


                <strong>

                    <?php

                    if (
                        !empty(
                            $transfer[
                                "membership_end_date"
                            ]
                        )
                    ) {

                        echo date(
                            "d M Y",
                            strtotime(
                                $transfer[
                                    "membership_end_date"
                                ]
                            )
                        );

                    } else {

                        echo "N/A";

                    }

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

            <p>

                The membership is being transferred
                from the original gym to your gym.

            </p>

        </div>


        <div class="transfer-route">


            <!-- ORIGINAL GYM -->

            <div class="gym-box">


                <span>
                    Original Gym
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



            <!-- ARROW -->

            <div class="route-arrow">

                →

            </div>



            <!-- DESTINATION GYM -->

            <div class="gym-box">


                <span>
                    Your Gym
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
         MEMBER REASON
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">

            <h2>
                Reason for Transfer
            </h2>

        </div>


        <div class="reason-box">


            <p>

                <?php

                if (
                    !empty(
                        $transfer["reason"]
                    )
                ) {

                    echo nl2br(
                        htmlspecialchars(
                            $transfer["reason"]
                        )
                    );

                } else {

                    echo "No reason provided.";

                }

                ?>

            </p>


        </div>


    </div>



    <!-- =================================================
         TRANSFER FEE
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">

            <h2>
                Transfer Fee Distribution
            </h2>


            <p>

                The transfer fee is handled by
                GymConnect according to the transfer policy.

                The destination gym does not receive
                a share of this transfer fee.

            </p>

        </div>


        <div class="financial-grid">


            <div class="financial-item">


                <span>
                    Total Transfer Fee
                </span>


                <strong>

                    $<?php

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
                    Original Gym Share
                </span>


                <strong class="gym-share">

                    $<?php

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

                    $<?php

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
                    Your Gym Share
                </span>


                <strong>

                    $0.00

                </strong>


            </div>


        </div>


    </div>



    <!-- =================================================
         APPROVAL INFORMATION
    ================================================== -->

    <div class="details-card">


        <div class="details-card-header">

            <h2>
                Transfer Approval
            </h2>

        </div>


        <div class="details-grid">


            <div class="detail-item">


                <span>
                    Requested At
                </span>


                <strong>

                    <?php

                    if (
                        !empty(
                            $transfer[
                                "requested_at"
                            ]
                        )
                    ) {

                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $transfer[
                                    "requested_at"
                                ]
                            )
                        );

                    } else {

                        echo "N/A";

                    }

                    ?>

                </strong>


            </div>



            <div class="detail-item">


                <span>
                    Approved by Original Gym
                </span>


                <strong>

                    <?php

                    if (
                        !empty(
                            $transfer[
                                "approved_at"
                            ]
                        )
                    ) {

                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $transfer[
                                    "approved_at"
                                ]
                            )
                        );

                    } else {

                        echo "N/A";

                    }

                    ?>

                </strong>


            </div>


        </div>


    </div>



    <!-- =================================================
         ACCEPT / REJECT ACTIONS
    ================================================== -->

    <?php if (
        $transfer["status"]
        ===
        "Approved"
    ): ?>


        <div class="decision-card">


            <h2>
                Accept Membership Transfer
            </h2>


            <p>

                By accepting this transfer, you confirm
                that you agree to take this member into
                your gym.

                The existing membership at the original
                gym will be marked as transferred, and
                a new active membership will be created
                for your gym using the remaining
                membership period.

            </p>


            <div class="decision-actions">


                <a
                    href="accept_transfer.php?id=<?php
                    echo (int)
                        $transfer[
                            "transfer_id"
                        ];
                    ?>"
                    class="approve-btn"
                    onclick="return confirm('Are you sure you want to accept this membership transfer? This action will move the membership to your gym.');"
                >

                    ✓ Accept Transfer

                </a>


                <a
                    href="reject_incoming_transfer.php?id=<?php
                    echo (int)
                        $transfer[
                            "transfer_id"
                        ];
                    ?>"
                    class="reject-btn"
                    onclick="return confirm('Are you sure you want to reject this transfer request? The member will remain with the original gym.');"
                >

                    ✕ Reject Transfer

                </a>


            </div>


        </div>


    <?php elseif (
        $transfer["status"]
        ===
        "Completed"
    ): ?>


        <div class="info-card completed-info">


            <strong>
                Transfer Completed
            </strong>


            <p>

                This membership has already been
                successfully transferred to your gym.

            </p>


        </div>


    <?php elseif (
        $transfer["status"]
        ===
        "Rejected"
    ): ?>


        <div class="info-card rejected-info">


            <strong>
                Transfer Rejected
            </strong>


            <p>

                This transfer request has been rejected.

                The member's membership remains with
                the original gym.

            </p>


        </div>


    <?php else: ?>


        <div class="info-card">


            <strong>
                Transfer Not Available
            </strong>


            <p>

                This transfer request is currently not
                available for acceptance.

            </p>


        </div>


    <?php endif; ?>


</div>


</body>

</html>