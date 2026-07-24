
<?php

/* =========================================================
   GYMCONNECT
   REGISTER GYM PROCESS
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

require_once "database/connection.php";


/* =========================================================
   ONLY POST REQUEST ALLOWED
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: owner/register_gym.php");
    exit();

}


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = intval($_SESSION["user_id"]);


/* =========================================================
   GET FORM DATA
========================================================= */

$gym_name = trim($_POST["gym_name"] ?? "");

$description = trim($_POST["description"] ?? "");

$address = trim($_POST["address"] ?? "");

$city = trim($_POST["city"] ?? "");

$state = trim($_POST["state"] ?? "");

$country = trim($_POST["country"] ?? "India");

$pincode = trim($_POST["pincode"] ?? "");

$phone = trim($_POST["phone"] ?? "");

$email = trim($_POST["email"] ?? "");

$opening_time = trim($_POST["opening_time"] ?? "");

$closing_time = trim($_POST["closing_time"] ?? "");


/* =========================================================
   LOCATION DATA
========================================================= */

$latitude = null;

$longitude = null;


if (
    isset($_POST["latitude"]) &&
    $_POST["latitude"] !== ""
) {

    $latitude = floatval(
        $_POST["latitude"]
    );

}


if (
    isset($_POST["longitude"]) &&
    $_POST["longitude"] !== ""
) {

    $longitude = floatval(
        $_POST["longitude"]
    );

}


/* =========================================================
   VALIDATION
========================================================= */

if (
    empty($gym_name) ||
    empty($description) ||
    empty($address) ||
    empty($city) ||
    empty($state) ||
    empty($country) ||
    empty($pincode) ||
    empty($phone) ||
    empty($email) ||
    empty($opening_time) ||
    empty($closing_time)
) {

    die("
        <h2>Registration Failed</h2>

        <p>
            Please fill all required fields.
        </p>

        <a href='owner/register_gym.php'>
            Go Back
        </a>
    ");

}


/* =========================================================
   EMAIL VALIDATION
========================================================= */

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    die("
        <h2>Invalid Email</h2>

        <p>
            Please enter a valid email address.
        </p>

        <a href='owner/register_gym.php'>
            Go Back
        </a>
    ");

}


/* =========================================================
   IMAGE UPLOAD
========================================================= */

$profile_image = null;


/* =========================================================
   CREATE UPLOAD DIRECTORY
========================================================= */

$upload_dir = "uploads/gyms/";


if (
    !is_dir($upload_dir)
) {

    if (
        !mkdir(
            $upload_dir,
            0777,
            true
        )
    ) {

        die("
            <h2>Upload Directory Error</h2>

            <p>
                Unable to create gym image upload directory.
            </p>
        ");

    }

}


/* =========================================================
   CHECK IMAGE UPLOAD
========================================================= */

if (
    isset($_FILES["profile_image"]) &&
    $_FILES["profile_image"]["error"]
    === UPLOAD_ERR_OK
) {


    $file = $_FILES["profile_image"];


    /* =====================================================
       FILE SIZE LIMIT
    ====================================================== */

    $max_size =
        5 * 1024 * 1024;


    if (
        $file["size"] >
        $max_size
    ) {

        die("
            <h2>Image Too Large</h2>

            <p>
                Gym image must be less than 5 MB.
            </p>

            <a href='owner/register_gym.php'>
                Go Back
            </a>
        ");

    }


    /* =====================================================
       ALLOWED MIME TYPES
    ====================================================== */

    $allowed_types = [

        "image/jpeg",

        "image/jpg",

        "image/png",

        "image/webp"

    ];


    $file_type = mime_content_type(
        $file["tmp_name"]
    );


    if (
        !in_array(
            $file_type,
            $allowed_types
        )
    ) {

        die("
            <h2>Invalid Image</h2>

            <p>
                Please upload JPG, JPEG, PNG,
                or WEBP image.
            </p>

            <a href='owner/register_gym.php'>
                Go Back
            </a>
        ");

    }


    /* =====================================================
       FILE EXTENSION
    ====================================================== */

    $extension = strtolower(

        pathinfo(
            $file["name"],
            PATHINFO_EXTENSION
        )

    );


    /* =====================================================
       UNIQUE FILE NAME
    ====================================================== */

    $new_file_name =

        "gym_" .
        $owner_id .
        "_" .
        time() .
        "_" .
        uniqid() .
        "." .
        $extension;


    /* =====================================================
       TARGET FILE
    ====================================================== */

    $target_file =

        $upload_dir .
        $new_file_name;


    /* =====================================================
       MOVE FILE
    ====================================================== */

    if (
        !move_uploaded_file(
            $file["tmp_name"],
            $target_file
        )
    ) {

        die("
            <h2>Image Upload Failed</h2>

            <p>
                Unable to upload gym image.
            </p>

            <a href='owner/register_gym.php'>
                Go Back
            </a>
        ");

    }


    /* =====================================================
       DATABASE IMAGE PATH
    ====================================================== */

    $profile_image =
        $target_file;

}


/* =========================================================
   DEFAULT GYM STATUS
========================================================= */

/*
   Newly registered gyms require
   admin approval.
*/

$status = "pending";


/* =========================================================
   ACTIVE STATUS
========================================================= */

$is_active = 1;


/* =========================================================
   INSERT GYM
========================================================= */

$sql = "

    INSERT INTO gyms
    (
        owner_id,
        gym_name,
        description,
        address,
        city,
        state,
        country,
        pincode,
        phone,
        email,
        opening_time,
        closing_time,
        latitude,
        longitude,
        profile_image,
        status,
        is_active
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
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )

";


$stmt = $conn->prepare(
    $sql
);


/* =========================================================
   CHECK PREPARE
========================================================= */

if (
    !$stmt
) {

    die(

        "Database Prepare Error: " .

        htmlspecialchars(
            $conn->error
        )

    );

}


/* =========================================================
   BIND PARAMETERS
========================================================= */

/*

   17 VARIABLES
   17 TYPE CHARACTERS

   1.  owner_id       = i
   2.  gym_name       = s
   3.  description    = s
   4.  address        = s
   5.  city           = s
   6.  state          = s
   7.  country        = s
   8.  pincode        = s
   9.  phone          = s
   10. email          = s
   11. opening_time   = s
   12. closing_time   = s
   13. latitude       = d
   14. longitude      = d
   15. profile_image  = s
   16. status         = s
   17. is_active      = i

   CORRECT TYPE STRING:

   issssssssssssddssi

*/

$stmt->bind_param(
    "isssssssssssddssi",
    $owner_id,
    $gym_name,
    $description,
    $address,
    $city,
    $state,
    $country,
    $pincode,
    $phone,
    $email,
    $opening_time,
    $closing_time,
    $latitude,
    $longitude,
    $profile_image,
    $status,
    $is_active
);

/* =========================================================
   EXECUTE INSERT
========================================================= */

if (
    !$stmt->execute()
) {


    /* ==============================================
       DELETE IMAGE IF DATABASE INSERT FAILS
    =============================================== */

    if (
        $profile_image !== null &&
        file_exists(
            $profile_image
        )
    ) {

        unlink(
            $profile_image
        );

    }


    die(

        "Unable to register gym.<br><br>" .

        "Database Error: " .

        htmlspecialchars(
            $stmt->error
        )

    );

}


/* =========================================================
   GET NEW GYM ID
========================================================= */

$new_gym_id =
    $stmt->insert_id;


/* =========================================================
   CLOSE STATEMENT
========================================================= */

$stmt->close();


/* =========================================================
   SUCCESS PAGE
========================================================= */

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Gym Registration Successful
    </title>


    <style>

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

            font-family:
                'Poppins',
                Arial,
                sans-serif;

        }


        body {

            min-height: 100vh;

            background:
                #111827;

            color:
                #ffffff;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            padding:
                20px;

        }


        .success-box {

            width:
                100%;

            max-width:
                550px;

            background:
                #1e293b;

            border:
                1px solid
                #334155;

            border-radius:
                18px;

            padding:
                45px 35px;

            text-align:
                center;

            box-shadow:
                0 20px 50px
                rgba(
                    0,
                    0,
                    0,
                    0.3
                );

        }


        .icon {

            width:
                80px;

            height:
                80px;

            margin:
                0 auto 25px;

            border-radius:
                50%;

            background:
                #22c55e;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                40px;

            color:
                #ffffff;

        }


        h1 {

            color:
                #00c2ff;

            margin-bottom:
                15px;

            font-size:
                28px;

        }


        p {

            color:
                #cbd5e1;

            line-height:
                1.7;

            margin-bottom:
                25px;

        }


        .gym-id {

            background:
                #0f172a;

            padding:
                14px;

            border-radius:
                8px;

            margin-bottom:
                25px;

            color:
                #94a3b8;

        }


        .gym-id strong {

            color:
                #00c2ff;

        }


        .btn {

            display:
                inline-block;

            padding:
                13px 25px;

            background:
                #00c2ff;

            color:
                #000000;

            text-decoration:
                none;

            border-radius:
                9px;

            font-weight:
                700;

            transition:
                0.3s;

        }


        .btn:hover {

            background:
                #19ccff;

            transform:
                translateY(-2px);

        }

    </style>

</head>


<body>


<div class="success-box">


    <div class="icon">

        ✓

    </div>


    <h1>

        Gym Registered Successfully!

    </h1>


    <p>

        Your gym has been registered successfully
        and submitted for admin approval.

        Once the admin approves your gym,
        members will be able to discover
        your gym and join membership plans
        through GymConnect.

    </p>


    <div class="gym-id">

        Gym ID:

        <strong>

            #

            <?php

            echo htmlspecialchars(
                $new_gym_id
            );

            ?>

        </strong>

    </div>


    <a
        href="owner/my_gym.php"
        class="btn"
    >

        View My Gyms

    </a>


</div>


</body>

</html>


<?php


/* =========================================================
   CLOSE DATABASE CONNECTION
========================================================= */

$conn->close();


?>
