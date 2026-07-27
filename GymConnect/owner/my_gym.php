
<?php

/* =========================================================
   OWNER MY GYMS
   GymConnect
   Shows all gyms belonging to the logged-in owner
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

$owner_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET OWNER'S ALL GYMS
========================================================= */

$sql = "
    SELECT
        gym_id,
        gym_name,
        description,
        address,
        city,
        state,
        country,
        pincode,
        phone,
        email,
        opening_time,
        closing_time,
        latitude,
        longitude,
        profile_image,
        status,
        is_active,
        created_at
    FROM gyms
    WHERE owner_id = ?
    ORDER BY created_at DESC
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Database query failed: " .
        $conn->error
    );

}


$stmt->bind_param(
    "i",
    $owner_id
);


$stmt->execute();


$result = $stmt->get_result();


/* =========================================================
   TOTAL GYMS
========================================================= */

$total_gyms = $result->num_rows;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Gyms | GymConnect</title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- Leaflet CSS -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""
    >


    <style>

        /* =================================================
           GLOBAL
        ================================================= */

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

            font-family: 'Poppins', sans-serif;

        }


        body {

            background: #111827;

            color: #ffffff;

        }


        /* =================================================
           PAGE
        ================================================= */

        .page {

            min-height: 100vh;

            padding: 30px;

        }


        /* =================================================
           TOP BAR
        ================================================= */

        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 30px;

        }


        .topbar-left h1 {

            font-size: 32px;

            color: #ffffff;

            margin-bottom: 5px;

        }


        .topbar-left p {

            color: #94a3b8;

            font-size: 15px;

        }


        .register-btn {

            display: inline-block;

            padding: 13px 22px;

            background: #00c2ff;

            color: #000000;

            text-decoration: none;

            border-radius: 10px;

            font-size: 14px;

            font-weight: 600;

            transition: all 0.3s ease;

        }


        .register-btn:hover {

            background: #19ccff;

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(0, 194, 255, 0.25);

        }


        /* =================================================
           SUMMARY
        ================================================= */

        .summary {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 30px;

        }


        .summary-card {

            background: #1e293b;

            border-left: 4px solid #00c2ff;

            padding: 18px 25px;

            border-radius: 12px;

            min-width: 180px;

        }


        .summary-card h2 {

            color: #00c2ff;

            font-size: 28px;

        }


        .summary-card p {

            color: #cbd5e1;

            font-size: 14px;

            margin-top: 3px;

        }


        /* =================================================
           GYMS GRID
        ================================================= */

        .gyms-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(400px, 1fr)
                );

            gap: 30px;

        }


        /* =================================================
           GYM CARD
        ================================================= */

        .gym-card {

            background: #1e293b;

            border-radius: 18px;

            overflow: hidden;

            border: 1px solid #334155;

            transition: all 0.3s ease;

        }


        .gym-card:hover {

            transform: translateY(-5px);

            box-shadow:
                0 15px 35px
                rgba(0, 194, 255, 0.12);

        }


        /* =================================================
           GYM IMAGE
        ================================================= */

        .gym-image {

            width: 100%;

            height: 230px;

            background: #0f172a;

            overflow: hidden;

            position: relative;

        }


        .gym-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .no-image {

            width: 100%;

            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #64748b;

            font-size: 18px;

        }


        /* =================================================
           STATUS BADGE
        ================================================= */

        .status-badge {

            position: absolute;

            top: 15px;

            right: 15px;

            padding: 7px 14px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

            text-transform: capitalize;

        }


        .status-approved {

            background: #22c55e;

            color: #ffffff;

        }


        .status-pending {

            background: #f59e0b;

            color: #000000;

        }


        .status-rejected {

            background: #ef4444;

            color: #ffffff;

        }


        /* =================================================
           GYM CONTENT
        ================================================= */

        .gym-content {

            padding: 25px;

        }


        .gym-content h2 {

            color: #ffffff;

            font-size: 24px;

            margin-bottom: 10px;

        }


        .description {

            color: #94a3b8;

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 20px;

        }


        /* =================================================
           INFORMATION
        ================================================= */

        .info-list {

            display: flex;

            flex-direction: column;

            gap: 12px;

            margin-bottom: 20px;

        }


        .info-item {

            display: flex;

            gap: 10px;

            color: #cbd5e1;

            font-size: 14px;

            line-height: 1.5;

        }


        .info-icon {

            width: 25px;

            flex-shrink: 0;

        }


        /* =================================================
           MAP
        ================================================= */

        .map-container {

            width: 100%;

            height: 250px;

            border-radius: 12px;

            overflow: hidden;

            margin-top: 20px;

            border: 1px solid #334155;

        }


        .no-location {

            width: 100%;

            height: 250px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #0f172a;

            color: #64748b;

            text-align: center;

            padding: 20px;

            border-radius: 12px;

            margin-top: 20px;

        }


        /* =================================================
           ACTION BUTTONS
        ================================================= */

        .actions {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

            margin-top: 20px;

        }


        .action-btn {

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 12px;

            border-radius: 9px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition: all 0.3s ease;

        }


        .map-btn {

            background: #00c2ff;

            color: #000000;

        }


        .direction-btn {

            background: #334155;

            color: #ffffff;

        }


        .action-btn:hover {

            transform: translateY(-2px);

        }


        .map-btn:hover {

            background: #19ccff;

        }


        .direction-btn:hover {

            background: #475569;

        }


        /* =================================================
           NO GYMS
        ================================================= */

        .no-gyms {

            background: #1e293b;

            border: 1px solid #334155;

            border-radius: 18px;

            padding: 60px 30px;

            text-align: center;

        }


        .no-gyms-icon {

            font-size: 50px;

            margin-bottom: 15px;

        }


        .no-gyms h2 {

            color: #ffffff;

            margin-bottom: 10px;

        }


        .no-gyms p {

            color: #94a3b8;

            margin-bottom: 25px;

        }


        /* =================================================
           BACK BUTTON
        ================================================= */

        .back-btn {

            display: inline-block;

            margin-bottom: 25px;

            color: #00c2ff;

            text-decoration: none;

            font-size: 14px;

        }


        .back-btn:hover {

            text-decoration: underline;

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 900px) {

            .gyms-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 600px) {

            .page {

                padding: 20px;

            }


            .topbar {

                flex-direction: column;

                align-items: flex-start;

            }


            .register-btn {

                width: 100%;

                text-align: center;

            }


            .gym-content {

                padding: 20px;

            }


            .actions {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


<div class="page">


    <!-- =================================================
         BACK TO DASHBOARD
    ================================================== -->

    <a
        href="dashboard.php"
        class="back-btn"
    >

        ← Back to Dashboard

    </a>


    <!-- =================================================
         TOP BAR
    ================================================== -->

    <div class="topbar">


        <div class="topbar-left">

            <h1>
                My Gyms
            </h1>

            <p>
                Manage all your registered gyms from one place.
            </p>

        </div>


        <a
            href="register_gym.php"
            class="register-btn"
        >

            + Register Another Gym

        </a>


    </div>


    <!-- =================================================
         SUMMARY
    ================================================== -->

    <div class="summary">


        <div class="summary-card">

            <h2>
                <?php echo $total_gyms; ?>
            </h2>

            <p>
                Total Registered Gyms
            </p>

        </div>


    </div>


    <?php if ($total_gyms > 0): ?>


        <!-- =================================================
             ALL GYMS
        ================================================== -->

        <div class="gyms-grid">


            <?php while ($gym = $result->fetch_assoc()): ?>


                <?php

                /* =========================================
                   STATUS
                ========================================== */

                $status =
                    strtolower(
                        $gym["status"] ?? "pending"
                    );


                /* =========================================
                   IMAGE
                ========================================== */

                $image =
                    !empty(
                        $gym["profile_image"]
                    )
                    ? "../" .
                      $gym["profile_image"]
                    : "";


                /* =========================================
                   LOCATION
                ========================================== */

                $has_location =
                    isset(
                        $gym["latitude"],
                        $gym["longitude"]
                    ) &&
                    $gym["latitude"] !== null &&
                    $gym["longitude"] !== null &&
                    $gym["latitude"] !== "" &&
                    $gym["longitude"] !== "";


                $latitude =
                    $has_location
                    ? (float) $gym["latitude"]
                    : 0;


                $longitude =
                    $has_location
                    ? (float) $gym["longitude"]
                    : 0;


                /* =========================================
                   GOOGLE MAPS URL
                ========================================== */

                $directions_url =
                    "https://www.google.com/maps/dir/?api=1&destination=" .
                    urlencode(
                        $latitude . "," . $longitude
                    );

                ?>


                <!-- =================================================
                     GYM CARD
                ================================================== -->

                <div class="gym-card">


                    <!-- =============================================
                         IMAGE
                    ============================================== -->

                    <div class="gym-image">


                        <?php if ($image !== ""): ?>


                            <img
                                src="<?php echo htmlspecialchars($image); ?>"
                                alt="<?php echo htmlspecialchars($gym["gym_name"]); ?>"
                            >


                        <?php else: ?>


                            <div class="no-image">

                                🏋️

                                &nbsp;

                                No Gym Image

                            </div>


                        <?php endif; ?>


                        <!-- STATUS -->

                        <div
                            class="
                                status-badge
                                status-<?php
                                    echo htmlspecialchars(
                                        $status
                                    );
                                ?>
                            "
                        >

                            <?php
                                echo htmlspecialchars(
                                    ucfirst($status)
                                );
                            ?>

                        </div>


                    </div>


                    <!-- =============================================
                         CONTENT
                    ============================================== -->

                    <div class="gym-content">


                        <h2>

                            <?php

                            echo htmlspecialchars(
                                $gym["gym_name"]
                            );

                            ?>

                        </h2>


                        <!-- DESCRIPTION -->

                        <p class="description">

                            <?php

                            echo htmlspecialchars(
                                $gym["description"] ??
                                "No description available."
                            );

                            ?>

                        </p>


                        <!-- =========================================
                             INFORMATION
                        ========================================== -->

                        <div class="info-list">


                            <!-- ADDRESS -->

                            <div class="info-item">

                                <span class="info-icon">
                                    📍
                                </span>

                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $gym["address"]
                                    );

                                    ?>

                                    <br>

                                    <?php

                                    echo htmlspecialchars(
                                        $gym["city"] .
                                        ", " .
                                        $gym["state"] .
                                        " - " .
                                        $gym["pincode"]
                                    );

                                    ?>

                                </span>

                            </div>


                            <!-- PHONE -->

                            <div class="info-item">

                                <span class="info-icon">
                                    📞
                                </span>

                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $gym["phone"]
                                    );

                                    ?>

                                </span>

                            </div>


                            <!-- EMAIL -->

                            <div class="info-item">

                                <span class="info-icon">
                                    ✉️
                                </span>

                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $gym["email"]
                                    );

                                    ?>

                                </span>

                            </div>


                            <!-- OPENING TIME -->

                            <div class="info-item">

                                <span class="info-icon">
                                    🕐
                                </span>

                                <span>

                                    <?php

                                    echo
                                        date(
                                            "h:i A",
                                            strtotime(
                                                $gym["opening_time"]
                                            )
                                        );

                                    ?>

                                    -

                                    <?php

                                    echo
                                        date(
                                            "h:i A",
                                            strtotime(
                                                $gym["closing_time"]
                                            )
                                        );

                                    ?>

                                </span>

                            </div>


                        </div>


                        <!-- =========================================
                             MAP
                        ========================================== -->


                        <?php if ($has_location): ?>


                            <div
                                class="map-container"
                                id="map-<?php
                                    echo (int)
                                        $gym["gym_id"];
                                ?>"
                            ></div>


                        <?php else: ?>


                            <div class="no-location">

                                📍

                                <br><br>

                                Location has not been
                                added for this gym yet.

                            </div>


                        <?php endif; ?>


                        <!-- =========================================
                             ACTION BUTTONS
                        ========================================== -->


                        <?php if ($has_location): ?>


                            <div class="actions">


                                <!-- VIEW MAP -->

                                <a
                                    href="https://www.openstreetmap.org/?mlat=<?php echo $latitude; ?>&mlon=<?php echo $longitude; ?>#map=18/<?php echo $latitude; ?>/<?php echo $longitude; ?>"
                                    target="_blank"
                                    class="action-btn map-btn"
                                >

                                    🗺️ View Full Map

                                </a>


                                <!-- DIRECTIONS -->

                                <a
                                    href="<?php echo htmlspecialchars($directions_url); ?>"
                                    target="_blank"
                                    class="action-btn direction-btn"
                                >

                                    🚗 Get Directions

                                </a>


                            </div>


                        <?php endif; ?>


                    </div>


                </div>


                <!-- =================================================
                     MAP SCRIPT DATA
                ================================================== -->


                <?php if ($has_location): ?>


                    <script>

                        document.addEventListener(

                            "DOMContentLoaded",

                            function() {


                                const map =
                                    L.map(
                                        "map-<?php
                                            echo (int)
                                                $gym["gym_id"];
                                        ?>"
                                    ).setView(

                                        [
                                            <?php
                                                echo $latitude;
                                            ?>,

                                            <?php
                                                echo $longitude;
                                            ?>
                                        ],

                                        16

                                    );


                                L.tileLayer(

                                    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",

                                    {

                                        maxZoom: 19,

                                        attribution:
                                            "&copy; OpenStreetMap contributors"

                                    }

                                ).addTo(map);


                                L.marker(

                                    [
                                        <?php
                                            echo $latitude;
                                        ?>,

                                        <?php
                                            echo $longitude;
                                        ?>
                                    ]

                                )

                                .addTo(map)

                                .bindPopup(

                                    "<?php

                                    echo htmlspecialchars(
                                        $gym["gym_name"],
                                        ENT_QUOTES
                                    );

                                    ?>"

                                )

                                .openPopup();


                            }

                        );

                    </script>


                <?php endif; ?>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <!-- =================================================
             NO GYMS
        ================================================== -->

        <div class="no-gyms">


            <div class="no-gyms-icon">
                🏋️
            </div>


            <h2>
                You haven't registered any gyms yet.
            </h2>


            <p>

                Register your first gym and start managing
                your members, memberships and attendance
                through GymConnect.

            </p>


            <a
                href="register_gym.php"
                class="register-btn"
            >

                + Register Your First Gym

            </a>


        </div>


    <?php endif; ?>


</div>


<!-- =====================================================
     LEAFLET JAVASCRIPT
====================================================== -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin=""
></script>


</body>

</html>


<?php

/* =========================================================
   CLOSE DATABASE
========================================================= */

$stmt->close();

$conn->close();

?>