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


$owner_id =
    (int) $_SESSION["user_id"];


/* =========================================================
   ONLY POST REQUEST
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    !== "POST"
) {

    header(
        "Location: received_settlements.php"
    );

    exit();

}


/* =========================================================
   GET SETTLEMENT ID
========================================================= */

$settlement_id =
    isset(
        $_POST["settlement_id"]
    )
    ? (int)
        $_POST["settlement_id"]
    : 0;


if (
    $settlement_id <= 0
) {

    die(
        "Invalid settlement ID."
    );

}


/* =========================================================
   GET SETTLEMENT
========================================================= */

$stmt =
    $conn->prepare("
        SELECT

            mts.settlement_id,

            mts.amount,

            mts.payment_status,

            mts.to_gym_id,

            mt.member_id,

            tg.owner_id AS destination_owner_id

        FROM membership_transfer_settlements mts

        INNER JOIN membership_transfers mt

            ON mts.transfer_id =
               mt.transfer_id

        INNER JOIN gyms tg

            ON mts.to_gym_id =
               tg.gym_id

        WHERE mts.settlement_id = ?

        LIMIT 1
    ");


if (!$stmt) {

    die(
        "Database error."
    );

}


$stmt->bind_param(
    "i",
    $settlement_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    $stmt->close();


    die(
        "Settlement not found."
    );

}


$settlement =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   VERIFY NEW OWNER
========================================================= */

if (
    (int)
    $settlement[
        "destination_owner_id"
    ]
    !== $owner_id
) {

    die(
        "You are not authorized to confirm this settlement."
    );

}


/* =========================================================
   CHECK STATUS
========================================================= */

if (
    $settlement[
        "payment_status"
    ]
    !== "Paid"
) {

    die(
        "This settlement is not awaiting confirmation."
    );

}


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       UPDATE SETTLEMENT
    ====================================================== */

    $update =
        $conn->prepare("
            UPDATE
            membership_transfer_settlements

            SET
                payment_status = 'Completed'

            WHERE settlement_id = ?

            AND to_gym_id = ?

            AND payment_status = 'Paid'
        ");


    if (!$update) {

        throw new Exception(
            "Unable to update settlement."
        );

    }


    $update->bind_param(
        "ii",

        $settlement_id,

        $settlement[
            "to_gym_id"
        ]
    );


    if (
        !$update->execute()
    ) {

        throw new Exception(
            "Settlement confirmation failed."
        );

    }


    if (
        $update->affected_rows
        === 0
    ) {

        throw new Exception(
            "Settlement has already been confirmed."
        );

    }


    $update->close();



    /* =====================================================
       CREATE NOTIFICATION FOR MEMBER
    ====================================================== */

    $notification_title =
        "Membership Transfer Completed";


    $notification_message =

        "The settlement payment of ₹"

        . number_format(
            (float)
            $settlement[
                "amount"
            ],
            2
        )

        . " has been confirmed by the new gym. "
        . "Your membership transfer is now fully completed.";


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


    if ($notification) {


        $notification->bind_param(
            "isssi",

            $settlement[
                "member_id"
            ],

            $notification_title,

            $notification_message,

            $notification_type,

            $settlement_id
        );


        $notification->execute();


        $notification->close();

    }


    /* =====================================================
       COMMIT
    ====================================================== */

    $conn->commit();


    header(
        "Location: received_settlements.php?confirmed=success"
    );

    exit();


} catch (
    Exception $e
) {


    $conn->rollback();


    die(
        "Unable to confirm settlement: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}

?>