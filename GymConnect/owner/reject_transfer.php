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


$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET TRANSFER ID
========================================================= */

$transfer_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($transfer_id <= 0) {

    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "Invalid transfer request."
        )
    );

    exit();

}


/* =========================================================
   GET TRANSFER REQUEST
   VERIFY THAT THE OWNER OWNS THE ORIGINAL GYM
========================================================= */

$stmt = $conn->prepare("
    SELECT
        mt.transfer_id,
        mt.membership_id,
        mt.member_id,
        mt.from_gym_id,
        mt.to_gym_id,
        mt.status,

        g.gym_name

    FROM membership_transfers mt

    INNER JOIN gyms g
        ON mt.from_gym_id = g.gym_id

    WHERE mt.transfer_id = ?

    AND g.owner_id = ?

    LIMIT 1
");


if (!$stmt) {

    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "Unable to verify transfer request."
        )
    );

    exit();

}


$stmt->bind_param(
    "ii",
    $transfer_id,
    $owner_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    $stmt->close();


    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "You are not authorized to reject this transfer request."
        )
    );

    exit();

}


$transfer =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   CHECK CURRENT STATUS
========================================================= */

if (
    $transfer["status"] !== "Pending"
) {

    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "This transfer request has already been processed."
        )
    );

    exit();

}


/* =========================================================
   VERIFY MEMBERSHIP
========================================================= */

$stmt = $conn->prepare("
    SELECT
        membership_id,
        member_id,
        gym_id,
        status

    FROM memberships

    WHERE membership_id = ?

    AND member_id = ?

    AND gym_id = ?

    LIMIT 1
");


if (!$stmt) {

    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "Unable to verify membership."
        )
    );

    exit();

}


$stmt->bind_param(
    "iii",
    $transfer["membership_id"],
    $transfer["member_id"],
    $transfer["from_gym_id"]
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    $stmt->close();


    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "The original membership could not be found."
        )
    );

    exit();

}


$membership =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   CHECK MEMBERSHIP STATUS
========================================================= */

if (
    $membership["status"] !== "Active"
) {

    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "This membership is no longer active."
        )
    );

    exit();

}


/* =========================================================
   REJECT TRANSFER REQUEST
========================================================= */

$stmt = $conn->prepare("
    UPDATE membership_transfers

    SET
        status = 'Rejected'

    WHERE transfer_id = ?

    AND from_gym_id = ?

    AND status = 'Pending'
");


if (!$stmt) {

    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "Unable to reject transfer request."
        )
    );

    exit();

}


$stmt->bind_param(
    "ii",
    $transfer_id,
    $transfer["from_gym_id"]
);


if (
    !$stmt->execute()
) {

    $stmt->close();


    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "Transfer request could not be rejected."
        )
    );

    exit();

}


$affected_rows =
    $stmt->affected_rows;


$stmt->close();


/* =========================================================
   CHECK UPDATE SUCCESS
========================================================= */

if (
    $affected_rows === 0
) {

    header(
        "Location: transfer_requests.php?error="
        . urlencode(
            "Transfer request was already processed."
        )
    );

    exit();

}


/* =========================================================
   SUCCESS
========================================================= */

header(
    "Location: transfer_requests.php?success="
    . urlencode(
        "Membership transfer request rejected successfully."
    )
);

exit();

?>