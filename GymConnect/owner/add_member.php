
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "owner"
) {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET OWNER'S GYMS
========================================================= */

$gyms = [];

$stmt = $conn->prepare("
    SELECT gym_id, gym_name
    FROM gyms
    WHERE owner_id = ?
    AND status = 'approved'
    AND is_active = 1
    ORDER BY gym_name ASC
");

$stmt->bind_param("i", $owner_id);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $gyms[] = $row;

}

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Add Member | GymConnect</title>

<link rel="stylesheet"
      href="../css/owner_members.css">

</head>


<body>


<div class="form-page">


    <div class="form-card">


        <div class="form-header">

            <h1>Add Member</h1>

            <p>
                Manually add a member to your gym
            </p>

        </div>


        <form
            action="add_member_process.php"
            method="POST"
        >


            <!-- MEMBER EMAIL -->

            <div class="form-group">

                <label>
                    Member Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter registered member email"
                    required
                >

                <small>

                    The member must already have
                    a GymConnect account.

                </small>

            </div>


            <!-- SELECT GYM -->

            <div class="form-group">

                <label>
                    Select Gym
                </label>

                <select
                    name="gym_id"
                    id="gym_id"
                    required
                >

                    <option value="">
                        Select your gym
                    </option>


                    <?php foreach ($gyms as $gym): ?>

                        <option
                            value="<?php
                            echo $gym["gym_id"];
                            ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                $gym["gym_name"]
                            );

                            ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- MEMBERSHIP PLAN -->

            <div class="form-group">

                <label>
                    Membership Plan
                </label>

                <select
                    name="plan_id"
                    id="plan_id"
                    required
                >

                    <option value="">
                        First select a gym
                    </option>

                </select>

            </div>


            <!-- START DATE -->

            <div class="form-group">

                <label>
                    Membership Start Date
                </label>

                <input
                    type="date"
                    name="start_date"
                    value="<?php
                    echo date('Y-m-d');
                    ?>"
                    required
                >

            </div>


            <!-- PAYMENT METHOD -->

            <div class="form-group">

                <label>
                    Payment Method
                </label>

                <select
                    name="payment_method"
                    required
                >

                    <option value="">
                        Select payment method
                    </option>

                    <option value="Cash">
                        Cash
                    </option>

                    <option value="UPI">
                        UPI
                    </option>

                    <option value="Card">
                        Card
                    </option>

                    <option value="Bank Transfer">
                        Bank Transfer
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>


            <div class="form-note">

                <strong>Important:</strong>

                This feature is for members who
                have already created a GymConnect
                account.

            </div>


            <div class="form-actions">

                <a
                    href="members.php"
                    class="cancel-btn"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    class="submit-btn"
                >

                    Add Member

                </button>

            </div>


        </form>


    </div>


</div>


<script>

const gymSelect =
    document.getElementById("gym_id");

const planSelect =
    document.getElementById("plan_id");


gymSelect.addEventListener(
    "change",
    function () {

        const gymId =
            this.value;


        planSelect.innerHTML =
            '<option value="">Loading plans...</option>';


        if (!gymId) {

            planSelect.innerHTML =
                '<option value="">First select a gym</option>';

            return;

        }


        fetch(
            "get_plans.php?gym_id="
            + encodeURIComponent(gymId)
        )

        .then(response =>
            response.json()
        )

        .then(data => {


            planSelect.innerHTML =
                '<option value="">Select membership plan</option>';


            if (
                data.success &&
                data.plans.length > 0
            ) {


                data.plans.forEach(
                    function (plan) {


                        const option =
                            document.createElement(
                                "option"
                            );


                        option.value =
                            plan.plan_id;


                        option.textContent =
                            plan.plan_name
                            + " - ₹"
                            + plan.price
                            + " ("
                            + plan.duration_months
                            + " Months)";


                        planSelect.appendChild(
                            option
                        );

                    }
                );


            } else {


                planSelect.innerHTML =
                    '<option value="">No plans available</option>';


            }


        })

        .catch(error => {

            console.error(error);

            planSelect.innerHTML =
                '<option value="">Unable to load plans</option>';

        });

    }
);

</script>


</body>

</html>
