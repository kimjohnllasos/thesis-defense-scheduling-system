<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $studentNumber = trim($_POST['student_number'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    $allowedRoles = ['Student', 'Adviser', 'Admin', 'Panel Member'];

    if ($name === '' || $username === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Please select a valid role.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    
    } elseif (
        $role === 'Student' &&
        ($studentNumber === '' || $course === '')
    ) {
        $error = 'Student Number and Course are required for Student accounts.';
    
    } elseif ($role === 'Adviser' && $department === '') {
        $error = 'Department is required for Adviser accounts.';
    } elseif ($role === 'Panel Member' && $department === '') {
        $error = 'Department is required for Panel Member accounts.';
    } else {


        $check = $conn->prepare(
            'SELECT user_id FROM users WHERE username = ?'
        );
        $check->bind_param('s', $username);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'That username is already registered.';
        } else {
            try {
                $conn->begin_transaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    'INSERT INTO users (name, username, password, role)
                     VALUES (?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'ssss',
                    $name,
                    $username,
                    $hash,
                    $role
                );
                $stmt->execute();

                $userId = $conn->insert_id;

                
            if ($role === 'Student') {
                $studentStmt = $conn->prepare(
                    'INSERT INTO students (user_id, student_number, course)
                     VALUES (?, ?, ?)'
                );

                $studentStmt->bind_param(
                   'iss',
                    $userId,
                    $studentNumber,
                    $course
                );

                $studentStmt->execute();
                }

            if ($role === 'Adviser') {
                $adviserStmt = $conn->prepare(
                    'INSERT INTO advisers (user_id, department)
                     VALUES (?, ?)'
                );

                $adviserStmt->bind_param(
                    'is',
                    $userId,
                    $department
                );

                $adviserStmt->execute();
                }

            if ($role === 'Panel Member') {
                $panelStmt = $conn->prepare(
                    'INSERT INTO panel_members
                     (user_id, panel_name, department, specialization, is_active)
                     VALUES (?, ?, ?, ?, 1)'
                );

                $panelStmt->bind_param(
                    'isss',
                    $userId,
                    $name,
                    $department,
                    $specialization
                );

                $panelStmt->execute();
            }

                $conn->commit();


                header('Location: admin_dashboard.php');
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log($e->getMessage());
                $error = 'Could not create the account. Check the information and try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link rel="stylesheet" href="admin_dashboard.css?v=3">
</head>

<body>
    <main class="main-content" style="margin-left: 0; padding: 30px; min-height: 100vh; background: #0B132B; color: #FFFFFF;">
        <div class="card" style="max-width: 800px; margin: 20px auto; background: #151E30; border: 1px solid #39405A; border-radius: 10px; padding: 25px;">
            <h2 style="color: #39FF88; margin-bottom: 20px;">Add New User</h2>

            <?php if ($error !== ''): ?>
                <p style="color: #ff7777; margin-bottom: 15px;">
                    <?= h($error) ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="add_user.php">
                <p>
                    <label for="name">Full Name</label><br>
                    <input
                        id="name"
                        name="name"
                        maxlength="100"
                        value="<?= h($_POST['name'] ?? '') ?>"
                        required
                    >
                </p>

                <p>
                    <label for="username">Username</label><br>
                    <input
                        id="username"
                        name="username"
                        maxlength="50"
                        value="<?= h($_POST['username'] ?? '') ?>"
                        required
                    >
                </p>

                
                <p>
                    <label for="student_number">Student Number</label><br>
                    <input
                        id="student_number"
                        name="student_number"
                        maxlength="50"
                        value="<?= h($_POST['student_number'] ?? '') ?>"
                    >
                </p>

                
                <p>
                    <label for="course">Course / Program</label><br>
                    <input
                        id="course"
                        name="course"
                        maxlength="100"
                        value="<?= h($_POST['course'] ?? '') ?>"
                    >
                </p>

                <p>
                    <label for="department">
                        Department (for Advisers and Panel Members)
                    </label><br>
                    <input
                        id="department"
                        name="department"
                        maxlength="100"
                        value="<?= h($_POST['department'] ?? '') ?>"
                    >
                </p>

                <p>
                    <label for="specialization">
                        Specialization (for Panel Members)
                    </label><br>
                    <input
                        id="specialization"
                        name="specialization"
                        maxlength="100"
                        value="<?= h($_POST['specialization'] ?? '') ?>"
                        placeholder="e.g., Software Engineering"
                    >
                </p>

                <p>
                    <label for="password">Password (minimum 8 characters)</label><br>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        minlength="8"
                        required
                    >
                </p>

                <p>
                    <label for="role">Role</label><br>
                    <select id="role" name="role" required>
                        <option value="">Choose a role</option>
                        <option value="Student">Student</option>
                        <option value="Adviser">Adviser</option>
                        <option value="Admin">Admin</option>
                        <option value="Panel Member">Panel Member</option>
                    </select>
                </p>

                <button type="submit">Create User</button>
                <a href="admin_dashboard.php">Cancel</a>
            </form>
        </div>
    </main>
</body>
</html>
