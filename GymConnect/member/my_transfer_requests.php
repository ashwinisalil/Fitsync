<?php

session_start();


/* =========================================================
   MEMBER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: ../login.php");
    exit();
}


require_once "../database/connection.php";


$member_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET MEMBER TRANSFER REQUESTS
========================================================= */

$transfers = [];


$stmt = $conn->prepare("
    SELECT
        mt.transfer_id,
        mt.membership_id,
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

        from_gym.gym_name AS from_gym_name,
        from_gym.city AS from_gym_city,

        to_gym.gym_name AS to_gym_name,
        to_gym.city AS to_gym_city,

        mp.plan_name

    FROM membership_transfers mt

    INNER JOIN gyms from_gym
        ON mt.from_gym_id = from_gym.gym_id

    INNER JOIN gyms to_gym
        ON mt.to_gym_id = to_gym.gym_id

    LEFT JOIN memberships m
        ON mt.membership_id = m.membership_id

    LEFT JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE mt.member_id = ?

    ORDER BY mt.requested_at DESC
");


if (!$stmt) {

    die(
        "Unable to load transfer requests."
    );

}


$stmt->bind_param(
    "i",
    $member_id
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
    My Transfer Requests | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/transfer_requests.css"
>


</head>


<body>


<div class="transfer-page">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">


        <div>

            <h1>
                My Transfer Requests
            </h1>

            <p>
                View and track your membership transfer requests.
            </p>

        </div>


        <div class="header-actions">


            <a
                href="request_transfer.php"
                class="new-transfer-btn"
            >

                + New Transfer Request

            </a>


            <a
                href="dashboard.php"
                class="back-btn"
            >

                ← Dashboard

            </a>


        </div>


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
         TRANSFER REQUESTS
    ================================================== -->

    <?php if (
        count($transfers) === 0
    ): ?>


        <div class="empty-card">


            <div class="empty-icon">

                ⇄

            </div>


            <h2>
                No Transfer Requests
            </h2>


            <p>

                You have not submitted any
                membership transfer requests yet.

            </p>


            <a
                href="request_transfer.php"
                class="primary-btn"
            >

                Request Membership Transfer

            </a>


        </div>


    <?php else: ?>


        <div class="transfer-list">


            <?php foreach (
                $transfers
                as $transfer
            ): ?>


                <?php

                /*
                    Convert status to lowercase
                    for CSS class.
                */

                $status_class =
                    strtolower(
                        $transfer["status"]
                    );

                ?>


                <div
                    class="transfer-card
                    <?php
                    echo htmlspecialchars(
                        $status_class
                    );
                    ?>"
                >


                    <!-- =================================
                         CARD HEADER
                    ================================== -->

                    <div class="card-header">


                        <div>


                            <span class="transfer-number">

                                Transfer Request #

                                <?php

                                echo
                                (int)
                                $transfer[
                                    "transfer_id"
                                ];

                                ?>

                            </span>


                            <h2>

                                <?php

                                echo htmlspecialchars(
                                    $transfer[
                                        "plan_name"
                                    ] ??
                                    "Membership"
                                );

                                ?>

                            </h2>


                        </div>


                        <div
                            class="status-badge
                            <?php
                            echo htmlspecialchars(
                                $status_class
                            );
                            ?>"
                        >


                            <?php

                            echo htmlspecialchars(
                                $transfer[
                                    "status"
                                ]
                            );

                            ?>


                        </div>


                    </div>



                    <!-- =================================
                         GYM TRANSFER PATH
                    ================================== -->

                    <div class="transfer-route">


                        <div class="gym-box">


                            <span class="label">

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
                                        "from_gym_city"
                                    ]
                                );

                                ?>

                            </small>


                        </div>



                        <div class="arrow">

                            →

                        </div>



                        <div class="gym-box">


                            <span class="label">

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
                                        "to_gym_city"
                                    ]
                                );

                                ?>

                            </small>


                        </div>


                    </div>



                    <!-- =================================
                         TRANSFER DETAILS
                    ================================== -->

                    <div class="details-grid">


                        <div class="detail-item">


                            <span>
                                Requested On
                            </span>


                            <strong>

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

                            </strong>


                        </div>



                        <div class="detail-item">


                            <span>
                                Transfer Fee
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



                        <div class="detail-item">


                            <span>
                                Original Gym Share
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



                        <div class="detail-item">


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


                    </div>



                    <!-- =================================
                         REASON
                    ================================== -->

                    <div class="reason-box">


                        <span>
                            Transfer Reason
                        </span>


                        <p>

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $transfer[
                                        "reason"
                                    ] ??
                                    "No reason provided."
                                )
                            );

                            ?>

                        </p>


                    </div>



                    <!-- =================================
                         STATUS INFORMATION
                    ================================== -->

                    <div class="status-information">


                        <?php if (
                            $transfer[
                                "status"
                            ] === "Pending"
                        ): ?>


                            <div class="status-message pending-message">

                                <strong>
                                    Transfer Request Pending
                                </strong>

                                <p>

                                    Your transfer request is waiting
                                    for approval from the current gym.

                                </p>

                            </div>


                        <?php elseif (
                            $transfer[
                                "status"
                            ] === "Approved"
                        ): ?>


                            <div class="status-message approved-message">

                                <strong>
                                    Transfer Approved
                                </strong>

                                <p>

                                    Your current gym has approved
                                    the transfer. The destination gym
                                    must now complete the transfer process.

                                </p>


                                <?php if (
                                    !empty(
                                        $transfer[
                                            "approved_at"
                                        ]
                                    )
                                ): ?>

                                    <small>

                                        Approved on:

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

                                    </small>

                                <?php endif; ?>


                            </div>


                        <?php elseif (
                            $transfer[
                                "status"
                            ] === "Rejected"
                        ): ?>


                            <div class="status-message rejected-message">

                                <strong>
                                    Transfer Request Rejected
                                </strong>

                                <p>

                                    Your membership transfer request
                                    was rejected.

                                </p>

                            </div>


                        <?php elseif (
                            $transfer[
                                "status"
                            ] === "Completed"
                        ): ?>


                            <div class="status-message completed-message">

                                <strong>
                                    Transfer Completed
                                </strong>

                                <p>

                                    Your membership has successfully
                                    been transferred to the new gym.

                                </p>


                                <?php if (
                                    !empty(
                                        $transfer[
                                            "completed_at"
                                        ]
                                    )
                                ): ?>

                                    <small>

                                        Completed on:

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

                                    </small>

                                <?php endif; ?>


                            </div>


                        <?php endif; ?>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


</body>

</html>