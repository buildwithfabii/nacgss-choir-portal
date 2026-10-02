<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$meeting_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$meeting_id) {
    die('Invalid attendance record.');
}

// Get meeting information
$stmt = $pdo->prepare(
    "SELECT id, meeting_title, meeting_date
     FROM meetings
     WHERE id = ?"
);

$stmt->execute([$meeting_id]);

$meeting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$meeting) {
    die('Attendance session not found.');
}

// Get attendance records
$stmt = $pdo->prepare(
    "SELECT
        members.full_name,
        members.email,
        members.phone,
        members.voice_part,
        attendance.status
     FROM attendance
     INNER JOIN members
        ON attendance.member_id = members.id
     WHERE attendance.meeting_id = ?
     ORDER BY members.full_name"
);

$stmt->execute([$meeting_id]);

$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total = count($attendance);

$present = 0;
$absent = 0;
$excused = 0;

foreach ($attendance as $record) {

    if ($record['status'] === 'Present') {
        $present++;
    }

    if ($record['status'] === 'Absent') {
        $absent++;
    }

    if ($record['status'] === 'Excused') {
        $excused++;
    }
}

$attendance_percentage = $total > 0
    ? ($present / $total) * 100
    : 0;

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
        Attendance Details | NACGSS Mass Choir
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .attendance-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;
            margin: 25px 0;
        }

        .stat-box {
            padding: 20px;
            background: white;
            border-radius: 10px;
            text-align: center;
            box-shadow:
                0 3px 10px rgba(0,0,0,.06);
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-top: 8px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .attendance-table th {
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

        @media (max-width: 700px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .attendance-table {
                font-size: 13px;
            }

            .attendance-table th,
            .attendance-table td {
                padding: 8px;
            }

        }

    </style>

</head>

<body>

<div class="attendance-container">

    <h1>NACGSS Mass Choir</h1>

    <h2>
        <?= htmlspecialchars($meeting['meeting_title']) ?>
    </h2>

    <p>
        Date:
        <strong>
    <a href="view_attendance.php?id=<?= (int)$meeting['id'] ?>">
        <?= htmlspecialchars($meeting['meeting_title']) ?>
    </a>
</strong>
    </p>

    <p>
        <a href="attendance.php">
            ← Back to Attendance
        </a>
    </p>

    <div class="stats">

        <div class="stat-box">

            <div>Total Members</div>

            <div class="stat-number">
                <?= $total ?>
            </div>

        </div>

        <div class="stat-box">

            <div>Present</div>

            <div class="stat-number present">
                <?= $present ?>
            </div>

        </div>

        <div class="stat-box">

            <div>Absent</div>

            <div class="stat-number absent">
                <?= $absent ?>
            </div>

        </div>

        <div class="stat-box">

            <div>Attendance Rate</div>

            <div class="stat-number">
                <?= number_format($attendance_percentage, 1) ?>%
            </div>

        </div>

    </div>

    <div class="registration-card">

        <h3>Attendance Details</h3>

        <?php if ($attendance): ?>

            <div style="overflow-x:auto;">

                <table class="attendance-table">

                    <thead>

                    <tr>

                        <th>#</th>

                        <th>Member Name</th>

                        <th>Voice Part</th>

                        <th>Email</th>

                        <th>Status</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($attendance as $index => $record): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record['full_name']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record['voice_part']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record['email']
                                ) ?>
                            </td>

                            <td>

                                <?php if (
                                    $record['status'] === 'Present'
                                ): ?>

                                    <span class="present">
                                        Present
                                    </span>

                                <?php elseif (
                                    $record['status'] === 'Absent'
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
                No attendance records were found.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>

</html>