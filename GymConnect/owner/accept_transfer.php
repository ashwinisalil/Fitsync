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
   GET TRANSFER ID
========================================================= */

$transfer_id =
    isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if (
    $transfer_id <= 0
) {

    header(
        "Location: incoming_transfer_requests.php?error="
        . urlencode(
            "Invalid transfer request."
        )
    );

    exit();

}


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       GET TRANSFER
       LOCK ROW FOR PROCESSING
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT

            mt.transfer_id,
            mt.membership_id,
            mt.member_id,
            mt.from_gym_id,
            mt.to_gym_id,
            mt.status,

            m.plan_id,
            m.start_date,
            m.end_date,
            m.status AS membership_status

        FROM membership_transfers mt

        INNER JOIN memberships m
            ON mt.membership_id = m.membership_id

        INNER JOIN gyms g
            ON mt.to_gym_id = g.gym_id

        WHERE mt.transfer_id = ?

        AND g.owner_id = ?

        LIMIT 1

        FOR UPDATE
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to load transfer request."
        );

    }


    $stmt->bind_param(
        "ii",
        $transfer_id,
        $owner_id
    );


    if (
        !$stmt->execute()
    ) {

        throw new Exception(
            "Unable to verify transfer request."
        );

    }


    $result =
        $stmt->get_result();


    if (
        $result->num_rows === 0
    ) {

        $stmt->close();

        throw new Exception(
            "Transfer request not found or you are not authorized to accept it."
        );

    }


    $transfer =
        $result->fetch_assoc();


    $stmt->close();



    /* =====================================================
       VERIFY TRANSFER STATUS
    ===================================================== */

    if (
        $transfer["status"]
        !==
        "Approved"
    ) {

        throw new Exception(
            "This transfer is no longer available for acceptance."
        );

    }



    /* =====================================================
       VERIFY MEMBERSHIP STATUS
    ===================================================== */

    if (
        $transfer[
            "membership_status"
        ]
        !==
        "Active"
    ) {

        throw new Exception(
            "The original membership is no longer active."
        );

    }



    /* =====================================================
       VERIFY MEMBERSHIP HAS NOT EXPIRED
    ===================================================== */

    $today =
        date("Y-m-d");


    if (
        $transfer["end_date"]
        <
        $today
    ) {

        throw new Exception(
            "The membership has already expired."
        );

    }



    /* =====================================================
       CHECK DESTINATION MEMBERSHIP
       PREVENT DUPLICATE ACTIVE MEMBERSHIP
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT membership_id

        FROM memberships

        WHERE member_id = ?

        AND gym_id = ?

        AND status = 'Active'

        LIMIT 1

        FOR UPDATE
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to verify existing memberships."
        );

    }


    $stmt->bind_param(
        "ii",
        $transfer["member_id"],
        $transfer["to_gym_id"]
    );


    if (
        !$stmt->execute()
    ) {

        throw new Exception(
            "Unable to check destination membership."
        );

    }


    $result =
        $stmt->get_result();


    if (
        $result->num_rows > 0
    ) {

        $stmt->close();

        throw new Exception(
            "This member already has an active membership in the destination gym."
        );

    }


    $stmt->close();



    /* =====================================================
       MARK OLD MEMBERSHIP AS TRANSFERRED
    ===================================================== */

    $stmt = $conn->prepare("
        UPDATE memberships

        SET status = 'Transferred'

        WHERE membership_id = ?

        AND status = 'Active'
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to update original membership."
        );

    }


    $stmt->bind_param(
        "i",
        $transfer["membership_id"]
    );


    if (
        !$stmt->execute()
    ) {

        throw new Exception(
            "Unable to transfer original membership."
        );

    }


    if (
        $stmt->affected_rows !== 1
    ) {

        throw new Exception(
            "The original membership could not be transferred."
        );

    }


    $stmt->close();



    /* =====================================================
       CREATE NEW MEMBERSHIP
       SAME PLAN + ORIGINAL END DATE
    ===================================================== */

    $new_status =
        "Active";


    $stmt = $conn->prepare("
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
            ?
        )
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to create new membership."
        );

    }


    /*
       The new membership starts from today.
       The original end date is preserved.
    */

    $new_start_date =
        $today;


    $stmt->bind_param(
        "iiisss",
        $transfer["member_id"],
        $transfer["to_gym_id"],
        $transfer["plan_id"],
        $new_start_date,
        $transfer["end_date"],
        $new_status
    );


    if (
        !$stmt->execute()
    ) {

        throw new Exception(
            "Unable to create destination membership."
        );

    }


    $new_membership_id =
        $conn->insert_id;


    $stmt->close();



    /* =====================================================
       UPDATE TRANSFER STATUS
    ===================================================== */

    $completed_status =
        "Completed";


    $stmt = $conn->prepare("
        UPDATE membership_transfers

        SET
            status = ?,
            completed_at = NOW()

        WHERE transfer_id = ?

        AND status = 'Approved'
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to complete transfer."
        );

    }


    $stmt->bind_param(
        "si",
        $completed_status,
        $transfer_id
    );


    if (
        !$stmt->execute()
    ) {

        throw new Exception(
            "Unable to update transfer status."
        );

    }


    if (
        $stmt->affected_rows !== 1
    ) {

        throw new Exception(
            "This transfer may have already been completed."
        );

    }


    $stmt->close();



    /* =====================================================
       CREATE NOTIFICATION FOR MEMBER
    ===================================================== */

    $title =
        "Membership Transfer Completed";


    $message =
        "Your membership has been successfully "
        . "transferred to the new gym. "
        . "Your new membership is active until "
        . date(
            "d M Y",
            strtotime(
                $transfer["end_date"]
            )
        )
        . ".";


    $notification_type =
        "Membership Transfer";


    $stmt = $conn->prepare("
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


    if (!$stmt) {

        throw new Exception(
            "Unable to create notification."
        );

    }


    $stmt->bind_param(
        "isssi",
        $transfer["member_id"],
        $title,
        $message,
        $notification_type,
        $new_membership_id
    );


    if (
        !$stmt->execute()
    ) {

        throw new Exception(
            "Unable to create member notification."
        );

    }


    $stmt->close();



    /* =====================================================
       COMMIT EVERYTHING
    ===================================================== */

    $conn->commit();


    header(
        "Location: incoming_transfer_requests.php?success="
        . urlencode(
            "Membership transfer completed successfully."
        )
    );

    exit();


} catch (
    Exception $e
) {


    /* =====================================================
       ROLLBACK EVERYTHING
    ===================================================== */

    $conn->rollback();


    header(
        "Location: incoming_transfer_requests.php?error="
        . urlencode(
            $e->getMessage()
        )
    );

    exit();

}

?>