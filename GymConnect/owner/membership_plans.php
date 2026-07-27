
<?php

/* =========================================================
   GYMCONNECT - OWNER MEMBERSHIP PLANS
   Supports Multiple Gyms Per Owner
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

$owner_id = intval(
    $_SESSION["user_id"]
);


/* =========================================================
   GET ALL GYMS OF LOGGED-IN OWNER
========================================================= */

$gym_sql = "

    SELECT
        gym_id,
        gym_name,
        city,
        state,
        status,
        is_active

    FROM gyms

    WHERE owner_id = ?

    ORDER BY gym_id DESC

";


$gym_stmt = $conn->prepare(
    $gym_sql
);


if (!$gym_stmt) {

    die(
        "Unable to load gyms: " .
        $conn->error
    );

}


$gym_stmt->bind_param(
    "i",
    $owner_id
);


$gym_stmt->execute();


$gym_result =
    $gym_stmt->get_result();


/* =========================================================
   CHECK IF OWNER HAS ANY GYM
========================================================= */

if (
    $gym_result->num_rows === 0
) {

    die("
        <div style='
            font-family: Arial;
            padding: 40px;
            text-align: center;
        '>

            <h2>
                No Gym Registered
            </h2>

            <p>
                Please register a gym first
                before creating membership plans.
            </p>

            <br>

            <a href='register_gym.php'>
                Register Your Gym
            </a>

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

    Membership Plans | GymConnect

</title>


<link
    rel="stylesheet"
    href="../css/membership_plans.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   MULTIPLE GYM SECTION
========================================================= */

.gym-section {

    background:
        #1e293b;

    border:
        1px solid #334155;

    border-radius:
        18px;

    margin-bottom:
        35px;

    padding:
        25px;

}


/* =========================================================
   GYM HEADER
========================================================= */

.gym-header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        20px;

    margin-bottom:
        20px;

    padding-bottom:
        20px;

    border-bottom:
        1px solid #334155;

}


.gym-info h2 {

    color:
        #00c2ff;

    font-size:
        24px;

    margin-bottom:
        6px;

}


.gym-location {

    color:
        #94a3b8;

    font-size:
        14px;

}


.gym-status {

    display:
        inline-block;

    margin-top:
        8px;

    padding:
        5px 12px;

    border-radius:
        20px;

    font-size:
        12px;

    font-weight:
        600;

}


.status-approved {

    background:
        #22c55e;

    color:
        white;

}


.status-pending {

    background:
        #f59e0b;

    color:
        #000;

}


.status-rejected {

    background:
        #ef4444;

    color:
        white;

}


/* =========================================================
   ADD PLAN BUTTON
========================================================= */

.add-plan-btn {

    display:
        inline-block;

    background:
        #00c2ff;

    color:
        #000;

    padding:
        11px 20px;

    border-radius:
        9px;

    text-decoration:
        none;

    font-weight:
        600;

    white-space:
        nowrap;

    transition:
        0.3s;

}


.add-plan-btn:hover {

    background:
        #19ccff;

    transform:
        translateY(-2px);

}


/* =========================================================
   PLAN TABLE
========================================================= */

.plan-table-wrapper {

    overflow-x:
        auto;

}


.plan-table {

    width:
        100%;

    border-collapse:
        collapse;

}


.plan-table th {

    background:
        #0f172a;

    color:
        #ffffff;

    padding:
        14px;

    text-align:
        left;

}


.plan-table td {

    padding:
        14px;

    border-bottom:
        1px solid #334155;

    color:
        #cbd5e1;

}


.plan-table tr:hover {

    background:
        #263548;

}


/* =========================================================
   ACTIONS
========================================================= */

.edit-btn {

    color:
        #00c2ff;

    text-decoration:
        none;

    font-weight:
        600;

}


.delete-btn {

    color:
        #ef4444;

    text-decoration:
        none;

    font-weight:
        600;

}


.edit-btn:hover,
.delete-btn:hover {

    text-decoration:
        underline;

}


/* =========================================================
   NO PLANS
========================================================= */

.no-plans {

    text-align:
        center;

    padding:
        30px;

    color:
        #94a3b8;

}


.no-plans a {

    display:
        inline-block;

    margin-top:
        12px;

    color:
        #00c2ff;

    text-decoration:
        none;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 700px
) {

    .gym-header {

        flex-direction:
            column;

        align-items:
            flex-start;

    }


    .add-plan-btn {

        width:
            100%;

        text-align:
            center;

    }

}

</style>


</head>


<body>


<div class="container">


    <!-- =====================================================
         MAIN PAGE HEADER
    ====================================================== -->

    <div class="header">


        <div>

            <h1>

                Membership Plans

            </h1>

            <p>

                Manage membership plans
                for all your gyms.

            </p>

        </div>


        <a
            href="dashboard.php"
            class="add-btn"
        >

            ← Dashboard

        </a>


    </div>


    <!-- =====================================================
         LOOP THROUGH ALL OWNER GYMS
    ====================================================== -->

    <?php

    while (
        $gym = $gym_result->fetch_assoc()
    ):

        $gym_id =
            intval(
                $gym["gym_id"]
            );


        /* =================================================
           GET PLANS FOR CURRENT GYM
        ================================================== */

        $plan_sql = "

            SELECT
                plan_id,
                plan_name,
                duration_months,
                price,
                description,
                created_at

            FROM membership_plans

            WHERE gym_id = ?

            ORDER BY created_at DESC

        ";


        $plan_stmt =
            $conn->prepare(
                $plan_sql
            );


        $plan_stmt->bind_param(
            "i",
            $gym_id
        );


        $plan_stmt->execute();


        $plan_result =
            $plan_stmt->get_result();


        /* =================================================
           STATUS CLASS
        ================================================== */

        $status =
            strtolower(
                $gym["status"]
            );


        ?>



        <!-- =================================================
             GYM SECTION
        ================================================== -->

        <div class="gym-section">


            <!-- =============================================
                 GYM HEADER
            ============================================== -->

            <div class="gym-header">


                <div class="gym-info">


                    <h2>

                        🏢

                        <?php

                        echo htmlspecialchars(
                            $gym["gym_name"]
                        );

                        ?>

                    </h2>


                    <div class="gym-location">


                        📍

                        <?php

                        echo htmlspecialchars(
                            $gym["city"]
                        );

                        ?>,


                        <?php

                        echo htmlspecialchars(
                            $gym["state"]
                        );

                        ?>


                    </div>


                    <span
                        class="
                            gym-status
                            status-<?php
                                echo $status;
                            ?>
                        "
                    >


                        <?php

                        echo ucfirst(
                            $status
                        );

                        ?>


                    </span>


                </div>



                <!-- =========================================
                     ADD PLAN FOR THIS SPECIFIC GYM
                ========================================== -->

                <a
                    href="add_plan.php?gym_id=<?php
                        echo $gym_id;
                    ?>"
                    class="add-plan-btn"
                >

                    + Add Plan

                </a>


            </div>



            <!-- =================================================
                 PLAN TABLE
            ================================================== -->

            <div class="plan-table-wrapper">


                <?php

                if (
                    $plan_result->num_rows > 0
                ):

                ?>


                <table
                    class="plan-table"
                >


                    <thead>


                    <tr>


                        <th>
                            Plan Name
                        </th>


                        <th>
                            Duration
                        </th>


                        <th>
                            Price
                        </th>


                        <th>
                            Description
                        </th>


                        <th>
                            Actions
                        </th>


                    </tr>


                    </thead>


                    <tbody>


                    <?php

                    while (
                        $row =
                        $plan_result->fetch_assoc()
                    ):

                    ?>


                    <tr>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row["plan_name"]
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo intval(
                                $row["duration_months"]
                            );

                            ?>

                            Month(s)

                        </td>


                        <td>

                            ₹

                            <?php

                            echo number_format(
                                $row["price"],
                                2
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $row["description"]
                            );

                            ?>

                        </td>


                        <td>


                            <a
                                href="edit_plan.php?id=<?php
                                    echo $row["plan_id"];
                                ?>"
                                class="edit-btn"
                            >

                                Edit

                            </a>


                            &nbsp; | &nbsp;


                            <a
                                href="delete_plan.php?id=<?php
                                    echo $row["plan_id"];
                                ?>"
                                class="delete-btn"
                                onclick="
                                    return confirm(
                                        'Delete this membership plan?'
                                    );
                                "
                            >

                                Delete

                            </a>


                        </td>


                    </tr>


                    <?php

                    endwhile;

                    ?>


                    </tbody>


                </table>


                <?php

                else:

                ?>


                <div class="no-plans">


                    <p>

                        No membership plans
                        have been created
                        for this gym yet.

                    </p>


                    <a
                        href="add_plan.php?gym_id=<?php
                            echo $gym_id;
                        ?>"
                    >

                        Create the first plan
                        for this gym →

                    </a>


                </div>


                <?php

                endif;

                ?>


            </div>


        </div>


        <?php


        $plan_stmt->close();


    endwhile;


    ?>


</div>


</body>


</html>


<?php

$gym_stmt->close();

$conn->close();

?>