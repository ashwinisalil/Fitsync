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


/* =========================================================
   ONLY POST REQUEST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: memberships.php");
    exit();

}


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET FORM DATA
========================================================= */

$membership_id = (int) (
    $_POST["membership_id"] ?? 0
);

$new_status = trim(
    $_POST["status"] ?? ""
);


/* =========================================================
   VALIDATION
========================================================= */

if ($membership_id <= 0) {

    die(
        "Invalid membership selected."
    );

}


$allowed_statuses = [
    "Active",
    "Expired",
    "Transferred"
];


if (
    !in_array(
        $new_status,
        $allowed_statuses,
        true
    )
) {

    die(
        "Invalid membership status."
    );

}


/* =========================================================
   VERIFY MEMBERSHIP BELONGS TO OWNER
========================================================= */

$stmt = $conn->prepare("
    SELECT
        m.membership_id,
        m.member_id,
        m.gym_id,
        m.status,
        g.gym_name
    FROM memberships m
    INNER JOIN gyms g
        ON m.gym_id = g.gym_id
    WHERE m.membership_id = ?
    AND g.owner_id = ?
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
    $owner_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    die(
        "Membership not found or you do not have permission to modify it."
    );

}


$membership =
    $result->fetch_assoc();


$stmt->close();


$current_status =
    $membership["status"];


/* =========================================================
   IF STATUS IS ALREADY SAME
========================================================= */

if (
    $current_status ===
    $new_status
) {

    header(
        "Location: membership_details.php?id="
        . $membership_id
        . "&status=same"
    );

    exit();

}


/* =========================================================
   UPDATE MEMBERSHIP STATUS
========================================================= */

$conn->begin_transaction();


try {


    $update = $conn->prepare("
        UPDATE memberships
        SET status = ?
        WHERE membership_id = ?
    ");


    if (!$update) {

        throw new Exception(
            "Unable to prepare membership update."
        );

    }


    $update->bind_param(
        "si",
        $new_status,
        $membership_id
    );


    if (!$update->execute()) {

        throw new Exception(
            "Membership status could not be updated: "
            . $update->error
        );

    }


    $update->close();


    /* =====================================================
       CREATE NOTIFICATION
    ===================================================== */

    $title =
        "Membership Status Updated";


    $message =
        "Your membership status at "
        . $membership["gym_name"]
        . " has been changed from "
        . $current_status
        . " to "
        . $new_status
        . ".";


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
        $membership["member_id"],
        $title,
        $message,
        $notification_type,
        $membership_id
    );


    if (!$notification->execute()) {

        throw new Exception(
            "Notification could not be created: "
            . $notification->error
        );

    }


    $notification->close();


    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();


    header(
        "Location: membership_details.php?id="
        . $membership_id
        . "&status=updated"
    );

    exit();


} catch (Exception $e) {


    $conn->rollback();


    die(
        "Unable to update membership status: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}

?>