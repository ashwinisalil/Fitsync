<?php

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

require_once "../database/connection.php";

$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET SETTLEMENTS FOR OWNER'S GYMS
========================================================= */

$settlements = [];

$stmt = $conn->prepare("
    SELECT

        mt.transfer_id,
        mt.membership_id,
        mt.member_id,

        mt.from_gym_id,
        mt.to_gym_id,

        mt.transfer_fee,
        mt.original_gym_amount,
        mt.gymconnect_amount,

        mt.remaining_membership_value,

        mt.status,
        mt.payment_status,
        mt.settlement_status,

        mt.requested_at,
        mt.completed_at,

        from_gym.gym_name AS from_gym_name,

        to_gym.gym_name AS to_gym_name,

        u.full_name AS member_name,

        u.email AS member_email

    FROM membership_transfers mt

    INNER JOIN gyms from_gym
        ON mt.from_gym_id = from_gym.gym_id

    INNER JOIN gyms to_gym
        ON mt.to_gym_id = to_gym.gym_id

    INNER JOIN users u
        ON mt.member_id = u.user_id

    WHERE to_gym.owner_id = ?

    AND mt.payment_status = 'Paid'

    AND mt.status = 'Completed'

    ORDER BY mt.completed_at DESC
");

if (!$stmt) {
    die(
        "Database error: "
        . htmlspecialchars($conn->error)
    );
}

$stmt->bind_param(
    "i",
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $settlements[] = $row;
}

$stmt->close();


/* =========================================================
   CALCULATE TOTALS
========================================================= */

$total_pending = 0;

$total_paid = 0;

foreach ($settlements as $settlement) {

    $amount =
        (float)
        $settlement["remaining_membership_value"];

    if (
        $settlement["settlement_status"]
        ===
        "Completed"
    ) {

        $total_paid += $amount;

    } else {

        $total_pending += $amount;

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
    Gym Settlements | GymConnect
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
        30px;

}


.container {

    max-width:
        1300px;

    margin:
        auto;

}


.back-btn {

    display:
        inline-block;

    color:
        #9ca8b7;

    text-decoration:
        none;

    margin-bottom:
        25px;

}


.back-btn:hover {

    color:
        #ffffff;

}


h1 {

    font-size:
        32px;

    margin-bottom:
        8px;

}


.subtitle {

    color:
        #8d99a8;

    margin-bottom:
        30px;

}


/* =========================================================
   STAT CARDS
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(
            3,
            1fr
        );

    gap:
        20px;

    margin-bottom:
        30px;

}


.stat-card {

    background:
        #11161d;

    border:
        1px solid
        #252e39;

    border-radius:
        12px;

    padding:
        25px;

}


.stat-title {

    color:
        #8e9aa9;

    font-size:
        14px;

    margin-bottom:
        12px;

}


.stat-value {

    font-size:
        28px;

    font-weight:
        bold;

}


/* =========================================================
   TABLE
========================================================= */

.table-card {

    background:
        #11161d;

    border:
        1px solid
        #252e39;

    border-radius:
        12px;

    padding:
        25px;

    overflow-x:
        auto;

}


.table-title {

    font-size:
        22px;

    font-weight:
        bold;

    margin-bottom:
        20px;

}


table {

    width:
        100%;

    border-collapse:
        collapse;

    min-width:
        1100px;

}


th {

    text-align:
        left;

    color:
        #8e9aa9;

    font-size:
        13px;

    padding:
        14px;

    border-bottom:
        1px solid
        #2a323d;

}


td {

    padding:
        15px
        14px;

    border-bottom:
        1px solid
        #202832;

    font-size:
        14px;

}


tr:hover {

    background:
        #151b22;

}


/* =========================================================
   BADGES
========================================================= */

.badge {

    display:
        inline-block;

    padding:
        6px
        10px;

    border-radius:
        20px;

    font-size:
        12px;

    font-weight:
        bold;

}


.pending {

    background:
        #443716;

    color:
        #f2c45e;

}


.completed {

    background:
        #173d2c;

    color:
        #55e39a;

}


/* =========================================================
   PAY BUTTON
========================================================= */

.pay-btn {

    display:
        inline-block;

    padding:
        9px
        14px;

    background:
        #20c77a;

    color:
        #06150e;

    text-decoration:
        none;

    border-radius:
        7px;

    font-size:
        13px;

    font-weight:
        bold;

}


.pay-btn:hover {

    background:
        #28e28a;

}


.no-data {

    text-align:
        center;

    color:
        #8d99a8;

    padding:
        50px;

}


@media (
    max-width: 800px
) {

    .stats {

        grid-template-columns:
            1fr;

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
    Gym Settlements
</h1>


<p class="subtitle">

    View payments owed to other gyms
    for transferred memberships.

</p>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">


<div class="stat-card">

    <div class="stat-title">

        Pending Settlement

    </div>

    <div class="stat-value">

        ₹<?php

        echo number_format(
            $total_pending,
            2
        );

        ?>

    </div>

</div>


<div class="stat-card">

    <div class="stat-title">

        Completed Settlement

    </div>

    <div class="stat-value">

        ₹<?php

        echo number_format(
            $total_paid,
            2
        );

        ?>

    </div>

</div>


<div class="stat-card">

    <div class="stat-title">

        Total Transfers

    </div>

    <div class="stat-value">

        <?php

        echo count(
            $settlements
        );

        ?>

    </div>

</div>


</div>


<!-- =====================================================
     SETTLEMENT TABLE
===================================================== -->

<div class="table-card">


<div class="table-title">

    Membership Transfer Settlements

</div>


<table>


<thead>

<tr>

<th>
    Transfer ID
</th>

<th>
    Member
</th>

<th>
    From Gym
</th>

<th>
    Your Gym
</th>

<th>
    Remaining Value
</th>

<th>
    Settlement Status
</th>

<th>
    Action
</th>

</tr>

</thead>


<tbody>


<?php if (
    count($settlements) > 0
): ?>


<?php foreach (
    $settlements
    as $settlement
): ?>


<tr>


<td>

#<?php

echo
    (int)
    $settlement[
        "transfer_id"
    ];

?>

</td>


<td>

<strong>

<?php

echo htmlspecialchars(
    $settlement[
        "member_name"
    ]
);

?>

</strong>

<br>

<small style="color:#8d99a8;">

<?php

echo htmlspecialchars(
    $settlement[
        "member_email"
    ]
);

?>

</small>

</td>


<td>

<?php

echo htmlspecialchars(
    $settlement[
        "from_gym_name"
    ]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $settlement[
        "to_gym_name"
    ]
);

?>

</td>


<td>

<strong>

₹<?php

echo number_format(
    (float)
    $settlement[
        "remaining_membership_value"
    ],
    2
);

?>

</strong>

</td>


<td>


<?php if (
    $settlement[
        "settlement_status"
    ]
    ===
    "Completed"
): ?>


<span class="badge completed">

    Paid

</span>


<?php else: ?>


<span class="badge pending">

    Pending

</span>


<?php endif; ?>


</td>


<td>


<?php if (
    $settlement[
        "settlement_status"
    ]
    !==
    "Completed"
): ?>


<a
    href="pay_settlement.php?id=<?php
        echo
        (int)
        $settlement[
            "transfer_id"
        ];
    ?>"
    class="pay-btn"
>

    Pay Settlement

</a>


<?php else: ?>


<span style="color:#55e39a;">

    Completed

</span>


<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="7"
    class="no-data"
>

No pending or completed settlements found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>


</body>

</html>