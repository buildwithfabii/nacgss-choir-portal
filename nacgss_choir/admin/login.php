<?php

require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = 'Please enter your email and password.';

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password_hash
             FROM admins
             WHERE email = ?"
        );

        $stmt->execute([$email]);

        $admin = $stmt->fetch();

        if ($admin && password_verify(
            $password,
            $admin['password_hash']
        )) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];

            header('Location: dashboard.php');
            exit;

        } else {

            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Login | NACGSS Mass Choir</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <div class="registration-card">

        <h1>NACGSS Mass Choir</h1>

        <h2>Administrator Login</h2>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >

            </div>

            <button type="submit">
                Login
            </button>

        </form>

    </div>

</div>

</body>

</html>