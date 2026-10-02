
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

// Save a new attendance session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid form submission. Please refresh and try again.';
    } else {
        $title = trim($_POST['meeting_title'] ?? '');
        $date = trim($_POST['meeting_date'] ?? '');
        $statuses = $_POST['attendance'] ?? [];

        $dateObject = DateTime::createFromFormat('Y-m-d', $date);
        $validDate = $dateObject &&
            $dateObject->format('Y-m-d') === $date;

        if ($title === '') {
            $error = 'Please enter the gathering title.';
        } elseif (!$validDate) {
            $error = 'Please enter a valid gathering date.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO meetings (meeting_title, meeting_date)
                     VALUES (?, ?)"
                );
                $stmt->execute([$title, $date]);

                $meetingId = $pdo->lastInsertId();

                $memberStmt = $pdo->query("SELECT id FROM members");
                $members = $memberStmt->fetchAll(PDO::FETCH_COLUMN);

                $insertStmt = $pdo->prepare(
                    "INSERT INTO attendance (meeting_id, member_id, status)
                     VALUES (?, ?, ?)"
                );

                foreach ($members as $memberId) {
                    $status = $statuses[$memberId] ?? 'Absent';

                    if (!in_array($status, ['Present', 'Absent', 'Excused'], true)) {
                        $status = 'Absent';
                    }

                    $insertStmt->execute([
                        $meetingId,
                        $memberId,
                        $status
                    ]);
                }

                $pdo->commit();
                $message = 'Attendance has been saved successfully.';

                // Refresh token after successful submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'Unable to save attendance. Please try again.';
            }
        }
    }
}

// Get registered members
$stmt = $pdo->query(
    "SELECT id, full_name, voice_part
     FROM members
     ORDER BY full_name"
);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get previous attendance sessions
$stmt = $pdo->query(
    "SELECT
        meetings.id,
        meetings.meeting_title,
        meetings.meeting_date,
        COUNT(attendance.id) AS marked_count,
        SUM(attendance.status = 'Present') AS present_count
     FROM meetings
     LEFT JOIN attendance ON attendance.meeting_id = meetings.id
     GROUP BY meetings.id
     ORDER BY meetings.meeting_date DESC, meetings.id DESC"
);
$previousMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | NACGSS Mass Choir</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .attendance-page {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }

        .attendance-section {
            background: white;
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        .attendance-section input,
        .attendance-section select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .attendance-section input[type="text"],
        .attendance-section input[type="date"] {
            width: 100%;
            box-sizing: border-box;
        }

        .member-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr;
            gap: 12px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .member-row select {
            width: 100%;
        }

        .save-button {
            margin-top: 20px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            background: #14532d;
            color: white;
            cursor: pointer;
        }

        .notice {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .success {
            background: #e7f7ec;
            color: #14532d;
        }

        .error {
            background: #fde8e8;
            color: #991b1b;
        }

        @media (max-width: 650px) {
            .member-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }
        }
    </style>
</head>

<body>
<div class="attendance-page">

    <h1>NACGSS Mass Choir</h1>
    <h2>Attendance Management</h2>

    <p><a href="dashboard.php">← Back to Dashboard</a></p>

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

    <div class="attendance-section">
        <h3>Record Attendance</h3>

        <?php if (!$members): ?>
            <p>No members are registered yet. Add members before recording attendance.</p>
        <?php else: ?>

            <form method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                >

                <label for="meeting_title">Gathering title</label><br>
                <input
                    type="text"
                    id="meeting_title"
                    name="meeting_title"
                    placeholder="e.g. Thursday Rehearsal"
                    required
                >

                <br><br>

                <label for="meeting_date">Gathering date</label><br>
                <input
                    type="date"
                    id="meeting_date"
                    name="meeting_date"
                    required
                >

                <hr style="margin: 25px 0;">

                <div class="member-row">
                    <strong>Member</strong>
                    <strong>Voice Part</strong>
                    <strong>Attendance Status</strong>
                </div>

                <?php foreach ($members as $member): ?>
                    <div class="member-row">
                        <span>
                            <?= htmlspecialchars($member['full_name']) ?>
                        </span>

                        <span>
                            <?= htmlspecialchars($member['voice_part']) ?>
                        </span>

                        <select name="attendance[<?= (int)$member['id'] ?>]">
                            <option value="Present">Present</option>
                            <option value="Absent" selected>Absent</option>
                            <option value="Excused">Excused</option>
                        </select>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="save-button">
                    Save Attendance
                </button>
            </form>

        <?php endif; ?>
    </div>

    <div class="attendance-section">
        <h3>Previous Attendance Records</h3>

        <?php if (!$previousMeetings): ?>
            <p>No attendance sessions have been recorded yet.</p>
        <?php else: ?>

            <?php foreach ($previousMeetings as $meeting): ?>
                <div style="padding: 12px 0; border-bottom: 1px solid #eee;">
                    <strong>
                        <?= htmlspecialchars($meeting['meeting_title']) ?>
                    </strong>
                    <br>

                    Date:
                    <?= htmlspecialchars($meeting['meeting_date']) ?>
                    <br>

                    Present:
                    <?= (int)$meeting['present_count'] ?>
                    /
                    <?= (int)$meeting['marked_count'] ?>
                    members
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</div>
</body>
</html>
