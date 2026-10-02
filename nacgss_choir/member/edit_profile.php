<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["member_logged_in"]) || $_SESSION["member_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION["member_id"];

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Get current member details
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        full_name,
        email,
        phone,
        voice_part,
        fellowship
    FROM members
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$member_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    session_destroy();

    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Update profile
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $voice_part = trim($_POST["voice_part"] ?? "");
    $fellowship = trim($_POST["fellowship"] ?? "");

    if (
        $full_name === "" ||
        $email === "" ||
        $phone === "" ||
        $voice_part === "" ||
        $fellowship === ""
    ) {

        $error = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check if another member is already using this email
        |--------------------------------------------------------------------------
        */

        $check = $pdo->prepare("
            SELECT id
            FROM members
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $check->execute([$email, $member_id]);

        if ($check->fetch()) {

            $error = "This email address is already being used by another member.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update member
            |--------------------------------------------------------------------------
            */

            $update = $pdo->prepare("
                UPDATE members
                SET
                    full_name = ?,
                    email = ?,
                    phone = ?,
                    voice_part = ?,
                    fellowship = ?
                WHERE id = ?
            ");

            $update->execute([
                $full_name,
                $email,
                $phone,
                $voice_part,
                $fellowship,
                $member_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update session name and email
            |--------------------------------------------------------------------------
            */

            $_SESSION["member_name"] = $full_name;
            $_SESSION["member_email"] = $email;

            $message = "Your profile has been updated successfully.";

            /*
            |--------------------------------------------------------------------------
            | Reload updated member information
            |--------------------------------------------------------------------------
            */

            $stmt->execute([$member_id]);

            $member = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile - NACGSS Mass Choir</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #333;
        }

        .page-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
        }

        .profile-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .page-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .page-subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: 7px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-save {
            background: #007bff;
            color: white;
        }

        .btn-save:hover {
            background: #0056b3;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #545b62;
        }

        @media (max-width: 600px) {

            .page-container {
                margin: 20px auto;
                padding: 15px;
            }

            .profile-card {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<div class="page-container">

    <div class="profile-card">

        <h1 class="page-title">
            Edit My Profile
        </h1>

        <p class="page-subtitle">
            Update your personal information
        </p>


        <?php if ($message): ?>

            <div class="success-message">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= htmlspecialchars($member["full_name"]) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($member["email"]) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($member["phone"]) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="voice_part">
                    Voice Part
                </label>

                <select
                    id="voice_part"
                    name="voice_part"
                    required
                >

                    <option value="">
                        Select Voice Part
                    </option>

                    <option
                        value="Soprano"
                        <?= $member["voice_part"] === "Soprano" ? "selected" : "" ?>
                    >
                        Soprano
                    </option>

                    <option
                        value="Alto"
                        <?= $member["voice_part"] === "Alto" ? "selected" : "" ?>
                    >
                        Alto
                    </option>

                    <option
                        value="Tenor"
                        <?= $member["voice_part"] === "Tenor" ? "selected" : "" ?>
                    >
                        Tenor
                    </option>

                    <option
                        value="Bass"
                        <?= $member["voice_part"] === "Bass" ? "selected" : "" ?>
                    >
                        Bass
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="fellowship">
                    Fellowship / Church
                </label>

                <input
                    type="text"
                    id="fellowship"
                    name="fellowship"
                    value="<?= htmlspecialchars($member["fellowship"]) ?>"
                    required
                >

            </div>


            <div class="button-group">

                <button
                    type="submit"
                    class="btn btn-save"
                >
                    Save Changes
                </button>

                <a
                    href="dashboard.php"
                    class="btn btn-cancel"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>

</div>

</body>

</html>