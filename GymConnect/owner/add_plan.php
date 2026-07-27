```php
<?php

/* =========================================================
   GYMCONNECT - ADD MEMBERSHIP PLAN
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
   OWNER ID
========================================================= */

$owner_id = intval(
    $_SESSION["user_id"]
);


/* =========================================================
   GET ALL GYMS BELONGING TO THIS OWNER
========================================================= */

$sql = "

    SELECT
        gym_id,
        gym_name,
        city,
        state,
        status

    FROM gyms

    WHERE owner_id = ?

    ORDER BY gym_name ASC

";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
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
   CHECK IF OWNER HAS GYMS
========================================================= */

if ($result->num_rows == 0) {

    die("

        <div style='
            background:#111827;
            color:white;
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            text-align:center;
            font-family:Arial;
        '>

            <div>

                <h2>
                    No Gym Found
                </h2>

                <p>
                    You need to register a gym
                    before creating membership plans.
                </p>

                <br>

                <a
                    href='owner/register_gym.php'
                    style='
                        color:#00c2ff;
                        text-decoration:none;
                    '
                >

                    Register Gym

                </a>

            </div>

        </div>

    ");

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
    Add Membership Plan | GymConnect
</title>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin:
        0;

    padding:
        0;

    box-sizing:
        border-box;

    font-family:
        'Poppins',
        sans-serif;

}


/* =========================================================
   BODY
========================================================= */

body {

    background:
        #111827;

    color:
        #ffffff;

    min-height:
        100vh;

}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width:
        100%;

    max-width:
        700px;

    margin:
        50px auto;

    padding:
        0 20px;

}


/* =========================================================
   FORM BOX
========================================================= */

.form-box {

    background:
        #1e293b;

    padding:
        35px;

    border-radius:
        18px;

    border:
        1px solid #334155;

    box-shadow:
        0 20px 50px
        rgba(
            0,
            0,
            0,
            0.3
        );

}


/* =========================================================
   HEADER
========================================================= */

.form-box h1 {

    color:
        #00c2ff;

    margin-bottom:
        8px;

}


.form-box > p {

    color:
        #94a3b8;

    margin-bottom:
        30px;

}


/* =========================================================
   INPUT GROUP
========================================================= */

.input-group {

    margin-bottom:
        22px;

}


.input-group label {

    display:
        block;

    margin-bottom:
        8px;

    color:
        #e2e8f0;

    font-weight:
        500;

}


/* =========================================================
   INPUTS
========================================================= */

.input-group input,
.input-group textarea,
.input-group select {

    width:
        100%;

    padding:
        13px 15px;

    background:
        #0f172a;

    color:
        #ffffff;

    border:
        1px solid #334155;

    border-radius:
        9px;

    outline:
        none;

    font-size:
        15px;

}


.input-group input:focus,
.input-group textarea:focus,
.input-group select:focus {

    border-color:
        #00c2ff;

    box-shadow:
        0 0 0 3px
        rgba(
            0,
            194,
            255,
            0.1
        );

}


.input-group textarea {

    min-height:
        120px;

    resize:
        vertical;

}


/* =========================================================
   GYM SELECT
========================================================= */

.gym-select {

    border-left:
        4px solid #00c2ff;

    padding:
        15px;

    background:
        #0f172a;

    border-radius:
        10px;

}


.gym-select small {

    display:
        block;

    margin-top:
        7px;

    color:
        #94a3b8;

}


/* =========================================================
   ROW
========================================================= */

.row {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        20px;

}


/* =========================================================
   BUTTONS
========================================================= */

.buttons {

    display:
        flex;

    gap:
        15px;

    margin-top:
        30px;

}


.btn {

    flex:
        1;

    padding:
        13px 20px;

    border:
        none;

    border-radius:
        9px;

    text-decoration:
        none;

    text-align:
        center;

    font-weight:
        600;

    cursor:
        pointer;

    transition:
        0.3s;

}


.btn-primary {

    background:
        #00c2ff;

    color:
        #000000;

}


.btn-primary:hover {

    background:
        #19ccff;

    transform:
        translateY(-2px);

}


.btn-secondary {

    background:
        #334155;

    color:
        #ffffff;

}


.btn-secondary:hover {

    background:
        #475569;

}


/* =========================================================
   MOBILE
========================================================= */

@media (
    max-width: 600px
) {

    .container {

        margin:
            20px auto;

    }


    .form-box {

        padding:
            25px;

    }


    .row {

        grid-template-columns:
            1fr;

        gap:
            0;

    }


    .buttons {

        flex-direction:
            column;

    }

}

</style>


</head>


<body>


<div class="container">


<div class="form-box">


<h1>

    Add Membership Plan

</h1>


<p>

    Select a gym and create a membership plan for it.

</p>



<form
    action="add_plan_process.php"
    method="POST"
>


<!-- =====================================================
     SELECT GYM
====================================================== -->

<div class="input-group">


<label for="gym_id">

    Select Gym

</label>


<select
    name="gym_id"
    id="gym_id"
    class="gym-select"
    required
>


<option
    value=""
>

    -- Select Your Gym --

</option>


<?php

while (
    $gym =
    $result->fetch_assoc()
):

?>


<option
    value="<?php
        echo intval(
            $gym["gym_id"]
        );
    ?>"
>


<?php

echo htmlspecialchars(
    $gym["gym_name"]
);

?>


 -

<?php

echo htmlspecialchars(
    $gym["city"]
);

?>


<?php

if (
    !empty(
        $gym["state"]
    )
) {

    echo ", " .
        htmlspecialchars(
            $gym["state"]
        );

}

?>


</option>


<?php

endwhile;

?>


</select>


<small>

    Select the gym where you want to create
    this membership plan.

</small>


</div>



<!-- =====================================================
     PLAN NAME
====================================================== -->

<div class="input-group">


<label for="plan_name">

    Plan Name

</label>


<input
    type="text"
    id="plan_name"
    name="plan_name"
    placeholder="Example: Premium Monthly"
    required
>


</div>



<!-- =====================================================
     DURATION + PRICE
====================================================== -->

<div class="row">


<div class="input-group">


<label for="duration_months">

    Duration (Months)

</label>


<input
    type="number"
    id="duration_months"
    name="duration_months"
    min="1"
    max="120"
    placeholder="Example: 3"
    required
>


</div>



<div class="input-group">


<label for="price">

    Price (₹)

</label>


<input
    type="number"
    id="price"
    name="price"
    min="0"
    step="0.01"
    placeholder="Example: 1500"
    required
>


</div>


</div>



<!-- =====================================================
     DESCRIPTION
====================================================== -->

<div class="input-group">


<label for="description">

    Plan Description

</label>


<textarea
    id="description"
    name="description"
    placeholder="Describe this membership plan..."
></textarea>


</div>



<!-- =====================================================
     BUTTONS
====================================================== -->

<div class="buttons">


<a
    href="owner/membership_plans.php"
    class="btn btn-secondary"
>

    Cancel

</a>


<button
    type="submit"
    class="btn btn-primary"
>

    Create Membership Plan

</button>


</div>


</form>


</div>


</div>


</body>


</html>


<?php

$stmt->close();

$conn->close();

?>
```
