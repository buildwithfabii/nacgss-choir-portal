
<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("
    SELECT
        feedback.id,
        feedback.performance_rating,
        feedback.comments,
        feedback.created_at,
        members.full_name,
        members.email
    FROM feedback
    LEFT JOIN members ON feedback.member_id = members.id
    ORDER BY feedback.created_at DESC
");

$feedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$averageStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_feedback,
        AVG(performance_rating) AS average_rating
    FROM feedback
");

$stats = $averageStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choir Feedback</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .feedback-page {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .feedback-summary {
            background: #f0fdf4;
            padding: 18px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .feedback-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .feedback-card h3 {
            margin-top: 0;
        }

        .feedback-date {
            color: #777;
            font-size: 13px;
        }

        .feedback-comment {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>

<div class="feedback-page">
    <h1>Choir Performance Feedback</h1>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>

    <div class="feedback-summary">
        <strong>Total feedback:</strong>
        <?= (int) $stats['total_feedback'] ?>
        <br>

        <strong>Average rating:</strong>
        <?= $stats['average_rating'] !== null
            ? number_format((float) $stats['average_rating'], 1) . ' / 5'
            : 'No ratings yet' ?>
    </div>

    <?php if (!$feedbackList): ?>
        <p>No feedback has been submitted yet.</p>
    <?php endif; ?>

    <?php foreach ($feedbackList as $item): ?>
        <div class="feedback-card">
            <h3>
                Rating: <?= (int) $item['performance_rating'] ?> / 5
            </h3>

            <p>
                <strong>Submitted by:</strong>
                <?php if ($item['full_name']): ?>
                    <?= htmlspecialchars($item['full_name']) ?>
                    (<?= htmlspecialchars($item['email']) ?>)
                <?php else: ?>
                    Anonymous
                <?php endif; ?>
            </p>

            <p class="feedback-comment">
                <strong>Comment:</strong><br>
                <?= htmlspecialchars($item['comments'] ?? 'No comment provided') ?>
            </p>

            <div class="feedback-date">
                <?= htmlspecialchars($item['created_at']) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
