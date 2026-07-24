
<?php

session_start();

header("Content-Type: application/json");


/* =========================================================
   CHECK OWNER LOGIN
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "owner"
) {

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();

}


require_once "../database/connection.php";


$owner_id = (int) $_SESSION["user_id"];

$gym_id = isset($_GET["gym_id"])
    ? (int) $_GET["gym_id"]
    : 0;


/* =========================================================
   VALIDATE GYM ID
========================================================= */

if ($gym_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid gym ID."
    ]);

    exit();

}


/* =========================================================
   CHECK IF GYM BELONGS TO LOGGED-IN OWNER
========================================================= */

$check = $conn->prepare("
    SELECT gym_id
    FROM gyms
    WHERE gym_id = ?
    AND owner_id = ?
    LIMIT 1
");


if (!$check) {

    echo json_encode([
        "success" => false,
        "message" => "Gym verification query failed: "
                     . $conn->error
    ]);

    exit();

}


$check->bind_param(
    "ii",
    $gym_id,
    $owner_id
);

$check->execute();

$result = $check->get_result();


if ($result->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "This gym does not belong to you."
    ]);

    $check->close();

    exit();

}


$check->close();


/* =========================================================
   GET MEMBERSHIP PLANS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        plan_id,
        plan_name,
        duration_months,
        price
    FROM membership_plans
    WHERE gym_id = ?
    ORDER BY price ASC
");


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to get plans: "
                     . $conn->error
    ]);

    exit();

}


$stmt->bind_param(
    "i",
    $gym_id
);


if (!$stmt->execute()) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to execute plan query: "
                     . $stmt->error
    ]);

    $stmt->close();

    exit();

}


$result = $stmt->get_result();


$plans = [];


while ($row = $result->fetch_assoc()) {

    $plans[] = [

        "plan_id" =>
            (int) $row["plan_id"],

        "plan_name" =>
            $row["plan_name"],

        "duration_months" =>
            (int) $row["duration_months"],

        "price" =>
            (float) $row["price"]

    ];

}


$stmt->close();


/* =========================================================
   RETURN PLANS
========================================================= */

echo json_encode([

    "success" => true,

    "plans" => $plans

]);


$conn->close();

?>