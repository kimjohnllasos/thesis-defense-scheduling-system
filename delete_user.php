<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php');
    exit;
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId || $userId < 1) {
    header('Location: admin_dashboard.php');
    exit;
}

// Prevent the logged-in administrator from deleting their own account.
if ($userId === (int) $_SESSION['user_id']) {
    header('Location: admin_dashboard.php?error=self_delete');
    exit;
}

try {
    $stmt = $conn->prepare(
        'DELETE FROM users WHERE user_id = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    header('Location: admin_dashboard.php');
    exit;
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());

    header('Location: admin_dashboard.php?error=delete_failed');
    exit;
}
?>
