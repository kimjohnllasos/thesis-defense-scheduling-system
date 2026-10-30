<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

$error = '';
$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId || $userId < 1) {
    header('Location: admin_dashboard.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT user_id, name, username, role
     FROM users
     WHERE user_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if ($name === '' || $username === '') {
        $error = 'Name and username are required.';
    } else {
        $check = $conn->prepare(
            'SELECT user_id
             FROM users
             WHERE username = ? AND user_id != ?'
        );
        $check->bind_param('si', $username, $userId);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'That username is already registered.';
        } else {
            $update = $conn->prepare(
                'UPDATE users
                 SET name = ?, username = ?
                 WHERE user_id = ?'
            );
            $update->bind_param('ssi', $name, $username, $userId);
            $update->execute();

            header('Location: admin_dashboard.php');
            exit;
        }
    }

    $user['name'] = $name;
    $user['username'] = $username;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="admin_dashboard.css?v=3">
</head>
<body>
    <main class="main-content"
          style="margin-left:0; padding:30px; min-height:100vh;
                 background:#0B132B; color:#FFFFFF;">
        <div class="card"
             style="max-width:800px; margin:20px auto; background:#151E30;
                    border:1px solid #39405A; border-radius:10px; padding:25px;">

            <h2 style="color:#39FF88; margin-bottom:20px;">Edit User</h2>

            <?php if ($error !== ''): ?>
                <p style="color:#ff7777;">
                    <?= h($error) ?>
                </p>
            <?php endif; ?>

            <p>Role: <?= h($user['role']) ?></p>

            <form method="POST"
                  action="edit_user.php?id=<?= (int) $userId ?>">

                <p>
                    <label for="name">Full Name</label><br>
                    <input
                        id="name"
                        name="name"
                        maxlength="100"
                        value="<?= h($user['name']) ?>"
                        required
                    >
                </p>

                <p>
                    <label for="username">Username</label><br>
                    <input
                        id="username"
                        name="username"
                        maxlength="50"
                        value="<?= h($user['username']) ?>"
                        required
                    >
                </p>

                <button type="submit">Save Changes</button>
                <a href="admin_dashboard.php">Cancel</a>
            </form>
        </div>
    </main>
</body>
</html>
