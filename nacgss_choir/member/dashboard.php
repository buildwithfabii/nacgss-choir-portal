<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["member_logged_in"]) || $_SESSION["member_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$member_id = (int)$_SESSION["member_id"];

// Get member information
$stmt = $pdo->prepare("
    SELECT
        id,
        member_code,
        full_name,
        email,
        phone,
        voice_part,
        fellowship,
        created_at
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


// Attendance statistics
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Present') AS present,
        SUM(status = 'Absent') AS absent,
        SUM(status = 'Excused') AS excused
    FROM attendance
    WHERE member_id = ?
");

$stmt->execute([$member_id]);

$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

$total_attendance = (int)($attendance["total"] ?? 0);
$present = (int)($attendance["present"] ?? 0);
$absent = (int)($attendance["absent"] ?? 0);
$excused = (int)($attendance["excused"] ?? 0);

$attendance_rate = $total_attendance > 0
    ? round(($present / $total_attendance) * 100, 1)
    : 0;


// Attendance history
$stmt = $pdo->prepare("
    SELECT
        meetings.meeting_title,
        meetings.meeting_date,
        attendance.status
    FROM attendance

    INNER JOIN meetings
        ON attendance.meeting_id = meetings.id

    WHERE attendance.member_id = ?

    ORDER BY meetings.meeting_date DESC
");

$stmt->execute([$member_id]);

$attendance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Feedback history
$stmt = $pdo->prepare("
    SELECT
        performance_rating,
        comments,
        created_at
    FROM feedback

    WHERE member_id = ?

    ORDER BY created_at DESC
");

$stmt->execute([$member_id]);

$feedback_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Dashboard - NACGSS Mass Choir</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        .dashboard-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .welcome-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .welcome-box h2 {
            margin-top: 0;
        }

        .logout-btn {
            float: right;
            text-decoration: none;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
        }

        .profile-card strong {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-top: 8px;
        }

        .section-box {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .present {
            color: green;
            font-weight: bold;
        }

        .absent {
            color: red;
            font-weight: bold;
        }

        .excused {
            color: orange;
            font-weight: bold;
        }

        .rating {
            font-weight: bold;
        }
        .member-nav {
    background: #ffffff;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 3px 15px rgba(0,0,0,0.06);
}

.nav-brand {
    font-weight: bold;
    font-size: 18px;
}

.nav-links {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.nav-links a {
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 6px;
}

.nav-links a:hover {
    background: #f0f0f0;
}

@media (max-width: 700px) {

    .member-nav {
        flex-direction: column;
        align-items: stretch;
    }

    .nav-links {
        flex-direction: column;
    }

    .nav-links a {
        text-align: center;
    }
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
.attendance-progress {
    margin-top: 20px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 18px;
    background: #e5e5e5;
    border-radius: 20px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #28a745;
    border-radius: 20px;
    transition: width 0.5s ease;
}
.edit-profile-btn {
    display: inline-block;
    margin-top: 15px;
    padding: 11px 18px;
    background: #007bff;
    color: #ffffff;
    text-decoration: none;
    border-radius: 7px;
    font-weight: 600;
    transition: 0.3s ease;
}
.submit-feedback-btn {
    display: inline-block;
    margin-top: 15px;
    padding: 11px 18px;
    background: #28a745;
    color: #ffffff;
    text-decoration: none;
    border-radius: 7px;
    font-weight: 600;
    transition: 0.3s ease;
}

.submit-feedback-btn:hover {
    background: #218838;
}
.edit-profile-btn:hover {
    background: #0056b3;
}

    </style>

</head>

<body>
    <?php include "includes/nav.php"; ?>

<div class="dashboard-container">
    <nav class="member-nav">

    <div class="nav-brand">
        🎶 NACGSS Mass Choir
    </div>

    <div class="nav-links">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="#profile">
            My Profile
        </a>

        <a href="#attendance">
            Attendance
        </a>

        <a href="#feedback">
            Feedback
        </a>

        <a href="change_password.php">
            Change Password
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</nav>

    <div class="welcome-box">

         <a
            href="change_password.php"
            style="margin-right: 15px;"
        >
            Change Password
        </a>

        <a class="logout-btn" href="logout.php">
            Logout
        </a>

        <h2>
            Welcome, <?= htmlspecialchars($member["full_name"]) ?> 👋
        </h2>

        <p>
            This is your NACGSS Mass Choir member dashboard.
        </p>

    </div>


    <!-- PERSONAL INFORMATION -->

     <a href="edit_profile.php" class="edit-profile-btn">
        ✏️ Edit My Profile
    </a>

        <div class="profile-grid">

            <div class="profile-card">
                <strong>Member Code</strong>
                <?= htmlspecialchars($member["member_code"]) ?>
            </div>

            <div class="profile-card">
                <strong>Full Name</strong>
                <?= htmlspecialchars($member["full_name"]) ?>
            </div>

            <div class="profile-card">
                <strong>Email</strong>
                <?= htmlspecialchars($member["email"]) ?>
            </div>

            <div class="profile-card">
                <strong>Phone</strong>
                <?= htmlspecialchars($member["phone"]) ?>
            </div>

            <div class="profile-card">
                <strong>Voice Part</strong>
                <?= htmlspecialchars($member["voice_part"]) ?>
            </div>

            <div class="profile-card">
                <strong>Fellowship / Church</strong>
                <?= htmlspecialchars($member["fellowship"]) ?>
            </div>

        </div>

    </div>


    <!-- ATTENDANCE SUMMARY -->

   <div class="section-box" id="attendance">

    <h2>My Attendance</h2>
        <div class="stats-grid">

            <div class="stat-card">
                Total Meetings
                <div class="stat-number">
                    <?= $total_attendance ?>
                </div>
            </div>

            <div class="stat-card">
                Present
                <div class="stat-number">
                    <?= $present ?>
                </div>
            </div>

            <div class="stat-card">
                Absent
                <div class="stat-number">
                    <?= $absent ?>
                </div>
            </div>

            <div class="stat-card">
                Excused
                <div class="stat-number">
                    <?= $excused ?>
                </div>
            </div>

            <div class="stat-card">
                Attendance Rate
                <div class="stat-number">
                    <?= $attendance_rate ?>%
                </div>
            </div>
            <div class="attendance-progress">

    <div class="progress-label">

        <span>
            Attendance Progress
        </span>

        <span>
            <?= $attendance_rate ?>%
        </span>

    </div>

   <div class="progress-bar">

    <div
        class="progress-fill"
        style="width: <?= $attendance_rate ?>%;"
    ></div>

</div>

</div>

        </div>

    </div>


    <!-- ATTENDANCE HISTORY -->

    <div class="section-box">

        <h2>Attendance History</h2>

        <?php if (count($attendance_history) > 0): ?>

            <table>

                <thead>

                    <tr>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($attendance_history as $row): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($row["meeting_title"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["meeting_date"]) ?>
                        </td>

                        <td class="<?= strtolower($row["status"]) ?>">
                            <?= htmlspecialchars($row["status"]) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <p>
                No attendance records available yet.
            </p>

        <?php endif; ?>

    </div>


    <!-- FEEDBACK -->

    <div class="section-box" id="feedback">
        <a href="submit_feedback.php" class="submit-feedback-btn">
    ⭐ Submit New Feedback
</a>

    <h2>My Feedback</h2>

        <?php if (count($feedback_history) > 0): ?>

            <table>

                <thead>

                    <tr>
                        <th>Rating</th>
                        <th>Comments</th>
                        <th>Date</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($feedback_history as $row): ?>

                    <tr>

                        <td class="rating">
                            <?= (int)$row["performance_rating"] ?>/5
                        </td>

                        <td>
                            <?= htmlspecialchars($row["comments"] ?: "No comment") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["created_at"]) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <p>
                No feedback records are linked to your account yet.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>

</html>