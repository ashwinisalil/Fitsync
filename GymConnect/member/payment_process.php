```php
<?php

/* =========================================================
   GYMCONNECT - PAYMENT PROCESS
========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', 1);

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


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../database/connection.php";


/* =========================================================
   ONLY ALLOW POST REQUEST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: join_gym.php");
    exit();

}


/* =========================================================
   GET FORM DATA
========================================================= */

$member_id = (int) $_SESSION["user_id"];

$plan_id = isset($_POST["plan_id"])
    ? (int) $_POST["plan_id"]
    : 0;

$gym_id = isset($_POST["gym_id"])
    ? (int) $_POST["gym_id"]
    : 0;

$payment_method = trim(
    $_POST["payment_method"] ?? ""
);


/* =========================================================
   BASIC VALIDATION
========================================================= */

if ($plan_id <= 0) {

    die("Invalid membership plan.");

}

if ($gym_id <= 0) {

    die("Invalid gym.");

}

if (empty($payment_method)) {

    die("Please select a payment method.");

}


/* =========================================================
   GET MEMBERSHIP PLAN
========================================================= */

$sql = "
    SELECT
        plan_id,
        gym_id,
        duration_months,
        price,
        plan_name
    FROM membership_plans
    WHERE plan_id = ?
    AND gym_id = ?
    AND is_active = 1
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Unable to prepare membership plan query: "
        . $conn->error
    );

}

$stmt->bind_param(
    "ii",
    $plan_id,
    $gym_id
);

$stmt->execute();

$result = $stmt->get_result();


/* =========================================================
   CHECK PLAN
========================================================= */

if ($result->num_rows === 0) {

    $stmt->close();

    die("Invalid membership plan or gym.");

}

$plan = $result->fetch_assoc();

$stmt->close();


$duration = (int) $plan["duration_months"];

$amount = (float) $plan["price"];


/* =========================================================
   CHECK IF MEMBER ALREADY HAS ACTIVE MEMBERSHIP
========================================================= */

$check = $conn->prepare("
    SELECT membership_id
    FROM memberships
    WHERE member_id = ?
    AND status = 'Active'
    LIMIT 1
");

if (!$check) {

    die(
        "Unable to check existing membership: "
        . $conn->error
    );

}

$check->bind_param(
    "i",
    $member_id
);

$check->execute();

$activeMembership = $check->get_result();


if ($activeMembership->num_rows > 0) {

    $check->close();

    die(
        "You already have an active membership. "
        . "You cannot purchase another membership "
        . "until your current membership expires or is transferred."
    );

}

$check->close();


/* =========================================================
   CALCULATE MEMBERSHIP DATES
========================================================= */

$start_date = date("Y-m-d");

$end_date = date(
    "Y-m-d",
    strtotime("+{$duration} months")
);


/* =========================================================
   START DATABASE TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       CREATE MEMBERSHIP
    ===================================================== */

    $membership = $conn->prepare("
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


    if (!$membership) {

        throw new Exception(
            "Unable to prepare membership query."
        );

    }


    $membership->bind_param(
        "iiiss",
        $member_id,
        $gym_id,
        $plan_id,
        $start_date,
        $end_date
    );


    if (!$membership->execute()) {

        throw new Exception(
            "Membership could not be created."
        );

    }


    /* Get newly created membership ID */

    $membership_id = $conn->insert_id;

    $membership->close();


    /* =====================================================
       SAVE PAYMENT

       IMPORTANT:
       payments table does NOT contain plan_id.

       We use membership_id instead.
    ===================================================== */

    $payment_type = "Membership";

    $payment_status = "Completed";

    $payment = $conn->prepare("
        INSERT INTO payments
        (
            membership_id,
            member_id,
            gym_id,
            amount,
            payment_type,
            payment_method,
            payment_status
        )
        VALUES
        (
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
            "Unable to prepare payment query."
        );

    }


    $payment->bind_param(
        "iiidsss",
        $membership_id,
        $member_id,
        $gym_id,
        $amount,
        $payment_type,
        $payment_method,
        $payment_status
    );


    if (!$payment->execute()) {

        throw new Exception(
            "Payment could not be processed."
        );

    }


    $payment->close();


    /* =====================================================
       CREATE NOTIFICATION

       notifications table requires:
       user_id
       title
       message
    ===================================================== */

    $notification_title =
        "Membership Activated";

    $notification_message =
        "Your "
        . $plan["plan_name"]
        . " membership has been activated successfully.";

    $notification_type =
        "Membership";


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


    if (!$notification) {

        throw new Exception(
            "Unable to create notification."
        );

    }


    $notification->bind_param(
        "isssi",
        $member_id,
        $notification_title,
        $notification_message,
        $notification_type,
        $membership_id
    );


    if (!$notification->execute()) {

        throw new Exception(
            "Notification could not be created."
        );

    }


    $notification->close();


    /* =====================================================
       COMMIT TRANSACTION
    ===================================================== */

    $conn->commit();


    /* =====================================================
       REDIRECT TO MEMBER DASHBOARD
    ===================================================== */

    header(
        "Location: dashboard.php?payment=success"
    );

    exit();


} catch (Exception $e) {


    /* =====================================================
       ROLLBACK EVERYTHING IF SOMETHING FAILS
    ===================================================== */

    $conn->rollback();


    die(
        "Payment process failed: "
        . htmlspecialchars($e->getMessage())
    );

}

?>
```
