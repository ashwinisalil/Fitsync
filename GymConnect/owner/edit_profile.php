<?php

/* =========================================================
   GYMCONNECT - EDIT OWNER PROFILE
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
   GET OWNER DETAILS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        user_id,
        full_name,
        email,
        phone,
        profile_image
    FROM users
    WHERE user_id = ?
    AND role = 'owner'
    LIMIT 1
");


if (!$stmt) {
    die(
        "Database error: " .
        $conn->error
    );
}


$stmt->bind_param(
    "i",
    $owner_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {
    die("Owner profile not found.");
}


$owner =
    $result->fetch_assoc();


$stmt->close();

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
    Edit Profile | GymConnect
</title>


<link
    rel="stylesheet"
    href="../css/edit_profile.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


</head>


<body>


<div class="edit-container">


<!-- =====================================================
     PAGE HEADER
====================================================== -->

<div class="page-header">


<div>

<h1>
    Edit Profile
</h1>


<p>
    Update your GymConnect owner profile
</p>

</div>


<a
    href="profile.php"
    class="back-btn"
>

    ← Back to Profile

</a>


</div>



<!-- =====================================================
     EDIT PROFILE CARD
====================================================== -->

<div class="edit-card">


<form
    action="edit_profile_process.php"
    method="POST"
    enctype="multipart/form-data"
>


<!-- =====================================================
     PROFILE PHOTO
====================================================== -->

<div class="photo-section">


<div class="profile-preview">


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
    alt="Profile Photo"
    id="profilePreview"
>


<?php

} else {

?>


<div
    class="default-preview"
    id="defaultPreview"
>

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


<img
    src=""
    alt="Profile Preview"
    id="profilePreview"
    style="display:none;"
>


<?php

}

?>


</div>


<div class="photo-info">


<h3>
    Profile Photo
</h3>


<p>
    Upload a new profile photo.
</p>


<label
    for="profile_image"
    class="upload-btn"
>

    Choose Photo

</label>


<input
    type="file"
    id="profile_image"
    name="profile_image"
    accept="image/jpeg,image/png,image/jpg,image/webp"
    onchange="previewImage(event)"
>


<small>
    JPG, JPEG, PNG or WEBP.
    Maximum size: 5 MB.
</small>


</div>


</div>



<!-- =====================================================
     FULL NAME
====================================================== -->

<div class="input-group">


<label for="full_name">

    Full Name

</label>


<input
    type="text"
    id="full_name"
    name="full_name"
    value="<?php

        echo htmlspecialchars(
            $owner["full_name"]
        );

    ?>"
    required
    maxlength="100"
>


</div>



<!-- =====================================================
     EMAIL
====================================================== -->

<div class="input-group">


<label for="email">

    Email Address

</label>


<input
    type="email"
    id="email"
    value="<?php

        echo htmlspecialchars(
            $owner["email"]
        );

    ?>"
    readonly
>


<small class="input-note">

    Email address cannot be changed from here.

</small>


</div>



<!-- =====================================================
     PHONE
====================================================== -->

<div class="input-group">


<label for="phone">

    Phone Number

</label>


<input
    type="tel"
    id="phone"
    name="phone"
    value="<?php

        echo htmlspecialchars(
            $owner["phone"] ?? ""
        );

    ?>"
    maxlength="20"
    placeholder="Enter your phone number"
>


</div>



<!-- =====================================================
     ACTION BUTTONS
====================================================== -->

<div class="form-actions">


<a
    href="profile.php"
    class="cancel-btn"
>

    Cancel

</a>


<button
    type="submit"
    class="save-btn"
>

    Save Changes

</button>


</div>


</form>


</div>


</div>



<!-- =====================================================
     IMAGE PREVIEW JAVASCRIPT
====================================================== -->

<script>

function previewImage(event) {

    const file =
        event.target.files[0];

    const preview =
        document.getElementById(
            "profilePreview"
        );

    const defaultPreview =
        document.getElementById(
            "defaultPreview"
        );


    if (!file) {

        return;

    }


    /* =========================================
       CHECK FILE TYPE
    ========================================= */

    const allowedTypes = [

        "image/jpeg",

        "image/jpg",

        "image/png",

        "image/webp"

    ];


    if (
        !allowedTypes.includes(
            file.type
        )
    ) {

        alert(
            "Please select a JPG, JPEG, PNG or WEBP image."
        );

        event.target.value = "";

        return;

    }


    /* =========================================
       CHECK FILE SIZE
       Maximum 5 MB
    ========================================= */

    if (
        file.size >
        5 * 1024 * 1024
    ) {

        alert(
            "Profile image must be smaller than 5 MB."
        );

        event.target.value = "";

        return;

    }


    /* =========================================
       SHOW PREVIEW
    ========================================= */

    const reader =
        new FileReader();


    reader.onload =
        function(e) {

            preview.src =
                e.target.result;

            preview.style.display =
                "block";


            if (
                defaultPreview
            ) {

                defaultPreview.style.display =
                    "none";

            }

        };


    reader.readAsDataURL(
        file
    );

}

</script>


</body>

</html>