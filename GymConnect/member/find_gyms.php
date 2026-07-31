
<?php

/* =========================================================
   FIND NEARBY GYMS
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
   GET ALL APPROVED GYMS WITH LOCATION
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
        status
    FROM gyms
    WHERE status = 'approved'
    AND is_active = 1
    AND latitude IS NOT NULL
    AND longitude IS NOT NULL
    ORDER BY gym_name ASC
";


$result = $conn->query($sql);


if (!$result) {

    die(
        "Unable to load gyms: " .
        $conn->error
    );

}


/* =========================================================
   STORE GYMS IN ARRAY
========================================================= */

$gyms = [];


while ($gym = $result->fetch_assoc()) {

    $gyms[] = $gym;

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

    <title>Find Gyms Near You | GymConnect</title>


    <!-- =========================================
         GOOGLE FONT
    ========================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =========================================
         LEAFLET CSS
    ========================================== -->

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

            margin-bottom: 25px;

        }


        .topbar h1 {

            font-size: 30px;

            margin-bottom: 5px;

        }


        .topbar p {

            color: #94a3b8;

            font-size: 14px;

        }


        .back-btn {

            color: #00c2ff;

            text-decoration: none;

            font-size: 14px;

        }


        .back-btn:hover {

            text-decoration: underline;

        }


        /* =================================================
           LOCATION CONTROL
        ================================================= */

        .location-control {

            background: #1e293b;

            border: 1px solid #334155;

            border-radius: 15px;

            padding: 20px;

            margin-bottom: 25px;

        }


        .location-control h2 {

            color: #00c2ff;

            font-size: 19px;

            margin-bottom: 8px;

        }


        .location-control p {

            color: #94a3b8;

            font-size: 14px;

            margin-bottom: 15px;

        }


        .location-actions {

            display: flex;

            gap: 12px;

            flex-wrap: wrap;

        }


        .location-btn {

            padding: 12px 20px;

            border: none;

            border-radius: 9px;

            background: #00c2ff;

            color: #000000;

            font-weight: 600;

            cursor: pointer;

            transition: 0.3s;

        }


        .location-btn:hover {

            background: #19ccff;

            transform: translateY(-2px);

        }


        .location-btn:disabled {

            opacity: 0.6;

            cursor: not-allowed;

            transform: none;

        }


        .show-all-btn {

            padding: 12px 20px;

            border-radius: 9px;

            background: #334155;

            color: #ffffff;

            border: none;

            font-weight: 600;

            cursor: pointer;

        }


        .show-all-btn:hover {

            background: #475569;

        }


        #locationStatus {

            margin-top: 12px;

            color: #94a3b8;

            font-size: 14px;

        }


        /* =================================================
           MAIN LAYOUT
        ================================================= */

        .content-layout {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 25px;

        }


        /* =================================================
           MAP
        ================================================= */

        .map-section {

            background: #1e293b;

            padding: 15px;

            border-radius: 15px;

            border: 1px solid #334155;

            height: 650px;

            position: sticky;

            top: 20px;

        }


        #map {

            width: 100%;

            height: 100%;

            border-radius: 10px;

        }


        /* =================================================
           GYM LIST
        ================================================= */

        .gym-list-section {

            min-width: 0;

        }


        .list-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 18px;

        }


        .list-header h2 {

            font-size: 22px;

        }


        .gym-count {

            color: #00c2ff;

            font-size: 14px;

        }


        /* =================================================
           GYM CARD
        ================================================= */

        .gym-card {

            background: #1e293b;

            border: 1px solid #334155;

            border-radius: 15px;

            overflow: hidden;

            margin-bottom: 20px;

            transition: 0.3s;

        }


        .gym-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 12px 30px
                rgba(0, 194, 255, 0.12);

        }


        .gym-card.highlighted {

            border-color: #00c2ff;

            box-shadow:
                0 0 0 2px
                rgba(0, 194, 255, 0.2);

        }


        /* =================================================
           IMAGE
        ================================================= */

        .gym-image {

            width: 100%;

            height: 180px;

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

            display: flex;

            align-items: center;

            justify-content: center;

            color: #64748b;

            font-size: 40px;

        }


        /* =================================================
           GYM CONTENT
        ================================================= */

        .gym-content {

            padding: 20px;

        }


        .gym-title-row {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 10px;

        }


        .gym-title-row h3 {

            font-size: 21px;

            color: #ffffff;

        }


        .distance {

            white-space: nowrap;

            color: #00c2ff;

            font-size: 13px;

            font-weight: 600;

            background: #0f172a;

            padding: 6px 10px;

            border-radius: 15px;

        }


        .description {

            color: #94a3b8;

            font-size: 13px;

            line-height: 1.6;

            margin-bottom: 15px;

        }


        /* =================================================
           INFO
        ================================================= */

        .gym-info {

            display: flex;

            flex-direction: column;

            gap: 9px;

            margin-bottom: 18px;

        }


        .info-row {

            display: flex;

            gap: 9px;

            color: #cbd5e1;

            font-size: 13px;

            line-height: 1.5;

        }


        /* =================================================
           BUTTONS
        ================================================= */

        .gym-actions {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 8px;

        }


        .gym-btn {

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 10px 5px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            text-align: center;

            transition: 0.3s;

        }


        .view-btn {

            background: #334155;

            color: #ffffff;

        }


        .join-btn {

            background: #00c2ff;

            color: #000000;

        }


        .direction-btn {

            background: #22c55e;

            color: #ffffff;

        }


        .gym-btn:hover {

            transform: translateY(-2px);

        }


        .view-btn:hover {

            background: #475569;

        }


        .join-btn:hover {

            background: #19ccff;

        }


        .direction-btn:hover {

            background: #16a34a;

        }


        /* =================================================
           NO GYMS
        ================================================= */

        .no-gyms {

            background: #1e293b;

            border: 1px solid #334155;

            border-radius: 15px;

            padding: 50px 25px;

            text-align: center;

        }


        .no-gyms-icon {

            font-size: 50px;

            margin-bottom: 15px;

        }


        .no-gyms h2 {

            margin-bottom: 10px;

        }


        .no-gyms p {

            color: #94a3b8;

            font-size: 14px;

            line-height: 1.6;

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1000px) {

            .content-layout {

                grid-template-columns: 1fr;

            }


            .map-section {

                position: relative;

                top: 0;

                height: 450px;

                order: 1;

            }


            .gym-list-section {

                order: 2;

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


            .map-section {

                height: 350px;

            }


            .gym-title-row {

                flex-direction: column;

            }


            .gym-actions {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


<div class="page">


    <!-- =================================================
         TOP BAR
    ================================================== -->

    <a
        href="dashboard.php"
        class="back-btn"
    >

        ← Back to Dashboard

    </a>


    <div class="topbar">

        <div>

            <h1>
                Find Gyms Near You
            </h1>

            <p>

                Discover gyms around your location
                and find the perfect place to train.

            </p>

        </div>

    </div>


    <!-- =================================================
         LOCATION CONTROL
    ================================================== -->

    <div class="location-control">


        <h2>
            📍 Find Gyms Near Your Location
        </h2>


        <p>

            Allow location access to see gyms near you
            sorted by distance.

        </p>


        <div class="location-actions">


            <button
                type="button"
                class="location-btn"
                id="useLocationBtn"
            >

                📍 Use My Current Location

            </button>


            <button
                type="button"
                class="show-all-btn"
                id="showAllBtn"
            >

                🗺️ Show All Gyms

            </button>


        </div>


        <div id="locationStatus">

            Showing all available gyms.
            Click "Use My Current Location" to find nearby gyms.

        </div>


    </div>


    <!-- =================================================
         CONTENT
    ================================================== -->

    <div class="content-layout">


        <!-- =================================================
             MAP
        ================================================== -->

        <div class="map-section">

            <div id="map"></div>

        </div>


        <!-- =================================================
             GYM LIST
        ================================================== -->

        <div class="gym-list-section">


            <div class="list-header">


                <h2>
                    Available Gyms
                </h2>


                <span
                    class="gym-count"
                    id="gymCount"
                >

                    <?php
                        echo count($gyms);
                    ?>
                    Gyms

                </span>


            </div>


            <div id="gymList">


                <?php if (count($gyms) > 0): ?>


                    <?php foreach ($gyms as $gym): ?>


                        <?php

                        $gym_id =
                            (int) $gym["gym_id"];


                        $latitude =
                            (float) $gym["latitude"];


                        $longitude =
                            (float) $gym["longitude"];


                        $image =
                            !empty(
                                $gym["profile_image"]
                            )
                            ? "../" .
                              $gym["profile_image"]
                            : "";


                        $directions_url =
                            "https://www.google.com/maps/dir/?api=1&destination=" .
                            urlencode(
                                $latitude .
                                "," .
                                $longitude
                            );

                        ?>


                        <!-- =================================================
                             GYM CARD
                        ================================================== -->

                        <div
                            class="gym-card"
                            id="gym-card-<?php
                                echo $gym_id;
                            ?>"
                            data-gym-id="<?php
                                echo $gym_id;
                            ?>"
                            data-latitude="<?php
                                echo $latitude;
                            ?>"
                            data-longitude="<?php
                                echo $longitude;
                            ?>"
                        >


                            <!-- IMAGE -->

                            <div class="gym-image">


                                <?php if ($image !== ""): ?>


                                    <img
                                        src="<?php
                                            echo htmlspecialchars(
                                                $image
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


                            <!-- CONTENT -->

                            <div class="gym-content">


                                <div class="gym-title-row">


                                    <h3>

                                        <?php

                                        echo htmlspecialchars(
                                            $gym["gym_name"]
                                        );

                                        ?>

                                    </h3>


                                    <span
                                        class="distance"
                                        id="distance-<?php
                                            echo $gym_id;
                                        ?>"
                                    >

                                        Distance unavailable

                                    </span>


                                </div>


                                <!-- DESCRIPTION -->

                                <p class="description">

                                    <?php

                                    echo htmlspecialchars(
                                        $gym["description"] ??
                                        "No description available."
                                    );

                                    ?>

                                </p>


                                <!-- INFO -->

                                <div class="gym-info">


                                    <div class="info-row">

                                        📍

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


                                    <div class="info-row">

                                        🕐

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


                                    <?php if (!empty($gym["phone"])): ?>


                                        <div class="info-row">

                                            📞

                                            <span>

                                                <?php

                                                echo htmlspecialchars(
                                                    $gym["phone"]
                                                );

                                                ?>

                                            </span>

                                        </div>


                                    <?php endif; ?>


                                </div>


                                <!-- ACTIONS -->

                                <div class="gym-actions">


                                    <!-- VIEW GYM -->

                                    <a
                                        href="gym_details.php?gym_id=<?php
                                            echo $gym_id;
                                        ?>"
                                        class="gym-btn view-btn"
                                    >

                                        👁 View

                                    </a>


                                    <!-- JOIN -->

                                    <a
                                        href="view_plans.php?gym_id=<?php
                                            echo $gym_id;
                                        ?>"
                                        class="gym-btn join-btn"
                                    >

                                        💳 Join

                                    </a>


                                    <!-- DIRECTIONS -->

                                    <a
                                        href="<?php
                                            echo htmlspecialchars(
                                                $directions_url
                                            );
                                        ?>"
                                        target="_blank"
                                        class="gym-btn direction-btn"
                                    >

                                        🚗 Directions

                                    </a>


                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="no-gyms">


                        <div class="no-gyms-icon">
                            🏋️
                        </div>


                        <h2>
                            No Gyms Available
                        </h2>


                        <p>

                            There are currently no approved gyms
                            with location information available.

                            Please check again later.

                        </p>


                    </div>


                <?php endif; ?>


            </div>


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
   GYM DATA FROM PHP
========================================================= */

const gyms = <?php

    echo json_encode(
        $gyms,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    );

?>;


/* =========================================================
   DEFAULT LOCATION

   India
========================================================= */

const defaultLatitude =
    20.5937;


const defaultLongitude =
    78.9629;


/* =========================================================
   CREATE MAP
========================================================= */

const map =
    L.map(
        "map"
    ).setView(

        [
            defaultLatitude,

            defaultLongitude

        ],

        5

    );


/* =========================================================
   OPENSTREETMAP
========================================================= */

L.tileLayer(

    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",

    {

        maxZoom: 19,

        attribution:
            "&copy; OpenStreetMap contributors"

    }

).addTo(map);


/* =========================================================
   MARKERS
========================================================= */

let markers = [];


/* =========================================================
   USER LOCATION MARKER
========================================================= */

let userMarker = null;


/* =========================================================
   CALCULATE DISTANCE

   Haversine Formula
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
            lat2 - lat1
        ) *
        Math.PI /
        180;


    const dLon =
        (
            lon2 - lon1
        ) *
        Math.PI /
        180;


    const a =

        Math.sin(
            dLat / 2
        ) *
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
   CLEAR MARKERS
========================================================= */

function clearMarkers() {


    markers.forEach(

        function(marker) {

            map.removeLayer(
                marker
            );

        }

    );


    markers = [];

}


/* =========================================================
   SHOW ALL GYMS ON MAP
========================================================= */

function showAllGyms() {


    clearMarkers();


    const bounds = [];


    gyms.forEach(

        function(gym) {


            const latitude =
                parseFloat(
                    gym.latitude
                );


            const longitude =
                parseFloat(
                    gym.longitude
                );


            const marker =

                L.marker(

                    [
                        latitude,

                        longitude

                    ]

                )

                .addTo(map);


            marker.bindPopup(

                `

                <strong>

                    ${gym.gym_name}

                </strong>

                <br>

                ${gym.city}

                <br><br>

                <a
                    href="gym_details.php?gym_id=${gym.gym_id}"
                >

                    View Gym

                </a>

                `

            );


            marker.on(

                "click",

                function() {


                    highlightGym(
                        gym.gym_id
                    );


                }

            );


            markers.push(
                marker
            );


            bounds.push(

                [
                    latitude,

                    longitude

                ]

            );


        }

    );


    if (
        bounds.length > 0
    ) {


        map.fitBounds(

            bounds,

            {

                padding: [
                    30,
                    30
                ]

            }

        );


    }

}


/* =========================================================
   HIGHLIGHT GYM CARD
========================================================= */

function highlightGym(
    gymId
) {


    document
        .querySelectorAll(
            ".gym-card"
        )
        .forEach(

            function(card) {

                card.classList.remove(
                    "highlighted"
                );

            }

        );


    const card =

        document.getElementById(

            "gym-card-" +
            gymId

        );


    if (card) {


        card.classList.add(
            "highlighted"
        );


        card.scrollIntoView(

            {

                behavior:
                    "smooth",

                block:
                    "center"

            }

        );


    }

}


/* =========================================================
   SORT GYMS BY DISTANCE
========================================================= */

function sortGymsByDistance(
    userLatitude,
    userLongitude
) {


    const gymList =
        document.getElementById(
            "gymList"
        );


    const cards =

        Array.from(

            gymList.querySelectorAll(
                ".gym-card"
            )

        );


    const distances = [];


    gyms.forEach(

        function(gym) {


            const distance =

                calculateDistance(

                    userLatitude,

                    userLongitude,

                    parseFloat(
                        gym.latitude
                    ),

                    parseFloat(
                        gym.longitude
                    )

                );


            distances.push(

                {

                    id:
                        parseInt(
                            gym.gym_id
                        ),

                    distance:
                        distance

                }

            );


            const distanceElement =

                document.getElementById(

                    "distance-" +
                    gym.gym_id

                );


            if (
                distanceElement
            ) {


                if (
                    distance < 1
                ) {


                    distanceElement.innerText =

                        Math.round(
                            distance *
                            1000
                        ) +

                        " m away";


                } else {


                    distanceElement.innerText =

                        distance.toFixed(
                            1
                        ) +

                        " km away";


                }


            }


        }

    );


    distances.sort(

        function(a, b) {

            return (
                a.distance -
                b.distance
            );

        }

    );


    distances.forEach(

        function(item) {


            const card =

                document.getElementById(

                    "gym-card-" +
                    item.id

                );


            if (
                card
            ) {


                gymList.appendChild(
                    card
                );


            }


        }

    );


    /* Update count */

    document.getElementById(
        "gymCount"
    ).innerText =

        distances.length +

        " Gyms Near You";


}


/* =========================================================
   USE CURRENT LOCATION
========================================================= */

document.getElementById(

    "useLocationBtn"

).addEventListener(

    "click",

    function() {


        const button =
            this;


        const status =

            document.getElementById(

                "locationStatus"

            );


        if (
            !navigator.geolocation
        ) {


            status.innerText =

                "Geolocation is not supported by your browser.";


            status.style.color =
                "#ef4444";


            return;


        }


        button.disabled =
            true;


        button.innerText =

            "📍 Getting Location...";


        status.innerText =

            "Please allow location access in your browser.";


        status.style.color =
            "#f59e0b";


        navigator.geolocation.getCurrentPosition(

            function(position) {


                const userLatitude =

                    position.coords.latitude;


                const userLongitude =

                    position.coords.longitude;


                /* ================================
                   USER MARKER
                ================================= */

                if (
                    userMarker !== null
                ) {


                    map.removeLayer(
                        userMarker
                    );


                }


                userMarker =

                    L.marker(

                        [

                            userLatitude,

                            userLongitude

                        ]

                    )

                    .addTo(map);


                userMarker.bindPopup(

                    "📍 You are here"

                ).openPopup();


                /* ================================
                   CENTER MAP
                ================================= */

                map.setView(

                    [

                        userLatitude,

                        userLongitude

                    ],

                    12

                );


                /* ================================
                   SORT GYMS
                ================================= */

                sortGymsByDistance(

                    userLatitude,

                    userLongitude

                );


                /* ================================
                   STATUS
                ================================= */

                status.innerText =

                    "✓ Gyms are now sorted by distance from your current location.";


                status.style.color =
                    "#22c55e";


                button.disabled =
                    false;


                button.innerText =

                    "📍 Update My Location";


            },


            function(error) {


                button.disabled =
                    false;


                button.innerText =

                    "📍 Use My Current Location";


                status.style.color =
                    "#ef4444";


                if (
                    error.code === 1
                ) {


                    status.innerText =

                        "Location permission was denied. Please allow location access and try again.";


                }

                else if (
                    error.code === 2
                ) {


                    status.innerText =

                        "Unable to determine your location.";


                }

                else if (
                    error.code === 3
                ) {


                    status.innerText =

                        "Location request timed out. Please try again.";


                }

                else {


                    status.innerText =

                        "Unable to get your current location.";


                }


            },


            {

                enableHighAccuracy:
                    true,

                timeout:
                    15000,

                maximumAge:
                    0

            }

        );


    }

);


/* =========================================================
   SHOW ALL BUTTON
========================================================= */

document.getElementById(

    "showAllBtn"

).addEventListener(

    "click",

    function() {


        showAllGyms();


        document.getElementById(

            "locationStatus"

        ).innerText =

            "Showing all approved gyms.";


        document.getElementById(

            "locationStatus"

        ).style.color =

            "#94a3b8";


        document.getElementById(

            "gymCount"

        ).innerText =

            gyms.length +

            " Gyms";


    }

);


/* =========================================================
   INITIAL LOAD
========================================================= */

showAllGyms();


</script>


</body>

</html>


<?php

$conn->close();

?>