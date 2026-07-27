
<?php

/* =========================================================
   GYMCONNECT - MEMBER PAYMENT PAGE
========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();


/* =========================================================
   MEMBER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: ../login.php");
    exit();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../database/connection.php";


/* =========================================================
   CHECK REQUIRED PARAMETERS
========================================================= */

if (
    !isset($_GET["plan_id"]) ||
    !isset($_GET["gym_id"]) ||
    !is_numeric($_GET["plan_id"]) ||
    !is_numeric($_GET["gym_id"])
) {
    header("Location: find_gyms.php");
    exit();
}


$plan_id = intval($_GET["plan_id"]);

$gym_id = intval($_GET["gym_id"]);

$member_id = $_SESSION["user_id"];


/* =========================================================
   GET GYM AND PLAN DETAILS
========================================================= */

$sql = "
    SELECT
        g.gym_id,
        g.gym_name,
        g.address,
        g.city,
        g.state,
        g.pincode,
        g.phone,
        g.profile_image,

        mp.plan_id,
        mp.plan_name,
        mp.duration_months,
        mp.price,
        mp.description

    FROM membership_plans mp

    INNER JOIN gyms g
        ON mp.gym_id = g.gym_id

    WHERE mp.plan_id = ?
    AND mp.gym_id = ?
    AND g.status = 'approved'
    AND g.is_active = 1

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Unable to prepare payment query: " .
        $conn->error
    );

}


$stmt->bind_param(
    "ii",
    $plan_id,
    $gym_id
);


$stmt->execute();


$result = $stmt->get_result();


/* =========================================================
   CHECK PLAN
========================================================= */

if ($result->num_rows === 0) {

    die("Invalid gym or membership plan.");

}


$data = $result->fetch_assoc();


/* =========================================================
   CHECK EXISTING ACTIVE MEMBERSHIP
========================================================= */

$check_sql = "

    SELECT membership_id

    FROM memberships

    WHERE member_id = ?

    AND status = 'Active'

    LIMIT 1

";


$check = $conn->prepare(
    $check_sql
);


if (!$check) {

    die(
        "Unable to check membership: " .
        $conn->error
    );

}


$check->bind_param(
    "i",
    $member_id
);


$check->execute();


$active_result =
    $check->get_result();


$has_active_membership =

    $active_result->num_rows > 0;


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

    Complete Payment | GymConnect

</title>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>


/* =========================================================
   GLOBAL
========================================================= */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

    font-family:
        'Poppins',
        sans-serif;

}


body {

    background: #111827;

    color: #ffffff;

    min-height: 100vh;

}


/* =========================================================
   PAGE
========================================================= */

.page {

    max-width: 1100px;

    margin: auto;

    padding: 40px 25px;

}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-link {

    display: inline-block;

    color: #00c2ff;

    text-decoration: none;

    margin-bottom: 25px;

    font-size: 14px;

}


.back-link:hover {

    text-decoration: underline;

}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {

    margin-bottom: 30px;

}


.page-title h1 {

    font-size: 32px;

    color: #ffffff;

}


.page-title p {

    color: #94a3b8;

    margin-top: 5px;

}


/* =========================================================
   PAYMENT GRID
========================================================= */

.payment-container {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 25px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background: #1e293b;

    border: 1px solid #334155;

    border-radius: 18px;

    padding: 30px;

}


/* =========================================================
   GYM INFORMATION
========================================================= */

.gym-header {

    display: flex;

    align-items: center;

    gap: 18px;

    margin-bottom: 25px;

}


.gym-image {

    width: 80px;

    height: 80px;

    border-radius: 12px;

    object-fit: cover;

    background: #0f172a;

}


.gym-placeholder {

    width: 80px;

    height: 80px;

    border-radius: 12px;

    background: #0f172a;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 35px;

}


.gym-header h2 {

    color: #00c2ff;

    font-size: 23px;

}


.gym-location {

    color: #94a3b8;

    font-size: 13px;

    margin-top: 5px;

}


.info-list {

    display: flex;

    flex-direction: column;

    gap: 14px;

}


.info-item {

    padding: 13px;

    background: #0f172a;

    border-radius: 9px;

    color: #cbd5e1;

    font-size: 14px;

}


.info-item strong {

    color: #ffffff;

}


/* =========================================================
   PLAN CARD
========================================================= */

.plan-title {

    color: #00c2ff;

    font-size: 22px;

    margin-bottom: 20px;

}


.plan-price {

    font-size: 42px;

    font-weight: 700;

    color: #00c2ff;

}


.plan-duration {

    color: #94a3b8;

    font-size: 14px;

    margin-bottom: 20px;

}


.plan-description {

    color: #cbd5e1;

    line-height: 1.7;

    font-size: 14px;

    padding: 15px;

    background: #0f172a;

    border-radius: 10px;

}


/* =========================================================
   PAYMENT FORM
========================================================= */

.payment-form {

    margin-top: 30px;

}


.payment-form h3 {

    font-size: 19px;

    margin-bottom: 15px;

}


.payment-method {

    display: flex;

    flex-direction: column;

    gap: 12px;

}


.payment-option {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 15px;

    background: #0f172a;

    border: 1px solid #334155;

    border-radius: 10px;

    cursor: pointer;

    transition: .3s;

}


.payment-option:hover {

    border-color: #00c2ff;

}


.payment-option input {

    accent-color: #00c2ff;

}


.payment-option label {

    cursor: pointer;

    color: #ffffff;

}


/* =========================================================
   PAY BUTTON
========================================================= */

.pay-btn {

    width: 100%;

    border: none;

    padding: 15px;

    margin-top: 25px;

    border-radius: 10px;

    background: #00c2ff;

    color: #000000;

    font-size: 16px;

    font-weight: 700;

    cursor: pointer;

    transition: .3s;

}


.pay-btn:hover {

    background: #19ccff;

    transform:
        translateY(-2px);

}


/* =========================================================
   ACTIVE MEMBERSHIP WARNING
========================================================= */

.warning {

    background:
        rgba(
            239,
            68,
            68,
            0.12
        );

    border:
        1px solid
        #ef4444;

    color: #fecaca;

    padding: 20px;

    border-radius: 10px;

    line-height: 1.7;

}


.warning h3 {

    color: #ef4444;

    margin-bottom: 8px;

}


.warning a {

    display: inline-block;

    margin-top: 15px;

    color: #ffffff;

    background: #ef4444;

    padding: 10px 18px;

    border-radius: 7px;

    text-decoration: none;

}


/* =========================================================
   SECURE PAYMENT
========================================================= */

.secure-text {

    margin-top: 15px;

    color: #64748b;

    font-size: 12px;

    text-align: center;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 800px
) {

    .payment-container {

        grid-template-columns:
            1fr;

    }


    .page-title h1 {

        font-size: 26px;

    }


    .plan-price {

        font-size: 35px;

    }

}


</style>


</head>


<body>


<div class="page">


    <!-- =====================================================
         BACK
    ====================================================== -->

    <a
        href="gym_details.php?gym_id=<?php
            echo $gym_id;
        ?>"
        class="back-link"
    >

        ← Back to Gym Details

    </a>


    <!-- =====================================================
         TITLE
    ====================================================== -->

    <div class="page-title">

        <h1>

            Complete Your Membership

        </h1>

        <p>

            Review your membership details
            and complete the payment.

        </p>

    </div>


    <?php if (
        $has_active_membership
    ): ?>


        <!-- =================================================
             ACTIVE MEMBERSHIP WARNING
        ================================================== -->

        <div class="card warning">


            <h3>

                ⚠️ Active Membership Already Exists

            </h3>


            <p>

                You already have an active gym membership.
                You cannot join another gym until your
                current membership expires or is transferred.

            </p>


            <a
                href="dashboard.php"
            >

                Go to My Dashboard

            </a>


        </div>


    <?php else: ?>


        <!-- =================================================
             PAYMENT CONTAINER
        ================================================== -->

        <div class="payment-container">


            <!-- =============================================
                 GYM DETAILS
            ============================================== -->

            <div class="card">


                <div class="gym-header">


                    <?php if (
                        !empty(
                            $data["profile_image"]
                        )
                    ): ?>


                        <img
                            src="../<?php
                                echo htmlspecialchars(
                                    $data["profile_image"]
                                );
                            ?>"
                            class="gym-image"
                            alt="Gym Image"
                        >


                    <?php else: ?>


                        <div class="gym-placeholder">

                            🏋️

                        </div>


                    <?php endif; ?>


                    <div>


                        <h2>

                            <?php

                            echo htmlspecialchars(
                                $data["gym_name"]
                            );

                            ?>

                        </h2>


                        <div class="gym-location">

                            📍

                            <?php

                            echo htmlspecialchars(
                                $data["city"]
                            );

                            ?>,

                            <?php

                            echo htmlspecialchars(
                                $data["state"]
                            );

                            ?>

                        </div>


                    </div>


                </div>


                <div class="info-list">


                    <div class="info-item">

                        <strong>
                            Address
                        </strong>

                        <br>

                        <?php

                        echo htmlspecialchars(
                            $data["address"]
                        );

                        ?>

                        <br>

                        <?php

                        echo htmlspecialchars(
                            $data["city"] .
                            ", " .
                            $data["state"] .
                            " - " .
                            $data["pincode"]
                        );

                        ?>

                    </div>


                    <?php if (
                        !empty(
                            $data["phone"]
                        )
                    ): ?>


                        <div class="info-item">

                            <strong>
                                Phone
                            </strong>

                            <br>

                            <?php

                            echo htmlspecialchars(
                                $data["phone"]
                            );

                            ?>

                        </div>


                    <?php endif; ?>


                    <div class="info-item">

                        <strong>
                            Membership Plan
                        </strong>

                        <br>

                        <?php

                        echo htmlspecialchars(
                            $data["plan_name"]
                        );

                        ?>

                    </div>


                    <div class="info-item">

                        <strong>
                            Duration
                        </strong>

                        <br>

                        <?php

                        echo htmlspecialchars(
                            $data["duration_months"]
                        );

                        ?>

                        Month(s)

                    </div>


                </div>


            </div>


            <!-- =============================================
                 PAYMENT DETAILS
            ============================================== -->

            <div class="card">


                <h2 class="plan-title">

                    💳 Payment Summary

                </h2>


                <div class="plan-price">

                    ₹<?php

                    echo number_format(
                        $data["price"],
                        2
                    );

                    ?>

                </div>


                <div class="plan-duration">

                    Total membership fee

                </div>


                <div class="plan-description">


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $data["plan_name"]
                        );

                        ?>

                    </strong>


                    <br><br>


                    Membership Duration:

                    <?php

                    echo htmlspecialchars(
                        $data["duration_months"]
                    );

                    ?>

                    Month(s)


                    <br><br>


                    <?php

                    echo !empty(
                        $data["description"]
                    )

                    ?

                    nl2br(
                        htmlspecialchars(
                            $data["description"]
                        )
                    )

                    :

                    "No additional plan information.";

                    ?>


                </div>


                <!-- =========================================
                     PAYMENT FORM
                ========================================== -->

                <form
                    action="payment_process.php"
                    method="POST"
                    class="payment-form"
                >


                    <!-- HIDDEN PLAN ID -->

                    <input
                        type="hidden"
                        name="plan_id"
                        value="<?php
                            echo $plan_id;
                        ?>"
                    >


                    <!-- HIDDEN GYM ID -->

                    <input
                        type="hidden"
                        name="gym_id"
                        value="<?php
                            echo $gym_id;
                        ?>"
                    >


                    <h3>

                        Select Payment Method

                    </h3>


                    <div class="payment-method">


                        <!-- CASH -->

                        <div class="payment-option">


                            <input
                                type="radio"
                                id="cash"
                                name="payment_method"
                                value="Cash"
                                required
                            >


                            <label for="cash">

                                💵 Cash

                            </label>


                        </div>


                        <!-- UPI -->

                        <div class="payment-option">


                            <input
                                type="radio"
                                id="upi"
                                name="payment_method"
                                value="UPI"
                            >


                            <label for="upi">

                                📱 UPI

                            </label>


                        </div>


                        <!-- CARD -->

                        <div class="payment-option">


                            <input
                                type="radio"
                                id="card"
                                name="payment_method"
                                value="Card"
                            >


                            <label for="card">

                                💳 Debit / Credit Card

                            </label>


                        </div>


                        <!-- ONLINE -->

                        <div class="payment-option">


                            <input
                                type="radio"
                                id="online"
                                name="payment_method"
                                value="Online"
                            >


                            <label for="online">

                                🌐 Online Payment

                            </label>


                        </div>


                    </div>


                    <!-- =====================================
                         SUBMIT
                    ====================================== -->

                    <button
                        type="submit"
                        class="pay-btn"
                    >

                        Pay ₹<?php

                        echo number_format(
                            $data["price"],
                            2
                        );

                        ?>

                        & Activate Membership

                    </button>


                    <div class="secure-text">

                        🔒 Your membership information
                        is securely processed by GymConnect.

                    </div>


                </form>


            </div>


        </div>


    <?php endif; ?>


</div>


</body>

</html>


<?php

$conn->close();

?>