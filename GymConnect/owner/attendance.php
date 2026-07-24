<?php

/* =========================================================
   GYMCONNECT - OWNER ATTENDANCE
   Today's Attendance + Attendance History
   Multiple Gyms Support
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

$owner_id = intval($_SESSION["user_id"]);


/* =========================================================
   TODAY'S DATE
========================================================= */

$today = date("Y-m-d");


/* =========================================================
   SELECTED GYM
========================================================= */

$selected_gym_id = isset($_GET["gym_id"])
    ? intval($_GET["gym_id"])
    : 0;


/* =========================================================
   HISTORY FILTERS
========================================================= */

$history_date = isset($_GET["history_date"])
    ? trim($_GET["history_date"])
    : "";

$search_member = isset($_GET["search_member"])
    ? trim($_GET["search_member"])
    : "";


/* =========================================================
   GET ALL GYMS BELONGING TO OWNER
========================================================= */

$gyms = [];

$gym_stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name,
        city,
        state
    FROM gyms
    WHERE owner_id = ?
    ORDER BY gym_name ASC
");


if ($gym_stmt) {

    $gym_stmt->bind_param(
        "i",
        $owner_id
    );

    $gym_stmt->execute();

    $gym_result =
        $gym_stmt->get_result();


    while (
        $gym_row =
        $gym_result->fetch_assoc()
    ) {

        $gyms[] = $gym_row;

    }


    $gym_stmt->close();

}


/* =========================================================
   IF NO GYM SELECTED
   AUTOMATICALLY SELECT FIRST GYM
========================================================= */

if (
    $selected_gym_id <= 0 &&
    count($gyms) > 0
) {

    $selected_gym_id =
        intval($gyms[0]["gym_id"]);

}


/* =========================================================
   VERIFY SELECTED GYM BELONGS TO OWNER
========================================================= */

$selected_gym = null;


if ($selected_gym_id > 0) {

    $selected_stmt = $conn->prepare("
        SELECT
            gym_id,
            gym_name,
            city,
            state
        FROM gyms
        WHERE gym_id = ?
        AND owner_id = ?
        LIMIT 1
    ");


    if ($selected_stmt) {

        $selected_stmt->bind_param(
            "ii",
            $selected_gym_id,
            $owner_id
        );

        $selected_stmt->execute();

        $selected_result =
            $selected_stmt->get_result();


        if (
            $selected_result->num_rows > 0
        ) {

            $selected_gym =
                $selected_result->fetch_assoc();

        }


        $selected_stmt->close();

    }

}


/* =========================================================
   GET ACTIVE MEMBERS OF SELECTED GYM
========================================================= */

$members = [];


if ($selected_gym !== null) {

    $member_stmt = $conn->prepare("
        SELECT

            u.user_id,

            u.full_name,

            u.email,

            u.phone,

            m.membership_id,

            m.start_date,

            m.end_date,

            m.status AS membership_status,

            a.attendance_id,

            a.check_in_time,

            a.check_out_time,

            a.attendance_method,

            a.status AS attendance_status

        FROM memberships m

        INNER JOIN users u
            ON m.member_id = u.user_id

        LEFT JOIN attendance a
            ON a.member_id = m.member_id

            AND a.gym_id = m.gym_id

            AND a.attendance_date = ?

        WHERE m.gym_id = ?

        AND m.status = 'Active'

        ORDER BY u.full_name ASC
    ");


    if ($member_stmt) {

        $member_stmt->bind_param(
            "si",
            $today,
            $selected_gym_id
        );

        $member_stmt->execute();

        $member_result =
            $member_stmt->get_result();


        while (
            $member_row =
            $member_result->fetch_assoc()
        ) {

            $members[] =
                $member_row;

        }


        $member_stmt->close();

    }

}


/* =========================================================
   COUNT TODAY'S PRESENT MEMBERS
========================================================= */

$present_count = 0;


if ($selected_gym !== null) {

    $present_stmt = $conn->prepare("
        SELECT COUNT(*) AS total

        FROM attendance

        WHERE gym_id = ?

        AND attendance_date = ?

        AND status = 'Present'
    ");


    if ($present_stmt) {

        $present_stmt->bind_param(
            "is",
            $selected_gym_id,
            $today
        );

        $present_stmt->execute();

        $present_result =
            $present_stmt->get_result();

        $present_data =
            $present_result->fetch_assoc();

        $present_count =
            intval(
                $present_data["total"]
            );

        $present_stmt->close();

    }

}


/* =========================================================
   COUNT TODAY'S ABSENT MEMBERS
========================================================= */

$absent_count = 0;


if ($selected_gym !== null) {

    $absent_stmt = $conn->prepare("
        SELECT COUNT(*) AS total

        FROM attendance

        WHERE gym_id = ?

        AND attendance_date = ?

        AND status = 'Absent'
    ");


    if ($absent_stmt) {

        $absent_stmt->bind_param(
            "is",
            $selected_gym_id,
            $today
        );

        $absent_stmt->execute();

        $absent_result =
            $absent_stmt->get_result();

        $absent_data =
            $absent_result->fetch_assoc();

        $absent_count =
            intval(
                $absent_data["total"]
            );

        $absent_stmt->close();

    }

}


/* =========================================================
   ATTENDANCE HISTORY
========================================================= */

$history = [];


if ($selected_gym !== null) {

    /*
       Build query dynamically so we can support:

       1. Selected gym
       2. Optional date
       3. Optional member search
    */

    $history_sql = "
        SELECT

            a.attendance_id,

            a.attendance_date,

            a.check_in_time,

            a.check_out_time,

            a.attendance_method,

            a.status,

            u.full_name,

            u.email,

            u.phone

        FROM attendance a

        INNER JOIN users u
            ON a.member_id = u.user_id

        WHERE a.gym_id = ?
    ";


    $history_types = "i";

    $history_params = [
        $selected_gym_id
    ];


    /* =====================================================
       DATE FILTER
    ===================================================== */

    if (
        $history_date !== ""
    ) {

        $history_sql .= "
            AND a.attendance_date = ?
        ";

        $history_types .= "s";

        $history_params[] =
            $history_date;

    }


    /* =====================================================
       MEMBER SEARCH
    ===================================================== */

    if (
        $search_member !== ""
    ) {

        $history_sql .= "
            AND u.full_name LIKE ?
        ";

        $history_types .= "s";

        $history_params[] =
            "%" .
            $search_member .
            "%";

    }


    $history_sql .= "
        ORDER BY
            a.attendance_date DESC,
            a.check_in_time DESC
    ";


    $history_stmt =
        $conn->prepare(
            $history_sql
        );


    if ($history_stmt) {

        /*
           mysqli bind_param requires
           variables passed by reference.

           This array creates references
           to the parameters dynamically.
        */

        $bind_values = [];

        $bind_values[] =
            $history_types;


        foreach (
            $history_params
            as $key => $value
        ) {

            $bind_values[] =
                &$history_params[$key];

        }


        call_user_func_array(
            [
                $history_stmt,
                "bind_param"
            ],
            $bind_values
        );


        $history_stmt->execute();


        $history_result =
            $history_stmt->get_result();


        while (
            $history_row =
            $history_result->fetch_assoc()
        ) {

            $history[] =
                $history_row;

        }


        $history_stmt->close();

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
    Attendance | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/attendance.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


</head>


<body>


<div class="attendance-container">


<!-- =====================================================
     PAGE HEADER
====================================================== -->

<div class="page-header">


<div>

<h1>
    📅 Attendance
</h1>


<p>
    Manage and track your gym members' attendance
</p>


</div>


<a
    href="dashboard.php"
    class="back-btn"
>

    ← Dashboard

</a>


</div>



<!-- =====================================================
     SUCCESS / ERROR MESSAGES
====================================================== -->

<?php if (
    isset($_GET["success"])
): ?>


<div class="alert success-alert">


<?php

if (
    $_GET["success"] === "checkin"
) {

    echo "✓ Attendance marked successfully.";

}
elseif (
    $_GET["success"] === "checkout"
) {

    echo "✓ Member checked out successfully.";

}

?>


</div>


<?php endif; ?>



<?php if (
    isset($_GET["error"])
): ?>


<div class="alert error-alert">


<?php

if (
    $_GET["error"] === "already_marked"
) {

    echo "⚠ Attendance has already been marked for this member today.";

}

?>


</div>


<?php endif; ?>



<!-- =====================================================
     GYM SELECTION
====================================================== -->

<div class="gym-selection">


<h2>
    Select Gym
</h2>


<form
    method="GET"
    action="attendance.php"
>


<select
    name="gym_id"
    onchange="this.form.submit()"
>


<option value="">
    -- Select Your Gym --
</option>


<?php foreach (
    $gyms as $gym
): ?>


<option

    value="<?php

        echo intval(
            $gym["gym_id"]
        );

    ?>"

    <?php

    if (
        $selected_gym_id ==
        $gym["gym_id"]
    ) {

        echo "selected";

    }

    ?>

>


<?php

echo htmlspecialchars(
    $gym["gym_name"]
);

?>


<?php

if (
    !empty(
        $gym["city"]
    )
) {

    echo " - " .
        htmlspecialchars(
            $gym["city"]
        );

}

?>


</option>


<?php endforeach; ?>


</select>


</form>


<?php if (
    count($gyms) === 0
): ?>


<div class="message warning">

    You have not registered any gym yet.

    <br><br>

    <a href="register_gym.php">

        Register Your Gym

    </a>

</div>


<?php endif; ?>


</div>



<?php if (
    $selected_gym !== null
): ?>


<!-- =====================================================
     SELECTED GYM INFORMATION
====================================================== -->

<div class="selected-gym">


<h2>

    🏢

    <?php

    echo htmlspecialchars(
        $selected_gym["gym_name"]
    );

    ?>

</h2>


<p>

    <?php

    echo htmlspecialchars(
        $selected_gym["city"]
    );

    ?>


    ,

    <?php

    echo htmlspecialchars(
        $selected_gym["state"]
    );

    ?>

</p>


<div class="date">

    📅 Today:

    <?php

    echo date(
        "d M Y"
    );

    ?>

</div>


</div>



<!-- =====================================================
     SUMMARY CARDS
====================================================== -->

<div class="summary-cards">


<div class="summary-card">

<h3>
    <?php
    echo $present_count;
    ?>
</h3>

<p>
    Present Today
</p>

</div>


<div class="summary-card">

<h3>
    <?php
    echo $absent_count;
    ?>
</h3>

<p>
    Absent Today
</p>

</div>


<div class="summary-card">

<h3>
    <?php
    echo count($members);
    ?>
</h3>

<p>
    Active Members
</p>

</div>


</div>



<!-- =====================================================
     TODAY'S ATTENDANCE
====================================================== -->

<div class="attendance-table">


<div class="table-header">


<h2>
    Today's Attendance
</h2>


</div>


<?php if (
    count($members) > 0
): ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>
    Member
</th>

<th>
    Contact
</th>

<th>
    Membership
</th>

<th>
    Today's Status
</th>

<th>
    Check In
</th>

<th>
    Check Out
</th>

<th>
    Action
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $members as $member
): ?>


<tr>


<td>

<strong>

<?php

echo htmlspecialchars(
    $member["full_name"]
);

?>

</strong>

<br>

<small>

<?php

echo htmlspecialchars(
    $member["email"]
);

?>

</small>

</td>



<td>

<?php

echo htmlspecialchars(
    $member["phone"]
);

?>

</td>



<td>

<span class="membership-active">

    Active

</span>

<br>

<small>

Until

<?php

echo htmlspecialchars(
    $member["end_date"]
);

?>

</small>

</td>



<td>


<?php

if (
    $member["attendance_id"]
) {


if (
    $member["attendance_status"]
    === "Present"
) {

?>

<span class="status present">

    Present

</span>

<?php

} else {

?>

<span class="status absent">

    Absent

</span>

<?php

}


} else {

?>

<span class="status not-marked">

    Not Marked

</span>

<?php

}

?>


</td>



<td>

<?php

if (
    !empty(
        $member["check_in_time"]
    )
) {

    echo date(
        "h:i A",
        strtotime(
            $member["check_in_time"]
        )
    );

} else {

    echo "--";

}

?>

</td>



<td>

<?php

if (
    !empty(
        $member["check_out_time"]
    )
) {

    echo date(
        "h:i A",
        strtotime(
            $member["check_out_time"]
        )
    );

} else {

    echo "--";

}

?>

</td>



<td>


<?php

if (
    $member["attendance_id"]
) {

?>


<?php

if (
    empty(
        $member["check_out_time"]
    )
) {

?>


<a
    href="attendance_process.php?action=checkout&attendance_id=<?php

        echo intval(
            $member["attendance_id"]
        );

    ?>&gym_id=<?php

        echo $selected_gym_id;

    ?>"
    class="action-btn checkout-btn"
>

    Check Out

</a>


<?php

} else {

?>


<span class="completed-text">

    Completed

</span>


<?php

}

?>


<?php

} else {

?>


<a
    href="attendance_process.php?action=checkin&member_id=<?php

        echo intval(
            $member["user_id"]
        );

    ?>&gym_id=<?php

        echo $selected_gym_id;

    ?>"
    class="action-btn checkin-btn"
>

    Mark Present

</a>


<?php

}

?>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php else: ?>


<div class="empty-state">


<h3>
    No Active Members
</h3>


<p>

There are currently no active members
in this gym.

</p>


</div>


<?php endif; ?>


</div>



<!-- =====================================================
     ATTENDANCE HISTORY
====================================================== -->

<div class="attendance-table history-section">


<div class="table-header">


<div>

<h2>
    📊 Attendance History
</h2>


<p class="history-description">

View previous attendance records
for this gym.

</p>

</div>


</div>



<!-- =====================================================
     HISTORY FILTER
====================================================== -->

<form
    method="GET"
    action="attendance.php"
    class="history-filter"
>


<input
    type="hidden"
    name="gym_id"
    value="<?php

        echo $selected_gym_id;

    ?>"
>


<div class="filter-group">


<label>
    Date
</label>


<input
    type="date"
    name="history_date"
    value="<?php

        echo htmlspecialchars(
            $history_date
        );

    ?>"
>


</div>



<div class="filter-group">


<label>
    Search Member
</label>


<input
    type="text"
    name="search_member"
    placeholder="Enter member name"
    value="<?php

        echo htmlspecialchars(
            $search_member
        );

    ?>"
>


</div>



<div class="filter-buttons">


<button
    type="submit"
    class="filter-btn"
>

    🔍 Search

</button>


<a
    href="attendance.php?gym_id=<?php

        echo $selected_gym_id;

    ?>"
    class="clear-btn"
>

    Clear

</a>


</div>


</form>



<!-- =====================================================
     HISTORY TABLE
====================================================== -->

<?php if (
    count($history) > 0
): ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>
    Date
</th>

<th>
    Member
</th>

<th>
    Contact
</th>

<th>
    Check In
</th>

<th>
    Check Out
</th>

<th>
    Method
</th>

<th>
    Status
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $history as $record
): ?>


<tr>


<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $record[
            "attendance_date"
        ]
    )
);

?>

</td>



<td>

<strong>

<?php

echo htmlspecialchars(
    $record["full_name"]
);

?>

</strong>

</td>



<td>

<?php

echo htmlspecialchars(
    $record["phone"]
);

?>

<br>

<small>

<?php

echo htmlspecialchars(
    $record["email"]
);

?>

</small>

</td>



<td>

<?php

if (
    !empty(
        $record["check_in_time"]
    )
) {

    echo date(
        "h:i A",
        strtotime(
            $record["check_in_time"]
        )
    );

} else {

    echo "--";

}

?>

</td>



<td>

<?php

if (
    !empty(
        $record["check_out_time"]
    )
) {

    echo date(
        "h:i A",
        strtotime(
            $record["check_out_time"]
        )
    );

} else {

    echo "--";

}

?>

</td>



<td>


<span class="method-badge">

<?php

echo htmlspecialchars(
    $record[
        "attendance_method"
    ]
);

?>

</span>


</td>



<td>


<?php

if (
    $record["status"]
    === "Present"
) {

?>


<span class="status present">

    Present

</span>


<?php

} else {

?>


<span class="status absent">

    Absent

</span>


<?php

}

?>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php else: ?>


<div class="empty-state history-empty">


<h3>
    No Attendance History Found
</h3>


<p>

No attendance records match your
selected filters.

</p>


</div>


<?php endif; ?>


</div>


<?php endif; ?>


</div>


</body>

</html>