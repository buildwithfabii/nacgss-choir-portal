
<?php
require_once __DIR__ . '/includes/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $email = trim($_POST['email'] ?? '');
    $comments = trim($_POST['comments'] ?? '');

    if (!$rating || $rating < 1 || $rating > 5) {
        $error = 'Please select a rating from 1 to 5.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address or leave it blank.';
    } else {
        try {
            $memberId = null;

            // Link feedback to a registered member when their email is provided.
            if ($email !== '') {
                $stmt = $pdo->prepare(
                    "SELECT id FROM members WHERE email = ? LIMIT 1"
                );
                $stmt->execute([$email]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($member) {
                    $memberId = $member['id'];
                }
            }

            $stmt = $pdo->prepare(
                "INSERT INTO feedback
                 (member_id, performance_rating, comments)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $memberId,
                $rating,
                $comments !== '' ? $comments : null
            ]);

            $message = 'Thank you! Your feedback has been submitted.';
        } catch (PDOException $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choir Performance Feedback</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .feedback-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,.08);
        }

        .feedback-container h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        .feedback-container p {
            text-align: center;
            color: #666;
        }

        .feedback-container label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
        }

        .feedback-container input,
        .feedback-container textarea,
        .feedback-container select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .feedback-container button {
            margin-top: 20px;
            width: 100%;
            padding: 13px;
            background: #14532d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .notice {
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .success { background: #e7f7ec; color: #14532d; }
        .error { background: #fde8e8; color: #991b1b; }
    </style>
</head>
<body>

<div class="feedback-container">
    <h1>Choir Performance Feedback</h1>
    <p>Share your thoughts and help us improve in excellence and worship.</p>

    <?php if ($message): ?>
        <div class="notice success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="email">
            Registered member email (optional)
        </label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Leave blank to submit anonymously"
        >

        <label for="rating">Rate the choir performance</label>
        <select id="rating" name="rating" required>
            <option value="">Choose a rating</option>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Very Good</option>
            <option value="3">3 - Good</option>
            <option value="2">2 - Fair</option>
            <option value="1">1 - Needs Improvement</option>
        </select>

        <label for="comments">Comments (optional)</label>
        <textarea
            id="comments"
            name="comments"
            rows="5"
            placeholder="What did you appreciate? What can we improve?"
        ></textarea>

        <button type="submit">Submit Feedback</button>
    </form>
</div>

</body>
</html>
