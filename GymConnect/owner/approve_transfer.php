<?php

session_start();

require_once "../database/connection.php";


/* =========================================================
   CHECK OWNER LOGIN
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
   CHECK TRANSFER ID
========================================================= */

if (!isset($_GET['id'])) {
    die("Invalid transfer request.");
}


$transfer_id = (int) $_GET['id'];


if ($transfer_id <= 0) {
    die("Invalid transfer ID.");
}


/* =========================================================
   FIND TRANSFER AND VERIFY OWNER
========================================================= */

$check = $conn->prepare("
    SELECT
        mt.transfer_id,
        mt.membership_id,
        mt.member_id,
        mt.from_gym_id,
        mt.to_gym_id,
        mt.status,
        mt.payment_status,

        g.gym_name

    FROM membership_transfers mt

    INNER JOIN gyms g
        ON mt.from_gym_id = g.gym_id

    WHERE mt.transfer_id = ?

    AND g.owner_id = ?

    LIMIT 1
");


if (!$check) {
    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );
}


$check->bind_param(
    "ii",
    $transfer_id,
    $owner_id
);


if (!$check->execute()) {
    die(
        "Unable to check transfer: " .
        htmlspecialchars($check->error)
    );
}


$result = $check->get_result();


if ($result->num_rows === 0) {

    $check->close();

    die(
        "Transfer request not found or you are not authorized to approve it."
    );
}


$transfer = $result->fetch_assoc();


$check->close();


/* =========================================================
   CHECK CURRENT STATUS
========================================================= */

$current_status = trim(
    $transfer['status']
);


/* =========================================================
   ONLY PENDING REQUEST CAN BE APPROVED
========================================================= */

if (
    strtolower($current_status) !== 'pending'
) {

    die(
        "This transfer request cannot be approved. " .
        "Current status: " .
        htmlspecialchars($current_status)
    );

}


/* =========================================================
   APPROVE TRANSFER

   status:
   Pending
       ↓
   Awaiting Payment

   payment_status:
   Pending
       ↓
   Pending

========================================================= */

$update = $conn->prepare("
    UPDATE membership_transfers

    SET
        status = 'Awaiting Payment',
        payment_status = 'Pending',
        approved_at = NOW(),
        approved_by = ?

    WHERE transfer_id = ?

    AND status = 'Pending'
");


if (!$update) {

    die(
        "Update error: " .
        htmlspecialchars($conn->error)
    );

}


$update->bind_param(
    "ii",
    $owner_id,
    $transfer_id
);


if (!$update->execute()) {

    die(
        "Unable to approve transfer: " .
        htmlspecialchars($update->error)
    );

}


/* =========================================================
   CHECK IF UPDATE WAS SUCCESSFUL
========================================================= */

if (
    $update->affected_rows > 0
) {

    $update->close();

    header(
        "Location: transfer_requests.php?success=" .
        urlencode(
            "Transfer request approved successfully. The member must now pay the ₹500 transfer fee."
        )
    );

    exit();

} else {

    $update->close();

    die(
        "Transfer could not be approved. " .
        "The request may have already been processed."
    );

}

?>