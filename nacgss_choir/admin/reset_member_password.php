<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireAdmin();

$error = "";
$success = "";

$member_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$member_id) {
    die("Invalid member ID.");
}


// Get member information
$stmt = $pdo->prepare("
    SELECT
        id,
        full_name,
        email,
        member_code
    FROM members
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$member_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    die("Member not found.");
}


// Check whether the member has an account
$stmt = $pdo->prepare("
    SELECT id
    FROM member_accounts
    WHERE member_id = ?
    LIMIT 1
");

$stmt->execute([$member_id]);

$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    $error = "This member does not have a login account yet.";
}


// Process password reset
if ($_SERVER["REQUEST_METHOD"] === "POST" && $account) {

    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    if ($new_password === "" || $confirm_password === "") {

        $error = "Please fill in both password fields.";

    } elseif (strlen($new_password) < 8) {

        $error = "Password must be at least 8 characters long.";

    } elseif ($new_password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        $password_hash = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE member_accounts
            SET password_hash = ?,
                status = 'Active'
            WHERE member_id = ?
        ");

        $stmt->execute([
            $password_hash,
            $member_id
        ]);

        $success = "Member password has been reset successfully.";
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

    <title>Reset Member Password</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .container {
            max-width: 550px;
            margin: 50px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        .member-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 7px;
        }

        button {
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

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
        }

    </style>

</head>


<body>

<div class="container">

    <h2>🔐 Reset Member Password</h2>


    <div class="member-info">

        <p>
            <strong>Name:</strong>
            <?= htmlspecialchars($member["full_name"]) ?>
        </p>

        <p>
            <strong>Member Code:</strong>
            <?= htmlspecialchars($member["member_code"]) ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?= htmlspecialchars($member["email"]) ?>
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($success !== ""): ?>

        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>

        <p>
            The member can now log in using the new password.
        </p>

    <?php endif; ?>


    <?php if ($account): ?>

        <form method="POST">

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
                    placeholder="Enter password again"
                >

            </div>


            <button type="submit">
                Reset Password
            </button>

        </form>

    <?php endif; ?>


    <a
        class="back"
        href="members.php"
    >
        ← Back to Manage Members
    </a>

</div>

</body>

</html>