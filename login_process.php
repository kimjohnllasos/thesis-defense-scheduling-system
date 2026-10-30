<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: index.php?error=login");
    exit;
}

$stmt = $conn->prepare(
    "SELECT user_id, name, username, password, role
     FROM users
     WHERE username = ?
     LIMIT 1"
);

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: index.php?error=login");
    exit;
}

if (!in_array($user['role'], ['Student', 'Adviser', 'Admin', 'Panel Member'], true)) {
    header("Location: index.php?error=login");
    exit;
}

session_regenerate_id(true);

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['name'] = $user['name'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'Student') {
    header("Location: student_dashboard.php");
} elseif ($user['role'] === 'Adviser') {
    header("Location: adviser_dashboard.php");
} elseif ($user['role'] === 'Panel Member') {
    header("Location: panel_dashboard.php");
} else {
    header("Location: admin_dashboard.php");
}

exit;
?>
