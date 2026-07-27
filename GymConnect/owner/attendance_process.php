<?php

/* =========================================================
   GYMCONNECT - OWNER ATTENDANCE PROCESS
   Handles Check-In and Check-Out
========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', 1);

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


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../database/connection.php";


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = intval($_SESSION["user_id"]);


/* =========================================================
   GET ACTION
========================================================= */

$action = isset($_GET["action"])
    ? trim($_GET["action"])
    : "";


/* =========================================================
   GET GYM ID
========================================================= */

$gym_id = isset($_GET["gym_id"])
    ? intval($_GET["gym_id"])
    : 0;


/* =========================================================
   VALIDATE GYM ID
========================================================= */

if ($gym_id <= 0) {

    die("Invalid gym selected.");

}


/* =========================================================
   VERIFY THAT GYM BELONGS TO LOGGED-IN OWNER
========================================================= */

$gym_check = $conn->prepare("
    SELECT gym_id
    FROM gyms
    WHERE gym_id = ?
    AND owner_id = ?
    LIMIT 1
");


if (!$gym_check) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}


$gym_check->bind_param(
    "ii",
    $gym_id,
    $owner_id
);


$gym_check->execute();


$gym_result =
    $gym_check->get_result();


if ($gym_result->num_rows === 0) {

    $gym_check->close();

    die("You are not authorized to manage attendance for this gym.");

}


$gym_check->close();


/* =========================================================
   CHECK-IN / MARK PRESENT
========================================================= */

if ($action === "checkin") {


    /* =====================================================
       GET MEMBER ID
    ===================================================== */

    $member_id = isset($_GET["member_id"])
        ? intval($_GET["member_id"])
        : 0;


    if ($member_id <= 0) {

        die("Invalid member selected.");

    }


    /* =====================================================
       VERIFY MEMBER HAS ACTIVE MEMBERSHIP IN THIS GYM
    ===================================================== */

    $member_check = $conn->prepare("
        SELECT membership_id
        FROM memberships
        WHERE member_id = ?
        AND gym_id = ?
        AND status = 'Active'
        LIMIT 1
    ");


    if (!$member_check) {

        die(
            "Database Error: " .
            htmlspecialchars($conn->error)
        );

    }


    $member_check->bind_param(
        "ii",
        $member_id,
        $gym_id
    );


    $member_check->execute();


    $member_result =
        $member_check->get_result();


    if ($member_result->num_rows === 0) {

        $member_check->close();

        die(
            "This member does not have an active membership in this gym."
        );

    }


    $member_check->close();


    /* =====================================================
       CHECK IF ATTENDANCE ALREADY EXISTS TODAY
    ===================================================== */

    $today =
        date("Y-m-d");


    $existing_check = $conn->prepare("
        SELECT attendance_id
        FROM attendance
        WHERE member_id = ?
        AND gym_id = ?
        AND attendance_date = ?
        LIMIT 1
    ");


    if (!$existing_check) {

        die(
            "Database Error: " .
            htmlspecialchars($conn->error)
        );

    }


    $existing_check->bind_param(
        "iis",
        $member_id,
        $gym_id,
        $today
    );


    $existing_check->execute();


    $existing_result =
        $existing_check->get_result();


    if ($existing_result->num_rows > 0) {

        $existing_check->close();

        header(
            "Location: attendance.php?gym_id=" .
            $gym_id .
            "&error=already_marked"
        );

        exit();

    }


    $existing_check->close();


    /* =====================================================
       CURRENT CHECK-IN TIME
    ===================================================== */

    $check_in_time =
        date("H:i:s");


    /* =====================================================
       INSERT ATTENDANCE
    ===================================================== */

    $insert = $conn->prepare("
        INSERT INTO attendance
        (
            member_id,
            gym_id,
            attendance_date,
            check_in_time,
            attendance_method,
            status
        )
        VALUES
        (?, ?, ?, ?, 'Manual', 'Present')
    ");


    if (!$insert) {

        die(
            "Unable to prepare attendance insertion: " .
            htmlspecialchars($conn->error)
        );

    }


    $insert->bind_param(
        "iiss",
        $member_id,
        $gym_id,
        $today,
        $check_in_time
    );


    if (!$insert->execute()) {

        die(
            "Unable to mark attendance: " .
            htmlspecialchars($insert->error)
        );

    }


    $insert->close();


    /* =====================================================
       SUCCESS REDIRECT
    ===================================================== */

    header(
        "Location: attendance.php?gym_id=" .
        $gym_id .
        "&success=checkin"
    );

    exit();

}


/* =========================================================
   CHECK-OUT
========================================================= */

elseif ($action === "checkout") {


    /* =====================================================
       GET ATTENDANCE ID
    ===================================================== */

    $attendance_id = isset(
        $_GET["attendance_id"]
    )
        ? intval($_GET["attendance_id"])
        : 0;


    if ($attendance_id <= 0) {

        die("Invalid attendance record.");

    }


    /* =====================================================
       VERIFY ATTENDANCE BELONGS TO OWNER'S GYM
    ===================================================== */

    $attendance_check = $conn->prepare("
        SELECT attendance_id
        FROM attendance
        WHERE attendance_id = ?
        AND gym_id = ?
        LIMIT 1
    ");


    if (!$attendance_check) {

        die(
            "Database Error: " .
            htmlspecialchars($conn->error)
        );

    }


    $attendance_check->bind_param(
        "ii",
        $attendance_id,
        $gym_id
    );


    $attendance_check->execute();


    $attendance_result =
        $attendance_check->get_result();


    if (
        $attendance_result->num_rows === 0
    ) {

        $attendance_check->close();

        die(
            "Invalid attendance record or unauthorized access."
        );

    }


    $attendance_check->close();


    /* =====================================================
       CURRENT CHECK-OUT TIME
    ===================================================== */

    $check_out_time =
        date("H:i:s");


    /* =====================================================
       UPDATE CHECK-OUT TIME
    ===================================================== */

    $update = $conn->prepare("
        UPDATE attendance
        SET check_out_time = ?
        WHERE attendance_id = ?
        AND gym_id = ?
    ");


    if (!$update) {

        die(
            "Unable to prepare checkout update: " .
            htmlspecialchars($conn->error)
        );

    }


    $update->bind_param(
        "sii",
        $check_out_time,
        $attendance_id,
        $gym_id
    );


    if (!$update->execute()) {

        die(
            "Unable to record checkout: " .
            htmlspecialchars($update->error)
        );

    }


    $update->close();


    /* =====================================================
       SUCCESS REDIRECT
    ===================================================== */

    header(
        "Location: attendance.php?gym_id=" .
        $gym_id .
        "&success=checkout"
    );

    exit();

}


/* =========================================================
   INVALID ACTION
========================================================= */

else {

    die("Invalid attendance action.");

}


/* =========================================================
   CLOSE CONNECTION
========================================================= */

$conn->close();

?>