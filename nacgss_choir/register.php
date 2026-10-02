<?php
require_once __DIR__ . '/includes/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $voice_part = trim($_POST['voice_part'] ?? '');
    $fellowship = trim($_POST['fellowship'] ?? '');

    // Basic validation
    if (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
        empty($voice_part) ||
        empty($fellowship)
    ) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {

        try {

            // Check if email already exists
            $check = $pdo->prepare(
                "SELECT id FROM members WHERE email = ?"
            );

            $check->execute([$email]);

            if ($check->fetch()) {

                $error = 'This email address has already been registered.';

            } else {

                // Generate member code
                $member_code = 'NMC-' . date('Y') . '-' .
                    strtoupper(substr(uniqid(), -6));

                // Insert member
                $stmt = $pdo->prepare(
                    "INSERT INTO members
                    (member_code, full_name, email, phone, voice_part, fellowship)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $member_code,
                    $full_name,
                    $email,
                    $phone,
                    $voice_part,
                    $fellowship
                ]);

                $success =
                    'Registration successful! Your Member ID is ' .
                    htmlspecialchars($member_code);
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = 'Something went wrong. Please try again.';
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

    <title>Member Registration | NACGSS Mass Choir</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

</head>

<body>

<div class="container">

    <div class="registration-card">

        <h1>NACGSS Mass Choir</h1>

        <h2>Membership Registration</h2>

        <p class="intro">
            Please provide your information below to join
            the NACGSS Mass Choir database.
        </p>

        <?php if ($success): ?>

            <div class="success">
                <?= $success ?>
            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="full_name">
                    Full Name *
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address *
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    required
                >

            </div>


            <div class="form-group">

                <label for="phone">
                    Phone Number *
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="Enter your phone number"
                    required
                >

            </div>


            <div class="form-group">

                <label for="voice_part">
                    Voice Part *
                </label>

                <select
                    id="voice_part"
                    name="voice_part"
                    required
                >

                    <option value="">
                        Select your voice part
                    </option>

                    <option value="Soprano">
                        Soprano
                    </option>

                    <option value="Alto">
                        Alto
                    </option>

                    <option value="Tenor">
                        Tenor
                    </option>

                    <option value="Bass">
                        Bass
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="fellowship">
                    Fellowship / Church of Attendance *
                </label>

                <input
                    type="text"
                    id="fellowship"
                    name="fellowship"
                    placeholder="Enter your fellowship or church"
                    required
                >

            </div>


            <button type="submit">
                Submit Registration
            </button>

        </form>

    </div>

</div>

</body>

</html>