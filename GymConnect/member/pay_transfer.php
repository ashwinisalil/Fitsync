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
   GET TRANSFER ID
========================================================= */

$transfer_id = 0;

if (isset($_GET['id'])) {

    $transfer_id = (int) $_GET['id'];

} elseif (isset($_POST['transfer_id'])) {

    $transfer_id = (int) $_POST['transfer_id'];

}

if ($transfer_id <= 0) {

    die("Invalid transfer request.");

}


/* =========================================================
   LOAD TRANSFER
========================================================= */

$stmt = $conn->prepare("
    SELECT

        mt.*,

        fg.gym_name AS from_gym_name,

        tg.gym_name AS to_gym_name

    FROM membership_transfers mt

    INNER JOIN gyms fg
        ON mt.from_gym_id = fg.gym_id

    INNER JOIN gyms tg
        ON mt.to_gym_id = tg.gym_id

    WHERE mt.transfer_id = ?

    AND mt.member_id = ?

    LIMIT 1
");

if (!$stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}

$stmt->bind_param(
    "ii",
    $transfer_id,
    $member_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    die("Invalid transfer request.");

}

$transfer = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   GET IMPORTANT IDs
========================================================= */

$membership_id =
    (int) $transfer['membership_id'];

$from_gym_id =
    (int) $transfer['from_gym_id'];

$to_gym_id =
    (int) $transfer['to_gym_id'];

$transfer_fee =
    (float) $transfer['transfer_fee'];


/* =========================================================
   VALIDATE GYM IDS
========================================================= */

if (
    $from_gym_id <= 0 ||
    $to_gym_id <= 0
) {

    die(
        "Transfer cannot continue because the source or destination gym ID is invalid."
    );

}


/* =========================================================
   TRANSFER FEE DISTRIBUTION
========================================================= */

$gymconnect_fee = 50.00;

$destination_gym_fee = 450.00;


/*
 * Make sure the configured transfer fee
 * is exactly ₹500.
 */

if (
    abs(
        $transfer_fee - 500.00
    ) > 0.01
) {

    $transfer_fee = 500.00;

}


/* =========================================================
   SUCCESS / ERROR
========================================================= */

$success = "";

$error = "";


/* =========================================================
   PROCESS PAYMENT
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['pay_transfer'])
) {

    $conn->begin_transaction();

    try {


        /* =================================================
           STEP 1
           LOCK AND RECHECK TRANSFER
        ================================================= */

        $check = $conn->prepare("
            SELECT

                *

            FROM membership_transfers

            WHERE transfer_id = ?

            AND member_id = ?

            FOR UPDATE
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

        $current =
            $check_result->fetch_assoc();

        $check->close();


        /* =================================================
           UPDATE LATEST VALUES
        ================================================= */

        $membership_id =
            (int) $current['membership_id'];

        $from_gym_id =
            (int) $current['from_gym_id'];

        $to_gym_id =
            (int) $current['to_gym_id'];

        $transfer_fee =
            (float) $current['transfer_fee'];


        /* =================================================
           VALIDATE GYM IDS AGAIN
        ================================================= */

        if (
            $from_gym_id <= 0 ||
            $to_gym_id <= 0
        ) {

            throw new Exception(
                "Source or destination gym ID is missing."
            );

        }


        /* =================================================
           CHECK PAYMENT STATUS
        ================================================= */

        if (
            strtolower(
                trim(
                    $current['payment_status']
                )
            ) === 'paid'
        ) {

            throw new Exception(
                "Transfer payment has already been completed."
            );

        }


        /* =================================================
           CHECK TRANSFER STATUS
        ================================================= */

        $allowed_statuses = [

            'Approved',

            'Awaiting Payment'

        ];

        if (
            !in_array(
                trim(
                    $current['status']
                ),
                $allowed_statuses,
                true
            )
        ) {

            throw new Exception(

                "Payment is not available for this transfer. " .

                "Current status: " .

                $current['status']

            );

        }


        /* =================================================
           STEP 2
           VERIFY OLD MEMBERSHIP
        ================================================= */

        $membership_check = $conn->prepare("
            SELECT

                membership_id,

                member_id,

                gym_id,

                plan_id,

                start_date,

                end_date,

                status

            FROM memberships

            WHERE membership_id = ?

            AND member_id = ?

            AND gym_id = ?

            LIMIT 1

            FOR UPDATE
        ");

        if (!$membership_check) {

            throw new Exception(
                "Unable to verify membership."
            );

        }

        $membership_check->bind_param(
            "iii",
            $membership_id,
            $member_id,
            $from_gym_id
        );

        $membership_check->execute();

        $membership_result =
            $membership_check->get_result();

        if (
            $membership_result->num_rows === 0
        ) {

            throw new Exception(
                "Original membership could not be found."
            );

        }

        $old_membership =
            $membership_result->fetch_assoc();

        $membership_check->close();


        /* =================================================
           STEP 3
           CHECK EXISTING TRANSFER PAYMENT
        ================================================= */

        $payment_check = $conn->prepare("
            SELECT

                payment_id

            FROM payments

            WHERE transaction_id = ?

            LIMIT 1
        ");

        $transfer_transaction_id =
            "TRF-" .
            $transfer_id;

        $payment_check->bind_param(
            "s",
            $transfer_transaction_id
        );

        $payment_check->execute();

        $payment_result =
            $payment_check->get_result();

        $payment_exists =
            $payment_result->num_rows > 0;

        $payment_check->close();


        /* =================================================
           STEP 4
           RECORD ₹450 FOR DESTINATION GYM
        ================================================= */

        if (
            !$payment_exists
        ) {


            $payment_method =
                "Online";


            $payment_notes =

                "Transfer fee distribution. " .

                "Total paid by member: ₹500. " .

                "Destination gym share: ₹450. " .

                "GymConnect platform fee: ₹50. " .

                "Transfer ID: " .

                $transfer_id;


            /*
             * IMPORTANT:
             *
             * gym_id = destination gym
             *
             * This is NEVER NULL.
             */

            $gym_payment = $conn->prepare("
                INSERT INTO payments
                (
                    membership_id,

                    member_id,

                    gym_id,

                    amount,

                    payment_type,

                    payment_method,

                    transaction_id,

                    payment_status,

                    payment_date,

                    notes,

                    recorded_by
                )

                VALUES
                (
                    ?,

                    ?,

                    ?,

                    ?,

                    'Transfer',

                    ?,

                    ?,

                    'Completed',

                    NOW(),

                    ?,

                    ?
                )
            ");

            if (!$gym_payment) {

                throw new Exception(

                    "Unable to create gym payment record: " .

                    $conn->error

                );

            }


            $recorded_by =
                $member_id;


            $gym_payment->bind_param(

                "iiidsssi",

                $membership_id,

                $member_id,

                $to_gym_id,

                $destination_gym_fee,

                $payment_method,

                $transfer_transaction_id,

                $payment_notes,

                $recorded_by

            );


            if (
                !$gym_payment->execute()
            ) {

                throw new Exception(

                    "Unable to record destination gym payment: " .

                    $gym_payment->error

                );

            }

            $gym_payment->close();

        }


        /* =================================================
           STEP 5
           RECORD ₹50 GYMCONNECT REVENUE
        ================================================= */

        $revenue_check = $conn->prepare("
            SELECT

                revenue_id

            FROM gymconnect_revenue

            WHERE transfer_id = ?

            AND revenue_type = 'Transfer Fee'

            LIMIT 1
        ");

        if (!$revenue_check) {

            throw new Exception(

                "Unable to check GymConnect revenue."

            );

        }

        $revenue_check->bind_param(
            "i",
            $transfer_id
        );

        $revenue_check->execute();

        $revenue_result =
            $revenue_check->get_result();

        $revenue_exists =
            $revenue_result->num_rows > 0;

        $revenue_check->close();


        if (
            !$revenue_exists
        ) {


            $gymconnect_transaction_id =

                "GC-TRF-" .

                $transfer_id;


            $revenue_notes =

                "GymConnect platform fee from membership transfer. " .

                "Total transfer fee: ₹500. " .

                "Destination gym received: ₹450. " .

                "GymConnect received: ₹50. " .

                "Transfer ID: " .

                $transfer_id;


            $revenue_insert = $conn->prepare("
                INSERT INTO gymconnect_revenue
                (
                    transfer_id,

                    member_id,

                    from_gym_id,

                    to_gym_id,

                    amount,

                    revenue_type,

                    transaction_id,

                    payment_status,

                    payment_date,

                    notes
                )

                VALUES
                (
                    ?,

                    ?,

                    ?,

                    ?,

                    ?,

                    'Transfer Fee',

                    ?,

                    'Completed',

                    NOW(),

                    ?
                )
            ");

            if (!$revenue_insert) {

                throw new Exception(

                    "Unable to create GymConnect revenue record: " .

                    $conn->error

                );

            }


            $revenue_insert->bind_param(

                "iiiidss",

                $transfer_id,

                $member_id,

                $from_gym_id,

                $to_gym_id,

                $gymconnect_fee,

                $gymconnect_transaction_id,

                $revenue_notes

            );


            if (
                !$revenue_insert->execute()
            ) {

                throw new Exception(

                    "Unable to record GymConnect revenue: " .

                    $revenue_insert->error

                );

            }


            $revenue_insert->close();

        }


        /* =================================================
           STEP 6
           MARK OLD MEMBERSHIP AS TRANSFERRED
        ================================================= */

        $old_membership_update =
            $conn->prepare("
                UPDATE memberships

                SET status = 'Transferred'

                WHERE membership_id = ?

                AND member_id = ?

                AND gym_id = ?

                AND status = 'Active'
            ");

        if (!$old_membership_update) {

            throw new Exception(
                "Unable to update old membership."
            );

        }

        $old_membership_update->bind_param(
            "iii",
            $membership_id,
            $member_id,
            $from_gym_id
        );

        $old_membership_update->execute();

        if (
            $old_membership_update->affected_rows !== 1
        ) {

            throw new Exception(
                "Old membership could not be transferred."
            );

        }

        $old_membership_update->close();


        /* =================================================
           STEP 7
           CHECK DESTINATION MEMBERSHIP
        ================================================= */

        $new_membership_check =
            $conn->prepare("
                SELECT

                    membership_id

                FROM memberships

                WHERE member_id = ?

                AND gym_id = ?

                AND status = 'Active'

                LIMIT 1

                FOR UPDATE
            ");

        if (!$new_membership_check) {

            throw new Exception(
                "Unable to check destination membership."
            );

        }

        $new_membership_check->bind_param(
            "ii",
            $member_id,
            $to_gym_id
        );

        $new_membership_check->execute();

        $new_membership_result =
            $new_membership_check->get_result();

        $already_has_membership =
            $new_membership_result->num_rows > 0;

        $new_membership_check->close();


        /* =================================================
           STEP 8
           CREATE NEW MEMBERSHIP
        ================================================= */

        if (
            !$already_has_membership
        ) {


            $plan_id =
                (int)
                $old_membership['plan_id'];


            $new_membership_insert =
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

            if (!$new_membership_insert) {

                throw new Exception(
                    "Unable to create new membership."
                );

            }

            $new_membership_insert->bind_param(

                "iiiss",

                $member_id,

                $to_gym_id,

                $plan_id,

                $old_membership['start_date'],

                $old_membership['end_date']

            );

            if (
                !$new_membership_insert->execute()
            ) {

                throw new Exception(

                    "Unable to create new gym membership: " .

                    $new_membership_insert->error

                );

            }

            $new_membership_id =
                $conn->insert_id;

            $new_membership_insert->close();

        } else {


            $existing_membership =
                $new_membership_result
                ->fetch_assoc();


            $new_membership_id =
                (int)
                $existing_membership[
                    'membership_id'
                ];

        }


        /* =================================================
           STEP 9
           CALCULATE REMAINING MEMBERSHIP VALUE
        ================================================= */

        $remaining_membership_value =

            (float)
            (
                $current[
                    'remaining_membership_value'
                ]
                ?? 0
            );


        if (
            $remaining_membership_value <= 0
        ) {


            $plan_stmt =
                $conn->prepare("
                    SELECT price

                    FROM membership_plans

                    WHERE plan_id = ?

                    LIMIT 1
                ");

            $plan_id =
                (int)
                $old_membership[
                    'plan_id'
                ];

            $plan_stmt->bind_param(
                "i",
                $plan_id
            );

            $plan_stmt->execute();

            $plan_result =
                $plan_stmt->get_result();

            if (
                $plan_result->num_rows > 0
            ) {

                $plan_row =
                    $plan_result->fetch_assoc();

                $plan_price =
                    (float)
                    $plan_row['price'];

                $original_gym_amount =

                    (float)
                    (
                        $current[
                            'original_gym_amount'
                        ]
                        ?? 0
                    );

                $remaining_membership_value =

                    max(

                        0,

                        $plan_price
                        -
                        $original_gym_amount

                    );

            }

            $plan_stmt->close();

        }


        /* =================================================
           STEP 10
           CREATE SETTLEMENT RECORD
        ================================================= */

        if (
            $remaining_membership_value > 0
        ) {


            $settlement_check =
                $conn->prepare("
                    SELECT settlement_id

                    FROM membership_transfer_settlements

                    WHERE transfer_id = ?

                    LIMIT 1
                ");

            if (!$settlement_check) {

                throw new Exception(
                    "Unable to check settlement."
                );

            }

            $settlement_check->bind_param(
                "i",
                $transfer_id
            );

            $settlement_check->execute();

            $settlement_result =
                $settlement_check->get_result();

            $settlement_exists =
                $settlement_result->num_rows > 0;

            $settlement_check->close();


            if (
                !$settlement_exists
            ) {


                $settlement_payment_status =
                    "Pending";


                $settlement_insert =
                    $conn->prepare("
                        INSERT INTO membership_transfer_settlements
                        (
                            transfer_id,

                            membership_id,

                            from_gym_id,

                            to_gym_id,

                            amount,

                            payment_status
                        )

                        VALUES
                        (
                            ?,

                            ?,

                            ?,

                            ?,

                            ?,

                            ?
                        )
                    ");

                if (!$settlement_insert) {

                    throw new Exception(

                        "Unable to create settlement: " .

                        $conn->error

                    );

                }

                $settlement_insert->bind_param(

                    "iiiids",

                    $transfer_id,

                    $membership_id,

                    $from_gym_id,

                    $to_gym_id,

                    $remaining_membership_value,

                    $settlement_payment_status

                );

                if (
                    !$settlement_insert->execute()
                ) {

                    throw new Exception(

                        "Unable to create settlement: " .

                        $settlement_insert->error

                    );

                }

                $settlement_insert->close();

            }

        }


        /* =================================================
           STEP 11
           UPDATE TRANSFER
        ================================================= */

        $update_transfer =
            $conn->prepare("
                UPDATE membership_transfers

                SET

                    member_payment_id =

                        COALESCE(
                            member_payment_id,
                            0
                        ),

                    payment_status = 'Paid',

                    status =

                        CASE

                            WHEN ? > 0

                            THEN 'Settlement Pending'

                            ELSE 'Completed'

                        END,

                    settlement_status =

                        CASE

                            WHEN ? > 0

                            THEN 'Pending'

                            ELSE 'Completed'

                        END,

                    remaining_membership_value = ?,

                    paid_at =

                        COALESCE(
                            paid_at,
                            NOW()
                        )

                WHERE transfer_id = ?

                AND member_id = ?
            ");

        if (!$update_transfer) {

            throw new Exception(
                "Unable to update transfer."
            );

        }

        $update_transfer->bind_param(

            "ddiii",

            $remaining_membership_value,

            $remaining_membership_value,

            $remaining_membership_value,

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

        $update_transfer->close();


        /* =================================================
           COMMIT
        ================================================= */

        $conn->commit();


        $success =

            "₹500 transfer fee paid successfully. " .

            "₹450 has been allocated to the destination gym " .

            "and ₹50 has been recorded as GymConnect revenue. " .

            "Your membership has been transferred successfully.";


    }

    catch (
        Exception $e
    ) {


        $conn->rollback();


        $error =
            $e->getMessage();

    }

}


/* =========================================================
   RELOAD TRANSFER
========================================================= */

$reload_stmt = $conn->prepare("
    SELECT

        mt.*,

        fg.gym_name AS from_gym_name,

        tg.gym_name AS to_gym_name

    FROM membership_transfers mt

    INNER JOIN gyms fg
        ON mt.from_gym_id = fg.gym_id

    INNER JOIN gyms tg
        ON mt.to_gym_id = tg.gym_id

    WHERE mt.transfer_id = ?

    AND mt.member_id = ?

    LIMIT 1
");

if ($reload_stmt) {

    $reload_stmt->bind_param(
        "ii",
        $transfer_id,
        $member_id
    );

    $reload_stmt->execute();

    $reload_result =
        $reload_stmt->get_result();

    if (
        $reload_result->num_rows > 0
    ) {

        $transfer =
            $reload_result->fetch_assoc();

    }

    $reload_stmt->close();

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
Transfer Payment | GymConnect
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

    max-width: 750px;

    margin: auto;

}

.back {

    display: inline-block;

    color: #ffffff;

    text-decoration: none;

    background: #171d25;

    padding: 11px 18px;

    border-radius: 7px;

    margin-bottom: 25px;

}

h1 {

    font-size: 32px;

    margin-bottom: 10px;

}

.subtitle {

    color: #91a0b3;

    margin-bottom: 30px;

}

.card {

    background: #11161d;

    border: 1px solid #252d38;

    border-radius: 14px;

    padding: 30px;

}

.route {

    display: grid;

    grid-template-columns: 1fr 60px 1fr;

    gap: 15px;

    align-items: center;

    margin: 25px 0;

}

.gym {

    background: #0b1016;

    padding: 20px;

    border-radius: 10px;

}

.gym span {

    display: block;

    color: #91a0b3;

    margin-bottom: 8px;

}

.gym strong {

    font-size: 18px;

}

.arrow {

    text-align: center;

    color: #20d47a;

    font-size: 28px;

}

.fee {

    background: #0b1016;

    border-radius: 10px;

    padding: 20px;

    margin: 20px 0;

}

.fee span {

    color: #91a0b3;

    display: block;

    margin-bottom: 8px;

}

.fee strong {

    font-size: 28px;

}

.distribution {

    background: #172b43;

    border: 1px solid #28527b;

    border-radius: 10px;

    padding: 20px;

    margin: 20px 0;

    line-height: 1.8;

}

.distribution-row {

    display: flex;

    justify-content: space-between;

    padding: 8px 0;

    border-bottom: 1px solid #2c4964;

}

.distribution-row:last-child {

    border-bottom: none;

}

.pay-btn {

    width: 100%;

    border: none;

    padding: 16px;

    background: #20ce7a;

    color: #04150d;

    border-radius: 8px;

    font-size: 17px;

    font-weight: bold;

    cursor: pointer;

}

.pay-btn:hover {

    background: #28e28a;

}

.success {

    background: #10251a;

    border: 1px solid #267647;

    color: #72e79a;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 20px;

}

.error {

    background: #321719;

    border: 1px solid #793238;

    color: #ff8585;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 20px;

}

.done {

    background: #10251a;

    border: 1px solid #267647;

    padding: 20px;

    border-radius: 10px;

    color: #72e79a;

    line-height: 1.8;

}

@media(max-width:600px) {

    .route {

        grid-template-columns: 1fr;

    }

    .arrow {

        transform: rotate(90deg);

    }

    .distribution-row {

        flex-direction: column;

    }

}

</style>

</head>

<body>

<div class="container">

<a
    href="transfer_membership.php"
    class="back"
>
    ← Back to Transfer
</a>

<h1>
Transfer Membership Payment
</h1>

<p class="subtitle">

Complete your ₹500 transfer fee payment.

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


<div class="card">


<div class="route">


<div class="gym">

<span>
From Gym
</span>

<strong>

<?php

echo htmlspecialchars(
    $transfer['from_gym_name']
);

?>

</strong>

</div>


<div class="arrow">
→
</div>


<div class="gym">

<span>
To Gym
</span>

<strong>

<?php

echo htmlspecialchars(
    $transfer['to_gym_name']
);

?>

</strong>

</div>


</div>


<div class="fee">

<span>
Total Transfer Fee
</span>

<strong>
₹500.00
</strong>

</div>


<div class="distribution">

<strong>
Payment Distribution
</strong>

<div class="distribution-row">

<span>
Destination Gym
</span>

<strong>
₹450.00
</strong>

</div>

<div class="distribution-row">

<span>
GymConnect Platform Fee
</span>

<strong>
₹50.00
</strong>

</div>

<div class="distribution-row">

<span>
Total
</span>

<strong>
₹500.00
</strong>

</div>

</div>


<?php

$final_payment_status =

    strtolower(
        trim(
            $transfer[
                'payment_status'
            ]
        )
    );

?>


<?php if (
    $final_payment_status === 'paid'
): ?>


<div class="done">

<strong>
✓ Payment Completed
</strong>

<br>

Your ₹500 transfer fee has already been paid.

<br>

Destination Gym Share:

<strong>
₹450.00
</strong>

<br>

GymConnect Platform Fee:

<strong>
₹50.00
</strong>

<br>

Transfer Status:

<strong>

<?php

echo htmlspecialchars(
    $transfer['status']
);

?>

</strong>

</div>


<?php else: ?>


<form
    method="POST"
    onsubmit="
        return confirm(
            'Confirm payment of ₹500 and transfer your membership?'
        );
    "
>

<input
    type="hidden"
    name="transfer_id"
    value="<?php

    echo (int)
        $transfer['transfer_id'];

    ?>"
>

<button
    type="submit"
    name="pay_transfer"
    class="pay-btn"
>

Pay ₹500 & Transfer Membership

</button>

</form>


<?php endif; ?>


</div>

</div>

</body>

</html>