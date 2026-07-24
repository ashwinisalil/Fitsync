<?php

/* =========================================================
   GYMCONNECT - OWNER PROFILE
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
   GET OWNER PROFILE
========================================================= */

$owner_stmt = $conn->prepare("
    SELECT
        user_id,
        full_name,
        email,
        phone,
        role,
        profile_image
    FROM users
    WHERE user_id = ?
    AND role = 'owner'
    LIMIT 1
");


if (!$owner_stmt) {
    die(
        "Database error: " .
        $conn->error
    );
}


$owner_stmt->bind_param(
    "i",
    $owner_id
);


$owner_stmt->execute();


$owner_result =
    $owner_stmt->get_result();


if (
    $owner_result->num_rows === 0
) {
    die("Owner profile not found.");
}


$owner =
    $owner_result->fetch_assoc();


$owner_stmt->close();


/* =========================================================
   GET ALL GYMS OF THIS OWNER
========================================================= */

$gym_stmt = $conn->prepare("
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
        profile_image,
        status,
        is_active
    FROM gyms
    WHERE owner_id = ?
    ORDER BY gym_id DESC
");


if (!$gym_stmt) {
    die(
        "Database error: " .
        $conn->error
    );
}


$gym_stmt->bind_param(
    "i",
    $owner_id
);


$gym_stmt->execute();


$gym_result =
    $gym_stmt->get_result();


$gyms = [];


while (
    $gym = $gym_result->fetch_assoc()
) {

    $gyms[] = $gym;

}


$gym_stmt->close();

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
    My Profile | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/owner_profile.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


</head>


<body>


<div class="profile-container">


<!-- =====================================================
     PAGE HEADER
====================================================== -->

<div class="page-header">


<div>

<h1>
    My Profile
</h1>


<p>
    Manage your GymConnect owner account
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
     PROFILE CARD
====================================================== -->

<div class="profile-card">


<div class="profile-top">


<!-- =====================================================
     PROFILE IMAGE
====================================================== -->

<div class="profile-image-box">


<?php

if (
    !empty(
        $owner["profile_image"]
    )
) {


$image_path =
    "../" .
    $owner["profile_image"];


?>


<img
    src="<?php

        echo htmlspecialchars(
            $image_path
        );

    ?>"
    alt="Owner Profile"
    class="profile-image"
>


<?php

} else {

?>


<div class="default-profile">

    <?php

    echo strtoupper(
        substr(
            $owner["full_name"],
            0,
            1
        )
    );

    ?>

</div>


<?php

}

?>


</div>



<!-- =====================================================
     OWNER BASIC INFORMATION
====================================================== -->

<div class="profile-basic">


<h2>

<?php

echo htmlspecialchars(
    $owner["full_name"]
);

?>

</h2>


<p class="owner-role">

    👤 Gym Owner

</p>


<p class="owner-email">

    <?php

    echo htmlspecialchars(
        $owner["email"]
    );

    ?>

</p>


</div>


</div>



<!-- =====================================================
     OWNER DETAILS
====================================================== -->

<div class="profile-details">


<div class="detail-item">


<span class="detail-label">

    Full Name

</span>


<span class="detail-value">

<?php

echo htmlspecialchars(
    $owner["full_name"]
);

?>

</span>


</div>



<div class="detail-item">


<span class="detail-label">

    Email Address

</span>


<span class="detail-value">

<?php

echo htmlspecialchars(
    $owner["email"]
);

?>

</span>


</div>



<div class="detail-item">


<span class="detail-label">

    Phone Number

</span>


<span class="detail-value">

<?php

if (
    !empty(
        $owner["phone"]
    )
) {

    echo htmlspecialchars(
        $owner["phone"]
    );

} else {

    echo "Not provided";

}

?>

</span>


</div>



<div class="detail-item">


<span class="detail-label">

    Account Type

</span>


<span class="detail-value role-badge">

    Gym Owner

</span>


</div>


</div>



<!-- =====================================================
     PROFILE ACTIONS
====================================================== -->

<div class="profile-actions">


<a
    href="edit_profile.php"
    class="btn btn-primary"
>

    ✏ Edit Profile

</a>


<a
    href="change_password.php"
    class="btn btn-secondary"
>

    🔒 Change Password

</a>


</div>


</div>



<!-- =====================================================
     MY GYMS SECTION
====================================================== -->

<div class="gyms-section">


<div class="section-header">


<div>

<h2>

    🏢 My Gyms

</h2>


<p>

    All gyms registered under your owner account

</p>

</div>


<a
    href="register_gym.php"
    class="add-gym-btn"
>

    + Register New Gym

</a>


</div>



<?php if (
    count($gyms) > 0
): ?>


<div class="gym-grid">


<?php foreach (
    $gyms as $gym
): ?>


<div class="gym-card">


<!-- =================================================
     GYM IMAGE
================================================== -->

<div class="gym-image">


<?php

if (
    !empty(
        $gym["profile_image"]
    )
) {


$gym_image =
    "../" .
    $gym["profile_image"];


?>


<img
    src="<?php

        echo htmlspecialchars(
            $gym_image
        );

    ?>"
    alt="Gym Image"
>


<?php

} else {

?>


<div class="gym-placeholder">

    🏋️

</div>


<?php

}

?>


</div>



<!-- =================================================
     GYM INFORMATION
================================================== -->

<div class="gym-info">


<div class="gym-title-row">


<h3>

<?php

echo htmlspecialchars(
    $gym["gym_name"]
);

?>

</h3>



<?php

$status =
    strtolower(
        $gym["status"]
    );


?>


<span
    class="gym-status
    <?php

        echo htmlspecialchars(
            $status
        );

    ?>"
>


<?php

echo ucfirst(
    $status
);

?>


</span>


</div>



<p class="gym-location">


📍


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


</p>



<p class="gym-address">


<?php

echo htmlspecialchars(
    $gym["address"]
);

?>


<br>


<?php

echo htmlspecialchars(
    $gym["pincode"]
);

?>


</p>



<div class="gym-contact">


<p>

    📞

    <?php

    echo htmlspecialchars(
        $gym["phone"]
    );

    ?>

</p>


<p>

    ✉

    <?php

    echo htmlspecialchars(
        $gym["email"]
    );

    ?>

</p>


</div>



<div class="gym-timing">


🕐


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


</div>



<!-- =================================================
     GYM ACTIONS
================================================== -->

<div class="gym-actions">


<a
    href="my_gym.php?gym_id=<?php

        echo intval(
            $gym["gym_id"]
        );

    ?>"
    class="view-gym-btn"
>

    View Gym

</a>


<a
    href="membership_plans.php?gym_id=<?php

        echo intval(
            $gym["gym_id"]
        );

    ?>"
    class="plans-btn"
>

    Plans

</a>


</div>


</div>


</div>


<?php endforeach; ?>


</div>


<?php else: ?>


<div class="no-gyms">


<div class="no-gyms-icon">

    🏢

</div>


<h3>

    No Gyms Registered

</h3>


<p>

    You have not registered any gym yet.

</p>


<a
    href="register_gym.php"
    class="add-gym-btn"
>

    Register Your First Gym

</a>


</div>


<?php endif; ?>


</div>


</div>


</body>

</html>