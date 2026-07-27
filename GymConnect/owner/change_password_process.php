<?php

/* =========================================================
   GYMCONNECT - OWNER CHANGE PASSWORD PROCESS
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
   ONLY POST REQUEST ALLOWED
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: change_password.php");

    exit();

}


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = intval(
    $_SESSION["user_id"]
);


/* =========================================================
   GET FORM DATA
========================================================= */

$current_password =
    $_POST["current_password"] ?? "";

$new_password =
    $_POST["new_password"] ?? "";

$confirm_password =
    $_POST["confirm_password"] ?? "";


/* =========================================================
   CHECK EMPTY FIELDS
========================================================= */

if (
    empty($current_password) ||
    empty($new_password) ||
    empty($confirm_password)
) {

    die(
        "All password fields are required."
    );

}


/* =========================================================
   CHECK NEW PASSWORD LENGTH
========================================================= */

if (
    strlen($new_password) < 8
) {

    die(
        "New password must contain at least 8 characters."
    );

}


/* =========================================================
   CHECK PASSWORD MATCH
========================================================= */

if (
    $new_password !== $confirm_password
) {

    die(
        "New password and confirm password do not match."
    );

}


/* =========================================================
   GET CURRENT PASSWORD FROM DATABASE
========================================================= */

$stmt = $conn->prepare("
    SELECT password
    FROM users
    WHERE user_id = ?
    AND role = 'owner'
    LIMIT 1
");


if (!$stmt) {

    die(
        "Database error: " .
        $conn->error
    );

}


$stmt->bind_param(
    "i",
    $owner_id
);


$stmt->execute();


$result =
    $stmt->get_result();


/* =========================================================
   CHECK OWNER ACCOUNT
========================================================= */

if (
    $result->num_rows === 0
) {

    die(
        "Owner account not found."
    );

}


$owner =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   VERIFY CURRENT PASSWORD
========================================================= */

if (
    !password_verify(
        $current_password,
        $owner["password"]
    )
) {

    die(
        "Current password is incorrect."
    );

}


/* =========================================================
   PREVENT SAME PASSWORD
========================================================= */

if (
    password_verify(
        $new_password,
        $owner["password"]
    )
) {

    die(
        "New password must be different from your current password."
    );

}


/* =========================================================
   HASH NEW PASSWORD
========================================================= */

$hashed_password =
    password_hash(
        $new_password,
        PASSWORD_DEFAULT
    );


/* =========================================================
   UPDATE PASSWORD
========================================================= */

$update = $conn->prepare("
    UPDATE users
    SET password = ?
    WHERE user_id = ?
    AND role = 'owner'
");


if (!$update) {

    die(
        "Database error: " .
        $conn->error
    );

}


$update->bind_param(
    "si",
    $hashed_password,
    $owner_id
);


/* =========================================================
   EXECUTE UPDATE
========================================================= */

if (
    $update->execute()
) {

    $update->close();

    /*
       Password changed successfully.
       The current login session remains active.
    */

    header(
        "Location: profile.php?password=success"
    );

    exit();

} else {

    $update->close();

    die(
        "Unable to change password. Please try again."
    );

}

?>