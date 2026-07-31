/* =============================================
   STEP 1
   RECORD OUTGOING PAYMENT FOR OLD GYM
============================================= */

$outgoing_transaction_id =
    "SETTLEMENT-OUT-" .
    $settlement_id .
    "-" .
    time();


$outgoing_notes =

    "Settlement payment sent from " .
    $settlement["from_gym_name"] .
    " to " .
    $settlement["to_gym_name"] .
    " for membership transfer #" .
    $transfer_id;


/* ---------------------------------------------
   INSERT OUTGOING PAYMENT
--------------------------------------------- */

$outgoing_payment = $conn->prepare("
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


if (!$outgoing_payment) {

    throw new Exception(
        "Unable to create outgoing payment record."
    );

}


$outgoing_payment->bind_param(

    "iiidsssi",

    $membership_id,

    $settlement["member_id"],

    $from_gym_id,

    $settlement_amount,

    $payment_method,

    $outgoing_transaction_id,

    $outgoing_notes,

    $owner_id

);


if (
    !$outgoing_payment->execute()
) {

    throw new Exception(
        "Unable to record outgoing settlement payment."
    );

}


$outgoing_payment->close();



/* =============================================
   STEP 2
   RECORD INCOMING REVENUE FOR NEW GYM
============================================= */


/*
 * Get the owner ID of the destination gym.
 */

$destination_owner_query = $conn->prepare("
    SELECT owner_id

    FROM gyms

    WHERE gym_id = ?

    LIMIT 1
");


if (!$destination_owner_query) {

    throw new Exception(
        "Unable to find destination gym owner."
    );

}


$destination_owner_query->bind_param(
    "i",
    $to_gym_id
);


if (
    !$destination_owner_query->execute()
) {

    throw new Exception(
        "Unable to get destination gym owner."
    );

}


$destination_owner_result =
    $destination_owner_query->get_result();


$destination_owner =
    $destination_owner_result->fetch_assoc();


$destination_owner_query->close();


if (!$destination_owner) {

    throw new Exception(
        "Destination gym owner could not be found."
    );

}


$destination_owner_id =
    (int)
    $destination_owner["owner_id"];


/* ---------------------------------------------
   CREATE INCOMING TRANSACTION ID
--------------------------------------------- */

$incoming_transaction_id =

    "SETTLEMENT-IN-" .

    $settlement_id .

    "-" .

    time();


$incoming_notes =

    "Settlement received by " .
    $settlement["to_gym_name"] .
    " from " .
    $settlement["from_gym_name"] .
    " for membership transfer #" .
    $transfer_id;


/* ---------------------------------------------
   INSERT INCOMING REVENUE
--------------------------------------------- */

$incoming_payment = $conn->prepare("
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


if (!$incoming_payment) {

    throw new Exception(
        "Unable to create incoming revenue record."
    );

}


$incoming_payment->bind_param(

    "iiidsssi",

    $membership_id,

    $settlement["member_id"],

    $to_gym_id,

    $settlement_amount,

    $payment_method,

    $incoming_transaction_id,

    $incoming_notes,

    $destination_owner_id

);


if (
    !$incoming_payment->execute()
) {

    throw new Exception(
        "Unable to record incoming settlement revenue."
    );

}


$incoming_payment->close();



/* =============================================
   STEP 3
   MARK SETTLEMENT AS PAID
============================================= */

$update = $conn->prepare("
    UPDATE membership_transfer_settlements

    SET

        payment_status = 'Paid',

        payment_method = ?,

        transaction_reference = ?,

        paid_at = NOW()

    WHERE settlement_id = ?

    AND payment_status = 'Pending'
");


if (!$update) {

    throw new Exception(
        "Unable to process settlement payment."
    );

}


$update->bind_param(

    "ssi",

    $payment_method,

    $transaction_reference,

    $settlement_id

);


if (
    !$update->execute()
) {

    throw new Exception(
        "Settlement payment could not be recorded."
    );

}


if (
    $update->affected_rows === 0
) {

    throw new Exception(
        "Settlement was already processed."
    );

}


$update->close();



/* =============================================
   STEP 4
   MARK MAIN TRANSFER AS COMPLETED
============================================= */

$transfer_update = $conn->prepare("
    UPDATE membership_transfers

    SET

        settlement_status = 'Completed',

        status = 'Completed',

        completed_at = NOW()

    WHERE transfer_id = ?

    AND settlement_status = 'Pending'
");


if (!$transfer_update) {

    throw new Exception(
        "Unable to update transfer settlement status."
    );

}


$transfer_update->bind_param(
    "i",
    $transfer_id
);


if (
    !$transfer_update->execute()
) {

    throw new Exception(
        "Unable to complete membership transfer."
    );

}


$transfer_update->close();



/* =============================================
   STEP 5
   NOTIFY NEW GYM OWNER
============================================= */

$notification_title =
    "Settlement Payment Received";


$notification_message =

    "The old gym owner has paid ₹" .

    number_format(
        $settlement_amount,
        2
    ) .

    " as settlement for the membership transfer of " .

    $settlement["member_name"] .

    ". The settlement has been recorded successfully.";


$notification_type =
    "Settlement";


$notification = $conn->prepare("
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


if ($notification) {

    $notification->bind_param(

        "isssi",

        $destination_owner_id,

        $notification_title,

        $notification_message,

        $notification_type,

        $settlement_id

    );


    $notification->execute();


    $notification->close();

}



/* =============================================
   STEP 6
   COMMIT EVERYTHING
============================================= */

$conn->commit();


header(
    "Location: transfer_settlements.php?payment=success"
);


exit();