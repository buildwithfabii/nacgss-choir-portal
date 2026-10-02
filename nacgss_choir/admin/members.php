
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$voice = trim($_GET['voice'] ?? '');

$allowedVoices = [
    'Soprano', 'Alto', 'Tenor', 'Bass', 'Other'
];

$sql = "SELECT id, member_code, full_name, email,
               phone, voice_part, fellowship, created_at
        FROM members
        WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        full_name LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
        OR member_code LIKE ?
        OR fellowship LIKE ?
    )";

    $term = "%$search%";
    $params = array_merge(
        $params,
        [$term, $term, $term, $term, $term]
    );
}

if (in_array($voice, $allowedVoices, true)) {
    $sql .= " AND voice_part = ?";
    $params[] = $voice;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Members | NACGSS Mass Choir</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <style>
        .table-wrap {
            overflow-x: auto;
            margin-top: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f2f5f7;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .search-form input,
        .search-form select {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
        }

        .search-form input {
            flex: 1;
            min-width: 180px;
        }

        .action-link {
            display: inline-block;
            margin: 3px;
            padding: 8px 12px;
            border-radius: 6px;
            background: #e8eef5;
            color: #123e35;
            text-decoration: none;
        }

        .delete-button {
            background: #a52828;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="registration-card">

        <h1>NACGSS Mass Choir</h1>
        <h2>Member Management</h2>

        <p>
            Welcome,
            <?= htmlspecialchars($_SESSION['admin_name']) ?>
        </p>

        <h3 style="margin-top:25px">
            Registered Members: <?= count($members) ?>
        </h3>

        <form method="GET" class="search-form">

            <input
                type="search"
                name="search"
                placeholder="Search name, email, phone..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <select name="voice">
                <option value="">All Voice Parts</option>

                <?php foreach ($allowedVoices as $v): ?>
                    <option
                        value="<?= htmlspecialchars($v) ?>"
                        <?= $voice === $v ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($v) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" style="width:auto">
                Search
            </button>

            <a class="action-link" href="members.php">
                Clear
            </a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Voice Part</th>
                        <th>Fellowship / Church</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($members): ?>

                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    $member['member_code'] ?? ''
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $member['full_name']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $member['email']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $member['phone']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $member['voice_part']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $member['fellowship']
                                ) ?>
                            </td>

                            <td>
                                <a
                                    class="action-link"
                                    
                                    href="edit_member.php?id=<?= (int)$member['id'] ?>"
                                >
                                    Edit
                                    
                                </a>
                               <a 
                               class="action-link"
                               href="member_profile.php?id=<?= (int)$member['id'] ?>">
                                    View Profile
                                </a>
                                <a 
                                class="action-link"
                                href="create_member_login.php?id=<?= (int)$member['id'] ?>">
                                    Create Login
                                </a>
                                <a 
                                class="action-link"
                                href="reset_member_password.php?id=<?= (int)$member['id'] ?>">
                                    Reset Password
                                </a>

                                <form
                                    method="POST"
                                    action="delete_member.php"
                                    style="display:inline"
                                    onsubmit="return confirm('Are you sure you want to delete this member?');"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$member['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="delete-button"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">
                            No members found.
                        </td>
                    </tr>

                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a class="back-link" href="dashboard.php">
            ← Back to Dashboard
        </a>

    </div>
</div>

</body>
</html>
