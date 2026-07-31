<?php

session_start();

require_once "../database/connection.php";


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


$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   VARIABLES
========================================================= */

$success_message = "";
$error_message = "";


/* =========================================================
   GET OWNER DETAILS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        user_id,
        full_name,
        email,
        phone
    FROM users
    WHERE user_id = ?
    AND role = 'owner'
    LIMIT 1
");


if (!$stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    "i",
    $owner_id
);


$stmt->execute();


$owner_result =
    $stmt->get_result();


if ($owner_result->num_rows === 0) {

    die("Owner account not found.");

}


$owner =
    $owner_result->fetch_assoc();


$stmt->close();


/* =========================================================
   GET OWNER GYM
========================================================= */

$gym = null;


$gym_stmt = $conn->prepare("
    SELECT
        gym_id,
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
        gym_capacity,
        website
    FROM gyms
    WHERE owner_id = ?
    LIMIT 1
");


if (!$gym_stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}


$gym_stmt->bind_param(
    "i",
    $owner_id
);


$gym_stmt->execute();


$gym_result =
    $gym_stmt->get_result();


if ($gym_result->num_rows > 0) {

    $gym =
        $gym_result->fetch_assoc();

}


$gym_stmt->close();


/* =========================================================
   UPDATE SETTINGS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    $action =
        $_POST["action"] ?? "";


    /* =====================================================
       UPDATE OWNER PROFILE
    ===================================================== */

    if (
        $action === "update_profile"
    ) {


        $full_name =
            trim(
                $_POST["full_name"] ?? ""
            );


        $email =
            trim(
                $_POST["email"] ?? ""
            );


        $phone =
            trim(
                $_POST["phone"] ?? ""
            );


        if (
            empty($full_name) ||
            empty($email)
        ) {

            $error_message =
                "Full name and email are required.";

        }

        elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error_message =
                "Please enter a valid email address.";

        }

        else {


            /* =============================================
               CHECK EMAIL
            ============================================= */

            $email_check =
                $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE email = ?
                    AND user_id != ?
                    LIMIT 1
                ");


            $email_check->bind_param(
                "si",
                $email,
                $owner_id
            );


            $email_check->execute();


            $email_result =
                $email_check->get_result();


            if (
                $email_result->num_rows > 0
            ) {

                $error_message =
                    "This email address is already registered.";

            }

            else {


                /* =========================================
                   UPDATE OWNER
                ========================================= */

                $update =
                    $conn->prepare("
                        UPDATE users

                        SET
                            full_name = ?,
                            email = ?,
                            phone = ?

                        WHERE user_id = ?

                        AND role = 'owner'
                    ");


                $update->bind_param(
                    "sssi",
                    $full_name,
                    $email,
                    $phone,
                    $owner_id
                );


                if (
                    $update->execute()
                ) {

                    $success_message =
                        "Profile updated successfully.";

                }

                else {

                    $error_message =
                        "Unable to update profile.";

                }


                $update->close();


                /* =========================================
                   UPDATE LOCAL DATA
                ========================================= */

                $owner["full_name"] =
                    $full_name;

                $owner["email"] =
                    $email;

                $owner["phone"] =
                    $phone;

            }


            $email_check->close();

        }

    }



    /* =====================================================
       UPDATE GYM
    ===================================================== */

    elseif (
        $action === "update_gym"
    ) {


        if (
            !$gym
        ) {

            $error_message =
                "You do not have a registered gym.";

        }

        else {


            $gym_name =
                trim(
                    $_POST["gym_name"] ?? ""
                );


            $description =
                trim(
                    $_POST["description"] ?? ""
                );


            $address =
                trim(
                    $_POST["address"] ?? ""
                );


            $city =
                trim(
                    $_POST["city"] ?? ""
                );


            $state =
                trim(
                    $_POST["state"] ?? ""
                );


            $country =
                trim(
                    $_POST["country"] ?? ""
                );


            $pincode =
                trim(
                    $_POST["pincode"] ?? ""
                );


            $gym_phone =
                trim(
                    $_POST["gym_phone"] ?? ""
                );


            $gym_email =
                trim(
                    $_POST["gym_email"] ?? ""
                );


            $opening_time =
                $_POST["opening_time"] ?? null;


            $closing_time =
                $_POST["closing_time"] ?? null;


            $gym_capacity =
                (int)
                ($_POST["gym_capacity"] ?? 0);


            $website =
                trim(
                    $_POST["website"] ?? ""
                );


            if (
                empty($gym_name)
            ) {

                $error_message =
                    "Gym name is required.";

            }

            elseif (
                !empty($gym_email) &&
                !filter_var(
                    $gym_email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $error_message =
                    "Please enter a valid gym email address.";

            }

            else {


                $update_gym =
                    $conn->prepare("
                        UPDATE gyms

                        SET

                            gym_name = ?,

                            description = ?,

                            address = ?,

                            city = ?,

                            state = ?,

                            country = ?,

                            pincode = ?,

                            phone = ?,

                            email = ?,

                            opening_time = ?,

                            closing_time = ?,

                            gym_capacity = ?,

                            website = ?

                        WHERE gym_id = ?

                        AND owner_id = ?
                    ");


                if (
                    !$update_gym
                ) {

                    $error_message =
                        "Unable to prepare gym update.";

                }

                else {


                    $update_gym->bind_param(

                        "sssssssssssissi",

                        $gym_name,

                        $description,

                        $address,

                        $city,

                        $state,

                        $country,

                        $pincode,

                        $gym_phone,

                        $gym_email,

                        $opening_time,

                        $closing_time,

                        $gym_capacity,

                        $website,

                        $gym["gym_id"],

                        $owner_id

                    );


                    if (
                        $update_gym->execute()
                    ) {

                        $success_message =
                            "Gym settings updated successfully.";


                        /* =============================
                           UPDATE LOCAL DATA
                        ============================= */

                        $gym["gym_name"] =
                            $gym_name;

                        $gym["description"] =
                            $description;

                        $gym["address"] =
                            $address;

                        $gym["city"] =
                            $city;

                        $gym["state"] =
                            $state;

                        $gym["country"] =
                            $country;

                        $gym["pincode"] =
                            $pincode;

                        $gym["phone"] =
                            $gym_phone;

                        $gym["email"] =
                            $gym_email;

                        $gym["opening_time"] =
                            $opening_time;

                        $gym["closing_time"] =
                            $closing_time;

                        $gym["gym_capacity"] =
                            $gym_capacity;

                        $gym["website"] =
                            $website;

                    }

                    else {

                        $error_message =
                            "Unable to update gym settings.";

                    }


                    $update_gym->close();

                }

            }

        }

    }



    /* =====================================================
       CHANGE PASSWORD
    ===================================================== */

    elseif (
        $action === "change_password"
    ) {


        $current_password =
            $_POST[
                "current_password"
            ] ?? "";


        $new_password =
            $_POST[
                "new_password"
            ] ?? "";


        $confirm_password =
            $_POST[
                "confirm_password"
            ] ?? "";


        if (
            empty($current_password) ||
            empty($new_password) ||
            empty($confirm_password)
        ) {

            $error_message =
                "All password fields are required.";

        }

        elseif (
            strlen(
                $new_password
            ) < 6
        ) {

            $error_message =
                "New password must contain at least 6 characters.";

        }

        elseif (
            $new_password !==
            $confirm_password
        ) {

            $error_message =
                "New password and confirm password do not match.";

        }

        else {


            /* =============================================
               GET CURRENT PASSWORD
            ============================================= */

            $password_stmt =
                $conn->prepare("
                    SELECT password
                    FROM users
                    WHERE user_id = ?
                    AND role = 'owner'
                    LIMIT 1
                ");


            $password_stmt->bind_param(
                "i",
                $owner_id
            );


            $password_stmt->execute();


            $password_result =
                $password_stmt->get_result();


            $password_data =
                $password_result->fetch_assoc();


            $password_stmt->close();


            if (
                !$password_data ||
                !password_verify(
                    $current_password,
                    $password_data["password"]
                )
            ) {

                $error_message =
                    "Current password is incorrect.";

            }

            else {


                $hashed_password =
                    password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );


                $password_update =
                    $conn->prepare("
                        UPDATE users

                        SET password = ?

                        WHERE user_id = ?

                        AND role = 'owner'
                    ");


                $password_update->bind_param(
                    "si",
                    $hashed_password,
                    $owner_id
                );


                if (
                    $password_update->execute()
                ) {

                    $success_message =
                        "Password changed successfully.";

                }

                else {

                    $error_message =
                        "Unable to change password.";

                }


                $password_update->close();

            }

        }

    }

}

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
    Owner Settings | GymConnect
</title>


<style>

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


body {

    font-family:
        Arial,
        sans-serif;

    background:
        #0b0f14;

    color:
        #ffffff;

    min-height:
        100vh;

    padding:
        35px;

}


.container {

    max-width:
        1100px;

    margin:
        auto;

}


.back-btn {

    display:
        inline-block;

    color:
        #ffffff;

    text-decoration:
        none;

    background:
        #171d25;

    padding:
        11px 18px;

    border-radius:
        7px;

    margin-bottom:
        25px;

}


.back-btn:hover {

    background:
        #202934;

}


h1 {

    font-size:
        32px;

    margin-bottom:
        8px;

}


.subtitle {

    color:
        #8e9aa9;

    margin-bottom:
        30px;

}


.message {

    padding:
        15px 18px;

    border-radius:
        8px;

    margin-bottom:
        25px;

}


.success {

    background:
        #10251a;

    border:
        1px solid
        #267647;

    color:
        #72e79a;

}


.error {

    background:
        #321719;

    border:
        1px solid
        #793238;

    color:
        #ff8585;

}


.card {

    background:
        #11161d;

    border:
        1px solid
        #252e39;

    border-radius:
        12px;

    padding:
        28px;

    margin-bottom:
        25px;

}


.card h2 {

    margin-bottom:
        8px;

}


.card-description {

    color:
        #8e9aa9;

    margin-bottom:
        25px;

}


.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            1fr
        );

    gap:
        20px;

}


.form-group {

    display:
        flex;

    flex-direction:
        column;

    gap:
        8px;

}


.form-group.full {

    grid-column:
        1 / -1;

}


label {

    color:
        #c5ccd5;

    font-size:
        14px;

    font-weight:
        600;

}


input,
textarea,
select {

    width:
        100%;

    background:
        #0b1016;

    color:
        #ffffff;

    border:
        1px solid
        #303946;

    border-radius:
        7px;

    padding:
        12px 14px;

    outline:
        none;

    font-size:
        14px;

}


input:focus,
textarea:focus,
select:focus {

    border-color:
        #20c77a;

}


textarea {

    min-height:
        110px;

    resize:
        vertical;

}


input::placeholder,
textarea::placeholder {

    color:
        #697585;

}


.btn {

    margin-top:
        25px;

    background:
        #20c77a;

    color:
        #06150e;

    border:
        none;

    padding:
        12px 22px;

    border-radius:
        7px;

    font-weight:
        bold;

    cursor:
        pointer;

}


.btn:hover {

    background:
        #28e28a;

}


.danger-card {

    border-color:
        #4b292d;

}


.logout-btn {

    display:
        inline-block;

    margin-top:
        10px;

    background:
        #321719;

    color:
        #ff8585;

    padding:
        11px 18px;

    border-radius:
        7px;

    text-decoration:
        none;

}


@media (
    max-width: 700px
) {

    body {

        padding:
            20px;

    }


    .form-grid {

        grid-template-columns:
            1fr;

    }


    .form-group.full {

        grid-column:
            auto;

    }

}

</style>

</head>


<body>


<div class="container">


<a
    href="dashboard.php"
    class="back-btn"
>

    ← Back to Dashboard

</a>


<h1>

    Owner Settings

</h1>


<p class="subtitle">

    Manage your owner profile, gym information,
    and account security.

</p>


<?php if (
    !empty(
        $success_message
    )
): ?>

<div class="message success">

    ✓

    <?php

    echo htmlspecialchars(
        $success_message
    );

    ?>

</div>

<?php endif; ?>


<?php if (
    !empty(
        $error_message
    )
): ?>

<div class="message error">

    ✕

    <?php

    echo htmlspecialchars(
        $error_message
    );

    ?>

</div>

<?php endif; ?>


<!-- =====================================================
     OWNER PROFILE
===================================================== -->

<div class="card">


<h2>

    Owner Profile

</h2>


<p class="card-description">

    Update your personal account information.

</p>


<form
    method="POST"
>


<input
    type="hidden"
    name="action"
    value="update_profile"
>


<div class="form-grid">


<div class="form-group">


<label>

    Full Name

</label>


<input
    type="text"
    name="full_name"
    value="<?php

        echo htmlspecialchars(
            $owner["full_name"]
        );

    ?>"
    required
>


</div>


<div class="form-group">


<label>

    Email

</label>


<input
    type="email"
    name="email"
    value="<?php

        echo htmlspecialchars(
            $owner["email"]
        );

    ?>"
    required
>


</div>


<div class="form-group">


<label>

    Phone

</label>


<input
    type="text"
    name="phone"
    value="<?php

        echo htmlspecialchars(
            $owner["phone"] ?? ""
        );

    ?>"
>


</div>


</div>


<button
    type="submit"
    class="btn"
>

    Save Profile

</button>


</form>


</div>


<?php if (
    $gym
): ?>


<!-- =====================================================
     GYM SETTINGS
===================================================== -->

<div class="card">


<h2>

    Gym Information

</h2>


<p class="card-description">

    Update the information displayed
    for your gym.

</p>


<form
    method="POST"
>


<input
    type="hidden"
    name="action"
    value="update_gym"
>


<div class="form-grid">


<div class="form-group">


<label>

    Gym Name

</label>


<input
    type="text"
    name="gym_name"
    value="<?php

        echo htmlspecialchars(
            $gym["gym_name"]
        );

    ?>"
    required
>


</div>


<div class="form-group">


<label>

    Gym Phone

</label>


<input
    type="text"
    name="gym_phone"
    value="<?php

        echo htmlspecialchars(
            $gym["phone"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    Gym Email

</label>


<input
    type="email"
    name="gym_email"
    value="<?php

        echo htmlspecialchars(
            $gym["email"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    Website

</label>


<input
    type="url"
    name="website"
    value="<?php

        echo htmlspecialchars(
            $gym["website"] ?? ""
        );

    ?>"
    placeholder="https://example.com"
>


</div>


<div class="form-group full">


<label>

    Description

</label>


<textarea
    name="description"
><?php

echo htmlspecialchars(
    $gym["description"] ?? ""
);

?></textarea>


</div>


<div class="form-group full">


<label>

    Address

</label>


<input
    type="text"
    name="address"
    value="<?php

        echo htmlspecialchars(
            $gym["address"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    City

</label>


<input
    type="text"
    name="city"
    value="<?php

        echo htmlspecialchars(
            $gym["city"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    State

</label>


<input
    type="text"
    name="state"
    value="<?php

        echo htmlspecialchars(
            $gym["state"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    Country

</label>


<input
    type="text"
    name="country"
    value="<?php

        echo htmlspecialchars(
            $gym["country"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    Pincode

</label>


<input
    type="text"
    name="pincode"
    value="<?php

        echo htmlspecialchars(
            $gym["pincode"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    Opening Time

</label>


<input
    type="time"
    name="opening_time"
    value="<?php

        echo htmlspecialchars(
            $gym["opening_time"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    Closing Time

</label>


<input
    type="time"
    name="closing_time"
    value="<?php

        echo htmlspecialchars(
            $gym["closing_time"] ?? ""
        );

    ?>"
>


</div>


<div class="form-group">


<label>

    Gym Capacity

</label>


<input
    type="number"
    name="gym_capacity"
    min="0"
    value="<?php

        echo (int)
            (
                $gym[
                    "gym_capacity"
                ] ?? 0
            );

    ?>"
>


</div>


</div>


<button
    type="submit"
    class="btn"
>

    Save Gym Settings

</button>


</form>


</div>


<?php endif; ?>


<!-- =====================================================
     CHANGE PASSWORD
===================================================== -->

<div class="card">


<h2>

    Change Password

</h2>


<p class="card-description">

    Update your owner account password.

</p>


<form
    method="POST"
>


<input
    type="hidden"
    name="action"
    value="change_password"
>


<div class="form-grid">


<div class="form-group full">


<label>

    Current Password

</label>


<input
    type="password"
    name="current_password"
    required
>


</div>


<div class="form-group">


<label>

    New Password

</label>


<input
    type="password"
    name="new_password"
    minlength="6"
    required
>


</div>


<div class="form-group">


<label>

    Confirm New Password

</label>


<input
    type="password"
    name="confirm_password"
    minlength="6"
    required
>


</div>


</div>


<button
    type="submit"
    class="btn"
>

    Change Password

</button>


</form>


</div>


<!-- =====================================================
     LOGOUT
===================================================== -->

<div class="card danger-card">


<h2>

    Account

</h2>


<p class="card-description">

    Sign out from your GymConnect owner account.

</p>


<a
    href="../logout.php"
    class="logout-btn"
    onclick="
        return confirm(
            'Are you sure you want to logout?'
        );
    "
>

    Logout

</a>


</div>


</div>


</body>

</html>