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
| Get member information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT full_name
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
| Submit feedback
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = (int)($_POST["rating"] ?? 0);
    $comment = trim($_POST["comment"] ?? "");

    if ($rating < 1 || $rating > 5) {

        $error = "Please select a rating between 1 and 5.";

    } elseif ($comment === "") {

        $error = "Please enter your feedback.";

    } else {

        $stmt = $pdo->prepare("
    INSERT INTO feedback
    (member_id, performance_rating, comments)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $member_id,
    $rating,
    $comment
]);

        $message = "Thank you! Your feedback has been submitted successfully.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Submit Feedback - NACGSS Mass Choir</title>

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

        .feedback-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .page-title {
            text-align: center;
            margin-bottom: 8px;
        }

        .page-subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
        }

        .form-group textarea {
            min-height: 140px;
            resize: vertical;
        }

        .form-group select:focus,
        .form-group textarea:focus {
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

        .rating-info {
            color: #777;
            font-size: 14px;
            margin-top: 6px;
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

        .btn-submit {
            background: #007bff;
            color: #ffffff;
        }

        .btn-submit:hover {
            background: #0056b3;
        }

        .btn-cancel {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-cancel:hover {
            background: #545b62;
        }

        @media (max-width: 600px) {

            .page-container {
                margin: 20px auto;
                padding: 15px;
            }

            .feedback-card {
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

    <div class="feedback-card">

        <h1 class="page-title">
            🎶 Submit Choir Feedback
        </h1>

        <p class="page-subtitle">
            Hello <?= htmlspecialchars($member["full_name"]) ?>,
            share your experience with the Mass Choir.
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

                <label for="rating">
                    Rate the Choir Performance
                </label>

                <select
                    id="rating"
                    name="rating"
                    required
                >

                    <option value="">
                        Select a rating
                    </option>

                    <option value="5">
                        ⭐⭐⭐⭐⭐ — Excellent
                    </option>

                    <option value="4">
                        ⭐⭐⭐⭐ — Very Good
                    </option>

                    <option value="3">
                        ⭐⭐⭐ — Good
                    </option>

                    <option value="2">
                        ⭐⭐ — Fair
                    </option>

                    <option value="1">
                        ⭐ — Needs Improvement
                    </option>

                </select>

                <div class="rating-info">
                    Please give an honest rating based on your experience.
                </div>

            </div>


            <div class="form-group">

                <label for="comment">
                    Your Feedback
                </label>

                <textarea
                    id="comment"
                    name="comment"
                    placeholder="Tell us what you think about the choir performance, rehearsals, coordination, or areas that can be improved..."
                    required
                ></textarea>

            </div>


            <div class="button-group">

                <button
                    type="submit"
                    class="btn btn-submit"
                >
                    Submit Feedback
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