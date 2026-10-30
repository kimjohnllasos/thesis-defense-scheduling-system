<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';

    if ($role === 'Student') {
        header("Location: student_dashboard.php");
    } elseif ($role === 'Adviser') {
        header("Location: adviser_dashboard.php");
    } elseif ($role === 'Admin') {
        header("Location: admin_dashboard.php");
    } else {
        session_destroy();
        header("Location: index.php?error=login");
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Defense System - Login</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
        }

        .login-box {
            width: 90%;
            max-width: 400px;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }

        h1 {
            text-align: center;
            font-size: 24px;
            color: #1e293b;
        }

        p {
            text-align: center;
            color: #64748b;
        }

        label {
            display: block;
            margin: 16px 0 6px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            color: white;
            background: #2563eb;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .error {
            color: #dc2626;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <main class="login-box">
        <h1>Thesis Defense System</h1>
        <p>Sign in to your account</p>

        <?php if (isset($_GET['error'])): ?>
            <p class="error">Invalid login or session. Please try again.</p>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                required
                autocomplete="username"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit">Log In</button>
        </form>
    </main>
</body>
</html>