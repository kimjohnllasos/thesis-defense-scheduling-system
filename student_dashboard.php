<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Student']);

$userId = (int) $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';
$message = '';
$error = '';

/* Get the logged-in student's student_id */
$stmt = $conn->prepare(
    "SELECT student_id FROM students WHERE user_id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    $error = "Your student profile was not found. Please contact the administrator.";
} else {
    $studentId = (int) $student['student_id'];

    /* Process form submissions */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        /* Submit a new project */
        if ($action === 'submit_project') {
            $title = trim($_POST['title'] ?? '');
        $projectType = $_POST['project_type'] ?? '';
        $adviserId = filter_input(
            INPUT_POST,
           'adviser_id',
            FILTER_VALIDATE_INT
        );

        $file = $_FILES['project_file'] ?? null;

        if (
            $title === '' ||
            !in_array($projectType, ['Thesis', 'Capstone'], true) ||
            !$adviserId
        ) {
            $error = "Please complete all project fields correctly.";
        } elseif (
            !$file ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {
            $error = "Please select a document to upload. The file must be 10 MB or smaller.";
        } elseif ($file['size'] <= 0 || $file['size'] > 10 * 1024 * 1024) {
            $error = "The document must be larger than 0 bytes and no bigger than 10 MB.";
        } else {
            $originalName = basename($file['name']);
            $extension = strtolower(
                pathinfo($originalName, PATHINFO_EXTENSION)
            );

            $allowedMimes = [
                'pdf' => ['application/pdf'],
                'docx' => [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/zip'
                ]
            ];

            $mimeType = '';

            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
            }

            if (
                !isset($allowedMimes[$extension]) ||
                !in_array($mimeType, $allowedMimes[$extension], true)
            ) {
                $error = "Only valid PDF and DOCX documents are allowed.";
            } else {
                $check = $conn->prepare(
                    "SELECT adviser_id FROM advisers WHERE adviser_id = ?"
                );
                $check->bind_param("i", $adviserId);
                $check->execute();

                if (!$check->get_result()->fetch_assoc()) {
                    $error = "Please select a valid adviser.";
                } else {
                    $uploadDir = __DIR__ . '/uploads/project_files';

                    if (
                        !is_dir($uploadDir) &&
                        !mkdir($uploadDir, 0750, true)
                    ) {
                        $error = "The upload folder could not be created.";
                    } else {
                        $storedName = bin2hex(random_bytes(16))
                            . '.' . $extension;

                        $absolutePath = $uploadDir . '/' . $storedName;
                        $relativePath = 'uploads/project_files/' . $storedName;

                        if (!move_uploaded_file(
                            $file['tmp_name'],
                            $absolutePath
                        )) {
                            $error = "The document could not be uploaded. Please try again.";
                        } else {
                            $status = 'In Progress';

                            $insert = $conn->prepare(
                                "INSERT INTO projects
                                 (student_id, adviser_id, title, project_type,
                                 status, file_name, file_path)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)"
                            );

                            $insert->bind_param(
                                "iisssss",
                                $studentId,
                                $adviserId,
                                $title,
                                $projectType,
                                $status,
                                $originalName,
                                $relativePath
                            );

                            if ($insert->execute()) {
                                header(
                                    "Location: student_dashboard.php?submitted=1"
                                );
                                exit;
                            } else {
                                if (is_file($absolutePath)) {
                                    unlink($absolutePath);
                                }

                                $error = "The project could not be saved. Please try again.";
                            }
                        }
                    }
                }
            }
        }
    }
        /* Submit a defense request */
        elseif ($action === 'submit_defense_request') {
            $projectId = filter_input(
                INPUT_POST,
                'project_id',
                FILTER_VALIDATE_INT
            );
            $defenseType = $_POST['defense_type'] ?? '';
            $requestedDate = $_POST['requested_date'] ?? '';
            $preferredTime = $_POST['preferred_time'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            if (
                !$projectId ||
                !in_array(
                    $defenseType,
                    ['Proposal', 'Pre-Oral', 'Final'],
                    true
                ) ||
                $requestedDate === '' ||
                $preferredTime === '' ||
                $reason === ''
            ) {
                $error = "Please complete all defense request fields.";
            } elseif ($requestedDate < date('Y-m-d')) {
                $error = "Please select today or a future date.";
            } else {
                /* Verify project ownership, readiness, and adviser assignment */
                $check = $conn->prepare(
                    "SELECT project_id
                     FROM projects
                     WHERE project_id = ?
                       AND student_id = ?
                       AND status = 'Ready for Defense'"
                );
                $check->bind_param("ii", $projectId, $studentId);
                $check->execute();

                if (!$check->get_result()->fetch_assoc()) {
                    $error = "You can only request a defense for your own project marked Ready for Defense.";
                } else {
                    /* Prevent duplicate pending or approved requests
                       for the same project and defense type */
                    $check = $conn->prepare(
                        "SELECT request_id
                         FROM defense_requests
                         WHERE project_id = ?
                           AND defense_type = ?
                           AND status IN ('Pending', 'Approved', 'Scheduled')
                         LIMIT 1"
                    );
                    $check->bind_param("is", $projectId, $defenseType);
                    $check->execute();

                    if ($check->get_result()->fetch_assoc()) {
                        $error = "A request for this defense type already exists and is still active.";
                    } else {
                        $status = 'Pending';

                        $insert = $conn->prepare(
                            "INSERT INTO defense_requests
                             (project_id, defense_type, requested_date,
                              preferred_time, reason, status)
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        $insert->bind_param(
                            "isssss",
                            $projectId,
                            $defenseType,
                            $requestedDate,
                            $preferredTime,
                            $reason,
                            $status
                        );
                        $insert->execute();

                        header(
                            "Location: student_dashboard.php?request_submitted=1"
                        );
                        exit;
                    }
                }
            }
        } else {
            $error = "Invalid form submission.";
        }
    }

    if (isset($_GET['submitted'])) {
        $message = "Your project was submitted successfully.";
    } elseif (isset($_GET['request_submitted'])) {
        $message = "Your defense request was submitted successfully and is awaiting review.";
    }

    /* Get this student's projects */   
    $stmt = $conn->prepare(
        "SELECT p.project_id, p.title, p.project_type, p.status,
                p.created_at, u.name AS adviser_name,
                p.review_status, p.adviser_feedback, p.reviewed_at
         FROM projects p
         LEFT JOIN advisers a ON p.adviser_id = a.adviser_id
         LEFT JOIN users u ON a.user_id = u.user_id
         WHERE p.student_id = ?
         ORDER BY p.created_at DESC"
    );
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $projects = $stmt->get_result();

    /* Get available advisers */
    $advisers = $conn->query(
        "SELECT a.adviser_id, u.name
         FROM advisers a
         INNER JOIN users u ON a.user_id = u.user_id
         ORDER BY u.name"
    );

    /* Get projects eligible for a defense request */
    $stmt = $conn->prepare(
        "SELECT p.project_id, p.title, p.project_type
         FROM projects p
         WHERE p.student_id = ?
           AND p.status = 'Ready for Defense'
         ORDER BY p.created_at DESC"
    );
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $eligibleProjects = $stmt->get_result();

    
    /* Get this student's defense requests */
    $stmt = $conn->prepare(
        "SELECT
            dr.request_id,
            p.title,
            dr.defense_type,
            dr.requested_date,
            dr.preferred_time,
            dr.status,
            dr.adviser_comments
        FROM defense_requests dr
        INNER JOIN projects p
            ON dr.project_id = p.project_id
        WHERE p.student_id = ?
        ORDER BY dr.submitted_at DESC"
    );

    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $defenseRequests = $stmt->get_result();

    
    /* Get this student's scheduled defenses */
    $stmt = $conn->prepare(
        "SELECT
            ds.schedule_id,
            p.title,
            dr.defense_type,
            ds.defense_date,
            ds.start_time,
            ds.end_time,
            r.room_name,
            ds.status
         FROM defense_schedules ds
         INNER JOIN defense_requests dr
            ON ds.request_id = dr.request_id
         INNER JOIN projects p
            ON dr.project_id = p.project_id
         INNER JOIN rooms r
            ON ds.room_id = r.room_id
         WHERE p.student_id = ?
         ORDER BY ds.defense_date ASC, ds.start_time ASC"
    );
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $scheduledDefenses = $stmt->get_result();
    
    /* Get the logged-in student's recent notifications */
    $stmt = $conn->prepare(
        "SELECT notification_id, message, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 10"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $notifications = $stmt->get_result();

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    
    <link rel="stylesheet" href="student_dashboard.css?v=3">

</head>

<body>

<aside class="sidebar">
    <div class="logo">
        <h2>TDS</h2>
        <p>THESIS DEFENSE<br>SYSTEM</p>
    </div>

    
    <nav>
        <a href="#dashboard-overview" class="nav-link active" data-section="dashboard-overview">
            <span>⌂</span> Dashboard
        </a>
        <a href="#submit-project" class="nav-link" data-section="submit-project">
            <span>▣</span> Submit Project
        </a>
        <a href="#request-defense" class="nav-link" data-section="request-defense">
            <span>◷</span> Request Defense
        </a>
        <a href="#defense-requests" class="nav-link" data-section="defense-requests">
            <span>☷</span> My Defense Requests
        </a>
        
        <a href="#student-notifications"
           class="nav-link"
           data-section="student-notifications">
        <span>🔔</span>
        <span>Notifications</span>
        </a>

        <a href="#defense-schedule" class="nav-link" data-section="defense-schedule">
            <span>▦</span> My Defense Schedule
        </a>
        <a href="#my-projects" class="nav-link" data-section="my-projects">
            <span>▤</span> My Projects
        </a>
    </nav>

    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</aside>

<main class="main-content">
<div class="container">


    <div class="header" id="dashboard">
    <div>
        <h1>Student Dashboard</h1>
        <p>Welcome, <?= h($name) ?>!</p>
    </div>
</div>

    <?php if ($message): ?>
        <div class="message"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($student): ?>
        <div class="overview-grid" id="dashboard-overview">
            <div class="overview-card">
                <p>Total Projects</p>
                <h2><?= $projects->num_rows ?></h2>
            </div>

            <div class="overview-card">
                <p>Defense Requests</p>
                <h2><?= $defenseRequests->num_rows ?></h2>
            </div>

            <div class="overview-card">
                <p>Scheduled Defenses</p>
                <h2><?= $scheduledDefenses->num_rows ?></h2>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($student): ?>
        <div class="card" id="submit-project">
            <h2>Submit Project</h2>
            <p>Enter your thesis or capstone project details below.</p>

            <form method="POST" action="student_dashboard.php" enctype="multipart/form-data">
    <input type="hidden" name="action" value="submit_project">
                <label for="title">Project Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    required
                    value="<?= h($_POST['title'] ?? '') ?>"
                >

                <label for="project_type">Project Type</label>
                <select id="project_type" name="project_type" required>
                    <option value="">Select project type</option>
                    <option value="Thesis"
                        <?= ($_POST['project_type'] ?? '') === 'Thesis' ? 'selected' : '' ?>>
                        Thesis
                    </option>
                    <option value="Capstone"
                        <?= ($_POST['project_type'] ?? '') === 'Capstone' ? 'selected' : '' ?>>
                        Capstone
                    </option>
                </select>

                <label for="adviser_id">Adviser</label>
                <select id="adviser_id" name="adviser_id" required>
                    <option value="">Select adviser</option>

                    <?php while ($adviser = $advisers->fetch_assoc()): ?>
                        <option
                            value="<?= (int) $adviser['adviser_id'] ?>"
                            <?= (string)($_POST['adviser_id'] ?? '') ===
                                (string)$adviser['adviser_id'] ? 'selected' : '' ?>
                        >
                            <?= h($adviser['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="project_file">Thesis / Capstone Document (PDF or DOCX)</label>
                <input
                    type="file"
                    id="project_file"
                    name="project_file"
                    accept=".pdf,.docx"
                    required
                >

                <button type="submit">Submit Project</button>
            </form>
        </div>
        
<div class="card" id="request-defense">
    <h2>Request a Defense</h2>
    <p>
        You can request a defense after your adviser marks your
        project as Ready for Defense.
    </p>

    <?php if ($eligibleProjects->num_rows > 0): ?>
        <form method="POST" action="student_dashboard.php">
            <input type="hidden" name="action" value="submit_defense_request">

            <label for="project_id">Project</label>
            <select id="project_id" name="project_id" required>
                <option value="">Select an eligible project</option>
                <?php while ($project = $eligibleProjects->fetch_assoc()): ?>
                    <option value="<?= (int) $project['project_id'] ?>">
                        <?= h($project['title']) ?>
                        (<?= h($project['project_type']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="defense_type">Defense Type</label>
            <select id="defense_type" name="defense_type" required>
                <option value="">Select defense type</option>
                <option value="Proposal">Proposal</option>
                <option value="Pre-Oral">Pre-Oral</option>
                <option value="Final">Final</option>
            </select>

            <label for="requested_date">Preferred Defense Date</label>
            <input
                type="date"
                id="requested_date"
                name="requested_date"
                min="<?= date('Y-m-d') ?>"
                required
            >

            <label for="preferred_time">Preferred Time</label>
            <input
                type="time"
                id="preferred_time"
                name="preferred_time"
                required
            >

            <label for="reason">Reason / Additional Information</label>
            <input
                type="text"
                id="reason"
                name="reason"
                maxlength="255"
                required
            >

            <button type="submit">Submit Defense Request</button>
        </form>
    <?php else: ?>
        <p>
            No eligible projects yet. Your adviser must first mark
            a project as Ready for Defense.
        </p>
    <?php endif; ?>
</div>

<div class="card" id="defense-requests">
    <h2>My Defense Requests</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Defense Type</th>
                    <th>Requested Date</th>
                    <th>Preferred Time</th>
                    <th>Status</th>
                    <th>Adviser Comments</th>
                </tr>
            </thead>
            <tbody>
                
                <?php if ($defenseRequests->num_rows > 0): ?>
                    <?php while ($request = $defenseRequests->fetch_assoc()): ?>
                        <tr>
                            <td><?= h($request['title']) ?></td>
                            <td><?= h($request['defense_type']) ?></td>
                            <td><?= h($request['requested_date']) ?></td>
                            <td><?= h($request['preferred_time']) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $request['status'])) ?>">
                                <?= h($request['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= !empty($request['adviser_comments'])
                                ? h($request['adviser_comments'])
                                : 'No comments yet' ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">You have not submitted a defense request yet.</td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<div class="card" id="student-notifications">
        <h2>Notifications</h2>
        <p>Updates about your defense requests, feedback, and schedules.</p>

            <?php if ($notifications->num_rows > 0): ?>
            <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-item"
                         style="padding:15px; margin-bottom:12px; border-radius:8px;
                            background:<?= (int)$notification['is_read'] === 1 ? '#1E293B' : '#173D35' ?>;
                            border:1px solid #2B374B;">

                    <p><?= h($notification['message']) ?></p>

                        <small>
                            <?= h($notification['created_at']) ?>
                            <?= (int)$notification['is_read'] === 0 ? ' • Unread' : ' • Read' ?>
                        </small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No notifications yet.</p>
            <?php endif; ?>
</div>
        
<div class="card" id="defense-schedule">
    <h2>My Defense Schedule</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Defense Type</th>
                    <th>Defense Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Room</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($scheduledDefenses->num_rows > 0): ?>
                    <?php while ($defense = $scheduledDefenses->fetch_assoc()): ?>
                        <tr>
                            <td><?= h($defense['title']) ?></td>
                            <td><?= h($defense['defense_type']) ?></td>
                            <td><?= h($defense['defense_date']) ?></td>
                            <td><?= h(date('g:i A', strtotime($defense['start_time']))) ?></td>
                            <td><?= h(date('g:i A', strtotime($defense['end_time']))) ?></td>
                            <td><?= h($defense['room_name']) ?></td>
                            <td><?= h($defense['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            No defense has been scheduled for your projects yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


        <div class="card" id="my-projects">
    <h2>My Projects</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th>Type</th>
                            <th>Adviser</th>
                            <th>Status</th>
                            <th>Document Review</th>
                            <th>Adviser Feedback</th>
                            <th>Last Reviewed</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($projects->num_rows > 0): ?>
                        <?php while ($project = $projects->fetch_assoc()): ?>
                            <tr>
                                <td><?= h($project['title']) ?></td>
                                <td><?= h($project['project_type']) ?></td>
                                <td><?= h($project['adviser_name'] ?? 'Not assigned') ?></td>                               
                                <td><?= h($project['status']) ?></td>
                            
                                <td>
                                    <?= h($project['review_status'] ?? 'Pending Review') ?>
                                </td>

                                <td>
                                    <?= !empty($project['adviser_feedback'])
                                        ? nl2br(h($project['adviser_feedback']))
                                        : 'No feedback yet' ?>
                                </td>

                                <td>
                                    <?= !empty($project['reviewed_at'])
                                        ? h($project['reviewed_at'])
                                        : 'Not reviewed yet' ?>
                                </td>

                                <td><?= h($project['created_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">You have not submitted a project yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('.sidebar .nav-link');

    const sections = [
        'dashboard-overview',
        'submit-project',
        'request-defense',
        'defense-requests',
        'student-notifications',
        'defense-schedule',
        'my-projects'
    ];

    function showSection(sectionId) {
        sections.forEach(function (id) {
            const element = document.getElementById(id);

            if (element) {
                element.style.display =
                    id === sectionId ? '' : 'none';
            }
        });

        links.forEach(function (link) {
            link.classList.toggle(
                'active',
                link.dataset.section === sectionId
            );
        });

        window.scrollTo({ top: 0, behavior: 'auto' });
    }

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            showSection(link.dataset.section);
        });
    });

    showSection('dashboard-overview');
});
</script>
</body>
</html>
