<?php

session_start();

require_once "../database/connection.php";


/* =========================================================
   OWNER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'owner'
) {
    header("Location: ../login.php");
    exit();
}


$owner_id = (int) $_SESSION['user_id'];


/* =========================================================
   VARIABLES
========================================================= */

$selected_gym_id = 0;

$selected_gym_name = "";

$success = "";

$error = "";

$gyms = [];

$result = null;


/* =========================================================
   SUCCESS MESSAGE
========================================================= */

if (isset($_GET['success'])) {

    $success = $_GET['success'];

}


/* =========================================================
   GET ALL GYMS OWNED BY CURRENT OWNER
========================================================= */

$gym_stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name,
        address,
        city,
        state

    FROM gyms

    WHERE owner_id = ?

    ORDER BY gym_name ASC
");


if (!$gym_stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}


$gym_stmt->bind_param(
    "i",
    $owner_id
);


if (!$gym_stmt->execute()) {

    die(
        "Unable to load gyms: " .
        htmlspecialchars($gym_stmt->error)
    );

}


$gym_result =
    $gym_stmt->get_result();


while (
    $gym_row =
    $gym_result->fetch_assoc()
) {

    $gyms[] =
        $gym_row;

}


$gym_stmt->close();


/* =========================================================
   GET SELECTED GYM ID
========================================================= */

if (isset($_GET['gym_id'])) {

    $selected_gym_id =
        (int) $_GET['gym_id'];

}

elseif (isset($_POST['gym_id'])) {

    $selected_gym_id =
        (int) $_POST['gym_id'];

}


/* =========================================================
   VERIFY SELECTED GYM BELONGS TO OWNER
========================================================= */

if ($selected_gym_id > 0) {

    $verify_stmt = $conn->prepare("
        SELECT
            gym_id,
            gym_name

        FROM gyms

        WHERE gym_id = ?

        AND owner_id = ?

        LIMIT 1
    ");


    if (!$verify_stmt) {

        $error =
            "Unable to verify selected gym.";

        $selected_gym_id = 0;

    }

    else {

        $verify_stmt->bind_param(
            "ii",
            $selected_gym_id,
            $owner_id
        );


        $verify_stmt->execute();


        $verify_result =
            $verify_stmt->get_result();


        if (
            $verify_result->num_rows === 0
        ) {

            $error =
                "Invalid gym selected.";

            $selected_gym_id = 0;

        }

        else {

            $selected_gym =
                $verify_result->fetch_assoc();


            $selected_gym_name =
                $selected_gym['gym_name'];

        }


        $verify_stmt->close();

    }

}


/* =========================================================
   GET TRANSFER REQUESTS FOR SELECTED GYM
=========================================================

   IMPORTANT:

   The selected gym is the OLD/CURRENT gym.

   Example:

   Selected Gym:
   Gym A

   Transfer:

   Gym A ---> Gym B

   The owner will see this request.

========================================================= */

if ($selected_gym_id > 0) {

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

            mt.remaining_membership_value,

            mt.settlement_status,

            mt.status,

            mt.payment_status,

            mt.member_payment_id,

            mt.paid_at,

            mt.requested_at,

            mt.approved_at,

            mt.completed_at,

            u.full_name AS member_name,

            u.email AS member_email,

            fg.gym_name AS from_gym_name,

            tg.gym_name AS to_gym_name

        FROM membership_transfers mt

        INNER JOIN users u
            ON mt.member_id = u.user_id

        INNER JOIN gyms fg
            ON mt.from_gym_id = fg.gym_id

        INNER JOIN gyms tg
            ON mt.to_gym_id = tg.gym_id

        WHERE mt.from_gym_id = ?

        ORDER BY
            mt.requested_at DESC
    ");


    if (!$stmt) {

        $error =
            "Database error: " .
            $conn->error;

    }

    else {

        $stmt->bind_param(
            "i",
            $selected_gym_id
        );


        if (!$stmt->execute()) {

            $error =
                "Unable to load transfer requests: " .
                $stmt->error;

        }

        else {

            $result =
                $stmt->get_result();

        }

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
        Transfer Requests | GymConnect
    </title>


    <style>

        * {

            box-sizing: border-box;

            margin: 0;

            padding: 0;

        }


        body {

            font-family: Arial, sans-serif;

            background: #0b0f14;

            color: #ffffff;

            padding: 40px;

        }


        .container {

            max-width: 1200px;

            margin: auto;

        }


        .back-btn {

            display: inline-block;

            margin-bottom: 25px;

            padding: 11px 18px;

            background: #171d25;

            color: #ffffff;

            text-decoration: none;

            border-radius: 7px;

            border: 1px solid #29323e;

        }


        .back-btn:hover {

            background: #202833;

        }


        h1 {

            margin-bottom: 10px;

        }


        .subtitle {

            color: #9ca8b8;

            margin-bottom: 30px;

        }


        .card {

            background: #11161d;

            border: 1px solid #252d38;

            border-radius: 12px;

            padding: 25px;

            margin-bottom: 25px;

        }


        .card h2 {

            margin-bottom: 15px;

        }


        .form-group {

            margin-top: 20px;

        }


        label {

            display: block;

            color: #b8c2d0;

            margin-bottom: 10px;

        }


        select {

            width: 100%;

            padding: 14px;

            background: #0c1117;

            color: #ffffff;

            border: 1px solid #303a47;

            border-radius: 8px;

            font-size: 16px;

            outline: none;

        }


        select:focus {

            border-color: #20ce7a;

        }


        .view-btn {

            display: inline-block;

            margin-top: 20px;

            padding: 13px 25px;

            background: #20ce7a;

            color: #04150d;

            border: none;

            border-radius: 8px;

            font-weight: bold;

            font-size: 16px;

            text-decoration: none;

        }


        .view-btn:hover {

            background: #28e28a;

        }


        .success {

            background: #14251a;

            border: 1px solid #2f7041;

            color: #71d38b;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 25px;

        }


        .error {

            background: #321719;

            border: 1px solid #793238;

            color: #ff8888;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 25px;

        }


        .selected-gym {

            border-color: #20ce7a;

        }


        .selected-gym-title {

            color: #20ce7a;

            font-size: 22px;

            margin-bottom: 10px;

        }


        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(180px, 1fr)
                );

            gap: 20px;

            margin: 20px 0;

        }


        .info-box {

            background: #171d25;

            padding: 15px;

            border-radius: 8px;

        }


        .info-box span {

            display: block;

            color: #8995a5;

            font-size: 13px;

            margin-bottom: 7px;

        }


        .info-box strong {

            color: #ffffff;

        }


        .route-box {

            display: grid;

            grid-template-columns:
                1fr
                60px
                1fr;

            gap: 15px;

            align-items: center;

            margin: 20px 0;

        }


        .route-gym {

            background: #0c1117;

            padding: 20px;

            border-radius: 10px;

            border: 1px solid #29323e;

        }


        .route-gym span {

            display: block;

            color: #8995a5;

            font-size: 13px;

            margin-bottom: 8px;

        }


        .route-gym strong {

            font-size: 18px;

        }


        .arrow {

            text-align: center;

            font-size: 30px;

            color: #20ce7a;

        }


        .status {

            display: inline-block;

            padding: 7px 13px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: bold;

        }


        .pending {

            background: #3b3016;

            color: #ffd866;

        }


        .awaiting {

            background: #172b43;

            color: #6fb7ff;

        }


        .paid {

            background: #14251a;

            color: #71d38b;

        }


        .completed {

            background: #14251a;

            color: #71d38b;

        }


        .rejected {

            background: #321b1b;

            color: #ff8585;

        }


        .approve-btn {

            display: inline-block;

            background: #1fa463;

            color: #ffffff;

            padding: 11px 20px;

            border-radius: 7px;

            text-decoration: none;

            font-weight: bold;

            margin-top: 20px;

        }


        .approve-btn:hover {

            background: #178850;

        }


        .disabled {

            color: #8995a5;

            margin-top: 20px;

            display: block;

        }


        .settlement-box {

            background: #1b1710;

            border: 1px solid #604b20;

            padding: 20px;

            border-radius: 10px;

            margin-top: 20px;

        }


        .settlement-box h3 {

            color: #ffd866;

            margin-bottom: 12px;

        }


        .settlement-amount {

            font-size: 24px;

            font-weight: bold;

            margin-top: 10px;

        }


        .empty {

            text-align: center;

            color: #8995a5;

            padding: 50px;

        }


        @media (
            max-width: 700px
        ) {

            body {

                padding: 25px 15px;

            }


            .route-box {

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

        Transfer Requests

    </h1>


    <p class="subtitle">

        Select a gym to view its
        membership transfer requests.

    </p>



    <?php if ($success !== ""): ?>

        <div class="success">

            ✓

            <?php

            echo htmlspecialchars(
                $success
            );

            ?>

        </div>

    <?php endif; ?>



    <?php if ($error !== ""): ?>

        <div class="error">

            ✕

            <?php

            echo htmlspecialchars(
                $error
            );

            ?>

        </div>

    <?php endif; ?>



    <!-- =========================================
         GYM SELECTION
    ========================================== -->


    <div class="card">


        <h2>

            Select Gym

        </h2>


        <p class="subtitle">

            Choose the gym whose transfer
            requests you want to manage.

        </p>


        <?php if (
            count($gyms) === 0
        ): ?>


            <div class="empty">

                You do not have any registered
                gyms.

            </div>


        <?php else: ?>


            <form
                method="GET"
            >


                <div class="form-group">


                    <label>

                        Select Gym

                    </label>


                    <select
                        name="gym_id"
                        required
                    >


                        <option value="">

                            -- Select Gym --

                        </option>


                        <?php foreach (
                            $gyms
                            as $gym
                        ): ?>


                            <option

                                value="<?php

                                echo (int)
                                    $gym['gym_id'];

                                ?>"

                                <?php

                                if (
                                    $selected_gym_id
                                    ===
                                    (int)
                                    $gym['gym_id']
                                ) {

                                    echo "selected";

                                }

                                ?>

                            >

                                <?php

                                echo htmlspecialchars(
                                    $gym['gym_name']
                                );

                                ?>


                                <?php if (
                                    !empty(
                                        $gym['city']
                                    )
                                ): ?>


                                    -

                                    <?php

                                    echo htmlspecialchars(
                                        $gym['city']
                                    );

                                    ?>


                                <?php endif; ?>


                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>



                <button
                    type="submit"
                    class="view-btn"
                >

                    View Transfer Requests

                </button>


            </form>


        <?php endif; ?>


    </div>



    <!-- =========================================
         SELECTED GYM REQUESTS
    ========================================== -->


    <?php if (
        $selected_gym_id > 0
        &&
        $result !== null
    ): ?>


        <div class="card selected-gym">


            <div class="selected-gym-title">

                <?php

                echo htmlspecialchars(
                    $selected_gym_name
                );

                ?>

            </div>


            <p class="subtitle">

                Showing transfer requests
                from this gym.

            </p>


        </div>



        <?php if (
            $result->num_rows === 0
        ): ?>


            <div class="card empty">

                <h2>

                    No Transfer Requests

                </h2>


                <br>


                No transfer requests have been
                made from this gym.

            </div>


        <?php endif; ?>



        <?php while (
            $row =
            $result->fetch_assoc()
        ): ?>


            <?php

            $transfer_status =
                strtolower(
                    trim(
                        $row['status']
                        ??
                        ''
                    )
                );


            $payment_status =
                strtolower(
                    trim(
                        $row['payment_status']
                        ??
                        ''
                    )
                );


            $settlement_status =
                strtolower(
                    trim(
                        $row['settlement_status']
                        ??
                        ''
                    )
                );


            $status_class =
                'pending';


            if (
                $transfer_status ===
                'awaiting payment'
            ) {

                $status_class =
                    'awaiting';

            }


            if (
                $payment_status ===
                'paid'
            ) {

                $status_class =
                    'paid';

            }


            if (
                $transfer_status ===
                'completed'
            ) {

                $status_class =
                    'completed';

            }


            if (
                $transfer_status ===
                'rejected'
            ) {

                $status_class =
                    'rejected';

            }

            ?>


            <div class="card">


                <h2>

                    Transfer Request #

                    <?php

                    echo (int)
                        $row['transfer_id'];

                    ?>

                </h2>



                <div class="route-box">


                    <div class="route-gym">


                        <span>

                            From Gym

                        </span>


                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $row[
                                    'from_gym_name'
                                ]
                            );

                            ?>

                        </strong>


                    </div>



                    <div class="arrow">

                        →

                    </div>



                    <div class="route-gym">


                        <span>

                            To Gym

                        </span>


                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $row[
                                    'to_gym_name'
                                ]
                            );

                            ?>

                        </strong>


                    </div>


                </div>



                <div class="info-grid">


                    <div class="info-box">


                        <span>

                            Member

                        </span>


                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $row[
                                    'member_name'
                                ]
                            );

                            ?>

                        </strong>


                    </div>



                    <div class="info-box">


                        <span>

                            Email

                        </span>


                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $row[
                                    'member_email'
                                ]
                            );

                            ?>

                        </strong>


                    </div>



                    <div class="info-box">


                        <span>

                            Transfer Fee

                        </span>


                        <strong>

                            ₹<?php

                            echo number_format(
                                (float)
                                $row[
                                    'transfer_fee'
                                ],
                                2
                            );

                            ?>

                        </strong>


                    </div>



                    <div class="info-box">


                        <span>

                            Remaining Membership Value

                        </span>


                        <strong>

                            ₹<?php

                            echo number_format(
                                (float)
                                $row[
                                    'remaining_membership_value'
                                ],
                                2
                            );

                            ?>

                        </strong>


                    </div>


                </div>



                <p>

                    <strong>

                        Reason:

                    </strong>


                    <?php

                    echo htmlspecialchars(
                        $row[
                            'reason'
                        ]
                        ??
                        'No reason provided'
                    );

                    ?>

                </p>



                <br>



                <p>

                    <strong>

                        Transfer Status:

                    </strong>


                    <span
                        class="
                            status
                            <?php

                            echo $status_class;

                            ?>
                        "
                    >

                        <?php

                        echo htmlspecialchars(
                            $row[
                                'status'
                            ]
                        );

                        ?>

                    </span>

                </p>



                <br>



                <p>

                    <strong>

                        Payment Status:

                    </strong>


                    <span
                        class="
                            status

                            <?php

                            echo
                                $payment_status
                                ===
                                'paid'

                                ?

                                'paid'

                                :

                                'pending';

                            ?>
                        "
                    >

                        <?php

                        echo htmlspecialchars(
                            $row[
                                'payment_status'
                            ]
                        );

                        ?>

                    </span>

                </p>



                <br>



                <p>

                    <strong>

                        Settlement Status:

                    </strong>


                    <span
                        class="
                            status

                            <?php

                            echo
                                $settlement_status
                                ===
                                'paid'

                                ?

                                'paid'

                                :

                                'pending';

                            ?>
                        "
                    >

                        <?php

                        echo htmlspecialchars(
                            $row[
                                'settlement_status'
                            ]
                        );

                        ?>

                    </span>

                </p>



                <!-- =================================
                     APPROVE
                ================================== -->


                <?php if (
                    $transfer_status ===
                    'pending'
                ): ?>


                    <a
                        href="
                            approve_transfer.php?id=<?php
                            echo (int)
                                $row[
                                    'transfer_id'
                                ];
                            ?>
                            &gym_id=<?php
                            echo (int)
                                $selected_gym_id;
                            ?>
                        "
                        class="approve-btn"

                        onclick="
                            return confirm(
                                'Are you sure you want to approve this transfer request?'
                            );
                        "
                    >

                        Approve Transfer

                    </a>


                <?php elseif (

                    $transfer_status ===
                    'awaiting payment'

                    &&

                    $payment_status !==
                    'paid'

                ): ?>


                    <span class="disabled">

                        ✓ Transfer approved.

                        <br><br>

                        Waiting for member to pay
                        the ₹500 transfer fee.

                    </span>


                <?php elseif (

                    $payment_status ===
                    'paid'

                    &&

                    $transfer_status !==
                    'completed'

                ): ?>


                    <div class="settlement-box">


                        <h3>

                            Member Payment Completed

                        </h3>


                        <p>

                            Member has paid the
                            transfer fee.

                        </p>


                        <br>


                        <p>

                            Remaining membership
                            value:

                        </p>


                        <div
                            class="settlement-amount"
                        >

                            ₹<?php

                            echo number_format(
                                (float)
                                $row[
                                    'remaining_membership_value'
                                ],
                                2
                            );

                            ?>

                        </div>


                        <br>


                        <p>

                            This amount should be
                            settled with:

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $row[
                                        'to_gym_name'
                                    ]
                                );

                                ?>

                            </strong>

                        </p>


                    </div>


                <?php elseif (

                    $transfer_status ===
                    'completed'

                ): ?>


                    <span class="disabled">

                        ✓ Membership transfer
                        completed successfully.

                    </span>


                <?php elseif (

                    $transfer_status ===
                    'rejected'

                ): ?>


                    <span class="disabled">

                        Transfer request rejected.

                    </span>


                <?php endif; ?>


            </div>


        <?php endwhile; ?>


    <?php endif; ?>


</div>


</body>

</html>


<?php

if (
    isset($stmt)
    &&
    $stmt
) {

    $stmt->close();

}

?>