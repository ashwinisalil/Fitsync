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
   GET TRANSFER REQUEST
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

        mt.remaining_membership_value,

        mt.status,
        mt.payment_status,
        mt.settlement_status,

        mt.requested_at,
        mt.approved_at,

        from_gym.gym_name
        AS from_gym_name,

        to_gym.gym_name
        AS to_gym_name,

        m.start_date,
        m.end_date,

        mp.plan_name,
        mp.price

    FROM membership_transfers mt

    INNER JOIN gyms from_gym
        ON mt.from_gym_id = from_gym.gym_id

    INNER JOIN gyms to_gym
        ON mt.to_gym_id = to_gym.gym_id

    INNER JOIN memberships m
        ON mt.membership_id = m.membership_id

    INNER JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE mt.member_id = ?

    AND mt.status = 'Awaiting Payment'

    AND mt.payment_status = 'Pending'

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
        "No transfer payment is currently pending."
    );

}


$transfer =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   PAYMENT ACTION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    $transfer_id =
        (int)
        ($_POST["transfer_id"] ?? 0);


    /* =====================================================
       VERIFY TRANSFER
    ===================================================== */

    if (
        $transfer_id !==
        (int)
        $transfer["transfer_id"]
    ) {

        die(
            "Invalid transfer request."
        );

    }


    /* =====================================================
       PAYMENT DETAILS
    ===================================================== */

    $amount =
        (float)
        $transfer["transfer_fee"];


    $payment_type =
        "Transfer";


    /*
       For now, payment is simulated.

       Later this will be replaced
       with Razorpay / Stripe payment.
    */

    $payment_method =
        "Online";


    $payment_status =
        "Completed";


    /* =====================================================
       START TRANSACTION
    ===================================================== */

    $conn->begin_transaction();


    try {


        /* =================================================
           CHECK TRANSFER AGAIN

           Prevent duplicate processing
        ================================================= */

        $check = $conn->prepare("
            SELECT

                transfer_id,
                membership_id,
                member_id,
                from_gym_id,
                to_gym_id,

                transfer_fee,
                remaining_membership_value,

                status,
                payment_status

            FROM membership_transfers

            WHERE transfer_id = ?

            AND member_id = ?

            LIMIT 1
        ");


        if (!$check) {

            throw new Exception(
                "Unable to verify transfer."
            );

        }


        $check->bind_param(
            "ii",
            $transfer_id,
            $member_id
        );


        $check->execute();


        $check_result =
            $check->get_result();


        if (
            $check_result->num_rows === 0
        ) {

            throw new Exception(
                "Transfer request not found."
            );

        }


        $current_transfer =
            $check_result->fetch_assoc();


        $check->close();


        /* =================================================
           VERIFY STATUS
        ================================================= */

        if (
            $current_transfer["status"]
            !==
            "Awaiting Payment"
        ) {

            throw new Exception(
                "This transfer is not waiting for payment."
            );

        }


        if (
            $current_transfer["payment_status"]
            !==
            "Pending"
        ) {

            throw new Exception(
                "Payment has already been processed."
            );

        }


        /* =================================================
           GET MEMBERSHIP DETAILS
        ================================================= */

        $membership_id =
            (int)
            $current_transfer["membership_id"];


        $from_gym_id =
            (int)
            $current_transfer["from_gym_id"];


        $to_gym_id =
            (int)
            $current_transfer["to_gym_id"];


        $remaining_value =
            (float)
            $current_transfer[
                "remaining_membership_value"
            ];


        /* =================================================
           GET ORIGINAL MEMBERSHIP
        ================================================= */

        $membership_stmt =
            $conn->prepare("
                SELECT

                    member_id,
                    gym_id,
                    plan_id,
                    start_date,
                    end_date,
                    status

                FROM memberships

                WHERE membership_id = ?

                AND member_id = ?

                LIMIT 1
            ");


        if (!$membership_stmt) {

            throw new Exception(
                "Unable to find membership."
            );

        }


        $membership_stmt->bind_param(
            "ii",
            $membership_id,
            $member_id
        );


        $membership_stmt->execute();


        $membership_result =
            $membership_stmt->get_result();


        if (
            $membership_result->num_rows === 0
        ) {

            throw new Exception(
                "Original membership not found."
            );

        }


        $membership =
            $membership_result->fetch_assoc();


        $membership_stmt->close();


        /* =================================================
           VERIFY ORIGINAL GYM
        ================================================= */

        if (
            (int)
            $membership["gym_id"]
            !==
            $from_gym_id
        ) {

            throw new Exception(
                "Membership and transfer gym do not match."
            );

        }


        /* =================================================
           CREATE PAYMENT RECORD
        ================================================= */

        $payment = $conn->prepare("
            INSERT INTO payments
            (
                membership_id,
                member_id,
                gym_id,
                amount,
                payment_type,
                payment_method,
                payment_status,
                transaction_id,
                notes,
                recorded_by
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
                ?
            )
        ");


        if (!$payment) {

            throw new Exception(
                "Unable to create payment record: "
                . $conn->error
            );

        }


        /*
           Payment is recorded
           against the original membership.

           The gym_id is the original gym.
        */

        $transaction_id =
            "TRF-"
            . $transfer_id
            . "-"
            . time();


        $notes =
            "Membership transfer fee of ₹"
            . number_format(
                $amount,
                2
            )
            . " paid by member. "
            . "Transfer ID: "
            . $transfer_id;


        $recorded_by =
            $member_id;


        $payment->bind_param(
            "iiidsssssi",

            $membership_id,

            $member_id,

            $from_gym_id,

            $amount,

            $payment_type,

            $payment_method,

            $payment_status,

            $transaction_id,

            $notes,

            $recorded_by
        );


        if (
            !$payment->execute()
        ) {

            throw new Exception(
                "Unable to record payment: "
                . $payment->error
            );

        }


        $payment_id =
            $conn->insert_id;


        $payment->close();


        /* =================================================
           MARK OLD MEMBERSHIP AS TRANSFERRED
        ================================================= */

        $old_membership =
            $conn->prepare("
                UPDATE memberships

                SET status = 'Transferred'

                WHERE membership_id = ?

                AND member_id = ?

                AND gym_id = ?

                AND status = 'Active'
            ");


        if (!$old_membership) {

            throw new Exception(
                "Unable to update old membership."
            );

        }


        $old_membership->bind_param(
            "iii",

            $membership_id,

            $member_id,

            $from_gym_id
        );


        if (
            !$old_membership->execute()
        ) {

            throw new Exception(
                "Unable to transfer old membership."
            );

        }


        if (
            $old_membership->affected_rows !== 1
        ) {

            throw new Exception(
                "Original membership is no longer active."
            );

        }


        $old_membership->close();


        /* =================================================
           CREATE NEW MEMBERSHIP
        ================================================= */

        $new_membership =
            $conn->prepare("
                INSERT INTO memberships
                (
                    member_id,
                    gym_id,
                    plan_id,
                    start_date,
                    end_date,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Active'
                )
            ");


        if (!$new_membership) {

            throw new Exception(
                "Unable to create new membership."
            );

        }


        $new_membership->bind_param(
            "iiiss",

            $member_id,

            $to_gym_id,

            $membership["plan_id"],

            $membership["start_date"],

            $membership["end_date"]
        );


        if (
            !$new_membership->execute()
        ) {

            throw new Exception(
                "Unable to create membership at destination gym."
            );

        }


        $new_membership_id =
            $conn->insert_id;


        $new_membership->close();


        /* =================================================
           UPDATE TRANSFER REQUEST
        ================================================= */

        $update_transfer =
            $conn->prepare("
                UPDATE membership_transfers

                SET

                    payment_status = 'Paid',

                    member_payment_id = ?,

                    paid_at = NOW(),

                    status = 'Completed',

                    completed_at = NOW(),

                    settlement_status = 'Pending'

                WHERE transfer_id = ?

                AND member_id = ?

                AND status = 'Awaiting Payment'
            ");


        if (!$update_transfer) {

            throw new Exception(
                "Unable to complete transfer."
            );

        }


        $update_transfer->bind_param(
            "iii",

            $payment_id,

            $transfer_id,

            $member_id
        );


        if (
            !$update_transfer->execute()
        ) {

            throw new Exception(
                "Unable to update transfer status."
            );

        }


        if (
            $update_transfer->affected_rows !== 1
        ) {

            throw new Exception(
                "Transfer could not be completed."
            );

        }


        $update_transfer->close();


        /* =================================================
           CREATE NOTIFICATION
        ================================================= */

        $title =
            "Membership Transfer Completed";


        $message =
            "Your membership has been successfully "
            . "transferred to the new gym. "
            . "Your transfer fee of ₹"
            . number_format(
                $amount,
                2
            )
            . " has been paid successfully.";


        $notification_type =
            "Membership Transfer";


        $notification =
            $conn->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    title,
                    message,
                    notification_type,
                    related_id
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        if (!$notification) {

            throw new Exception(
                "Unable to create notification."
            );

        }


        $notification->bind_param(
            "isssi",

            $member_id,

            $title,

            $message,

            $notification_type,

            $transfer_id
        );


        if (
            !$notification->execute()
        ) {

            throw new Exception(
                "Unable to create notification."
            );

        }


        $notification->close();


        /* =================================================
           COMMIT
        ================================================= */

        $conn->commit();


        header(
            "Location: transfer_request.php?success="
            . urlencode(
                "Payment successful. Your membership has been transferred successfully."
            )
        );

        exit();


    } catch (
        Exception $e
    ) {


        /* =================================================
           ROLLBACK
        ================================================= */

        $conn->rollback();


        die(
            "Transfer payment failed: "
            . htmlspecialchars(
                $e->getMessage()
            )
        );

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Pay Transfer Fee | GymConnect</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


body {

    font-family: Arial, sans-serif;

    background: #0b0f14;

    color: #ffffff;

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px;

}


.payment-card {

    width: 100%;

    max-width: 650px;

    background: #11161d;

    border: 1px solid #252d38;

    border-radius: 14px;

    padding: 35px;

}


.back-btn {

    display: inline-block;

    color: #9aa8b8;

    text-decoration: none;

    margin-bottom: 25px;

}


.back-btn:hover {

    color: #ffffff;

}


h1 {

    font-size: 30px;

    margin-bottom: 10px;

}


.subtitle {

    color: #8996a6;

    margin-bottom: 30px;

}


.details {

    border-top: 1px solid #29313c;

    border-bottom: 1px solid #29313c;

    padding: 25px 0;

}


.detail-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 12px 0;

}


.label {

    color: #8c99a8;

}


.value {

    font-weight: bold;

    text-align: right;

}


.fee-box {

    margin-top: 25px;

    background: #18231e;

    border: 1px solid #285239;

    border-radius: 10px;

    padding: 20px;

    text-align: center;

}


.fee-label {

    color: #9caf9f;

    margin-bottom: 8px;

}


.fee {

    font-size: 36px;

    font-weight: bold;

}


.pay-btn {

    width: 100%;

    border: none;

    border-radius: 8px;

    padding: 15px;

    margin-top: 25px;

    background: #20c77a;

    color: #06150e;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

}


.pay-btn:hover {

    background: #27e28b;

}


.warning {

    margin-top: 18px;

    color: #8996a6;

    font-size: 13px;

    line-height: 1.6;

    text-align: center;

}

</style>

</head>


<body>


<div class="payment-card">


<a
    href="transfer_request.php"
    class="back-btn"
>
    ← Back to Transfer
</a>


<h1>
    Pay Transfer Fee
</h1>


<p class="subtitle">

    Your transfer request has been approved
    by your current gym owner.

</p>


<div class="details">


    <div class="detail-row">

        <span class="label">
            From Gym
        </span>

        <span class="value">

            <?php
            echo htmlspecialchars(
                $transfer["from_gym_name"]
            );
            ?>

        </span>

    </div>


    <div class="detail-row">

        <span class="label">
            To Gym
        </span>

        <span class="value">

            <?php
            echo htmlspecialchars(
                $transfer["to_gym_name"]
            );
            ?>

        </span>

    </div>


    <div class="detail-row">

        <span class="label">
            Membership Plan
        </span>

        <span class="value">

            <?php
            echo htmlspecialchars(
                $transfer["plan_name"]
            );
            ?>

        </span>

    </div>


    <div class="detail-row">

        <span class="label">
            Remaining Membership Value
        </span>

        <span class="value">

            ₹<?php

            echo number_format(
                (float)
                $transfer[
                    "remaining_membership_value"
                ],
                2
            );

            ?>

        </span>

    </div>


</div>


<div class="fee-box">


    <div class="fee-label">

        Transfer Fee

    </div>


    <div class="fee">

        ₹<?php

        echo number_format(
            (float)
            $transfer["transfer_fee"],
            2
        );

        ?>

    </div>


</div>


<form
    method="POST"
    action=""
>


    <input
        type="hidden"
        name="transfer_id"
        value="<?php
        echo $transfer["transfer_id"];
        ?>"
    >


    <button
        type="submit"
        class="pay-btn"
    >

        Pay ₹<?php

        echo number_format(
            (float)
            $transfer["transfer_fee"],
            2
        );

        ?>

    </button>


</form>


<p class="warning">

    By continuing, you confirm that you want
    to transfer your membership to the selected gym.
    Your current membership will be marked as transferred
    after successful payment.

</p>


</div>


</body>

</html>