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


/* =========================================================
   ONLY POST REQUEST ALLOWED
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: request_transfer.php");
    exit();

}


$member_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET FORM DATA
========================================================= */

$membership_id = (int) (
    $_POST["membership_id"] ?? 0
);

$to_gym_id = (int) (
    $_POST["to_gym_id"] ?? 0
);

$reason = trim(
    $_POST["reason"] ?? ""
);


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $membership_id <= 0 ||
    $to_gym_id <= 0 ||
    empty($reason)
) {

    header(
        "Location: request_transfer.php?error="
        . urlencode(
            "Please fill all required fields."
        )
    );

    exit();

}


/* =========================================================
   REASON LENGTH VALIDATION
========================================================= */

if (
    strlen($reason) > 1000
) {

    header(
        "Location: request_transfer.php?error="
        . urlencode(
            "Transfer reason cannot exceed 1000 characters."
        )
    );

    exit();

}


/* =========================================================
   GET MEMBER'S ACTIVE MEMBERSHIP
========================================================= */

$stmt = $conn->prepare("
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

    AND status = 'Active'

    LIMIT 1
");


if (!$stmt) {

    die(
        "Unable to verify membership."
    );

}


$stmt->bind_param(
    "ii",
    $membership_id,
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
        "Location: request_transfer.php?error="
        . urlencode(
            "Invalid or inactive membership selected."
        )
    );

    exit();

}


$membership =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   GET CURRENT GYM
========================================================= */

$from_gym_id =
    (int) $membership["gym_id"];


/* =========================================================
   PREVENT TRANSFER TO SAME GYM
========================================================= */

if (
    $from_gym_id === $to_gym_id
) {

    header(
        "Location: request_transfer.php?error="
        . urlencode(
            "You cannot transfer your membership to the same gym."
        )
    );

    exit();

}


/* =========================================================
   VERIFY DESTINATION GYM
========================================================= */

$stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name,
        owner_id

    FROM gyms

    WHERE gym_id = ?

    AND status = 'approved'

    AND is_active = 1

    LIMIT 1
");


if (!$stmt) {

    die(
        "Unable to verify destination gym."
    );

}


$stmt->bind_param(
    "i",
    $to_gym_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    $stmt->close();


    header(
        "Location: request_transfer.php?error="
        . urlencode(
            "The selected destination gym is not available."
        )
    );

    exit();

}


$destination_gym =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   CHECK EXISTING PENDING TRANSFER
========================================================= */

$stmt = $conn->prepare("
    SELECT
        transfer_id

    FROM membership_transfers

    WHERE membership_id = ?

    AND member_id = ?

    AND status = 'Pending'

    LIMIT 1
");


if (!$stmt) {

    die(
        "Unable to check existing transfer requests."
    );

}


$stmt->bind_param(
    "ii",
    $membership_id,
    $member_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows > 0
) {

    $stmt->close();


    header(
        "Location: request_transfer.php?error="
        . urlencode(
            "You already have a pending transfer request for this membership."
        )
    );

    exit();

}


$stmt->close();


/* =========================================================
   TRANSFER FEE
========================================================= */

/*
    Total transfer fee = ₹500

    Original Gym = ₹400

    GymConnect = ₹100

    New Gym = ₹0
*/


$transfer_fee =
    500.00;


$original_gym_amount =
    400.00;


$gymconnect_amount =
    100.00;


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       INSERT TRANSFER REQUEST
    ===================================================== */

    $stmt = $conn->prepare("
        INSERT INTO membership_transfers
        (
            membership_id,
            member_id,
            from_gym_id,
            to_gym_id,
            reason,
            transfer_fee,
            original_gym_amount,
            gymconnect_amount,
            status
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
            'Pending'
        )
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to create transfer request."
        );

    }


    $stmt->bind_param(
        "iiiisddd",
        $membership_id,
        $member_id,
        $from_gym_id,
        $to_gym_id,
        $reason,
        $transfer_fee,
        $original_gym_amount,
        $gymconnect_amount
    );


    if (
        !$stmt->execute()
    ) {

        throw new Exception(
            "Transfer request could not be created."
        );

    }


    $transfer_id =
        $conn->insert_id;


    $stmt->close();


    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();


    /* =====================================================
       SUCCESS REDIRECT
    ===================================================== */

    header(
        "Location: my_transfer_requests.php?success="
        . urlencode(
            "Membership transfer request submitted successfully."
        )
    );

    exit();


} catch (
    Exception $e
) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    $conn->rollback();


    header(
        "Location: request_transfer.php?error="
        . urlencode(
            "Unable to submit transfer request. "
            . $e->getMessage()
        )
    );

    exit();

}

?>