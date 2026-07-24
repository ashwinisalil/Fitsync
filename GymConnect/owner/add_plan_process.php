
<?php

/* =========================================================
   GYMCONNECT
   ADD MEMBERSHIP PLAN PROCESS
   MULTIPLE GYMS SUPPORT
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
    header("Location: login.php");
    exit();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../database/connection.php";


/* =========================================================
   ONLY ALLOW POST REQUEST
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    header(
        "Location: owner/membership_plans.php"
    );

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

$gym_id = isset($_POST["gym_id"])
    ? intval($_POST["gym_id"])
    : 0;


$plan_name = isset($_POST["plan_name"])
    ? trim($_POST["plan_name"])
    : "";


$duration_months = isset(
    $_POST["duration_months"]
)
    ? intval($_POST["duration_months"])
    : 0;


$price = isset($_POST["price"])
    ? floatval($_POST["price"])
    : 0;


$description = isset(
    $_POST["description"]
)
    ? trim($_POST["description"])
    : "";


/* =========================================================
   VALIDATE GYM ID
========================================================= */

if (
    $gym_id <= 0
) {

    die("

        <h2>
            No valid gym was selected.
        </h2>

        <p>
            Please go back and select a gym.
        </p>

        <a href='add_plan.php'>
            ← Go Back
        </a>

    ");

}


/* =========================================================
   VALIDATE PLAN NAME
========================================================= */

if (
    empty($plan_name)
) {

    die("

        <h2>
            Plan name is required.
        </h2>

        <a href='add_plan.php'>
            ← Go Back
        </a>

    ");

}


/* =========================================================
   VALIDATE DURATION
========================================================= */

if (
    $duration_months <= 0
) {

    die("

        <h2>
            Invalid membership duration.
        </h2>

        <a href='add_plan.php'>
            ← Go Back
        </a>

    ");

}


/* =========================================================
   VALIDATE PRICE
========================================================= */

if (
    $price < 0
) {

    die("

        <h2>
            Invalid membership price.
        </h2>

        <a href='add_plan.php'>
            ← Go Back
        </a>

    ");

}


/* =========================================================
   VERIFY GYM BELONGS TO OWNER
========================================================= */

$gym_check = $conn->prepare("

    SELECT
        gym_id,
        gym_name

    FROM gyms

    WHERE gym_id = ?
    AND owner_id = ?

    LIMIT 1

");


if (
    !$gym_check
) {

    die(

        "Database Error: " .

        htmlspecialchars(
            $conn->error
        )

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


/* =========================================================
   GYM NOT FOUND OR DOES NOT BELONG TO OWNER
========================================================= */

if (
    $gym_result->num_rows === 0
) {

    $gym_check->close();

    die("

        <h2>
            Invalid Gym
        </h2>

        <p>
            The selected gym does not exist
            or does not belong to your account.
        </p>

        <a href='add_plan.php'>
            ← Go Back
        </a>

    ");

}


$gym_data =
    $gym_result->fetch_assoc();


$gym_check->close();


/* =========================================================
   CHECK FOR DUPLICATE PLAN NAME
   FOR THE SAME GYM
========================================================= */

$duplicate_check = $conn->prepare("

    SELECT
        plan_id

    FROM membership_plans

    WHERE gym_id = ?
    AND plan_name = ?

    LIMIT 1

");


if (
    !$duplicate_check
) {

    die(

        "Database Error: " .

        htmlspecialchars(
            $conn->error
        )

    );

}


$duplicate_check->bind_param(

    "is",

    $gym_id,

    $plan_name

);


$duplicate_check->execute();


$duplicate_result =
    $duplicate_check->get_result();


if (
    $duplicate_result->num_rows > 0
) {

    $duplicate_check->close();

    die("

        <h2>
            Plan Already Exists
        </h2>

        <p>

            A membership plan named

            <strong>

                " .

                htmlspecialchars(
                    $plan_name
                )

                . "

            </strong>

            already exists for

            <strong>

                " .

                htmlspecialchars(
                    $gym_data["gym_name"]
                )

                . "

            </strong>.

        </p>

        <a href='add_plan.php'>
            ← Add Another Plan
        </a>

    ");

}


$duplicate_check->close();


/* =========================================================
   INSERT MEMBERSHIP PLAN
========================================================= */

$insert = $conn->prepare("

    INSERT INTO membership_plans

    (
        gym_id,
        plan_name,
        duration_months,
        price,
        description
    )

    VALUES
    (?, ?, ?, ?, ?)

");


if (
    !$insert
) {

    die(

        "Unable to prepare plan insertion: " .

        htmlspecialchars(
            $conn->error
        )

    );

}


/* =========================================================
   BIND PARAMETERS
========================================================= */

$insert->bind_param(

    "isids",

    $gym_id,

    $plan_name,

    $duration_months,

    $price,

    $description

);


/* =========================================================
   EXECUTE INSERT
========================================================= */

if (
    !$insert->execute()
) {

    die(

        "Unable to create membership plan: " .

        htmlspecialchars(
            $insert->error
        )

    );

}


$insert->close();


/* =========================================================
   CLOSE DATABASE
========================================================= */

$conn->close();


/* =========================================================
   SUCCESS REDIRECT
========================================================= */

header(
    "Location: owner/membership_plans.php?success=plan_created"
);

exit();

?>
