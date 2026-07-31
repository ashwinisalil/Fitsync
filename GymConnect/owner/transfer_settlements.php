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

$success = "";
$error = "";


/* =========================================================
   PROCESS SETTLEMENT
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['complete_settlement'])
) {

    $transfer_id = isset($_POST['transfer_id'])
        ? (int) $_POST['transfer_id']
        : 0;

    if ($transfer_id <= 0) {

        $error = "Invalid transfer request.";

    } else {

        $conn->begin_transaction();

        try {

            /* =============================================
               GET TRANSFER
            ============================================== */

            $stmt = $conn->prepare("
                SELECT
                    mt.transfer_id,
                    mt.membership_id,
                    mt.member_id,
                    mt.from_gym_id,
                    mt.to_gym_id,
                    mt.transfer_fee,
                    mt.original_gym_amount,
                    mt.gymconnect_amount,
                    mt.remaining_membership_value,
                    mt.status,
                    mt.payment_status,
                    mt.settlement_status,

                    fg.gym_name AS from_gym_name,
                    tg.gym_name AS to_gym_name

                FROM membership_transfers mt

                INNER JOIN gyms fg
                    ON mt.from_gym_id = fg.gym_id

                INNER JOIN gyms tg
                    ON mt.to_gym_id = tg.gym_id

                WHERE mt.transfer_id = ?
                AND fg.owner_id = ?

                LIMIT 1

                FOR UPDATE
            ");

            if (!$stmt) {
                throw new Exception(
                    "Database error: " . $conn->error
                );
            }

            $stmt->bind_param(
                "ii",
                $transfer_id,
                $owner_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    "Unable to load transfer."
                );
            }

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception(
                    "Transfer not found or you are not authorized."
                );
            }

            $transfer = $result->fetch_assoc();

            $stmt->close();


            /* =============================================
               CHECK MEMBER PAYMENT
            ============================================== */

            if (
                strtolower(
                    trim($transfer['payment_status'])
                ) !== 'paid'
            ) {
                throw new Exception(
                    "Member has not paid the ₹500 transfer fee yet."
                );
            }


            /* =============================================
               CHECK TRANSFER STATUS
            ============================================== */

            if (
                strtolower(
                    trim($transfer['status'])
                ) !== 'settlement pending'
            ) {
                throw new Exception(
                    "This transfer is not waiting for settlement."
                );
            }


            /* =============================================
               CHECK SETTLEMENT STATUS
            ============================================== */

            if (
                strtolower(
                    trim($transfer['settlement_status'])
                ) === 'completed'
            ) {
                throw new Exception(
                    "Settlement is already completed."
                );
            }


            /* =============================================
               GET REMAINING MEMBERSHIP VALUE
            ============================================== */

            $settlement_amount = (float)
                $transfer['remaining_membership_value'];


            if ($settlement_amount <= 0) {
                throw new Exception(
                    "Remaining membership value is ₹0. Please check the transfer request calculation."
                );
            }


            /* =============================================
               COMPLETE SETTLEMENT

               In your current system this records the
               settlement as completed.

               A real payment gateway should be connected
               here if actual money transfer is required.
            ============================================== */

            $update = $conn->prepare("
                UPDATE membership_transfers

                SET
                    settlement_status = 'Completed',
                    status = 'Completed',
                    completed_at = NOW()

                WHERE transfer_id = ?

                AND status = 'Settlement Pending'

                AND settlement_status = 'Pending'
            ");

            if (!$update) {
                throw new Exception(
                    "Unable to prepare settlement update."
                );
            }

            $update->bind_param(
                "i",
                $transfer_id
            );

            if (!$update->execute()) {
                throw new Exception(
                    "Unable to complete settlement."
                );
            }

            if ($update->affected_rows !== 1) {
                throw new Exception(
                    "Settlement could not be completed."
                );
            }

            $update->close();


            /* =============================================
               COMMIT
            ============================================== */

            $conn->commit();

            $success =
                "Settlement of ₹" .
                number_format(
                    $settlement_amount,
                    2
                ) .
                " completed successfully. " .
                "The membership transfer is now fully completed.";

        } catch (Exception $e) {

            $conn->rollback();

            $error = $e->getMessage();
        }
    }
}


/* =========================================================
   FETCH PENDING SETTLEMENTS
========================================================= */

$settlements = [];

$stmt = $conn->prepare("
    SELECT

        mt.transfer_id,
        mt.membership_id,
        mt.member_id,

        mt.from_gym_id,
        mt.to_gym_id,

        mt.transfer_fee,
        mt.original_gym_amount,
        mt.gymconnect_amount,
        mt.remaining_membership_value,

        mt.status,
        mt.payment_status,
        mt.settlement_status,

        mt.paid_at,
        mt.requested_at,
        mt.approved_at,

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

    WHERE fg.owner_id = ?

    AND mt.status = 'Settlement Pending'

    AND mt.payment_status = 'Paid'

    AND mt.settlement_status = 'Pending'

    ORDER BY mt.transfer_id DESC
");

if (!$stmt) {
    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );
}

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $settlements[] = $row;
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
        Transfer Settlements | GymConnect
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
            max-width: 1200px;
            margin: auto;
        }

        .back-btn {
            display: inline-block;
            text-decoration: none;
            color: #ffffff;
            background: #171d25;
            padding: 11px 18px;
            border-radius: 7px;
            margin-bottom: 25px;
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
            padding: 16px 20px;
            border-radius: 9px;
            margin-bottom: 25px;
        }

        .success {
            background: #10251a;
            border: 1px solid #267647;
            color: #72e79a;
        }

        .error {
            background: #321719;
            border: 1px solid #793238;
            color: #ff8585;
        }

        .empty {
            background: #11161d;
            border: 1px solid #252d38;
            border-radius: 14px;
            padding: 50px;
            text-align: center;
            color: #91a0b3;
        }

        .settlement-card {
            background: #11161d;
            border: 1px solid #252d38;
            border-radius: 14px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .card-header h2 {
            font-size: 22px;
        }

        .badge {
            padding: 8px 15px;
            border-radius: 20px;
            background: #3b3016;
            color: #ffd866;
            font-size: 13px;
            font-weight: bold;
        }

        .route {
            display: grid;
            grid-template-columns: 1fr 60px 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 25px;
        }

        .gym-box {
            background: #0b1016;
            padding: 20px;
            border-radius: 10px;
        }

        .gym-box span {
            display: block;
            color: #91a0b3;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .gym-box strong {
            font-size: 18px;
        }

        .arrow {
            text-align: center;
            color: #20d47a;
            font-size: 30px;
        }

        .details {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .detail-box {
            background: #0b1016;
            padding: 18px;
            border-radius: 9px;
        }

        .detail-box span {
            display: block;
            color: #91a0b3;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .detail-box strong {
            font-size: 17px;
        }

        .settlement-amount {
            background: #12271b;
            border: 1px solid #267647;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .settlement-amount span {
            display: block;
            color: #91a0b3;
            margin-bottom: 8px;
        }

        .settlement-amount strong {
            color: #72e79a;
            font-size: 30px;
        }

        .payment-note {
            color: #91a0b3;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .pay-btn {
            background: #20ce7a;
            color: #04150d;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .pay-btn:hover {
            background: #28e28a;
        }

        @media (max-width: 700px) {

            body {
                padding: 25px 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .route {
                grid-template-columns: 1fr;
            }

            .arrow {
                transform: rotate(90deg);
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
        Transfer Settlements
    </h1>

    <p class="subtitle">
        Manage remaining membership value
        settlements for transferred memberships.
    </p>


    <?php if ($success !== ""): ?>

        <div class="message success">

            ✓

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="message error">

            ✕

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <?php if (count($settlements) === 0): ?>

        <div class="empty">

            <h2>
                No Pending Settlements
            </h2>

            <br>

            There are currently no
            membership transfer settlements
            waiting for payment.

        </div>

    <?php else: ?>


        <?php foreach ($settlements as $settlement): ?>

            <?php

            /*
             * The settlement amount is ONLY
             * the remaining membership value.
             */

            $settlement_amount = (float)
                $settlement[
                    'remaining_membership_value'
                ];

            ?>


            <div class="settlement-card">

                <div class="card-header">

                    <div>

                        <h2>
                            Membership Transfer
                        </h2>

                        <br>

                        Member:

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $settlement[
                                    'member_name'
                                ]
                            );
                            ?>
                        </strong>

                    </div>

                    <span class="badge">
                        Settlement Pending
                    </span>

                </div>


                <div class="route">

                    <div class="gym-box">

                        <span>
                            Old Gym (Your Gym)
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $settlement[
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
                            New Gym
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $settlement[
                                    'to_gym_name'
                                ]
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="details">

                    <div class="detail-box">

                        <span>
                            Member
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $settlement[
                                    'member_name'
                                ]
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            Transfer Fee Paid
                        </span>

                        <strong>
                            ₹500.00
                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            Transfer Status
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $settlement[
                                    'status'
                                ]
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            Settlement Status
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $settlement[
                                    'settlement_status'
                                ]
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="settlement-amount">

                    <span>
                        Remaining Membership Value
                    </span>

                    <strong>
                        ₹<?php
                        echo number_format(
                            $settlement_amount,
                            2
                        );
                        ?>
                    </strong>

                </div>


                <p class="payment-note">

                    The member has already paid the
                    ₹500 transfer fee.

                    <br><br>

                    The amount shown above is the
                    remaining value of the unused
                    membership period that must be
                    settled with the new gym.

                </p>


                <form
                    method="POST"
                    onsubmit="
                        return confirm(
                            'Confirm settlement payment of ₹<?php echo number_format($settlement_amount, 2); ?>?'
                        );
                    "
                >

                    <input
                        type="hidden"
                        name="transfer_id"
                        value="<?php
                        echo (int)
                            $settlement[
                                'transfer_id'
                            ];
                        ?>"
                    >

                    <button
                        type="submit"
                        name="complete_settlement"
                        class="pay-btn"
                    >

                        Pay Settlement ₹<?php
                        echo number_format(
                            $settlement_amount,
                            2
                        );
                        ?>

                        & Complete Transfer

                    </button>

                </form>

            </div>

        <?php endforeach; ?>


    <?php endif; ?>

</div>

</body>

</html>