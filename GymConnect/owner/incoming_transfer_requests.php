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
   GET OWNER'S APPROVED ACTIVE GYMS
========================================================= */

$gyms = [];


$stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name
    FROM gyms
    WHERE owner_id = ?
    AND status = 'approved'
    AND is_active = 1
    ORDER BY gym_name ASC
");


if (!$stmt) {

    die(
        "Unable to load gyms."
    );

}


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

    $gyms[] =
        $row;

}


$stmt->close();


/* =========================================================
   GET SELECTED GYM
========================================================= */

$selected_gym_id =
    isset($_GET["gym_id"])
    ? (int) $_GET["gym_id"]
    : 0;


/* =========================================================
   USE FIRST GYM BY DEFAULT
========================================================= */

if (
    $selected_gym_id <= 0 &&
    count($gyms) > 0
) {

    $selected_gym_id =
        (int) $gyms[0]["gym_id"];

}


/* =========================================================
   VERIFY SELECTED GYM BELONGS TO OWNER
========================================================= */

$valid_gym = false;


foreach (
    $gyms
    as $gym
) {

    if (
        (int) $gym["gym_id"]
        ===
        $selected_gym_id
    ) {

        $valid_gym = true;

        break;

    }

}


if (
    !$valid_gym
) {

    $selected_gym_id = 0;

}


/* =========================================================
   GET INCOMING TRANSFER REQUESTS
========================================================= */

$transfers = [];


if (
    $selected_gym_id > 0
) {


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
            from_gym.city AS from_gym_city,
            from_gym.address AS from_gym_address,

            to_gym.gym_name AS to_gym_name,

            mp.plan_name,
            mp.duration_months,
            mp.price,

            m.start_date,
            m.end_date,
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

        WHERE mt.to_gym_id = ?

        AND mt.status = 'Approved'

        ORDER BY mt.approved_at DESC
    ");


    if (!$stmt) {

        die(
            "Unable to load incoming transfer requests."
        );

    }


    $stmt->bind_param(
        "i",
        $selected_gym_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row =
        $result->fetch_assoc()
    ) {

        $transfers[] =
            $row;

    }


    $stmt->close();

}


/* =========================================================
   COUNT REQUESTS
========================================================= */

$incoming_count =
    count($transfers);

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
    Incoming Membership Transfers | GymConnect
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

            <h1>
                Incoming Membership Transfers
            </h1>

            <p>

                Review membership transfer requests
                approved by the original gym.

            </p>

        </div>


        <a
            href="dashboard.php"
            class="back-btn"
        >

            ← Back to Dashboard

        </a>


    </div>



    <!-- =================================================
         SUCCESS MESSAGE
    ================================================== -->

    <?php if (
        isset($_GET["success"])
    ): ?>


        <div class="alert success">

            ✓

            <?php

            echo htmlspecialchars(
                $_GET["success"]
            );

            ?>

        </div>


    <?php endif; ?>



    <!-- =================================================
         ERROR MESSAGE
    ================================================== -->

    <?php if (
        isset($_GET["error"])
    ): ?>


        <div class="alert error">

            !

            <?php

            echo htmlspecialchars(
                $_GET["error"]
            );

            ?>

        </div>


    <?php endif; ?>



    <!-- =================================================
         GYM SELECTOR
    ================================================== -->

    <?php if (
        count($gyms) > 0
    ): ?>


        <div class="gym-selector-card">


            <div>


                <label>
                    Select Your Gym
                </label>


                <p>

                    View incoming membership
                    transfers for your gym.

                </p>


            </div>


            <form
                method="GET"
                action="incoming_transfer_requests.php"
            >


                <select
                    name="gym_id"
                    onchange="this.form.submit()"
                >


                    <?php foreach (
                        $gyms
                        as $gym
                    ): ?>


                        <option
                            value="<?php
                            echo (int)
                                $gym["gym_id"];
                            ?>"
                            <?php

                            if (
                                (int)
                                $gym["gym_id"]
                                ===
                                $selected_gym_id
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


                    <?php endforeach; ?>


                </select>


            </form>


        </div>


    <?php endif; ?>



    <!-- =================================================
         SUMMARY
    ================================================== -->

    <?php if (
        $selected_gym_id > 0
    ): ?>


        <div class="summary-grid">


            <div class="summary-card">


                <span>
                    Incoming Transfers
                </span>


                <strong>

                    <?php

                    echo $incoming_count;

                    ?>

                </strong>


            </div>


        </div>


    <?php endif; ?>



    <!-- =================================================
         NO GYMS
    ================================================== -->

    <?php if (
        count($gyms) === 0
    ): ?>


        <div class="empty-card">


            <div class="empty-icon">
                !
            </div>


            <h2>
                No Active Gyms Found
            </h2>


            <p>

                You need to have an approved and
                active gym to receive membership transfers.

            </p>


        </div>



    <?php elseif (
        count($transfers) === 0
    ): ?>


        <!-- =============================================
             NO TRANSFERS
        ============================================== -->


        <div class="empty-card">


            <div class="empty-icon">

                ⇄

            </div>


            <h2>
                No Incoming Transfers
            </h2>


            <p>

                There are currently no membership
                transfer requests waiting for your approval.

            </p>


        </div>



    <?php else: ?>


        <!-- =============================================
             TRANSFER LIST
        ============================================== -->


        <div class="transfer-list">


            <?php foreach (
                $transfers
                as $transfer
            ): ?>


                <div class="transfer-card approved">


                    <!-- =================================
                         CARD HEADER
                    ================================== -->

                    <div class="card-header">


                        <div>


                            <span class="request-id">

                                Transfer Request #

                                <?php

                                echo (int)
                                    $transfer[
                                        "transfer_id"
                                    ];

                                ?>

                            </span>


                            <h2>

                                <?php

                                echo htmlspecialchars(
                                    $transfer[
                                        "member_name"
                                    ]
                                );

                                ?>

                            </h2>


                        </div>


                        <span class="status-badge approved">

                            Approved by Original Gym

                        </span>


                    </div>



                    <!-- =================================
                         MEMBER INFORMATION
                    ================================== -->

                    <div class="member-info">


                        <div>


                            <span>
                                Email
                            </span>


                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $transfer[
                                        "member_email"
                                    ]
                                );

                                ?>

                            </strong>


                        </div>



                        <div>


                            <span>
                                Phone
                            </span>


                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $transfer[
                                        "member_phone"
                                    ]
                                    ??
                                    "Not provided"
                                );

                                ?>

                            </strong>


                        </div>



                        <div>


                            <span>
                                Membership Plan
                            </span>


                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $transfer[
                                        "plan_name"
                                    ]
                                    ??
                                    "Membership"
                                );

                                ?>

                            </strong>


                        </div>


                    </div>



                    <!-- =================================
                         TRANSFER ROUTE
                    ================================== -->

                    <div class="transfer-route">


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
                                        "from_gym_city"
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


                        </div>


                    </div>



                    <!-- =================================
                         MEMBERSHIP DETAILS
                    ================================== -->

                    <div class="financial-section">


                        <h3>
                            Membership Details
                        </h3>


                        <div class="financial-grid">


                            <div>


                                <span>
                                    Plan
                                </span>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $transfer[
                                            "plan_name"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </strong>


                            </div>



                            <div>


                                <span>
                                    Original Price
                                </span>


                                <strong>

                                    ₹<?php

                                    echo number_format(
                                        (float)
                                        (
                                            $transfer[
                                                "price"
                                            ]
                                            ??
                                            0
                                        ),
                                        2
                                    );

                                    ?>

                                </strong>


                            </div>



                            <div>


                                <span>
                                    Membership Ends
                                </span>


                                <strong>

                                    <?php

                                    if (
                                        !empty(
                                            $transfer[
                                                "end_date"
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $transfer[
                                                    "end_date"
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



                    <!-- =================================
                         TRANSFER FEE INFORMATION
                    ================================== -->

                    <div class="financial-section">


                        <h3>
                            Transfer Fee
                        </h3>


                        <div class="financial-grid">


                            <div>


                                <span>
                                    Total Fee
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



                            <div>


                                <span>
                                    Original Gym Receives
                                </span>


                                <strong>

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



                            <div>


                                <span>
                                    GymConnect Receives
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



                            <div>


                                <span>
                                    Your Gym Receives
                                </span>


                                <strong>

                                    ₹0.00

                                </strong>


                            </div>


                        </div>


                    </div>



                    <!-- =================================
                         MEMBER REASON
                    ================================== -->

                    <div class="reason-box">


                        <span>
                            Member's Reason
                        </span>


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



                    <!-- =================================
                         APPROVAL INFORMATION
                    ================================== -->

                    <div class="request-date">


                        <span>
                            Approved by Original Gym:
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

                                echo "Not available";

                            }

                            ?>

                        </strong>


                    </div>



                    <!-- =================================
                         ACTIONS
                    ================================== -->

                    <div class="card-actions">


                        <a
                            href="incoming_transfer_details.php?id=<?php
                            echo (int)
                                $transfer[
                                    "transfer_id"
                                ];
                            ?>"
                            class="view-btn"
                        >

                            View Full Details

                        </a>


                        <a
                            href="accept_transfer.php?id=<?php
                            echo (int)
                                $transfer[
                                    "transfer_id"
                                ];
                            ?>"
                            class="approve-btn"
                            onclick="return confirm('Are you sure you want to accept this membership transfer? The member will become a member of your gym and the previous membership will be marked as transferred.');"
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
                            onclick="return confirm('Are you sure you want to reject this transfer? The member will remain with the original gym.');"
                        >

                            ✕ Reject Transfer

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


</body>

</html>