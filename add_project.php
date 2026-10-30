<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

$error = '';

$title = '';
$projectType = 'Thesis';
$studentId = '';
$adviserId = '';
$status = 'In Progress';

// Get registered students.
$students = $conn->query(
    "SELECT s.student_id, s.student_number, u.name
     FROM students s
     INNER JOIN users u ON s.user_id = u.user_id
     WHERE u.role = 'Student'
     ORDER BY u.name"
);

// Get registered advisers.
$advisers = $conn->query(
    "SELECT a.adviser_id, u.name, a.department
     FROM advisers a
     INNER JOIN users u ON a.user_id = u.user_id
     WHERE u.role = 'Adviser'
     ORDER BY u.name"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $projectType = $_POST['project_type'] ?? '';
    $studentId = $_POST['student_id'] ?? '';
    $adviserId = $_POST['adviser_id'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($title === '') {
        $error = 'Project title is required.';
    } elseif (!in_array($projectType, ['Thesis', 'Capstone'], true)) {
        $error = 'Please select a valid project type.';
    } elseif (!in_array($status, ['In Progress', 'Ready for Defense', 'Completed'], true)) {
        $error = 'Please select a valid project status.';
    } elseif (!ctype_digit($studentId) || (int)$studentId < 1) {
        $error = 'Please select a student.';
    } elseif (!ctype_digit($adviserId) || (int)$adviserId < 1) {
        $error = 'Please select an adviser.';
    } else {
        // Verify the selected student exists.
        $checkStudent = $conn->prepare(
            "SELECT student_id
             FROM students
             WHERE student_id = ?"
        );
        $studentIdInt = (int)$studentId;
        $checkStudent->bind_param('i', $studentIdInt);
        $checkStudent->execute();

        // Verify the selected adviser exists.
        $checkAdviser = $conn->prepare(
            "SELECT adviser_id
             FROM advisers
             WHERE adviser_id = ?"
        );
        $adviserIdInt = (int)$adviserId;
        $checkAdviser->bind_param('i', $adviserIdInt);
        $checkAdviser->execute();

        if ($checkStudent->get_result()->num_rows === 0) {
            $error = 'The selected student does not exist.';
        } elseif ($checkAdviser->get_result()->num_rows === 0) {
            $error = 'The selected adviser does not exist.';
        } else {
            $insert = $conn->prepare(
                "INSERT INTO projects
                    (student_id, adviser_id, title, project_type, status)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $insert->bind_param(
                'iisss',
                $studentIdInt,
                $adviserIdInt,
                $title,
                $projectType,
                $status
            );

            $insert->execute();

            header('Location: admin_dashboard.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Project</title>
    <link rel="stylesheet" href="admin_dashboard.css?v=3">
</head>
<body>
    <main class="main-content"
          style="margin-left:0; padding:30px; min-height:100vh;">

        <div class="card" style="max-width:800px; margin:20px auto;">

            <h2 style="margin-bottom:20px;">Add Project</h2>

            <?php if ($error !== ''): ?>
                <p style="color:#ff7777; margin-bottom:15px;">
                    <?= h($error) ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="add_project.php">

                <p>
                    <label for="title">Project Title</label><br>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="255"
                        value="<?= h($title) ?>"
                        required
                    >
                </p>

                <p>
                    <label for="project_type">Project Type</label><br>
                    <select id="project_type" name="project_type" required>
                        <option value="Thesis" <?= $projectType === 'Thesis' ? 'selected' : '' ?>>
                            Thesis
                        </option>
                        <option value="Capstone" <?= $projectType === 'Capstone' ? 'selected' : '' ?>>
                            Capstone
                        </option>
                    </select>
                </p>

                <p>
                    <label for="student_id">Student</label><br>
                    <select id="student_id" name="student_id" required>
                        <option value="">Select Student</option>

                        <?php while ($student = $students->fetch_assoc()): ?>
                            <option
                                value="<?= (int)$student['student_id'] ?>"
                                <?= (string)$studentId === (string)$student['student_id'] ? 'selected' : '' ?>
                            >
                                <?= h($student['name']) ?>
                                (<?= h($student['student_number']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </p>

                <p>
                    <label for="adviser_id">Adviser</label><br>
                    <select id="adviser_id" name="adviser_id" required>
                        <option value="">Select Adviser</option>

                        <?php while ($adviser = $advisers->fetch_assoc()): ?>
                            <option
                                value="<?= (int)$adviser['adviser_id'] ?>"
                                <?= (string)$adviserId === (string)$adviser['adviser_id'] ? 'selected' : '' ?>
                            >
                                <?= h($adviser['name']) ?>
                                (<?= h($adviser['department']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </p>

                <p>
                    <label for="status">Project Status</label><br>
                    <select id="status" name="status" required>
                        <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>
                            In Progress
                        </option>
                        <option value="Ready for Defense" <?= $status === 'Ready for Defense' ? 'selected' : '' ?>>
                            Ready for Defense
                        </option>
                        <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>
                            Completed
                        </option>
                    </select>
                </p>

                <button type="submit">Save Project</button>
                <a href="admin_dashboard.php">Cancel</a>

            </form>
        </div>
    </main>
</body>
</html>
