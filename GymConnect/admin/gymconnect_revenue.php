<?php

session_start();

require_once "../database/connection.php";


/* =========================================================
   ADMIN LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {

    header(
        "Location: ../login.php"
    );

    exit();

}


/* =========================================================
   TOTAL REVENUE
========================================================= */

$total_stmt = $conn->prepare("
    SELECT

        COALESCE(
            SUM(amount),
            0
        ) AS total_revenue

    FROM gymconnect_revenue

    WHERE payment_status = 'Completed'
");

$total_stmt->execute();

$total_result =
    $total_stmt->get_result();

$total_row =
    $total_result->fetch_assoc();

$total_revenue =
    (float)
    $total_row['total_revenue'];

$total_stmt->close();


/* =========================================================
   TOTAL TRANSFERS
========================================================= */

$count_stmt = $conn->prepare("
    SELECT

        COUNT(*) AS total_transfers

    FROM gymconnect_revenue

    WHERE payment_status = 'Completed'
");

$count_stmt->execute();

$count_result =
    $count_stmt->get_result();

$count_row =
    $count_result->fetch_assoc();

$total_transfers =
    (int)
    $count_row['total_transfers'];

$count_stmt->close();


/* =========================================================
   REVENUE LIST
========================================================= */

$list_stmt = $conn->prepare("
    SELECT

        gr.*,

        u.full_name AS member_name,

        fg.gym_name AS from_gym_name,

        tg.gym_name AS to_gym_name

    FROM gymconnect_revenue gr

    LEFT JOIN users u
        ON gr.member_id = u.user_id

    LEFT JOIN gyms fg
        ON gr.from_gym_id = fg.gym_id

    LEFT JOIN gyms tg
        ON gr.to_gym_id = tg.gym_id

    ORDER BY
        gr.revenue_id DESC
");

$list_stmt->execute();

$revenue_list =
    $list_stmt->get_result();

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
GymConnect Revenue
</title>

<style>

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

    font-family: Arial, sans-serif;

}

body {

    background: #0b0f14;

    color: #ffffff;

    padding: 40px;

}

.container {

    max-width: 1200px;

    margin: auto;

}

.back {

    display: inline-block;

    color: white;

    text-decoration: none;

    background: #171d25;

    padding: 10px 18px;

    border-radius: 7px;

    margin-bottom: 25px;

}

h1 {

    margin-bottom: 30px;

}

.stats {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;

    margin-bottom: 30px;

}

.stat-card {

    background: #11161d;

    border: 1px solid #252d38;

    border-radius: 12px;

    padding: 25px;

}

.stat-card span {

    color: #91a0b3;

}

.stat-card strong {

    display: block;

    font-size: 30px;

    margin-top: 10px;

    color: #20d47a;

}

.table-container {

    background: #11161d;

    border: 1px solid #252d38;

    border-radius: 12px;

    overflow-x: auto;

}

table {

    width: 100%;

    border-collapse: collapse;

}

th,

td {

    padding: 15px;

    border-bottom:
        1px solid #252d38;

    text-align: left;

}

th {

    color: #91a0b3;

}

.status {

    color: #20d47a;

    font-weight: bold;

}

@media(max-width:700px) {

    body {

        padding: 20px;

    }

    .stats {

        grid-template-columns: 1fr;

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

GymConnect Revenue

</h1>


<div class="stats">


<div class="stat-card">

<span>
Total GymConnect Revenue
</span>

<strong>

₹<?php

echo number_format(
    $total_revenue,
    2
);

?>

</strong>

</div>


<div class="stat-card">

<span>
Completed Transfer Fees
</span>

<strong>

<?php

echo $total_transfers;

?>

</strong>

</div>


</div>


<div class="table-container">


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
To Gym
</th>

<th>
GymConnect Fee
</th>

<th>
Transaction ID
</th>

<th>
Status
</th>

<th>
Date
</th>

</tr>

</thead>


<tbody>


<?php if (
    $revenue_list->num_rows > 0
): ?>


<?php while (
    $row =
    $revenue_list->fetch_assoc()
): ?>


<tr>


<td>

#<?php

echo (int)
    $row['transfer_id'];

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['member_name']
    ?? 'Unknown'
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['from_gym_name']
    ?? 'Unknown'
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['to_gym_name']
    ?? 'Unknown'
);

?>

</td>


<td>

₹<?php

echo number_format(
    (float)
    $row['amount'],
    2
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['transaction_id']
    ?? '-'
);

?>

</td>


<td class="status">

<?php

echo htmlspecialchars(
    $row['payment_status']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row['payment_date']
);

?>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
    colspan="8"
    style="text-align:center;"
>

No GymConnect revenue found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>

</body>

</html>