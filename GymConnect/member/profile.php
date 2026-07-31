<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: ../login.php");
    exit();
}

require_once "../database/connection.php";

$member_id = (int) $_SESSION["user_id"];

$success = "";
$error = "";


/* =========================================================
   GET USER INFORMATION
========================================================= */

$user_stmt = $conn->prepare("
    SELECT
        user_id,
        full_name,
        email,
        phone
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$user_stmt->bind_param(
    "i",
    $member_id
);

$user_stmt->execute();

$user_result = $user_stmt->get_result();

$user = $user_result->fetch_assoc();

$user_stmt->close();


/* =========================================================
   GET MEMBER PROFILE
========================================================= */

$profile_stmt = $conn->prepare("
    SELECT *
    FROM member_profile
    WHERE member_id = ?
    LIMIT 1
");

$profile_stmt->bind_param(
    "i",
    $member_id
);

$profile_stmt->execute();

$profile_result = $profile_stmt->get_result();

$profile = $profile_result->fetch_assoc();

$profile_stmt->close();


/* =========================================================
   CREATE EMPTY PROFILE IF NOT EXISTS
========================================================= */

if (!$profile) {

    $insert_profile = $conn->prepare("
        INSERT INTO member_profile
        (
            member_id
        )
        VALUES
        (?)
    ");

    $insert_profile->bind_param(
        "i",
        $member_id
    );

    $insert_profile->execute();

    $insert_profile->close();


    /* Reload profile */

    $profile_stmt = $conn->prepare("
        SELECT *
        FROM member_profile
        WHERE member_id = ?
        LIMIT 1
    ");

    $profile_stmt->bind_param(
        "i",
        $member_id
    );

    $profile_stmt->execute();

    $profile =
        $profile_stmt
        ->get_result()
        ->fetch_assoc();

    $profile_stmt->close();
}


/* =========================================================
   UPDATE PROFILE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $full_name =
        trim(
            $_POST["full_name"] ?? ""
        );

    $phone =
        trim(
            $_POST["phone"] ?? ""
        );

    $gender =
        trim(
            $_POST["gender"] ?? ""
        );

    $date_of_birth =
        !empty($_POST["date_of_birth"])
        ? $_POST["date_of_birth"]
        : null;

    $height_cm =
        !empty($_POST["height_cm"])
        ? (float) $_POST["height_cm"]
        : null;

    $weight_kg =
        !empty($_POST["weight_kg"])
        ? (float) $_POST["weight_kg"]
        : null;

    $body_fat_percent =
        !empty($_POST["body_fat_percent"])
        ? (float) $_POST["body_fat_percent"]
        : null;

    $waist_cm =
        !empty($_POST["waist_cm"])
        ? (float) $_POST["waist_cm"]
        : null;

    $shoulder_cm =
        !empty($_POST["shoulder_cm"])
        ? (float) $_POST["shoulder_cm"]
        : null;

    $biceps_cm =
        !empty($_POST["biceps_cm"])
        ? (float) $_POST["biceps_cm"]
        : null;

    $fitness_goal =
        trim(
            $_POST["fitness_goal"] ?? ""
        );

    $fitness_level =
        trim(
            $_POST["fitness_level"] ?? ""
        );

    $activity_level =
        trim(
            $_POST["activity_level"] ?? ""
        );

    $diet_type =
        trim(
            $_POST["diet_type"] ?? ""
        );

    $meals_per_day =
        !empty($_POST["meals_per_day"])
        ? (int) $_POST["meals_per_day"]
        : null;

    $medical_conditions =
        trim(
            $_POST["medical_conditions"] ?? ""
        );

    $injuries =
        trim(
            $_POST["injuries"] ?? ""
        );

    $allergies =
        trim(
            $_POST["allergies"] ?? ""
        );

    $dietary_restrictions =
        trim(
            $_POST["dietary_restrictions"] ?? ""
        );

    $workout_location =
        trim(
            $_POST["workout_location"] ?? ""
        );

    $workout_days_per_week =
        !empty($_POST["workout_days_per_week"])
        ? (int) $_POST["workout_days_per_week"]
        : null;

    $available_equipment =
        trim(
            $_POST["available_equipment"] ?? ""
        );

    $fitness_experience =
        trim(
            $_POST["fitness_experience"] ?? ""
        );

    $additional_notes =
        trim(
            $_POST["additional_notes"] ?? ""
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        empty($full_name) ||
        empty($phone)
    ) {

        $error =
            "Full name and mobile number are required.";

    } elseif (
        $height_cm !== null &&
        ($height_cm < 50 || $height_cm > 300)
    ) {

        $error =
            "Please enter a valid height.";

    } elseif (
        $weight_kg !== null &&
        ($weight_kg < 10 || $weight_kg > 500)
    ) {

        $error =
            "Please enter a valid weight.";

    } else {


        /* =================================================
           UPDATE USERS TABLE
        ================================================= */

        $user_update = $conn->prepare("
            UPDATE users
            SET
                full_name = ?,
                phone = ?
            WHERE user_id = ?
        ");

        $user_update->bind_param(
            "ssi",
            $full_name,
            $phone,
            $member_id
        );

        $user_update->execute();

        $user_update->close();


        /* =================================================
           UPDATE MEMBER PROFILE
        ================================================= */

        $profile_update = $conn->prepare("
            UPDATE member_profile
            SET

                gender = ?,
                date_of_birth = ?,

                height_cm = ?,
                weight_kg = ?,

                body_fat_percent = ?,

                waist_cm = ?,
                shoulder_cm = ?,
                biceps_cm = ?,

                fitness_goal = ?,
                fitness_level = ?,
                activity_level = ?,

                diet_type = ?,
                meals_per_day = ?,

                medical_conditions = ?,
                injuries = ?,
                allergies = ?,
                dietary_restrictions = ?,

                workout_location = ?,
                workout_days_per_week = ?,
                available_equipment = ?,

                fitness_experience = ?,
                additional_notes = ?

            WHERE member_id = ?
        ");


        $profile_update->bind_param(

            "ssddddddssssissssssissi",

            $gender,
            $date_of_birth,

            $height_cm,
            $weight_kg,

            $body_fat_percent,

            $waist_cm,
            $shoulder_cm,
            $biceps_cm,

            $fitness_goal,
            $fitness_level,
            $activity_level,

            $diet_type,
            $meals_per_day,

            $medical_conditions,
            $injuries,
            $allergies,
            $dietary_restrictions,

            $workout_location,
            $workout_days_per_week,
            $available_equipment,

            $fitness_experience,
            $additional_notes,

            $member_id
        );


        if (
            $profile_update->execute()
        ) {

            $success =
                "Your profile and fitness information have been updated successfully.";

        } else {

            $error =
                "Unable to update your profile.";

        }


        $profile_update->close();


        /* =================================================
           RELOAD DATA
        ================================================= */

        $user_stmt = $conn->prepare("
            SELECT
                user_id,
                full_name,
                email,
                phone
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $user_stmt->bind_param(
            "i",
            $member_id
        );

        $user_stmt->execute();

        $user =
            $user_stmt
            ->get_result()
            ->fetch_assoc();

        $user_stmt->close();


        $profile_stmt = $conn->prepare("
            SELECT *
            FROM member_profile
            WHERE member_id = ?
            LIMIT 1
        ");

        $profile_stmt->bind_param(
            "i",
            $member_id
        );

        $profile_stmt->execute();

        $profile =
            $profile_stmt
            ->get_result()
            ->fetch_assoc();

        $profile_stmt->close();

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
    My Fitness Profile | GymConnect
</title>

<link
    rel="stylesheet"
    href="../css/member_profile.css"
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

</head>


<body>


<div class="profile-container">


<a
    href="dashboard.php"
    class="back-btn"
>
    ← Back to Dashboard
</a>


<div class="page-header">

<h1>
    My Fitness Profile
</h1>

<p>
    Keep your personal and fitness information updated.
    This information can be used to create personalized
    workout and diet plans.
</p>

</div>


<?php if (!empty($success)): ?>

<div class="success-message">

<?php
echo htmlspecialchars($success);
?>

</div>

<?php endif; ?>


<?php if (!empty($error)): ?>

<div class="error-message">

<?php
echo htmlspecialchars($error);
?>

</div>

<?php endif; ?>


<form
    method="POST"
    class="profile-form"
>


<!-- =====================================================
     GENERAL INFORMATION
====================================================== -->

<div class="section-card">

<h2>
    General Information
</h2>

<div class="form-grid">


<div class="form-group">

<label>
    Full Name
</label>

<input
    type="text"
    name="full_name"
    value="<?php
        echo htmlspecialchars(
            $user["full_name"] ?? ""
        );
    ?>"
    required
>

</div>


<div class="form-group">

<label>
    Email Address
</label>

<input
    type="email"
    value="<?php
        echo htmlspecialchars(
            $user["email"] ?? ""
        );
    ?>"
    readonly
>

<small>
    Email cannot be changed here.
</small>

</div>


<div class="form-group">

<label>
    Mobile Number
</label>

<input
    type="text"
    name="phone"
    value="<?php
        echo htmlspecialchars(
            $user["phone"] ?? ""
        );
    ?>"
    required
>

</div>


<div class="form-group">

<label>
    Gender
</label>

<select name="gender">

<option value="">
    Select Gender
</option>

<option
    value="Male"
    <?php
    echo (
        ($profile["gender"] ?? "")
        === "Male"
    )
    ? "selected"
    : "";
    ?>
>
    Male
</option>

<option
    value="Female"
    <?php
    echo (
        ($profile["gender"] ?? "")
        === "Female"
    )
    ? "selected"
    : "";
    ?>
>
    Female
</option>

<option
    value="Other"
    <?php
    echo (
        ($profile["gender"] ?? "")
        === "Other"
    )
    ? "selected"
    : "";
    ?>
>
    Other
</option>

</select>

</div>


<div class="form-group">

<label>
    Date of Birth
</label>

<input
    type="date"
    name="date_of_birth"
    value="<?php
        echo htmlspecialchars(
            $profile["date_of_birth"] ?? ""
        );
    ?>"
>

</div>


</div>

</div>


<!-- =====================================================
     BODY MEASUREMENTS
====================================================== -->

<div class="section-card">

<h2>
    Physical Measurements
</h2>

<p class="section-description">
    These measurements help create more personalized
    fitness recommendations.
</p>


<div class="form-grid">


<div class="form-group">

<label>
    Height (cm)
</label>

<input
    type="number"
    step="0.01"
    name="height_cm"
    value="<?php
        echo htmlspecialchars(
            $profile["height_cm"] ?? ""
        );
    ?>"
    placeholder="Example: 165"
>

</div>


<div class="form-group">

<label>
    Weight (kg)
</label>

<input
    type="number"
    step="0.01"
    name="weight_kg"
    value="<?php
        echo htmlspecialchars(
            $profile["weight_kg"] ?? ""
        );
    ?>"
    placeholder="Example: 60"
>

</div>


<div class="form-group">

<label>
    Body Fat (%)
</label>

<input
    type="number"
    step="0.01"
    name="body_fat_percent"
    value="<?php
        echo htmlspecialchars(
            $profile["body_fat_percent"] ?? ""
        );
    ?>"
    placeholder="Optional"
>

</div>


<div class="form-group">

<label>
    Waist (cm)
</label>

<input
    type="number"
    step="0.01"
    name="waist_cm"
    value="<?php
        echo htmlspecialchars(
            $profile["waist_cm"] ?? ""
        );
    ?>"
>

</div>


<div class="form-group">

<label>
    Shoulder (cm)
</label>

<input
    type="number"
    step="0.01"
    name="shoulder_cm"
    value="<?php
        echo htmlspecialchars(
            $profile["shoulder_cm"] ?? ""
        );
    ?>"
>

</div>


<div class="form-group">

<label>
    Biceps (cm)
</label>

<input
    type="number"
    step="0.01"
    name="biceps_cm"
    value="<?php
        echo htmlspecialchars(
            $profile["biceps_cm"] ?? ""
        );
    ?>"
>

</div>


</div>

</div>


<!-- =====================================================
     FITNESS GOAL
====================================================== -->

<div class="section-card">

<h2>
    Fitness Information
</h2>


<div class="form-grid">


<div class="form-group">

<label>
    Fitness Goal
</label>

<select name="fitness_goal">

<option value="">
    Select Goal
</option>

<option value="Weight Loss">
    Weight Loss
</option>

<option value="Muscle Gain">
    Muscle Gain
</option>

<option value="Fat Loss and Muscle Gain">
    Fat Loss and Muscle Gain
</option>

<option value="Strength">
    Strength
</option>

<option value="Endurance">
    Endurance
</option>

<option value="General Fitness">
    General Fitness
</option>

</select>

</div>


<div class="form-group">

<label>
    Fitness Level
</label>

<select name="fitness_level">

<option value="">
    Select Level
</option>

<option value="Beginner">
    Beginner
</option>

<option value="Intermediate">
    Intermediate
</option>

<option value="Advanced">
    Advanced
</option>

</select>

</div>


<div class="form-group">

<label>
    Daily Activity Level
</label>

<select name="activity_level">

<option value="">
    Select Activity
</option>

<option value="Sedentary">
    Sedentary
</option>

<option value="Lightly Active">
    Lightly Active
</option>

<option value="Moderately Active">
    Moderately Active
</option>

<option value="Very Active">
    Very Active
</option>

</select>

</div>


<div class="form-group">

<label>
    Workout Days Per Week
</label>

<input
    type="number"
    name="workout_days_per_week"
    min="1"
    max="7"
    value="<?php
        echo htmlspecialchars(
            $profile["workout_days_per_week"] ?? ""
        );
    ?>"
>

</div>


</div>

</div>


<!-- =====================================================
     DIET INFORMATION
====================================================== -->

<div class="section-card">

<h2>
    Diet Information
</h2>


<div class="form-grid">


<div class="form-group">

<label>
    Diet Type
</label>

<select name="diet_type">

<option value="">
    Select Diet Type
</option>

<option value="Vegetarian">
    Vegetarian
</option>

<option value="Eggetarian">
    Eggetarian
</option>

<option value="Non-Vegetarian">
    Non-Vegetarian
</option>

<option value="Vegan">
    Vegan
</option>

</select>

</div>


<div class="form-group">

<label>
    Meals Per Day
</label>

<input
    type="number"
    name="meals_per_day"
    min="1"
    max="8"
    value="<?php
        echo htmlspecialchars(
            $profile["meals_per_day"] ?? ""
        );
    ?>"
>

</div>


<div class="form-group full-width">

<label>
    Dietary Restrictions
</label>

<textarea
    name="dietary_restrictions"
    placeholder="Example: Low sugar, lactose intolerant, no peanuts..."
><?php
echo htmlspecialchars(
    $profile["dietary_restrictions"] ?? ""
);
?></textarea>

</div>


<div class="form-group full-width">

<label>
    Food Allergies
</label>

<textarea
    name="allergies"
    placeholder="List any food allergies..."
><?php
echo htmlspecialchars(
    $profile["allergies"] ?? ""
);
?></textarea>

</div>


</div>

</div>


<!-- =====================================================
     HEALTH INFORMATION
====================================================== -->

<div class="section-card">

<h2>
    Health & Safety Information
</h2>


<div class="form-group full-width">

<label>
    Medical Conditions
</label>

<textarea
    name="medical_conditions"
    placeholder="Example: Asthma, diabetes, high blood pressure, etc."
><?php
echo htmlspecialchars(
    $profile["medical_conditions"] ?? ""
);
?></textarea>

</div>


<div class="form-group full-width">

<label>
    Injuries or Physical Limitations
</label>

<textarea
    name="injuries"
    placeholder="Example: Knee injury, shoulder pain, back pain..."
><?php
echo htmlspecialchars(
    $profile["injuries"] ?? ""
);
?></textarea>

</div>

</div>


<!-- =====================================================
     WORKOUT INFORMATION
====================================================== -->

<div class="section-card">

<h2>
    Workout Preferences
</h2>


<div class="form-grid">


<div class="form-group">

<label>
    Preferred Workout Location
</label>

<select name="workout_location">

<option value="">
    Select Location
</option>

<option value="Gym">
    Gym
</option>

<option value="Home">
    Home
</option>

<option value="Outdoor">
    Outdoor
</option>

</select>

</div>


<div class="form-group full-width">

<label>
    Available Equipment
</label>

<textarea
    name="available_equipment"
    placeholder="Example: Dumbbells, resistance bands, treadmill..."
><?php
echo htmlspecialchars(
    $profile["available_equipment"] ?? ""
);
?></textarea>

</div>


<div class="form-group full-width">

<label>
    Fitness Experience
</label>

<textarea
    name="fitness_experience"
    placeholder="Describe your previous workout experience..."
><?php
echo htmlspecialchars(
    $profile["fitness_experience"] ?? ""
);
?></textarea>

</div>


<div class="form-group full-width">

<label>
    Additional Notes
</label>

<textarea
    name="additional_notes"
    placeholder="Anything else your trainer should know..."
><?php
echo htmlspecialchars(
    $profile["additional_notes"] ?? ""
);
?></textarea>

</div>


</div>

</div>


<div class="form-actions">

<button
    type="submit"
    class="save-btn"
>

    Save Fitness Profile

</button>

</div>


</form>


</div>


</body>

</html>