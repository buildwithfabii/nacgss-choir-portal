
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Total members
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM members
");

$total_members = (int)$stmt->fetchColumn();


// Voice part statistics
$stmt = $pdo->query("
    SELECT voice_part, COUNT(*) AS total
    FROM members
    GROUP BY voice_part
");

$voice_stats = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $voice_stats[$row["voice_part"]] = (int)$row["total"];
}


// Total feedback
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM feedback
");

$total_feedback = (int)$stmt->fetchColumn();


// Average performance rating
$stmt = $pdo->query("
    SELECT AVG(performance_rating)
    FROM feedback
");

$average_rating = $stmt->fetchColumn();

$average_rating = $average_rating !== null
    ? round((float)$average_rating, 1)
    : 0;


// Total meetings
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM meetings
");

$total_meetings = (int)$stmt->fetchColumn();
// Total registered members
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM members");
$total_members = $stmt->fetch()['total'];

// Members by voice part
$stmt = $pdo->query(
    "SELECT voice_part, COUNT(*) AS total
     FROM members
     GROUP BY voice_part
     ORDER BY voice_part"
);
$voice_parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Average performance rating
$stmt = $pdo->query(
    "SELECT AVG(performance_rating) AS average_rating
     FROM feedback"
);
$average_rating = $stmt->fetch()['average_rating'];

// Total feedback and rating breakdown
$stmt = $pdo->query(
    "SELECT performance_rating, COUNT(*) AS total
     FROM feedback
     GROUP BY performance_rating"
);
$rating_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize all ratings so ratings with zero submissions appear
$rating_counts = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0
];

foreach ($rating_results as $rating) {
    $rating_counts[(int)$rating['performance_rating']] =
        (int)$rating['total'];
}

$total_feedback = array_sum($rating_counts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | NACGSS Mass Choir</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <style>
        .analytics-box {
            margin-top: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .rating-row {
            margin: 18px 0;
        }

        .rating-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
        }

        .rating-track {
            width: 100%;
            height: 16px;
            background: #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
        }

        .rating-bar {
            height: 100%;
            background: #15803d;
            border-radius: 20px;
        }

        .dashboard-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
        }

        .dashboard-links a {
            display: inline-block;
            padding: 10px 16px;
            background: #14532d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .admin-nav {
    background: #1f2937;
    color: #ffffff;
    padding: 15px 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.admin-brand {
    font-size: 18px;
    font-weight: 700;
}

.admin-links {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.admin-links a {
    color: #ffffff;
    text-decoration: none;
    padding: 9px 12px;
    border-radius: 6px;
    font-size: 14px;
    transition: 0.3s ease;
}

.admin-links a:hover {
    background: rgba(255, 255, 255, 0.15);
}

@media (max-width: 700px) {

    .admin-nav {
        flex-direction: column;
        align-items: stretch;
    }

    .admin-links {
        flex-direction: column;
        align-items: stretch;
    }

    .admin-links a {
        text-align: center;
    }

}

        .dashboard-links a:hover {
            background: #166534;
        }
        .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 25px 0;
}

.stat-card {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-icon {
    font-size: 32px;
}

.stat-info h3 {
    margin: 0;
    font-size: 26px;
}

.stat-info p {
    margin: 5px 0 0;
    color: #777;
    font-size: 14px;
}


@media (max-width: 1000px) {

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}


@media (max-width: 600px) {

    .stats-grid {
        grid-template-columns: 1fr;
    }

}
    </style>
</head>

<body>
    <?php include "includes/nav.php"; ?>

<div class="container">
    <div class="registration-card">

        <h1>NACGSS Mass Choir</h1>
        <h2>Admin Dashboard</h2>

        <p>
            Welcome,
            <strong>
                <?= htmlspecialchars($_SESSION['admin_name']) ?>
            </strong>
        </p>

        <hr style="margin: 25px 0;">

        <h3>Total Registered Members</h3>

        <p style="font-size: 40px; font-weight: bold;">
            <?= (int)$total_members ?>
        </p>

        <hr style="margin: 25px 0;">

        <h3>Members by Voice Part</h3>

        <?php if ($voice_parts): ?>
            <?php foreach ($voice_parts as $voice): ?>
                <p>
                    <strong>
                        <?= htmlspecialchars($voice['voice_part']) ?>
                    </strong>:
                    <?= (int)$voice['total'] ?>
                </p>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No members registered yet.</p>
        <?php endif; ?>

        <hr style="margin: 25px 0;">

        <h3>Average Choir Performance Rating</h3>

        <p style="font-size: 30px; font-weight: bold;">
            <?php
            if ($average_rating !== null) {
                echo number_format((float)$average_rating, 1) . ' / 5';
            } else {
                echo 'No ratings yet';
            }
            ?>
        </p>

        <hr style="margin: 25px 0;">

        <h3>Feedback Analytics</h3>

        <div class="analytics-box">

            <p>
                <strong>Total feedback received:</strong>
                <?= $total_feedback ?>
            </p>

            <?php foreach ([5, 4, 3, 2, 1] as $star): ?>

                <?php
                $count = $rating_counts[$star];
                $percentage = $total_feedback > 0
                    ? ($count / $total_feedback) * 100
                    : 0;
                ?>
                <div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <h3><?= $total_members ?></h3>
            <p>Total Members</p>
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-icon">🎤</div>
        <div class="stat-info">
            <h3><?= $voice_stats["Soprano"] ?? 0 ?></h3>
            <p>Soprano</p>
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-icon">🎵</div>
        <div class="stat-info">
            <h3><?= $voice_stats["Alto"] ?? 0 ?></h3>
            <p>Alto</p>
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-icon">🎶</div>
        <div class="stat-info">
            <h3><?= $voice_stats["Tenor"] ?? 0 ?></h3>
            <p>Tenor</p>
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-icon">🎼</div>
        <div class="stat-info">
            <h3><?= $voice_stats["Bass"] ?? 0 ?></h3>
            <p>Bass</p>
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-info">
            <h3><?= $average_rating ?>/5</h3>
            <p>Average Rating</p>
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
            <h3><?= $total_feedback ?></h3>
            <p>Total Feedback</p>
        </div>
    </div>


    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-info">
            <h3><?= $total_meetings ?></h3>
            <p>Total Meetings</p>
        </div>
    </div>

</div>

                <div class="rating-row">

                    <div class="rating-label">
                        <span>
                            <strong><?= $star ?> star<?= $star > 1 ? 's' : '' ?></strong>
                        </span>

                        <span>
                            <?= $count ?>
                            (<?= number_format($percentage, 1) ?>%)
                        </span>
                    </div>

                    <div class="rating-track">
                        <div
                            class="rating-bar"
                            style="width: <?= $percentage ?>%;">
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="dashboard-links">
            <a href="members.php">Manage Members</a>
            <a href="feedback.php">View Choir Feedback</a>
            <a href="logout.php">Logout</a>
            <a href="attendance.php">Manage Attendance</a>
        </div>

    </div>
</div>

</body>
</html>
