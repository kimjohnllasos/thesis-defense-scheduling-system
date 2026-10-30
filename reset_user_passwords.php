<?php
require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    );

    $newPassword = $_POST['new_password'] ?? '';

    if (!$userId || strlen($newPassword) < 8) {
        $error = "Select a user and enter a password with at least 8 characters.";
    } else {
        $passwordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            "UPDATE users SET password = ? WHERE user_id = ?"
        );

        $stmt->bind_param("si", $passwordHash, $userId);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $message = "Password updated successfully.";
        } else {
            $error = "Unable to update the password.";
        }
    }
}

$users = $conn->query(
    "SELECT user_id, name, username, role
     FROM users
     ORDER BY role, name"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset User Passwords</title>
</head>
<body>

<h2>Reset User Passwords</h2>

<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Select User:</label>

    <select name="user_id" required>
        <option value="">Select an account</option>

        <?php while ($user = $users->fetch_assoc()): ?>
            <option value="<?= (int) $user['user_id'] ?>">
                <?= htmlspecialchars($user['name']) ?>
                (<?= htmlspecialchars($user['username']) ?> -
                <?= htmlspecialchars($user['role']) ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <br><br>

    <label>New Password:</label>
    <input type="password" name="new_password"
           minlength="8" required>

    <br><br>

    <button type="submit">Reset Password</button>
</form>

</body>
</html>
