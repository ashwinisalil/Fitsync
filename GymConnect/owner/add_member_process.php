```php
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "owner"
) {

    header("Location: ../login.php");

    exit();

}


require_once "../database/connection.php";


/* =========================================================
   ONLY POST REQUEST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: members.php");

    exit();

}


$owner_id = (int) $_SESSION["user_id"];

$email = trim(
    $_POST["email"] ?? ""
);

$gym_id = (int) (
    $_POST["gym_id"] ?? 0
);

$plan_id = (int) (
    $_POST["plan_id"] ?? 0
);

$start_date =
    $_POST["start_date"] ?? "";

$payment_method =
    trim(
        $_POST["payment_method"] ?? ""
    );


/* =========================================================
   VALIDATION
========================================================= */

if (
    empty($email) ||
    $gym_id <= 0 ||
    $plan_id <= 0 ||
    empty($start_date) ||
    empty($payment_method)
) {

    die(
        "Please fill all required fields."
    );

}


/* =========================================================
   FIND MEMBER
========================================================= */

$stmt = $conn->prepare("
    SELECT user_id
    FROM users
    WHERE email = ?
    AND role = 'member'
    LIMIT 1
");

$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    die(
        "No GymConnect member found with this email. "
        . "Ask the member to create an account first."
    );

}


$member =
    $result->fetch_assoc();

$member_id =
    (int) $member["user_id"];

$stmt->close();


/* =========================================================
   VERIFY GYM BELONGS TO OWNER
========================================================= */

$stmt = $conn->prepare("
    SELECT gym_id
    FROM gyms
    WHERE gym_id = ?
    AND owner_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $gym_id,
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    die(
        "Invalid gym selection."
    );

}

$stmt->close();


/* =========================================================
   GET PLAN
========================================================= */

$stmt = $conn->prepare("
    SELECT
        plan_id,
        duration_months,
        price,
        plan_name
    FROM membership_plans
    WHERE plan_id = ?
    AND gym_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $plan_id,
    $gym_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    die(
        "Invalid membership plan."
    );

}


$plan =
    $result->fetch_assoc();

$duration =
    (int) $plan["duration_months"];

$amount =
    (float) $plan["price"];

$stmt->close();


/* =========================================================
   CHECK ACTIVE MEMBERSHIP
========================================================= */

$stmt = $conn->prepare("
    SELECT membership_id
    FROM memberships
    WHERE member_id = ?
    AND gym_id = ?
    AND status = 'Active'
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $member_id,
    $gym_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    $stmt->close();

    die(
        "This member already has an active membership "
        . "in this gym."
    );

}

$stmt->close();


/* =========================================================
   CALCULATE END DATE
========================================================= */

$end_date = date(
    "Y-m-d",
    strtotime(
        "+{$duration} months",
        strtotime($start_date)
    )
);


/* =========================================================
   START TRANSACTION
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
            "Unable to create membership."
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
            "Membership creation failed."
        );

    }


    $membership_id =
        $conn->insert_id;


    $membership->close();


    /* =====================================================
       CREATE PAYMENT RECORD
    ===================================================== */

    $payment_type =
        "Manual Membership";

    $payment_status =
        "Completed";


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
            "Unable to create payment record."
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
            "Payment record could not be created."
        );

    }


    $payment->close();


    /* =====================================================
       CREATE NOTIFICATION
    ===================================================== */

    $title =
        "Membership Added";

    $message =
        "Your "
        . $plan["plan_name"]
        . " membership has been added by the gym owner.";

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
        $title,
        $message,
        $notification_type,
        $membership_id
    );


    if (!$notification->execute()) {

        throw new Exception(
            "Notification creation failed."
        );

    }


    $notification->close();


    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();


    header(
        "Location: members.php?added=success"
    );

    exit();


} catch (Exception $e) {


    $conn->rollback();


    die(
        "Unable to add member: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}

?>
```
