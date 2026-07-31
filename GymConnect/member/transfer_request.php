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


$member_id =
    (int) $_SESSION["user_id"];


/* =========================================================
   GET MEMBER'S ACTIVE MEMBERSHIP
========================================================= */

$stmt = $conn->prepare("
    SELECT
        m.membership_id,
        m.gym_id,
        m.plan_id,
        m.start_date,
        m.end_date,
        m.status,

        g.gym_name,
        g.city,
        g.state,

        mp.plan_name,
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


if (!$stmt) {

    die(
        "Database error: "
        . htmlspecialchars(
            $conn->error
        )
    );

}


$stmt->bind_param(
    "i",
    $member_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    $stmt->close();

    die(
        "You do not have an active membership."
    );

}


$membership =
    $result->fetch_assoc();


$stmt->close();


$membership_id =
    (int) $membership["membership_id"];


$current_gym_id =
    (int) $membership["gym_id"];



/* =========================================================
   GET ALL OTHER APPROVED AND ACTIVE GYMS
========================================================= */

/*
   The member can transfer to any other GymConnect gym.

   Current gym is excluded.

   Destination gym must be:

   1. Approved
   2. Active
========================================================= */

$gyms = [];


$stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name,
        address,
        city,
        state

    FROM gyms

    WHERE gym_id != ?

    AND status = 'approved'

    AND is_active = 1

    ORDER BY gym_name ASC
");


if (!$stmt) {

    die(
        "Database error: "
        . htmlspecialchars(
            $conn->error
        )
    );

}


$stmt->bind_param(
    "i",
    $current_gym_id
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
   CHECK EXISTING ACTIVE TRANSFER REQUEST
========================================================= */

/*
   Only Pending and Approved requests are shown
   as active requests.

   Pending:
   Member can cancel.

   Approved:
   Member can cancel before completion.

   Cancelled:
   New request allowed.

   Rejected:
   New request allowed.

   Completed:
   New request allowed.
========================================================= */

$stmt = $conn->prepare("
    SELECT

        mt.transfer_id,
        mt.status,
        mt.reason,
        mt.transfer_fee,
        mt.original_gym_amount,
        mt.gymconnect_amount,
        mt.requested_at,
        mt.approved_at,
        mt.completed_at,

        from_gym.gym_name AS from_gym_name,
        from_gym.city AS from_gym_city,
        from_gym.state AS from_gym_state,

        to_gym.gym_name AS to_gym_name,
        to_gym.city AS to_gym_city,
        to_gym.state AS to_gym_state

    FROM membership_transfers mt

    INNER JOIN gyms from_gym
        ON mt.from_gym_id = from_gym.gym_id

    INNER JOIN gyms to_gym
        ON mt.to_gym_id = to_gym.gym_id

    WHERE mt.member_id = ?

    AND mt.membership_id = ?

    AND mt.status IN (
        'Pending',
        'Approved'
    )

    ORDER BY mt.requested_at DESC

    LIMIT 1
");


if (!$stmt) {

    die(
        "Database error: "
        . htmlspecialchars(
            $conn->error
        )
    );

}


$stmt->bind_param(
    "ii",
    $member_id,
    $membership_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$existing_transfer =
    null;


if (
    $result->num_rows > 0
) {

    $existing_transfer =
        $result->fetch_assoc();

}


$stmt->close();



/* =========================================================
   TRANSFER FEE
========================================================= */

$transfer_fee =
    50.00;


/* =========================================================
   FEE DISTRIBUTION
========================================================= */

$original_gym_amount =
    35.00;


$gymconnect_amount =
    15.00;


/* =========================================================
   PAGE MESSAGES
========================================================= */

$message =
    "";


$message_type =
    "";


if (
    isset($_GET["success"])
) {

    $message =
        $_GET["success"];

    $message_type =
        "success";

}


if (
    isset($_GET["error"])
) {

    $message =
        $_GET["error"];

    $message_type =
        "error";

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


<link
    rel="stylesheet"
    href="../css/member_transfer_request.css"
>


</head>


<body>


<div class="transfer-container">


    <!-- =====================================================
         BACK BUTTON
    ====================================================== -->

    <a
        href="dashboard.php"
        class="back-btn"
    >

        ← Back to Dashboard

    </a>



    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">


        <h1>
            Transfer Membership
        </h1>


        <p>

            Transfer your existing membership
            from your current gym to another
            approved GymConnect gym.

        </p>


    </div>



    <!-- =====================================================
         SUCCESS / ERROR MESSAGE
    ====================================================== -->

    <?php if (
        !empty($message)
    ): ?>


        <div
            class="alert
            <?php

            echo $message_type === "success"
                ? "alert-success"
                : "alert-error";

            ?>"
        >

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>


    <?php endif; ?>



    <!-- =====================================================
         EXISTING ACTIVE TRANSFER REQUEST
    ====================================================== -->

    <?php if (
        $existing_transfer !== null
    ): ?>


        <div class="status-card">


            <h2>
                Your Transfer Request
            </h2>


            <p>

                You already have an active membership
                transfer request.

                You cannot create another request
                until this request is cancelled,
                rejected, or completed.

            </p>



            <!-- =================================================
                 STATUS
            ================================================== -->

            <span
                class="status-badge
                <?php

                echo strtolower(
                    trim(
                        $existing_transfer[
                            "status"
                        ]
                    )
                );

                ?>"
            >

                <?php

                echo htmlspecialchars(
                    $existing_transfer[
                        "status"
                    ]
                );

                ?>

            </span>



            <!-- =================================================
                 TRANSFER DETAILS
            ================================================== -->

            <div class="history-details">


                <div>


                    <span>
                        From Gym
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $existing_transfer[
                                "from_gym_name"
                            ]
                        );

                        ?>

                    </strong>


                </div>



                <div>


                    <span>
                        To Gym
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $existing_transfer[
                                "to_gym_name"
                            ]
                        );

                        ?>

                    </strong>


                </div>



                <div>


                    <span>
                        Transfer Fee
                    </span>


                    <strong>

                        ₹<?php

                        echo number_format(
                            (float)
                            $existing_transfer[
                                "transfer_fee"
                            ],
                            2
                        );

                        ?>

                    </strong>


                </div>



                <div>


                    <span>
                        Requested On
                    </span>


                    <strong>

                        <?php

                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $existing_transfer[
                                    "requested_at"
                                ]
                            )
                        );

                        ?>

                    </strong>


                </div>


            </div>



            <!-- =================================================
                 TRANSFER REASON
            ================================================== -->

            <div class="reason-box">


                <span>
                    Reason for Transfer
                </span>


                <p>

                    <?php

                    echo nl2br(
                        htmlspecialchars(
                            $existing_transfer[
                                "reason"
                            ]
                        )
                    );

                    ?>

                </p>


            </div>



            <!-- =================================================
                 PENDING / APPROVED STATUS
            ================================================== -->

            <?php


            $current_status =
                strtolower(
                    trim(
                        $existing_transfer[
                            "status"
                        ]
                    )
                );


            if (
                $current_status === "pending"
                ||
                $current_status === "approved"
            ):

            ?>


                <div class="warning-box">


                    <div class="warning-icon">

                        !

                    </div>


                    <p>


                        <?php if (
                            $current_status
                            ===
                            "pending"
                        ): ?>


                            Your transfer request is currently
                            waiting for approval from the
                            original gym owner.


                        <?php else: ?>


                            Your transfer request has been
                            approved by the original gym owner.

                            The destination gym owner must
                            now accept the transfer.


                        <?php endif; ?>


                        <br><br>


                        You can cancel this transfer
                        before it is completed.


                    </p>


                </div>



                <!-- =================================================
                     CANCEL BUTTON
                ================================================== -->

                <div class="form-actions">


                    <a
                        href="cancel_transfer_request.php?id=<?php

                        echo (int)
                            $existing_transfer[
                                "transfer_id"
                            ];

                        ?>"
                        class="cancel-transfer-btn"
                        onclick="
                            return confirm(
                                'Are you sure you want to cancel this transfer request?'
                            );
                        "
                    >

                        Cancel Transfer Request

                    </a>


                </div>


            <?php endif; ?>


        </div>



    <!-- =====================================================
         NO ACTIVE TRANSFER REQUEST
         SHOW TRANSFER FORM
    ====================================================== -->

    <?php else: ?>


        <div class="transfer-form-card">


            <h2>
                Request Membership Transfer
            </h2>


            <p>

                Select any other approved and active
                GymConnect gym where you want to
                transfer your membership.

            </p>



            <!-- =================================================
                 CURRENT MEMBERSHIP
            ================================================== -->

            <div class="membership-info">


                <div class="membership-item">


                    <span>
                        Current Gym
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



                <div class="membership-item">


                    <span>
                        Membership Plan
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $membership[
                                "plan_name"
                            ]
                            ??
                            "Membership"
                        );

                        ?>

                    </strong>


                </div>



                <div class="membership-item">


                    <span>
                        Membership End Date
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


            </div>



            <!-- =================================================
                 TRANSFER FORM
            ================================================== -->

            <form
                action="transfer_request_process.php"
                method="POST"
                id="transferForm"
            >


                <!-- MEMBERSHIP ID -->

                <input
                    type="hidden"
                    name="membership_id"
                    value="<?php

                    echo $membership_id;

                    ?>"
                >



                <!-- =================================================
                     DESTINATION GYM
                ================================================== -->

                <div class="form-group">


                    <label>
                        Select Destination Gym
                    </label>


                    <select
                        name="to_gym_id"
                        id="to_gym_id"
                        required
                    >


                        <option value="">
                            Select a gym
                        </option>


                        <?php if (
                            count($gyms) > 0
                        ): ?>


                            <?php foreach (
                                $gyms
                                as $gym
                            ): ?>


                                <option
                                    value="<?php

                                    echo (int)
                                        $gym[
                                            "gym_id"
                                        ];

                                    ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $gym[
                                            "gym_name"
                                        ]
                                    );

                                    ?>


                                    <?php if (
                                        !empty(
                                            $gym[
                                                "city"
                                            ]
                                        )
                                    ): ?>


                                        -

                                        <?php

                                        echo htmlspecialchars(
                                            $gym[
                                                "city"
                                            ]
                                        );

                                        ?>


                                    <?php endif; ?>


                                </option>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <option
                                value=""
                                disabled
                            >

                                No other gyms available

                            </option>


                        <?php endif; ?>


                    </select>


                    <small>

                        Only approved and active
                        GymConnect gyms are available
                        for membership transfer.

                    </small>


                </div>



                <!-- =================================================
                     REASON
                ================================================== -->

                <div class="form-group">


                    <label>
                        Reason for Transfer
                    </label>


                    <textarea
                        name="reason"
                        placeholder="Enter the reason why you want to transfer your membership..."
                        required
                    ></textarea>


                </div>



                <!-- =================================================
                     TRANSFER FEE
                ================================================== -->

                <div class="transfer-fee-box">


                    <h3>
                        Transfer Fee
                    </h3>


                    <p>

                        A transfer fee of ₹50 is charged
                        for processing the membership transfer.

                        The destination gym does not receive
                        any part of this transfer fee.

                    </p>



                    <div class="fee-details">


                        <div class="fee-item">


                            <span>
                                Total Transfer Fee
                            </span>


                            <strong>

                                ₹<?php

                                echo number_format(
                                    $transfer_fee,
                                    2
                                );

                                ?>

                            </strong>


                        </div>



                        <div class="fee-item">


                            <span>
                                Original Gym Share
                            </span>


                            <strong>

                                ₹<?php

                                echo number_format(
                                    $original_gym_amount,
                                    2
                                );

                                ?>

                            </strong>


                        </div>



                        <div class="fee-item">


                            <span>
                                GymConnect Share
                            </span>


                            <strong>

                                ₹<?php

                                echo number_format(
                                    $gymconnect_amount,
                                    2
                                );

                                ?>

                            </strong>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                     IMPORTANT NOTE
                ================================================== -->

                <div class="warning-box">


                    <div class="warning-icon">

                        !

                    </div>


                    <p>

                        Once you submit the request,
                        you cannot submit another transfer
                        request until this request is cancelled
                        or completed.

                        Your current membership will remain
                        active until the transfer process
                        is successfully completed.

                    </p>


                </div>



                <!-- =================================================
                     FORM ACTIONS
                ================================================== -->

                <div class="form-actions">


                    <a
                        href="dashboard.php"
                        class="cancel-btn"
                    >

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        Submit Transfer Request

                    </button>


                </div>


            </form>


        </div>


    <?php endif; ?>


</div>


</body>

</html>