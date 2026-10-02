<?php

session_start();

require_once "../includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {

        $stmt = $pdo->prepare("
            SELECT
                member_accounts.id AS account_id,
                member_accounts.member_id,
                member_accounts.password_hash,
                member_accounts.status,
                members.full_name,
                members.email
            FROM member_accounts
            INNER JOIN members
                ON member_accounts.member_id = members.id
            WHERE members.email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {

            $error = "Invalid email or password.";

        } elseif ($account["status"] !== "Active") {

            $error = "Your account has been disabled. Please contact an administrator.";

        } elseif (!password_verify($password, $account["password_hash"])) {

            $error = "Invalid email or password.";

        } else {

            session_regenerate_id(true);

            $_SESSION["member_logged_in"] = true;
            $_SESSION["member_id"] = (int)$account["member_id"];
            $_SESSION["member_name"] = $account["full_name"];
            $_SESSION["member_email"] = $account["email"];

            // Update last login time
            $update = $pdo->prepare("
                UPDATE member_accounts
                SET last_login_at = NOW()
                WHERE id = ?
            ");

            $update->execute([$account["account_id"]]);

            header("Location: dashboard.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Member Login - NACGSS Mass Choir</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .login-container {
            max-width: 450px;
            margin: 70px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        .login-subtitle {
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
            border: 1px solid #ccc;
            border-radius: 7px;
            box-sizing: border-box;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }

        .error-message {
            background: #ffe5e5;
            color: #b00020;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="login-container">

    <h2>🎶 NACGSS Mass Choir</h2>

    <p class="login-subtitle">
        Member Login
    </p>

    <?php if ($error !== ""): ?>

        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                required
                value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
            >
        </div>

        <button type="submit" class="login-btn">
            Login
        </button>

    </form>

    <div class="back-link">
        <a href="../index.php">← Back to Home</a>
    </div>

</div>

</body>
</html>