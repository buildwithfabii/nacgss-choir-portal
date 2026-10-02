<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireAdmin();

$error = "";
$success = "";

$member_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$member_id) {
    die("Invalid member ID.");
}


// Get member
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


// Check if account already exists
$stmt = $pdo->prepare("
    SELECT id
    FROM member_accounts
    WHERE member_id = ?
    LIMIT 1
");

$stmt->execute([$member_id]);

$existing_account = $stmt->fetch(PDO::FETCH_ASSOC);


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($existing_account) {

        $error = "This member already has a login account.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters long.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO member_accounts
            (member_id, password_hash)
            VALUES (?, ?)
        ");

        $stmt->execute([
            $member_id,
            $password_hash
        ]);

        $success = "Member login account created successfully.";

        $existing_account = true;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Member Login</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        .container {
            max-width: 550px;
            margin: 50px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
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
            margin-top: 20px;
            text-align: center;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Create Member Login</h2>

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


    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($success): ?>

        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>

        <p>
            The member can now log in using:
        </p>

        <ul>
            <li>
                <strong>Email:</strong>
                <?= htmlspecialchars($member["email"]) ?>
            </li>

            <li>
                <strong>Password:</strong>
                The password you just created
            </li>
        </ul>

    <?php elseif (!$existing_account): ?>

        <form method="POST">

            <div class="form-group">

                <label for="password">
                    Create Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                    placeholder="Minimum 8 characters"
                >

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
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
                Create Login Account
            </button>

        </form>

    <?php else: ?>

        <div class="success">
            This member already has a login account.
        </div>

    <?php endif; ?>


    <a class="back" href="members.php">
        ← Back to Manage Members
    </a>

</div>

</body>

</html>