<?php

session_start();


/* =========================================================
   ADMIN LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: ../login.php");

    exit();

}


require_once "../database/connection.php";


/* =========================================================
   TOTAL GYMCONNECT TRANSFER REVENUE
========================================================= */

$total_revenue = 0;

$stmt = $conn->prepare("
    SELECT
        COALESCE(
            SUM(gymconnect_amount),
            0
        ) AS total_revenue

    FROM membership_transfers

    WHERE payment_status = 'Paid'
");


if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $total_revenue =
        (float)
        $row["total_revenue"];

    $stmt->close();

}


/* =========================================================
   TOTAL TRANSFER FEES
========================================================= */

$total_transfer_fees = 0;

$stmt = $conn->prepare("
    SELECT
        COALESCE(
            SUM(transfer_fee),
            0
        ) AS total_fees

    FROM membership_transfers

    WHERE payment_status = 'Paid'
");


if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $total_transfer_fees =
        (float)
        $row["total_fees"];

    $stmt->close();

}


/* =========================================================
   TOTAL ORIGINAL GYM SHARE
========================================================= */

$total_gym_share = 0;

$stmt = $conn->prepare("
    SELECT
        COALESCE(
            SUM(original_gym_amount),
            0
        ) AS total_gym_share

    FROM membership_transfers

    WHERE payment_status = 'Paid'
");


if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $total_gym_share =
        (float)
        $row["total_gym_share"];

    $stmt->close();

}


/* =========================================================
   TOTAL TRANSFERS
========================================================= */

$total_transfers = 0;

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_transfers

    FROM membership_transfers

    WHERE payment_status = 'Paid'
");


if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $total_transfers =
        (int)
        $row["total_transfers"];

    $stmt->close();

}


/* =========================================================
   PENDING SETTLEMENTS
========================================================= */

$pending_settlements = 0;

$stmt = $conn->prepare("
    SELECT

        COALESCE(
            SUM(remaining_membership_value),
            0
        ) AS pending_amount

    FROM membership_transfers

    WHERE payment_status = 'Paid'

    AND settlement_status != 'Completed'
");


if ($stmt) {

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $pending_settlements =
        (float)
        $row["pending_amount"];

    $stmt->close();

}


/* =========================================================
   TRANSFER HISTORY
========================================================= */

$transfers = [];

$query = "
    SELECT

        mt.transfer_id,

        mt.transfer_fee,

        mt.original_gym_amount,

        mt.gymconnect_amount,

        mt.remaining_membership_value,

        mt.status,

        mt.payment_status,

        mt.settlement_status,

        mt.requested_at,

        mt.paid_at,

        from_gym.gym_name
        AS from_gym_name,

        to_gym.gym_name
        AS to_gym_name,

        u.full_name
        AS member_name

    FROM membership_transfers mt

    INNER JOIN gyms from_gym

        ON mt.from_gym_id =
        from_gym.gym_id

    INNER JOIN gyms to_gym

        ON mt.to_gym_id =
        to_gym.gym_id

    INNER JOIN users u

        ON mt.member_id =
        u.user_id

    ORDER BY
        mt.requested_at DESC
";


$result =
    $conn->query(
        $query
    );


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $transfers[] =
            $row;

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
    Transfer Revenue | GymConnect Admin
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

    padding:
        30px;

}


.container {

    max-width:
        1400px;

    margin:
        auto;

}


.back {

    display:
        inline-block;

    margin-bottom:
        25px;

    color:
        #9ca9b8;

    text-decoration:
        none;

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


/* =====================================================
   STAT CARDS
===================================================== */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(
            4,
            1fr
        );

    gap:
        20px;

    margin-bottom:
        35px;

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


/* =====================================================
   TABLE
===================================================== */

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
        #8f9bab;

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


.paid {

    background:
        #173d2c;

    color:
        #55e39a;

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


.not-completed {

    background:
        #3d2222;

    color:
        #f17b7b;

}


@media (
    max-width: 900px
) {

    .stats {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }

}


@media (
    max-width: 600px
) {

    body {

        padding:
            15px;

    }


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
    class="back"
>
    ← Back to Admin Dashboard
</a>


<h1>
    Transfer Revenue
</h1>


<p class="subtitle">

    Monitor GymConnect transfer fees,
    revenue, and gym settlement activity.

</p>



<!-- =====================================================
     STATISTICS
===================================================== -->


<div class="stats">


<div class="stat-card">

    <div class="stat-title">

        GymConnect Revenue

    </div>

    <div class="stat-value">

        ₹<?php

        echo number_format(
            $total_revenue,
            2
        );

        ?>

    </div>

</div>



<div class="stat-card">

    <div class="stat-title">

        Total Transfer Fees

    </div>

    <div class="stat-value">

        ₹<?php

        echo number_format(
            $total_transfer_fees,
            2
        );

        ?>

    </div>

</div>



<div class="stat-card">

    <div class="stat-title">

        Original Gym Share

    </div>

    <div class="stat-value">

        ₹<?php

        echo number_format(
            $total_gym_share,
            2
        );

        ?>

    </div>

</div>



<div class="stat-card">

    <div class="stat-title">

        Pending Gym Settlements

    </div>

    <div class="stat-value">

        ₹<?php

        echo number_format(
            $pending_settlements,
            2
        );

        ?>

    </div>

</div>


</div>



<!-- =====================================================
     TRANSFER HISTORY
===================================================== -->


<div class="table-card">


<div class="table-title">

    Transfer Payment History

</div>


<table>


<thead>

<tr>

<th>
    ID
</th>

<th>
    Member
</th>

<th>
    From Gym
</th>

<th>
    To Gym
</th>

<th>
    Transfer Fee
</th>

<th>
    Gym Share
</th>

<th>
    GymConnect Share
</th>

<th>
    Membership Value
</th>

<th>
    Payment
</th>

<th>
    Settlement
</th>

<th>
    Date
</th>

</tr>

</thead>


<tbody>


<?php if (
    count($transfers) > 0
): ?>


<?php foreach (
    $transfers
    as $transfer
): ?>


<tr>


<td>

#<?php

echo
    (int)
    $transfer[
        "transfer_id"
    ];

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $transfer[
        "member_name"
    ]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $transfer[
        "from_gym_name"
    ]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $transfer[
        "to_gym_name"
    ]
);

?>

</td>


<td>

₹<?php

echo number_format(
    (float)
    $transfer[
        "transfer_fee"
    ],
    2
);

?>

</td>


<td>

₹<?php

echo number_format(
    (float)
    $transfer[
        "original_gym_amount"
    ],
    2
);

?>

</td>


<td>

<strong>

₹<?php

echo number_format(
    (float)
    $transfer[
        "gymconnect_amount"
    ],
    2
);

?>

</strong>

</td>


<td>

₹<?php

echo number_format(
    (float)
    $transfer[
        "remaining_membership_value"
    ],
    2
);

?>

</td>


<td>


<?php if (
    $transfer[
        "payment_status"
    ]
    ===
    "Paid"
): ?>


<span class="badge paid">

    Paid

</span>


<?php else: ?>


<span class="badge pending">

    <?php

    echo htmlspecialchars(
        $transfer[
            "payment_status"
        ]
    );

    ?>

</span>


<?php endif; ?>


</td>


<td>


<?php if (
    $transfer[
        "settlement_status"
    ]
    ===
    "Completed"
): ?>


<span class="badge completed">

    Completed

</span>


<?php else: ?>


<span class="badge not-completed">

    <?php

    echo htmlspecialchars(
        $transfer[
            "settlement_status"
        ]
    );

    ?>

</span>


<?php endif; ?>


</td>


<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $transfer[
            "requested_at"
        ]
    )
);

?>

</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="11"
    style="
        text-align:center;
        padding:40px;
        color:#8d99a8;
    "
>

No transfer payment records found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>


</body>

</html>