<?php
require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if ($username === '' || strlen($newPassword) < 8) {
        $message = 'Enter your Admin username and a password of at least 8 characters.';
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id FROM users
             WHERE username = ? AND role = 'Admin'
             LIMIT 1"
        );

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$admin) {
            $message = 'No Admin account was found with that username.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "UPDATE users SET password = ? WHERE user_id = ?"
            );

            $stmt->bind_param('si', $hash, $admin['user_id']);
            $stmt->execute();
            $stmt->close();

            $message = 'Password reset successfully! Delete reset_admin.php, then log in with your new password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Admin Password</title>
</head>
<body>
    <h2>Reset Admin Password</h2>

    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

    <form method="POST">
        <label>Admin username</label><br>
        <input type="text" name="username" required><br><br>

        <label>New password</label><br>
        <input type="password" name="new_password"
               minlength="8" required><br><br>

        <button type="submit">Reset Password</button>
    </form>
</body>
</html>