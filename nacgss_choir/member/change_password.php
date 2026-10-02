<?php

session_start();

require_once "../includes/db.php";


// Make sure member is logged in
if (
    !isset($_SESSION["member_logged_in"]) ||
    $_SESSION["member_logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


$member_id = (int)$_SESSION["member_id"];

$error = "";
$success = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    // Check empty fields
    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please fill in all fields.";

    }

    // Check new password length
    elseif (strlen($new_password) < 8) {

        $error = "Your new password must be at least 8 characters long.";

    }

    // Check password confirmation
    elseif ($new_password !== $confirm_password) {

        $error = "The new passwords do not match.";

    }

    else {

        // Get current password
        $stmt = $pdo->prepare("
            SELECT password_hash
            FROM member_accounts
            WHERE member_id = ?
            AND status = 'Active'
            LIMIT 1
        ");

        $stmt->execute([$member_id]);

        $account = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$account) {

            $error = "Your member account could not be found.";

        }

        elseif (
            !password_verify(
                $current_password,
                $account["password_hash"]
            )
        ) {

            $error = "Your current password is incorrect.";

        }

        else {

            // Create secure password hash
            $new_password_hash = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


            // Update password
            $stmt = $pdo->prepare("
                UPDATE member_accounts
                SET password_hash = ?
                WHERE member_id = ?
            ");

            $stmt->execute([
                $new_password_hash,
                $member_id
            ]);


            $success = "Your password has been changed successfully.";
        }
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

    <title>Change Password - NACGSS Mass Choir</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .password-container {

            max-width: 500px;

            margin: 50px auto;

            padding: 30px;

            background: #ffffff;

            border-radius: 12px;

            box-shadow:
                0 5px 25px rgba(0,0,0,0.08);

        }


        .password-container h2 {

            text-align: center;

            margin-bottom: 10px;

        }


        .subtitle {

            text-align: center;

            color: #666;

            margin-bottom: 25px;

        }


        .form-group {

            margin-bottom: 18px;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-weight: 600;

        }


        .form-group input {

            width: 100%;

            padding: 12px;

            box-sizing: border-box;

            border: 1px solid #ccc;

            border-radius: 7px;

        }


        .submit-btn {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 7px;

            cursor: pointer;

            font-size: 16px;

            font-weight: bold;

        }


        .error {

            background: #ffe5e5;

            color: #b00020;

            padding: 12px;

            border-radius: 7px;

            margin-bottom: 20px;

        }


        .success {

            background: #e5f7e5;

            color: #187a18;

            padding: 12px;

            border-radius: 7px;

            margin-bottom: 20px;

        }


        .back-link {

            display: block;

            text-align: center;

            margin-top: 20px;

            text-decoration: none;

        }

    </style>

</head>


<body>


<div class="password-container">

    <h2>🔐 Change Password</h2>

    <p class="subtitle">
        Keep your choir account secure.
    </p>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($success !== ""): ?>

        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <div class="form-group">

            <label for="current_password">
                Current Password
            </label>

            <input
                type="password"
                id="current_password"
                name="current_password"
                required
            >

        </div>


        <div class="form-group">

            <label for="new_password">
                New Password
            </label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                minlength="8"
                required
                placeholder="Minimum 8 characters"
            >

        </div>


        <div class="form-group">

            <label for="confirm_password">
                Confirm New Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

        </div>


        <button
            type="submit"
            class="submit-btn"
        >
            Change Password
        </button>

    </form>


    <a
        href="dashboard.php"
        class="back-link"
    >
        ← Back to Dashboard
    </a>

</div>


</body>

</html>