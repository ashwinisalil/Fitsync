
<?php

/* =========================================================
   GYM DETAILS PAGE
   GymConnect
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
   GET GYM ID
========================================================= */

if (
    !isset($_GET["gym_id"]) ||
    !is_numeric($_GET["gym_id"])
) {
    header("Location: find_gyms.php");
    exit();
}

$gym_id = intval($_GET["gym_id"]);


/* =========================================================
   GET GYM DETAILS
========================================================= */

$sql = "
    SELECT
        gym_id,
        owner_id,
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
        is_active
    FROM gyms
    WHERE gym_id = ?
    AND status = 'approved'
    AND is_active = 1
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to prepare gym query: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $gym_id
);

$stmt->execute();

$result = $stmt->get_result();


/* =========================================================
   CHECK GYM
========================================================= */

if ($result->num_rows === 0) {

    die("
        <div style='
            background:#111827;
            color:white;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family:Poppins,sans-serif;
            text-align:center;
            padding:30px;
        '>

            <div>

                <h1 style='color:#00c2ff;'>
                    Gym Not Found
                </h1>

                <p style='margin:15px 0;color:#94a3b8;'>
                    This gym is unavailable or has not been approved yet.
                </p>

                <a
                    href='find_gyms.php'
                    style='
                        display:inline-block;
                        padding:12px 25px;
                        background:#00c2ff;
                        color:#000;
                        text-decoration:none;
                        border-radius:8px;
                        font-weight:600;
                    '
                >
                    Back to Find Gyms
                </a>

            </div>

        </div>
    ");

}


$gym = $result->fetch_assoc();


/* =========================================================
   GET MEMBERSHIP PLANS
========================================================= */

$plans_sql = "
    SELECT
        plan_id,
        plan_name,
        duration_months,
        price,
        description
    FROM membership_plans
    WHERE gym_id = ?
    ORDER BY duration_months ASC
";

$plans_stmt = $conn->prepare($plans_sql);

if (!$plans_stmt) {
    die(
        "Unable to prepare membership plan query: " .
        $conn->error
    );
}

$plans_stmt->bind_param(
    "i",
    $gym_id
);

$plans_stmt->execute();

$plans_result =
    $plans_stmt->get_result();


/* =========================================================
   GYM IMAGE
========================================================= */

$gym_image = "";

if (
    !empty(
        $gym["profile_image"]
    )
) {

    $gym_image =
        "../" .
        $gym["profile_image"];

}


/* =========================================================
   GOOGLE MAPS DIRECTIONS
========================================================= */

$directions_url =

    "https://www.google.com/maps/dir/?api=1&destination=" .

    urlencode(

        $gym["latitude"] .
        "," .
        $gym["longitude"]

    );

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

        <?php

        echo htmlspecialchars(
            $gym["gym_name"]
        );

        ?>

        | GymConnect

    </title>


    <!-- =================================================
         GOOGLE FONT
    ================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =================================================
         LEAFLET CSS
    ================================================== -->

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

            font-family:
                'Poppins',
                sans-serif;

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

            max-width: 1400px;

            margin: auto;

        }


        /* =================================================
           BACK BUTTON
        ================================================= */

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


        /* =================================================
           GYM HERO
        ================================================= */

        .gym-hero {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            background: #1e293b;

            border-radius: 18px;

            overflow: hidden;

            border: 1px solid #334155;

            margin-bottom: 30px;

        }


        /* =================================================
           GYM IMAGE
        ================================================= */

        .gym-image {

            min-height: 400px;

            background: #0f172a;

        }


        .gym-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .no-image {

            width: 100%;

            height: 100%;

            min-height: 400px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 80px;

            color: #475569;

        }


        /* =================================================
           GYM MAIN INFO
        ================================================= */

        .gym-main-info {

            padding: 40px;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .gym-main-info h1 {

            color: #00c2ff;

            font-size: 36px;

            margin-bottom: 15px;

        }


        .description {

            color: #cbd5e1;

            font-size: 15px;

            line-height: 1.8;

            margin-bottom: 25px;

        }


        /* =================================================
           GYM INFO
        ================================================= */

        .info-list {

            display: flex;

            flex-direction: column;

            gap: 14px;

        }


        .info-item {

            display: flex;

            gap: 12px;

            color: #cbd5e1;

            font-size: 14px;

            line-height: 1.6;

        }


        .info-icon {

            min-width: 25px;

        }


        /* =================================================
           ACTIONS
        ================================================= */

        .hero-actions {

            display: flex;

            gap: 12px;

            margin-top: 25px;

            flex-wrap: wrap;

        }


        .action-btn {

            display: inline-block;

            padding: 12px 20px;

            border-radius: 9px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            transition: 0.3s;

        }


        .direction-btn {

            background: #22c55e;

            color: #ffffff;

        }


        .direction-btn:hover {

            background: #16a34a;

            transform:
                translateY(-2px);

        }


        .call-btn {

            background: #334155;

            color: #ffffff;

        }


        .call-btn:hover {

            background: #475569;

        }


        /* =================================================
           CONTENT GRID
        ================================================= */

        .content-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 25px;

        }


        /* =================================================
           SECTION
        ================================================= */

        .section {

            background: #1e293b;

            border: 1px solid #334155;

            border-radius: 15px;

            padding: 25px;

        }


        .section h2 {

            color: #00c2ff;

            font-size: 22px;

            margin-bottom: 20px;

        }


        /* =================================================
           MAP
        ================================================= */

        #map {

            width: 100%;

            height: 450px;

            border-radius: 10px;

        }


        /* =================================================
           PLANS
        ================================================= */

        .plans-section {

            grid-column:
                1 / -1;

        }


        .plans-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(
                        250px,
                        1fr
                    )
                );

            gap: 20px;

        }


        .plan-card {

            background: #0f172a;

            border: 1px solid #334155;

            border-radius: 15px;

            padding: 25px;

            transition: 0.3s;

        }


        .plan-card:hover {

            transform:
                translateY(-5px);

            border-color:
                #00c2ff;

            box-shadow:
                0 10px 25px
                rgba(
                    0,
                    194,
                    255,
                    0.12
                );

        }


        .plan-card h3 {

            color: #ffffff;

            font-size: 20px;

            margin-bottom: 10px;

        }


        .plan-price {

            color: #00c2ff;

            font-size: 30px;

            font-weight: 700;

            margin-bottom: 5px;

        }


        .plan-duration {

            color: #94a3b8;

            font-size: 13px;

            margin-bottom: 15px;

        }


        .plan-description {

            color: #cbd5e1;

            font-size: 13px;

            line-height: 1.6;

            min-height: 50px;

            margin-bottom: 20px;

        }


        .join-btn {

            display: block;

            width: 100%;

            padding: 12px;

            border: none;

            border-radius: 8px;

            background: #00c2ff;

            color: #000000;

            text-align: center;

            text-decoration: none;

            font-weight: 600;

            cursor: pointer;

            transition: 0.3s;

        }


        .join-btn:hover {

            background: #19ccff;

            transform:
                translateY(-2px);

        }


        /* =================================================
           NO PLANS
        ================================================= */

        .no-plans {

            background: #0f172a;

            border-radius: 10px;

            padding: 30px;

            text-align: center;

            color: #94a3b8;

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (
            max-width: 900px
        ) {

            .gym-hero {

                grid-template-columns:
                    1fr;

            }


            .gym-image {

                min-height: 300px;

            }


            .no-image {

                min-height: 300px;

            }


            .content-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (
            max-width: 600px
        ) {

            .page {

                padding: 20px;

            }


            .gym-main-info {

                padding: 25px;

            }


            .gym-main-info h1 {

                font-size: 28px;

            }


            #map {

                height: 350px;

            }

        }

    </style>

</head>


<body>


<div class="page">


    <!-- =================================================
         BACK
    ================================================== -->

    <a
        href="find_gyms.php"
        class="back-link"
    >

        ← Back to Find Gyms

    </a>


    <!-- =================================================
         GYM HERO
    ================================================== -->

    <div class="gym-hero">


        <!-- IMAGE -->

        <div class="gym-image">


            <?php if ($gym_image !== ""): ?>


                <img
                    src="<?php
                        echo htmlspecialchars(
                            $gym_image
                        );
                    ?>"
                    alt="<?php
                        echo htmlspecialchars(
                            $gym["gym_name"]
                        );
                    ?>"
                >


            <?php else: ?>


                <div class="no-image">

                    🏋️

                </div>


            <?php endif; ?>


        </div>


        <!-- MAIN INFORMATION -->

        <div class="gym-main-info">


            <h1>

                <?php

                echo htmlspecialchars(
                    $gym["gym_name"]
                );

                ?>

            </h1>


            <p class="description">

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $gym["description"] ??
                        "No description available."
                    )
                );

                ?>

            </p>


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


                <!-- OPENING HOURS -->

                <div class="info-item">

                    <span class="info-icon">
                        🕐
                    </span>

                    <span>

                        <?php

                        echo date(
                            "h:i A",
                            strtotime(
                                $gym["opening_time"]
                            )
                        );

                        ?>

                        -

                        <?php

                        echo date(
                            "h:i A",
                            strtotime(
                                $gym["closing_time"]
                            )
                        );

                        ?>

                    </span>

                </div>


                <!-- PHONE -->

                <?php if (!empty($gym["phone"])): ?>


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


                <?php endif; ?>


                <!-- EMAIL -->

                <?php if (!empty($gym["email"])): ?>


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


                <?php endif; ?>


            </div>


            <!-- ACTION BUTTONS -->

            <div class="hero-actions">


                <a
                    href="<?php
                        echo htmlspecialchars(
                            $directions_url
                        );
                    ?>"
                    target="_blank"
                    class="action-btn direction-btn"
                >

                    🚗 Get Directions

                </a>


                <?php if (!empty($gym["phone"])): ?>


                    <a
                        href="tel:<?php
                            echo htmlspecialchars(
                                $gym["phone"]
                            );
                        ?>"
                        class="action-btn call-btn"
                    >

                        📞 Call Gym

                    </a>


                <?php endif; ?>


            </div>


        </div>


    </div>


    <!-- =================================================
         CONTENT
    ================================================== -->

    <div class="content-grid">


        <!-- =================================================
             MAP
        ================================================== -->

        <div class="section">


            <h2>

                📍 Gym Location

            </h2>


            <div id="map"></div>


        </div>


        <!-- =================================================
             LOCATION INFORMATION
        ================================================== -->

        <div class="section">


            <h2>

                🗺️ Location Details

            </h2>


            <div class="info-list">


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
                            $gym["city"]
                        );

                        ?>,

                        <?php

                        echo htmlspecialchars(
                            $gym["state"]
                        );

                        ?>

                        <br>

                        <?php

                        echo htmlspecialchars(
                            $gym["country"]
                        );

                        ?>

                        -

                        <?php

                        echo htmlspecialchars(
                            $gym["pincode"]
                        );

                        ?>

                    </span>

                </div>


                <div class="info-item">

                    <span class="info-icon">
                        🌐
                    </span>

                    <span>

                        Latitude:

                        <?php

                        echo htmlspecialchars(
                            $gym["latitude"]
                        );

                        ?>

                        <br>

                        Longitude:

                        <?php

                        echo htmlspecialchars(
                            $gym["longitude"]
                        );

                        ?>

                    </span>

                </div>


                <div
                    class="info-item"
                    id="distanceInfo"
                >

                    <span class="info-icon">
                        📏
                    </span>

                    <span>

                        Calculating your distance
                        from this gym...

                    </span>

                </div>


            </div>


        </div>


        <!-- =================================================
             MEMBERSHIP PLANS
        ================================================== -->

        <div class="section plans-section">


            <h2>

                💳 Membership Plans

            </h2>


            <?php if (
                $plans_result->num_rows > 0
            ): ?>


                <div class="plans-grid">


                    <?php while (
                        $plan =
                        $plans_result->fetch_assoc()
                    ): ?>


                        <div class="plan-card">


                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $plan["plan_name"]
                                );

                                ?>

                            </h3>


                            <div class="plan-price">

                                ₹

                                <?php

                                echo number_format(
                                    $plan["price"],
                                    2
                                );

                                ?>

                            </div>


                            <div class="plan-duration">

                                <?php

                                echo htmlspecialchars(
                                    $plan["duration_months"]
                                );

                                ?>

                                Month(s)

                            </div>


                            <div class="plan-description">

                                <?php

                                echo !empty(
                                    $plan["description"]
                                )

                                ?

                                nl2br(
                                    htmlspecialchars(
                                        $plan["description"]
                                    )
                                )

                                :

                                "No plan description available.";

                                ?>

                            </div>


                            <!-- =========================================
                                 JOIN BUTTON
                            ========================================== -->

                            <a
                                href="payment.php?plan_id=<?php
                                    echo (int)
                                        $plan["plan_id"];
                                ?>&gym_id=<?php
                                    echo $gym_id;
                                ?>"
                                class="join-btn"
                            >

                                Join This Gym

                            </a>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <div class="no-plans">

                    <h3>

                        No Membership Plans Available

                    </h3>

                    <p>

                        This gym has not added any
                        membership plans yet.

                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


<!-- =====================================================
     LEAFLET JAVASCRIPT
====================================================== -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin=""
></script>


<script>

/* =========================================================
   GYM LOCATION
========================================================= */

const gymLatitude =

    <?php

    echo json_encode(
        (float)
        $gym["latitude"]
    );

    ?>;


const gymLongitude =

    <?php

    echo json_encode(
        (float)
        $gym["longitude"]
    );

    ?>;


/* =========================================================
   CREATE MAP
========================================================= */

const map =

    L.map(
        "map"
    ).setView(

        [

            gymLatitude,

            gymLongitude

        ],

        16

    );


/* =========================================================
   OPEN STREET MAP
========================================================= */

L.tileLayer(

    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",

    {

        maxZoom:
            19,

        attribution:
            "&copy; OpenStreetMap contributors"

    }

).addTo(map);


/* =========================================================
   GYM MARKER
========================================================= */

const gymMarker =

    L.marker(

        [

            gymLatitude,

            gymLongitude

        ]

    )

    .addTo(map);


gymMarker.bindPopup(

    `

        <strong>

            <?php

            echo htmlspecialchars(
                $gym["gym_name"],
                ENT_QUOTES
            );

            ?>

        </strong>

        <br>

        <?php

        echo htmlspecialchars(
            $gym["city"],
            ENT_QUOTES
        );

        ?>

    `

).openPopup();


/* =========================================================
   CALCULATE DISTANCE
========================================================= */

function calculateDistance(

    lat1,

    lon1,

    lat2,

    lon2

) {


    const earthRadius =
        6371;


    const dLat =

        (

            lat2 -
            lat1

        )

        *

        Math.PI /
        180;


    const dLon =

        (

            lon2 -
            lon1

        )

        *

        Math.PI /
        180;


    const a =

        Math.sin(
            dLat / 2
        )

        *

        Math.sin(
            dLat / 2
        )

        +

        Math.cos(

            lat1 *
            Math.PI /
            180

        )

        *

        Math.cos(

            lat2 *
            Math.PI /
            180

        )

        *

        Math.sin(
            dLon / 2
        )

        *

        Math.sin(
            dLon / 2
        );


    const c =

        2 *

        Math.atan2(

            Math.sqrt(a),

            Math.sqrt(
                1 - a
            )

        );


    return earthRadius * c;

}


/* =========================================================
   GET USER LOCATION
========================================================= */

if (
    navigator.geolocation
) {


    navigator.geolocation.getCurrentPosition(

        function(position) {


            const userLatitude =

                position.coords.latitude;


            const userLongitude =

                position.coords.longitude;


            /* =========================================
               CALCULATE DISTANCE
            ========================================== */

            const distance =

                calculateDistance(

                    userLatitude,

                    userLongitude,

                    gymLatitude,

                    gymLongitude

                );


            let distanceText;


            if (
                distance < 1
            ) {


                distanceText =

                    Math.round(

                        distance *
                        1000

                    ) +

                    " meters away from you";


            } else {


                distanceText =

                    distance.toFixed(
                        1
                    ) +

                    " km away from you";


            }


            document.getElementById(

                "distanceInfo"

            ).innerHTML =


                `

                    <span class="info-icon">

                        📏

                    </span>

                    <span>

                        This gym is approximately

                        <strong
                            style="color:#00c2ff;"
                        >

                            ${distanceText}

                        </strong>

                    </span>

                `;


            /* =========================================
               USER LOCATION MARKER
            ========================================== */

            const userMarker =

                L.marker(

                    [

                        userLatitude,

                        userLongitude

                    ]

                )

                .addTo(map);


            userMarker.bindPopup(

                "📍 Your Current Location"

            );


        },


        function(error) {


            document.getElementById(

                "distanceInfo"

            ).innerHTML =


                `

                    <span class="info-icon">

                        📏

                    </span>

                    <span>

                        Enable location access
                        to calculate your distance
                        from this gym.

                    </span>

                `;


        },


        {

            enableHighAccuracy:
                true,

            timeout:
                10000,

            maximumAge:
                0

        }

    );


}

else {


    document.getElementById(

        "distanceInfo"

    ).innerHTML =


        `

            <span class="info-icon">

                📏

            </span>

            <span>

                Your browser does not support
                location services.

            </span>

        `;


}

</script>


</body>

</html>

<?php

$conn->close();

?>