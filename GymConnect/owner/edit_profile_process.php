<?php

/* =========================================================
   GYMCONNECT - EDIT OWNER PROFILE PROCESS
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

    header("Location: profile.php");

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

$full_name = trim(
    $_POST["full_name"] ?? ""
);

$phone = trim(
    $_POST["phone"] ?? ""
);


/* =========================================================
   VALIDATE FULL NAME
========================================================= */

if (
    empty($full_name)
) {

    die(
        "Full name is required."
    );

}


/* =========================================================
   VALIDATE PHONE
========================================================= */

if (
    !empty($phone)
) {

    /*
       Allows:

       9876543210
       +919876543210
       020-12345678

       Only numbers, +, -, spaces and brackets.
    */

    if (
        !preg_match(
            "/^[0-9+\-\s()]+$/",
            $phone
        )
    ) {

        die(
            "Please enter a valid phone number."
        );

    }

}


/* =========================================================
   GET CURRENT PROFILE IMAGE
========================================================= */

$stmt = $conn->prepare("
    SELECT profile_image
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


if (
    $result->num_rows === 0
) {

    die(
        "Owner account not found."
    );

}


$current_owner =
    $result->fetch_assoc();


$current_profile_image =
    $current_owner["profile_image"];


$stmt->close();


/* =========================================================
   PROFILE IMAGE VARIABLE
========================================================= */

$new_profile_image =
    $current_profile_image;


/* =========================================================
   HANDLE PROFILE IMAGE UPLOAD
========================================================= */

if (
    isset(
        $_FILES["profile_image"]
    )
    &&
    $_FILES["profile_image"]["error"]
    !== UPLOAD_ERR_NO_FILE
) {


    /* =========================================
       CHECK UPLOAD ERROR
    ========================================= */

    if (
        $_FILES["profile_image"]["error"]
        !== UPLOAD_ERR_OK
    ) {

        die(
            "There was an error uploading the profile image."
        );

    }


    /* =========================================
       CHECK FILE SIZE
       Maximum 5 MB
    ========================================= */

    $max_size =
        5 * 1024 * 1024;


    if (
        $_FILES["profile_image"]["size"]
        > $max_size
    ) {

        die(
            "Profile image must be smaller than 5 MB."
        );

    }


    /* =========================================
       CHECK MIME TYPE
    ========================================= */

    $allowed_types = [

        "image/jpeg",

        "image/png",

        "image/webp"

    ];


    $file_tmp =
        $_FILES["profile_image"]["tmp_name"];


    $file_type =
        mime_content_type(
            $file_tmp
        );


    if (
        !in_array(
            $file_type,
            $allowed_types
        )
    ) {

        die(
            "Invalid image format. Please upload JPG, PNG or WEBP."
        );

    }


    /* =========================================
       CREATE UPLOAD DIRECTORY
    ========================================= */

    $upload_directory =
        "../uploads/owners/";


    if (
        !is_dir(
            $upload_directory
        )
    ) {

        if (
            !mkdir(
                $upload_directory,
                0755,
                true
            )
        ) {

            die(
                "Unable to create upload directory."
            );

        }

    }


    /* =========================================
       GET FILE EXTENSION
    ========================================= */

    $extension =
        strtolower(
            pathinfo(
                $_FILES["profile_image"]["name"],
                PATHINFO_EXTENSION
            )
        );


    /* =========================================
       GENERATE UNIQUE FILE NAME
    ========================================= */

    $new_file_name =
        "owner_" .
        $owner_id .
        "_" .
        time() .
        "_" .
        bin2hex(
            random_bytes(5)
        ) .
        "." .
        $extension;


    $destination =
        $upload_directory .
        $new_file_name;


    /* =========================================
       MOVE UPLOADED FILE
    ========================================= */

    if (
        !move_uploaded_file(
            $file_tmp,
            $destination
        )
    ) {

        die(
            "Unable to save the profile image."
        );

    }


    /* =========================================
       DATABASE PATH
    ========================================= */

    $new_profile_image =
        "uploads/owners/" .
        $new_file_name;


    /* =========================================
       DELETE OLD PROFILE IMAGE
       Only if it belongs to uploads/owners
    ========================================= */

    if (
        !empty(
            $current_profile_image
        )
    ) {


        if (
            strpos(
                $current_profile_image,
                "uploads/owners/"
            ) === 0
        ) {


            $old_file =
                "../" .
                $current_profile_image;


            if (
                file_exists(
                    $old_file
                )
            ) {

                unlink(
                    $old_file
                );

            }

        }

    }

}


/* =========================================================
   UPDATE OWNER PROFILE
========================================================= */

$update = $conn->prepare("
    UPDATE users
    SET
        full_name = ?,
        phone = ?,
        profile_image = ?
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
    "sssi",
    $full_name,
    $phone,
    $new_profile_image,
    $owner_id
);


/* =========================================================
   EXECUTE UPDATE
========================================================= */

if (
    $update->execute()
) {


    /* =========================================
       UPDATE SESSION NAME
    ========================================= */

    $_SESSION["full_name"] =
        $full_name;


    /* =========================================
       SUCCESS REDIRECT
    ========================================= */

    header(
        "Location: profile.php?updated=success"
    );

    exit();


} else {


    die(
        "Unable to update profile. Please try again."
    );

}


$update->close();

?>