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


/* =========================================================
   OWNER ID
========================================================= */

$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET FILTER VALUES
========================================================= */

$selected_gym = (int) (
    $_GET["gym_id"] ?? 0
);

$selected_status = trim(
    $_GET["status"] ?? ""
);

$search = trim(
    $_GET["search"] ?? ""
);


/* =========================================================
   GET OWNER'S GYMS
========================================================= */

$gyms = [];

$gym_stmt = $conn->prepare("
    SELECT
        gym_id,
        gym_name
    FROM gyms
    WHERE owner_id = ?
    ORDER BY gym_name ASC
");


if (!$gym_stmt) {
    die("Unable to load gyms.");
}


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


/* =========================================================
   BUILD MEMBERSHIP QUERY
========================================================= */

$sql = "
    SELECT

        m.membership_id,

        m.member_id,

        m.gym_id,

        m.plan_id,

        m.start_date,

        m.end_date,

        m.status,

        u.full_name AS member_name,

        u.email AS member_email,

        u.phone AS member_phone,

        g.gym_name,

        mp.plan_name,

        mp.duration_months,

        mp.price

    FROM memberships m

    INNER JOIN users u
        ON m.member_id = u.user_id

    INNER JOIN gyms g
        ON m.gym_id = g.gym_id

    INNER JOIN membership_plans mp
        ON m.plan_id = mp.plan_id

    WHERE g.owner_id = ?
";


$params = [
    $owner_id
];

$types = "i";


/* =========================================================
   FILTER BY GYM
========================================================= */

if ($selected_gym > 0) {

    $sql .= "
        AND m.gym_id = ?
    ";

    $params[] =
        $selected_gym;

    $types .= "i";

}


/* =========================================================
   FILTER BY STATUS
========================================================= */

if (
    $selected_status !== "" &&
    in_array(
        $selected_status,
        [
            "Active",
            "Expired",
            "Transferred"
        ],
        true
    )
) {

    $sql .= "
        AND m.status = ?
    ";

    $params[] =
        $selected_status;

    $types .= "s";

}


/* =========================================================
   SEARCH MEMBER
========================================================= */

if ($search !== "") {

    $sql .= "
        AND (
            u.full_name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $search_value =
        "%" . $search . "%";

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $types .= "ss";

}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY
        m.membership_id DESC
";


/* =========================================================
   PREPARE MEMBERSHIP QUERY
========================================================= */

$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Unable to load memberships."
    );

}


/* =========================================================
   BIND PARAMETERS
========================================================= */

$stmt->bind_param(
    $types,
    ...$params
);


/* =========================================================
   EXECUTE
========================================================= */

$stmt->execute();

$result =
    $stmt->get_result();


/* =========================================================
   MEMBERSHIP DATA
========================================================= */

$memberships = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $memberships[] =
        $row;

}


$stmt->close();


/* =========================================================
   AUTO UPDATE EXPIRED MEMBERSHIPS
========================================================= */

foreach (
    $memberships
    as &$membership
) {

    if (
        $membership["status"] === "Active" &&
        strtotime(
            $membership["end_date"]
        ) < strtotime(
            date("Y-m-d")
        )
    ) {

        $membership["status"] =
            "Expired";

    }

}

unset($membership);


/* =========================================================
   PAGE STATISTICS
========================================================= */

$total_memberships =
    count($memberships);

$active_memberships = 0;

$expired_memberships = 0;

$transferred_memberships = 0;


foreach (
    $memberships
    as $membership
) {

    if (
        $membership["status"] === "Active"
    ) {

        $active_memberships++;

    }

    elseif (
        $membership["status"] === "Expired"
    ) {

        $expired_memberships++;

    }

    elseif (
        $membership["status"] === "Transferred"
    ) {

        $transferred_memberships++;

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
    Memberships | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/memberships.css"
>

</head>


<body>


<div class="membership-page">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">


        <div>

            <h1>
                Memberships
            </h1>

            <p>
                Manage memberships across all your gyms
            </p>

        </div>


        <div class="header-actions">


            <a
                href="dashboard.php"
                class="back-btn"
            >

                ← Dashboard

            </a>


            <a
                href="members.php"
                class="members-btn"
            >

                Manage Members

            </a>


        </div>


    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats-grid">


        <div class="stat-card">

            <span>
                Total Memberships
            </span>

            <strong>
                <?php
                echo $total_memberships;
                ?>
            </strong>

        </div>


        <div class="stat-card active">

            <span>
                Active
            </span>

            <strong>
                <?php
                echo $active_memberships;
                ?>
            </strong>

        </div>


        <div class="stat-card expired">

            <span>
                Expired
            </span>

            <strong>
                <?php
                echo $expired_memberships;
                ?>
            </strong>

        </div>


        <div class="stat-card transferred">

            <span>
                Transferred
            </span>

            <strong>
                <?php
                echo $transferred_memberships;
                ?>
            </strong>

        </div>


    </div>



    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="filter-card">


        <form
            method="GET"
            action="memberships.php"
        >


            <!-- SEARCH -->

            <div class="filter-group">


                <label>
                    Search Member
                </label>


                <input
                    type="text"
                    name="search"
                    placeholder="Name or email"
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                >


            </div>



            <!-- GYM -->

            <div class="filter-group">


                <label>
                    Select Gym
                </label>


                <select
                    name="gym_id"
                >


                    <option value="0">

                        All Gyms

                    </option>


                    <?php foreach (
                        $gyms
                        as $gym
                    ): ?>


                        <option
                            value="<?php
                            echo $gym["gym_id"];
                            ?>"
                            <?php

                            if (
                                $selected_gym ==
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

                        </option>


                    <?php endforeach; ?>


                </select>


            </div>



            <!-- STATUS -->

            <div class="filter-group">


                <label>
                    Membership Status
                </label>


                <select
                    name="status"
                >


                    <option value="">

                        All Status

                    </option>


                    <option
                        value="Active"
                        <?php

                        if (
                            $selected_status ===
                            "Active"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Active

                    </option>


                    <option
                        value="Expired"
                        <?php

                        if (
                            $selected_status ===
                            "Expired"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Expired

                    </option>


                    <option
                        value="Transferred"
                        <?php

                        if (
                            $selected_status ===
                            "Transferred"
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Transferred

                    </option>


                </select>


            </div>



            <!-- ACTIONS -->

            <div class="filter-actions">


                <button
                    type="submit"
                    class="filter-btn"
                >

                    Filter

                </button>


                <a
                    href="memberships.php"
                    class="clear-btn"
                >

                    Clear

                </a>


            </div>


        </form>


    </div>



    <!-- =================================================
         MEMBERSHIP TABLE
    ================================================== -->

    <div class="membership-card">


        <div class="table-header">


            <h2>
                Membership Records
            </h2>


            <span>

                <?php
                echo $total_memberships;
                ?>

                Records

            </span>


        </div>



        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Member
                        </th>

                        <th>
                            Gym
                        </th>

                        <th>
                            Plan
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Start Date
                        </th>

                        <th>
                            End Date
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php if (
                    count($memberships) > 0
                ): ?>


                    <?php foreach (
                        $memberships
                        as $membership
                    ): ?>


                        <tr>


                            <!-- MEMBER -->

                            <td>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $membership[
                                            "member_name"
                                        ]
                                    );

                                    ?>

                                </strong>


                                <small>

                                    <?php

                                    echo htmlspecialchars(
                                        $membership[
                                            "member_email"
                                        ]
                                    );

                                    ?>

                                </small>


                            </td>



                            <!-- GYM -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $membership[
                                        "gym_name"
                                    ]
                                );

                                ?>

                            </td>



                            <!-- PLAN -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $membership[
                                        "plan_name"
                                    ]
                                );

                                ?>

                                <small>

                                    <?php

                                    echo $membership[
                                        "duration_months"
                                    ];

                                    ?>

                                    Month(s)

                                </small>

                            </td>



                            <!-- PRICE -->

                            <td>

                                ₹<?php

                                echo number_format(
                                    (float)
                                    $membership[
                                        "price"
                                    ],
                                    2
                                );

                                ?>

                            </td>



                            <!-- START DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $membership[
                                            "start_date"
                                        ]
                                    )
                                );

                                ?>

                            </td>



                            <!-- END DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $membership[
                                            "end_date"
                                        ]
                                    )
                                );

                                ?>

                            </td>



                            <!-- STATUS -->

                            <td>


                                <?php

                                $status =
                                    $membership[
                                        "status"
                                    ];

                                $status_class =
                                    strtolower(
                                        $status
                                    );

                                ?>


                                <span
                                    class="
                                    status-badge
                                    <?php
                                    echo
                                    $status_class;
                                    ?>
                                    "
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $status
                                    );

                                    ?>

                                </span>


                            </td>



                            <!-- ACTION -->

                            <td>


                                <a
                                    href="membership_details.php?id=<?php
                                    echo $membership[
                                        "membership_id"
                                    ];
                                    ?>"
                                    class="view-btn"
                                >

                                    View

                                </a>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>


                        <td
                            colspan="8"
                            class="no-records"
                        >

                            No memberships found.


                            <?php if (
                                $search !== "" ||
                                $selected_gym > 0 ||
                                $selected_status !== ""
                            ): ?>

                                <br><br>

                                Try changing your
                                filters.

                            <?php endif; ?>


                        </td>


                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


</body>

</html>