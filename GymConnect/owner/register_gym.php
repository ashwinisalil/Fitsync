
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "owner"
) {
    header("Location: ../login.php");
    exit();
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

    <title>Register Gym | GymConnect</title>


    <!-- =========================================
         GOOGLE FONT
    ========================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =========================================
         EXISTING REGISTER GYM CSS
    ========================================== -->

    <link
        rel="stylesheet"
        href="../css/register_gym.css"
    >


    <!-- =========================================
         LEAFLET MAP CSS
    ========================================== -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""
    >


    <style>

        /* =========================================
           LOCATION SECTION
        ========================================= */

        .location-section {

            margin-top: 25px;

            padding: 25px;

            background: #111827;

            border: 1px solid #334155;

            border-radius: 15px;

        }


        .location-section h2 {

            color: #00c2ff;

            font-size: 21px;

            margin-bottom: 8px;

        }


        .location-section > p {

            color: #94a3b8;

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 18px;

        }


        /* =========================================
           CURRENT LOCATION BUTTON
        ========================================== */

        .location-btn {

            display: inline-block;

            padding: 12px 20px;

            background: #00c2ff;

            color: #000;

            border: none;

            border-radius: 10px;

            font-family: 'Poppins', sans-serif;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition: all 0.3s ease;

            margin-bottom: 18px;

        }


        .location-btn:hover {

            background: #19ccff;

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(0, 194, 255, 0.25);

        }


        .location-btn:disabled {

            opacity: 0.6;

            cursor: not-allowed;

            transform: none;

        }


        /* =========================================
           MAP
        ========================================== */

        #map {

            width: 100%;

            height: 400px;

            border-radius: 12px;

            overflow: hidden;

            border: 1px solid #334155;

            margin-bottom: 15px;

        }


        /* =========================================
           LOCATION STATUS
        ========================================== */

        .location-status {

            color: #94a3b8;

            font-size: 14px;

            margin-bottom: 15px;

        }


        /* =========================================
           COORDINATES
        ========================================== */

        .coordinates {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;

        }


        .coordinates .input-group {

            margin-bottom: 0;

        }


        .coordinates input {

            width: 100%;

            padding: 12px 14px;

            background: #0f172a;

            color: #ffffff;

            border: 1px solid #334155;

            border-radius: 8px;

            outline: none;

            font-family: 'Poppins', sans-serif;

        }


        .coordinates input:focus {

            border-color: #00c2ff;

        }


        /* =========================================
           RESPONSIVE MAP
        ========================================== */

        @media (max-width: 600px) {

            .coordinates {

                grid-template-columns: 1fr;

            }


            #map {

                height: 320px;

            }


            .location-section {

                padding: 18px;

            }

        }

    </style>

</head>


<body>


<div class="container">


    <div class="form-box">


        <!-- =====================================
             PAGE TITLE
        ====================================== -->

        <h1>
            Register Your Gym
        </h1>


        <p>
            Complete the details below to register your gym.
        </p>


        <!-- =====================================
             REGISTRATION FORM
        ====================================== -->

        <form
            id="registerGymForm"
            action="../register_gym_process.php"
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- =====================================
                 GYM NAME
            ====================================== -->

            <div class="input-group">

                <label>
                    Gym Name
                </label>

                <input
                    type="text"
                    name="gym_name"
                    placeholder="Enter your gym name"
                    required
                >

            </div>


            <!-- =====================================
                 DESCRIPTION
            ====================================== -->

            <div class="input-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    placeholder="Describe your gym"
                    required
                ></textarea>

            </div>


            <!-- =====================================
                 ADDRESS
            ====================================== -->

            <div class="input-group">

                <label>
                    Address
                </label>

                <textarea
                    name="address"
                    rows="3"
                    placeholder="Enter complete gym address"
                    required
                ></textarea>

            </div>


            <!-- =====================================
                 CITY + STATE
            ====================================== -->

            <div class="row">


                <div class="input-group">

                    <label>
                        City
                    </label>

                    <input
                        type="text"
                        name="city"
                        placeholder="Enter city"
                        required
                    >

                </div>


                <div class="input-group">

                    <label>
                        State
                    </label>

                    <input
                        type="text"
                        name="state"
                        placeholder="Enter state"
                        required
                    >

                </div>


            </div>


            <!-- =====================================
                 COUNTRY + PINCODE
            ====================================== -->

            <div class="row">


                <div class="input-group">

                    <label>
                        Country
                    </label>

                    <input
                        type="text"
                        name="country"
                        value="India"
                        required
                    >

                </div>


                <div class="input-group">

                    <label>
                        Pincode
                    </label>

                    <input
                        type="text"
                        name="pincode"
                        placeholder="Enter pincode"
                        required
                    >

                </div>


            </div>


            <!-- =====================================
                 PHONE + EMAIL
            ====================================== -->

            <div class="row">


                <div class="input-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        placeholder="Enter gym phone number"
                        required
                    >

                </div>


                <div class="input-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter gym email"
                        required
                    >

                </div>


            </div>


            <!-- =====================================
                 OPENING + CLOSING TIME
            ====================================== -->

            <div class="row">


                <div class="input-group">

                    <label>
                        Opening Time
                    </label>

                    <input
                        type="time"
                        name="opening_time"
                        required
                    >

                </div>


                <div class="input-group">

                    <label>
                        Closing Time
                    </label>

                    <input
                        type="time"
                        name="closing_time"
                        required
                    >

                </div>


            </div>


            <!-- =====================================
                 GYM LOCATION
            ====================================== -->

            <div class="location-section">


                <h2>
                    📍 Gym Location
                </h2>


                <p>

                    Select the exact location of your gym on
                    the map. You can click directly on the map
                    or use your current location.

                </p>


                <!-- CURRENT LOCATION BUTTON -->

                <button
                    type="button"
                    id="currentLocationBtn"
                    class="location-btn"
                >

                    📍 Use My Current Location

                </button>


                <!-- MAP -->

                <div id="map"></div>


                <!-- STATUS -->

                <p
                    class="location-status"
                    id="locationStatus"
                >

                    Click on the map to select your gym location.

                </p>


                <!-- LATITUDE + LONGITUDE -->

                <div class="coordinates">


                    <div class="input-group">

                        <label>
                            Latitude
                        </label>

                        <input
                            type="text"
                            name="latitude"
                            id="latitude"
                            placeholder="Select location on map"
                            readonly
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label>
                            Longitude
                        </label>

                        <input
                            type="text"
                            name="longitude"
                            id="longitude"
                            placeholder="Select location on map"
                            readonly
                            required
                        >

                    </div>


                </div>


            </div>


            <!-- =====================================
                 GYM IMAGE
            ====================================== -->

            <div class="input-group">

                <label>
                    Gym Image
                </label>

                <input
                    type="file"
                    name="profile_image"
                    accept="image/*"
                >

            </div>


            <!-- =====================================
                 SUBMIT BUTTON
            ====================================== -->

            <button
                type="submit"
                class="btn"
            >

                Register Gym

            </button>


        </form>


    </div>


</div>


<!-- =========================================
     LEAFLET MAP JAVASCRIPT
========================================== -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin=""
></script>


<script>


/* =========================================
   DEFAULT MAP LOCATION

   India
========================================= */

const defaultLatitude = 20.5937;

const defaultLongitude = 78.9629;


/* =========================================
   CREATE MAP
========================================= */

const map = L.map(
    'map'
).setView(
    [
        defaultLatitude,
        defaultLongitude
    ],
    5
);


/* =========================================
   OPENSTREETMAP MAP TILES
========================================= */

L.tileLayer(

    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',

    {

        maxZoom: 19,

        attribution:
            '&copy; OpenStreetMap contributors'

    }

).addTo(map);


/* =========================================
   MARKER VARIABLE
========================================= */

let marker = null;


/* =========================================
   SELECT LOCATION FUNCTION
========================================= */

function selectLocation(
    latitude,
    longitude
) {


    /* ================================
       MOVE MAP
    ================================= */

    map.setView(

        [
            latitude,
            longitude
        ],

        16

    );


    /* ================================
       REMOVE PREVIOUS MARKER
    ================================= */

    if (marker !== null) {

        map.removeLayer(marker);

    }


    /* ================================
       CREATE NEW MARKER
    ================================= */

    marker = L.marker(

        [
            latitude,
            longitude
        ]

    ).addTo(map);


    /* ================================
       POPUP
    ================================= */

    marker
        .bindPopup(
            "📍 Gym Location"
        )
        .openPopup();


    /* ================================
       SET LATITUDE
    ================================= */

    document.getElementById(
        "latitude"
    ).value =
        latitude.toFixed(8);


    /* ================================
       SET LONGITUDE
    ================================= */

    document.getElementById(
        "longitude"
    ).value =
        longitude.toFixed(8);


    /* ================================
       UPDATE STATUS
    ================================= */

    document.getElementById(
        "locationStatus"
    ).innerText =
        "✓ Gym location selected successfully.";


    document.getElementById(
        "locationStatus"
    ).style.color =
        "#22c55e";

}


/* =========================================
   CLICK ON MAP
========================================= */

map.on(

    'click',

    function(event) {


        const latitude =
            event.latlng.lat;


        const longitude =
            event.latlng.lng;


        selectLocation(

            latitude,

            longitude

        );


    }

);


/* =========================================
   USE CURRENT LOCATION
========================================= */

document.getElementById(
    "currentLocationBtn"
).addEventListener(

    "click",

    function() {


        const button = this;


        const status =
            document.getElementById(
                "locationStatus"
            );


        /* ================================
           CHECK BROWSER SUPPORT
        ================================= */

        if (
            !navigator.geolocation
        ) {

            status.innerText =
                "Geolocation is not supported by your browser.";

            status.style.color =
                "#ef4444";

            return;

        }


        /* ================================
           DISABLE BUTTON
        ================================= */

        button.disabled = true;


        button.innerText =
            "📍 Getting Location...";


        status.innerText =
            "Please allow location access in your browser.";


        status.style.color =
            "#f59e0b";


        /* ================================
           GET LOCATION
        ================================= */

        navigator.geolocation.getCurrentPosition(

            function(position) {


                const latitude =
                    position.coords.latitude;


                const longitude =
                    position.coords.longitude;


                /* SELECT LOCATION */

                selectLocation(

                    latitude,

                    longitude

                );


                /* RESET BUTTON */

                button.disabled = false;


                button.innerText =
                    "📍 Use My Current Location";


            },


            function(error) {


                button.disabled = false;


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
                        "Your location could not be determined.";

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

                enableHighAccuracy: true,

                timeout: 15000,

                maximumAge: 0

            }

        );


    }

);


/* =========================================
   FORM SUBMISSION VALIDATION
========================================= */

document.getElementById(
    "registerGymForm"
).addEventListener(

    "submit",

    function(event) {


        const latitude =
            document.getElementById(
                "latitude"
            ).value;


        const longitude =
            document.getElementById(
                "longitude"
            ).value;


        /* ================================
           LOCATION REQUIRED
        ================================= */

        if (
            latitude === "" ||
            longitude === ""
        ) {


            event.preventDefault();


            alert(
                "Please select your gym's location on the map before registering."
            );


            document.getElementById(
                "locationStatus"
            ).innerText =
                "⚠ Please select your gym location on the map.";


            document.getElementById(
                "locationStatus"
            ).style.color =
                "#ef4444";


            return;

        }


    }

);


</script>


</body>

</html>
