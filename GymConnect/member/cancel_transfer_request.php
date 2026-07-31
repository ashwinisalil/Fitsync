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
   GET TRANSFER ID
========================================================= */

$transfer_id =
    isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($transfer_id <= 0) {

    header(
        "Location: transfer_request.php?error="
        . urlencode(
            "Invalid transfer request."
        )
    );

    exit();

}


/* =========================================================
   CHECK REQUEST BELONGS TO MEMBER
   AND IS STILL PENDING
========================================================= */

$stmt = $conn->prepare("
    SELECT
        transfer_id,
        membership_id,
        status

    FROM membership_transfers

    WHERE transfer_id = ?

    AND member_id = ?

    LIMIT 1
");


$stmt->bind_param(
    "ii",
    $transfer_id,
    $member_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    $stmt->close();

    header(
        "Location: transfer_request.php?error="
        . urlencode(
            "Transfer request not found."
        )
    );

    exit();

}


$transfer =
    $result->fetch_assoc();


$stmt->close();



/* =========================================================
   ONLY PENDING REQUEST CAN BE CANCELLED
========================================================= */

if (
    $transfer["status"]
    !==
    "Pending"
) {

    header(
        "Location: transfer_request.php?error="
        . urlencode(
            "Only pending transfer requests can be cancelled."
        )
    );

    exit();

}


/* =========================================================
   CANCEL REQUEST
========================================================= */

$new_status =
    "Cancelled";


$stmt = $conn->prepare("
    UPDATE membership_transfers

    SET
        status = ?

    WHERE transfer_id = ?

    AND member_id = ?

    AND status = 'Pending'
");


$stmt->bind_param(
    "sii",
    $new_status,
    $transfer_id,
    $member_id
);


if (
    !$stmt->execute()
) {

    $stmt->close();

    header(
        "Location: transfer_request.php?error="
        . urlencode(
            "Unable to cancel transfer request."
        )
    );

    exit();

}


if (
    $stmt->affected_rows !== 1
) {

    $stmt->close();

    header(
        "Location: transfer_request.php?error="
        . urlencode(
            "The transfer request could not be cancelled."
        )
    );

    exit();

}


$stmt->close();



/* =========================================================
   REDIRECT SUCCESS
========================================================= */

header(
    "Location: transfer_request.php?success="
    . urlencode(
        "Transfer request cancelled successfully."
    )
);

exit();

?>