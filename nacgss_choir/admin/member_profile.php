<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$member_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$member_id) {
    die('Invalid member.');
}

/*
|--------------------------------------------------------------------------
| Get member information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        member_code,
        full_name,
        email,
        phone,
        voice_part,
        fellowship,
        created_at
     FROM members
     WHERE id = ?"
);

$stmt->execute([$member_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    die('Member not found.');
}

/*
|--------------------------------------------------------------------------
| Attendance statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Present') AS present,
        SUM(status = 'Absent') AS absent,
        SUM(status = 'Excused') AS excused
     FROM attendance
     WHERE member_id = ?"
);

$stmt->execute([$member_id]);

$attendance_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_attendance = (int)($attendance_stats['total'] ?? 0);
$present = (int)($attendance_stats['present'] ?? 0);
$absent = (int)($attendance_stats['absent'] ?? 0);
$excused = (int)($attendance_stats['excused'] ?? 0);

$attendance_percentage = $total_attendance > 0
    ? ($present / $total_attendance) * 100
    : 0;

/*
|--------------------------------------------------------------------------
| Attendance history
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        meetings.meeting_title,
        meetings.meeting_date,
        attendance.status
     FROM attendance
     INNER JOIN meetings
        ON attendance.meeting_id = meetings.id
     WHERE attendance.member_id = ?
     ORDER BY meetings.meeting_date DESC"
);

$stmt->execute([$member_id]);

$attendance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Member feedback
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        performance_rating,
        comments,
        created_at
     FROM feedback
     WHERE member_id = ?
     ORDER BY created_at DESC"
);

$stmt->execute([$member_id]);

$feedback_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Member Profile | NACGSS Mass Choir
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .profile-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .profile-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow:
                0 3px 12px rgba(0,0,0,.06);
            margin-bottom: 25px;
        }

        .profile-header h1 {
            margin-bottom: 5px;
        }

        .member-code {
            color: #666;
        }

        .info-grid {
            display: grid;
            grid-template-columns:
                repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .info-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: bold;
        }

        .stats {
            display: grid;
            grid-template-columns:
                repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: white;
            padding: 18px;
            text-align: center;
            border-radius: 10px;
            box-shadow:
                0 3px 10px rgba(0,0,0,.06);
        }

        .stat-number {
            font-size: 25px;
            font-weight: bold;
            margin-top: 8px;
        }

        .section {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow:
                0 3px 10px rgba(0,0,0,.05);
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

        th {
            background: #f3f4f6;
        }

        .present {
            color: #15803d;
            font-weight: bold;
        }

        .absent {
            color: #b91c1c;
            font-weight: bold;
        }

        .excused {
            color: #a16207;
            font-weight: bold;
        }

        .rating {
            font-size: 20px;
            font-weight: bold;
        }

        @media (max-width: 800px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 500px) {

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="profile-container">

    <p>
        <a href="members.php">
            ← Back to Members
        </a>
    </p>

    <!-- MEMBER HEADER -->

    <div class="profile-header">

        <h1>
            <?= htmlspecialchars(
                $member['full_name']
            ) ?>
        </h1>

        <div class="member-code">

            Member Code:
            <strong>
                <?= htmlspecialchars(
                    $member['member_code']
                ) ?>
            </strong>

        </div>

        <div class="info-grid">

            <div class="info-box">

                <div class="info-label">
                    Email
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $member['email']
                    ) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    Phone
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $member['phone']
                    ) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    Voice Part
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $member['voice_part']
                    ) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    Fellowship / Church
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $member['fellowship']
                    ) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    Registered
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $member['created_at']
                    ) ?>
                </div>

            </div>

        </div>

    </div>

    <!-- ATTENDANCE STATISTICS -->

    <div class="stats">

        <div class="stat-box">

            <div>
                Total Meetings
            </div>

            <div class="stat-number">
                <?= $total_attendance ?>
            </div>

        </div>

        <div class="stat-box">

            <div>
                Present
            </div>

            <div class="stat-number present">
                <?= $present ?>
            </div>

        </div>

        <div class="stat-box">

            <div>
                Absent
            </div>

            <div class="stat-number absent">
                <?= $absent ?>
            </div>

        </div>

        <div class="stat-box">

            <div>
                Excused
            </div>

            <div class="stat-number excused">
                <?= $excused ?>
            </div>

        </div>

        <div class="stat-box">

            <div>
                Attendance Rate
            </div>

            <div class="stat-number">
                <?= number_format(
                    $attendance_percentage,
                    1
                ) ?>%
            </div>

        </div>

    </div>

    <!-- ATTENDANCE HISTORY -->

    <div class="section">

        <h2>
            Attendance History
        </h2>

        <?php if ($attendance_history): ?>

            <div style="overflow-x:auto;">

                <table>

                    <thead>

                    <tr>

                        <th>
                            Gathering
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach (
                        $attendance_history
                        as $record
                    ): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $record['meeting_title']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record['meeting_date']
                                ) ?>
                            </td>

                            <td>

                                <?php if (
                                    $record['status']
                                    === 'Present'
                                ): ?>

                                    <span class="present">
                                        Present
                                    </span>

                                <?php elseif (
                                    $record['status']
                                    === 'Absent'
                                ): ?>

                                    <span class="absent">
                                        Absent
                                    </span>

                                <?php else: ?>

                                    <span class="excused">
                                        Excused
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p>
                No attendance records yet.
            </p>

        <?php endif; ?>

    </div>

    <!-- FEEDBACK HISTORY -->

    <div class="section">

        <h2>
            Performance Feedback
        </h2>

        <?php if ($feedback_history): ?>

            <?php foreach (
                $feedback_history
                as $feedback
            ): ?>

                <div
                    style="
                        padding: 15px 0;
                        border-bottom: 1px solid #eee;
                    "
                >

                    <div class="rating">

                        Rating:
                        <?= (int)$feedback[
                            'performance_rating'
                        ] ?>
                        / 5

                    </div>

                    <p>

                        <?= htmlspecialchars(
                            $feedback['comments']
                            ?? 'No comment provided.'
                        ) ?>

                    </p>

                    <small>

                        <?= htmlspecialchars(
                            $feedback['created_at']
                        ) ?>

                    </small>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                This member has not submitted
                any performance feedback.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>

</html>