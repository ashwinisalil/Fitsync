<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
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


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET FORM DATA
========================================================= */

$email = trim(
    $_POST["email"] ?? ""
);

$gym_id = (int) (
    $_POST["gym_id"] ?? 0
);

$plan_id = (int) (
    $_POST["plan_id"] ?? 0
);

$start_date = trim(
    $_POST["start_date"] ?? ""
);

$payment_method = trim(
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
   ALLOWED PAYMENT METHODS
========================================================= */

$allowed_payment_methods = [
    "Cash",
    "UPI",
    "Card",
    "Bank Transfer",
    "Other"
];


if (
    !in_array(
        $payment_method,
        $allowed_payment_methods,
        true
    )
) {

    die(
        "Invalid payment method selected."
    );

}


/* =========================================================
   VALIDATE DATE
========================================================= */

$date_object = DateTime::createFromFormat(
    "Y-m-d",
    $start_date
);


if (
    !$date_object ||
    $date_object->format("Y-m-d") !== $start_date
) {

    die(
        "Invalid membership start date."
    );

}


/* =========================================================
   FIND MEMBER BY EMAIL
========================================================= */

$stmt = $conn->prepare("
    SELECT user_id, full_name
    FROM users
    WHERE email = ?
    AND role = 'member'
    LIMIT 1
");


if (!$stmt) {

    die(
        "Database error while finding member."
    );

}


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


$member = $result->fetch_assoc();

$member_id = (int) $member["user_id"];

$member_name = $member["full_name"];

$stmt->close();


/* =========================================================
   VERIFY GYM BELONGS TO OWNER
========================================================= */

$stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name
    FROM gyms
    WHERE gym_id = ?
    AND owner_id = ?
    LIMIT 1
");


if (!$stmt) {

    die(
        "Database error while verifying gym."
    );

}


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
        "Invalid gym selection. "
        . "This gym does not belong to your account."
    );

}


$gym = $result->fetch_assoc();

$gym_name = $gym["gym_name"];

$stmt->close();


/* =========================================================
   GET MEMBERSHIP PLAN
========================================================= */

$stmt = $conn->prepare("
    SELECT
        plan_id,
        plan_name,
        duration_months,
        price
    FROM membership_plans
    WHERE plan_id = ?
    AND gym_id = ?
    LIMIT 1
");


if (!$stmt) {

    die(
        "Database error while finding membership plan."
    );

}


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
        "Invalid membership plan selected."
    );

}


$plan = $result->fetch_assoc();

$plan_name = $plan["plan_name"];

$duration = (int) $plan["duration_months"];

$amount = (float) $plan["price"];

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


if (!$stmt) {

    die(
        "Database error while checking membership."
    );

}


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
        "+" . $duration . " months",
        strtotime($start_date)
    )
);


/* =========================================================
   START DATABASE TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       1. CREATE MEMBERSHIP
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
            "Unable to prepare membership creation."
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
            "Membership creation failed: "
            . $membership->error
        );

    }


    $membership_id =
        $conn->insert_id;


    $membership->close();


    /* =====================================================
       2. CREATE PAYMENT RECORD
    ===================================================== */

    /*
       IMPORTANT:

       Your payments table has:

       payment_id
       membership_id
       member_id
       gym_id
       amount
       payment_type
       payment_method
       transaction_id
       payment_status
       payment_date
       notes
       recorded_by

       We are not inserting:
       payment_id
       payment_date

       because they have automatic values.

       transaction_id is NULL for manual payments.
    */


    $payment_type =
        "Membership";

    $payment_status =
        "Completed";

    $transaction_id =
        NULL;

    $notes =
        "Membership payment recorded by gym owner.";


    $payment = $conn->prepare("
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
            notes,
            recorded_by
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
            ?,
            ?
        )
    ");


    if (!$payment) {

        throw new Exception(
            "Unable to prepare payment record."
        );

    }


    $payment->bind_param(
        "iiidsssssi",
        $membership_id,
        $member_id,
        $gym_id,
        $amount,
        $payment_type,
        $payment_method,
        $transaction_id,
        $payment_status,
        $notes,
        $owner_id
    );


    if (!$payment->execute()) {

        throw new Exception(
            "Payment record could not be created: "
            . $payment->error
        );

    }


    $payment->close();


    /* =====================================================
       3. CREATE NOTIFICATION
    ===================================================== */

    $title =
        "Membership Added";


    $message =
        "Your "
        . $plan_name
        . " membership at "
        . $gym_name
        . " has been successfully added.";


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
            "Unable to prepare notification."
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
            "Notification creation failed: "
            . $notification->error
        );

    }


    $notification->close();


    /* =====================================================
       4. COMMIT TRANSACTION
    ===================================================== */

    $conn->commit();


    /* =====================================================
       SUCCESS REDIRECT
    ===================================================== */

    header(
        "Location: members.php?added=success"
    );

    exit();


} catch (Exception $e) {


    /* =====================================================
       ROLLBACK IF ANYTHING FAILS
    ===================================================== */

    $conn->rollback();


    die(
        "Unable to add member: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}

?>