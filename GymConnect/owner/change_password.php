<?php

/* =========================================================
   GYMCONNECT - OWNER CHANGE PASSWORD
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


$ownerName =
    $_SESSION["full_name"] ?? "Owner";

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
    Change Password | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/change_password.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

</head>


<body>


<div class="password-container">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">

        <div>

            <h1>
                Change Password
            </h1>

            <p>
                Keep your GymConnect account secure
            </p>

        </div>


        <a
            href="profile.php"
            class="back-btn"
        >

            ← Back to Profile

        </a>

    </div>



    <!-- =====================================================
         PASSWORD CARD
    ====================================================== -->

    <div class="password-card">


        <div class="security-icon">

            🔐

        </div>


        <h2>
            Update Your Password
        </h2>


        <p class="description">

            Hello
            <strong>
                <?php
                echo htmlspecialchars(
                    $ownerName
                );
                ?>
            </strong>,
            enter your current password and
            choose a new password.

        </p>



        <!-- =================================================
             CHANGE PASSWORD FORM
        ================================================== -->

        <form
            action="change_password_process.php"
            method="POST"
        >


            <!-- =============================================
                 CURRENT PASSWORD
            ============================================== -->

            <div class="input-group">

                <label for="current_password">

                    Current Password

                </label>


                <div class="password-input">

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        placeholder="Enter your current password"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('current_password', this)"
                    >

                        👁

                    </button>

                </div>

            </div>



            <!-- =============================================
                 NEW PASSWORD
            ============================================== -->

            <div class="input-group">

                <label for="new_password">

                    New Password

                </label>


                <div class="password-input">

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Enter your new password"
                        minlength="8"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('new_password', this)"
                    >

                        👁

                    </button>

                </div>


                <small>

                    Password must contain at least
                    8 characters.

                </small>

            </div>



            <!-- =============================================
                 CONFIRM PASSWORD
            ============================================== -->

            <div class="input-group">

                <label for="confirm_password">

                    Confirm New Password

                </label>


                <div class="password-input">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your new password"
                        minlength="8"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('confirm_password', this)"
                    >

                        👁

                    </button>

                </div>

            </div>



            <!-- =============================================
                 PASSWORD REQUIREMENTS
            ============================================== -->

            <div class="password-rules">

                <h3>
                    Password Requirements
                </h3>


                <ul>

                    <li id="lengthRule">
                        At least 8 characters
                    </li>

                    <li id="matchRule">
                        New passwords must match
                    </li>

                </ul>

            </div>



            <!-- =============================================
                 BUTTONS
            ============================================== -->

            <div class="form-actions">


                <a
                    href="profile.php"
                    class="cancel-btn"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    class="save-btn"
                >

                    Change Password

                </button>


            </div>


        </form>


    </div>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* =========================================================
   SHOW / HIDE PASSWORD
========================================================= */

function togglePassword(
    fieldId,
    button
) {

    const field =
        document.getElementById(
            fieldId
        );


    if (
        field.type === "password"
    ) {

        field.type =
            "text";

        button.innerHTML =
            "🙈";

    } else {

        field.type =
            "password";

        button.innerHTML =
            "👁";

    }

}



/* =========================================================
   PASSWORD VALIDATION
========================================================= */

const newPassword =
    document.getElementById(
        "new_password"
    );


const confirmPassword =
    document.getElementById(
        "confirm_password"
    );


const lengthRule =
    document.getElementById(
        "lengthRule"
    );


const matchRule =
    document.getElementById(
        "matchRule"
    );



newPassword.addEventListener(
    "input",
    checkPassword
);


confirmPassword.addEventListener(
    "input",
    checkPassword
);



function checkPassword() {


    /* =========================================
       CHECK LENGTH
    ========================================= */

    if (
        newPassword.value.length >= 8
    ) {

        lengthRule.classList.add(
            "valid"
        );

    } else {

        lengthRule.classList.remove(
            "valid"
        );

    }


    /* =========================================
       CHECK PASSWORD MATCH
    ========================================= */

    if (
        confirmPassword.value !== "" &&
        newPassword.value ===
        confirmPassword.value
    ) {

        matchRule.classList.add(
            "valid"
        );

    } else {

        matchRule.classList.remove(
            "valid"
        );

    }

}


</script>


</body>

</html>