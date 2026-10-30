<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Adviser']);

$userId = (int) $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Adviser';
$error = '';
$message = '';

/* Find the adviser profile linked to this account */
$stmt = $conn->prepare(
    "SELECT adviser_id FROM advisers WHERE user_id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$adviser = $stmt->get_result()->fetch_assoc();

if (!$adviser) {
    $error = "Your adviser profile was not found. Please contact the administrator.";
} else {
    $adviserId = (int) $adviser['adviser_id'];

    /* Save document review and feedback */
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'review_document'
    ) {
        $projectId = filter_input(
            INPUT_POST,
            'project_id',
            FILTER_VALIDATE_INT
        );

        $reviewStatus = $_POST['review_status'] ?? '';
        $feedback = trim($_POST['adviser_feedback'] ?? '');

        $allowedReviewStatuses = [
            'Under Review',
            'Revision Required',
            'Approved'
        ];

        if (
            !$projectId
            || !in_array($reviewStatus, $allowedReviewStatuses, true)
        ) {
            $error = "Invalid document review information.";
        } else {
            $check = $conn->prepare(
                "SELECT project_id
                 FROM projects
                 WHERE project_id = ?
                   AND adviser_id = ?"
            );

            $check->bind_param("ii", $projectId, $adviserId);
            $check->execute();

            if (!$check->get_result()->fetch_assoc()) {
                $error = "This project is not assigned to you.";
            } else {
                $update = $conn->prepare(
                    "UPDATE projects
                     SET review_status = ?,
                         adviser_feedback = ?,
                         reviewed_at = NOW()
                     WHERE project_id = ?
                       AND adviser_id = ?"
                );

                $update->bind_param(
                    "ssii",
                    $reviewStatus,
                    $feedback,
                    $projectId,
                    $adviserId
                );

                if ($update->execute()) {
                    header(
                        "Location: adviser_dashboard.php?review_saved=1"
                    );
                    exit;
                } else {
                    $error = "Unable to save the document review.";
                }
            }
        }
    }

    /* Update a project's status */
    if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'update_project'
) {
        $projectId = filter_input(
            INPUT_POST,
            'project_id',
            FILTER_VALIDATE_INT
        );

        $newStatus = $_POST['status'] ?? '';

        if (!$projectId || !in_array(
            $newStatus,
            ['In Progress', 'Ready for Defense'],
            true
        )) {
            $error = "Invalid project information.";
        } else {
            /* Only update projects assigned to this adviser */
            $update = $conn->prepare(
                "UPDATE projects
                 SET status = ?
                 WHERE project_id = ?
                   AND adviser_id = ?
                   AND status != 'Completed'"
            );
            $update->bind_param(
                "sii",
                $newStatus,
                $projectId,
                $adviserId
            );
            $update->execute();

            if ($update->affected_rows > 0) {
                header("Location: adviser_dashboard.php?updated=1");
                exit;
            } else {
                $error = "The project was not changed. It may already have this status or may not be assigned to you.";
            }
        }
    }

    if (isset($_GET['updated'])) {
        $message = "Project status updated successfully.";
    }
    if (isset($_GET['review_saved'])) {
    $message = "Document review saved successfully.";
    }
    if (isset($_GET['comments_saved'])) {
    $message = "Adviser comments saved successfully.";
    }
    if (isset($_GET['decision_saved'])) {
        $message = "Defense request decision saved successfully.";
    }
    

    /* Approve or reject a defense request */
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'review_request'
    ) {
        $requestId = filter_input(
            INPUT_POST,
            'request_id',
            FILTER_VALIDATE_INT
        );

        $decision = $_POST['decision'] ?? '';
        $comments = trim($_POST['adviser_comments'] ?? '');

        if (
            !$requestId
            || !in_array($decision, ['approve', 'reject'], true)
        ) {
            $error = "Invalid defense request information.";
        } elseif ($decision === 'reject' && $comments === '') {
            $error = "Please provide revision notes when rejecting a request.";
        } else {
            $check = $conn->prepare(
                "SELECT dr.request_id, s.user_id, p.title
                 FROM defense_requests dr
                 INNER JOIN projects p ON dr.project_id = p.project_id
                 INNER JOIN students s ON p.student_id = s.student_id
                 WHERE dr.request_id = ?
                   AND p.adviser_id = ?
                   AND dr.status = 'Pending'"
            );

            $check->bind_param("ii", $requestId, $adviserId);
            $check->execute();
            $requestData = $check->get_result()->fetch_assoc();

            if (!$requestData) {
                $error = "This request is unavailable or has already been reviewed.";
            } else {
                $newStatus = ($decision === 'approve')
                    ? 'Approved'
                    : 'Rejected';

                $update = $conn->prepare(
                    "UPDATE defense_requests
                     SET status = ?, adviser_comments = ?
                     WHERE request_id = ?
                       AND status = 'Pending'
                       AND project_id IN (
                           SELECT project_id
                           FROM projects
                           WHERE adviser_id = ?
                       )"
                );

                $update->bind_param(
                    "ssii",
                    $newStatus,
                    $comments,
                    $requestId,
                    $adviserId
                );

                if ($update->execute() && $update->affected_rows === 1) {
                    $studentUserId = (int) $requestData['user_id'];
                    $projectTitle = $requestData['title'];

                    if ($newStatus === 'Approved') {
                        $notificationMessage =
                            "Your adviser approved your defense request for: "
                            . $projectTitle;
                    } else {
                        $notificationMessage =
                            "Your adviser rejected your defense request for: "
                            . $projectTitle
                            . ". Revision notes: "
                            . $comments;
                    }

                    $notification = $conn->prepare(
                        "INSERT INTO notifications (user_id, message)
                         VALUES (?, ?)"
                    );

                    $notification->bind_param(
                        "is",
                        $studentUserId,
                        $notificationMessage
                    );

                    if ($notification->execute()) {
                        header("Location: adviser_dashboard.php?decision_saved=1");
                        exit;
                    } else {
                        $error = "The decision was saved, but the notification could not be created.";
                    }
                } else {
                    $error = "The request could not be updated. Please try again.";
                }
            }
        }
    }

    /* Save adviser comments on a defense request */
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'save_comments'
    ) {
        $requestId = filter_input(
            INPUT_POST,
            'request_id',
            FILTER_VALIDATE_INT
        );

        $comments = trim($_POST['adviser_comments'] ?? '');

        if (!$requestId || $comments === '') {
            $error = "Please enter comments for a valid defense request.";
        } else {
            /* Only update requests for projects assigned to this adviser */
            $save = $conn->prepare(
                "UPDATE defense_requests dr
                 INNER JOIN projects p ON dr.project_id = p.project_id
                 SET dr.adviser_comments = ?
                 WHERE dr.request_id = ?
                   AND p.adviser_id = ?"
            );

            $save->bind_param(
                "sii",
                $comments,
                $requestId,
                $adviserId
            );
            

if ($save->execute()) {

    /* Verify the request belongs to this adviser */
    $check = $conn->prepare(
        "SELECT dr.request_id
         FROM defense_requests dr
         INNER JOIN projects p ON dr.project_id = p.project_id
         WHERE dr.request_id = ?
           AND p.adviser_id = ?"
    );

    $check->bind_param("ii", $requestId, $adviserId);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        /* Find the student account that owns this request */
        $studentStmt = $conn->prepare(
            "SELECT s.user_id, p.title
             FROM defense_requests dr
             INNER JOIN projects p ON dr.project_id = p.project_id
             INNER JOIN students s ON p.student_id = s.student_id
             WHERE dr.request_id = ?
               AND p.adviser_id = ?"
        );

        $studentStmt->bind_param("ii", $requestId, $adviserId);
        $studentStmt->execute();
        $student = $studentStmt->get_result()->fetch_assoc();

        if ($student) {
            $studentUserId = (int) $student['user_id'];
            $projectTitle = $student['title'];

            $notificationMessage =
                "Your adviser has added comments to your defense request for: "
                . $projectTitle;

            $notificationStmt = $conn->prepare(
                "INSERT INTO notifications (user_id, message)
                 VALUES (?, ?)"
            );

            $notificationStmt->bind_param(
                "is",
                $studentUserId,
                $notificationMessage
            );

            if ($notificationStmt->execute()) {
                header("Location: adviser_dashboard.php?comments_saved=1");
                exit;
            } else {
                $error = "Comments were saved, but the notification could not be created.";
            }
        } else {
            $error = "The student account for this defense request was not found.";
        }

    } else {
        $error = "No matching defense request was found for your account.";
    }

} else {
    $error = "Unable to save comments. Please try again.";
}
        }
    }

    
    /* Show only projects assigned to the logged-in adviser */
    $stmt = $conn->prepare(
        "SELECT p.project_id, p.title, p.project_type,
                p.status, p.created_at, p.file_name, p.file_path,
                p.review_status, p.adviser_feedback, p.reviewed_at,
                u.name AS student_name
         FROM projects p
         INNER JOIN students s ON p.student_id = s.student_id
         INNER JOIN users u ON s.user_id = u.user_id
         WHERE p.adviser_id = ?
         ORDER BY p.created_at DESC"
    );
    $stmt->bind_param("i", $adviserId);
    $stmt->execute();
    $projects = $stmt->get_result();

    $totalProjects = $projects->num_rows;
    
    /* Get defense requests for projects assigned to this adviser */
    $stmt = $conn->prepare(
        "SELECT dr.request_id, dr.defense_type,
                dr.requested_date, dr.preferred_time,
                dr.status, dr.adviser_comments,
                p.title, p.project_id,
                u.name AS student_name
         FROM defense_requests dr
         INNER JOIN projects p ON dr.project_id = p.project_id
         INNER JOIN students s ON p.student_id = s.student_id
         INNER JOIN users u ON s.user_id = u.user_id
         WHERE p.adviser_id = ?
         ORDER BY dr.submitted_at DESC"
    );
    $stmt->bind_param("i", $adviserId);
    $stmt->execute();
    $defenseRequests = $stmt->get_result();

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser Dashboard</title>

    <style>      
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px;
    font-family: Arial, sans-serif;
    background: #0B132B;
    color: #E8ECF4;
}

.container {
    max-width: 1200px;
    margin: auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 28px;
}

.header h1 {
    color: #FFFFFF;
    margin-bottom: 8px;
}

.header p {
    color: #AAB5C8;
}

.card {
    background: #151E30;
    padding: 24px;
    border: 1px solid #2B374B;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.card h2 {
    color: #FFFFFF;
    margin-top: 0;
    margin-bottom: 12px;
}

.card p {
    color: #B8C2D3;
}

.summary {
    font-size: 18px;
    font-weight: bold;
    color: #39FF88 !important;
}

.logout {
    display: inline-block;
    padding: 12px 18px;
    border-radius: 7px;
    background: #192541;
    border: 1px solid #34415A;
    color: #FFFFFF;
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s;
}

.logout:hover {
    background: #24334F;
    border-color: #39FF88;
}

.message {
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 18px;
    background: #123C30;
    border: 1px solid #267C58;
    color: #B8FFD5;
}

.message.error {
    background: #421F2A;
    border-color: #8F394B;
    color: #FFD4DB;
}

.table-wrap {
    overflow-x: auto;
    width: 100%;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th,
td {
    text-align: left;
    padding: 14px;
    border-bottom: 1px solid #2B374B;
    vertical-align: top;
}

th {
    background: #10192C;
    color: #39FF88;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

td {
    color: #E0E6F0;
}

tbody tr {
    transition: background 0.2s;
}

tbody tr:hover {
    background: #1B2940;
}

select,
textarea {
    background: #0F192B;
    color: #FFFFFF;
    border: 1px solid #34415A;
    border-radius: 6px;
    padding: 10px;
    font-family: inherit;
    max-width: 100%;
}

select:focus,
textarea:focus {
    outline: 1px solid #39FF88;
    border-color: #39FF88;
}

textarea {
    resize: vertical;
}

button {
    margin-top: 6px;
    padding: 10px 14px;
    border: 1px solid #39FF88;
    border-radius: 6px;
    background: #39FF88;
    color: #0B132B;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}

button:hover {
    background: #2CE879;
    color: #0B132B;
}

.status {
    font-weight: bold;
}

@media (max-width: 600px) {
    body {
        padding: 15px;
    }

    .card {
        padding: 16px;
    }

    .header h1 {
        font-size: 25px;
    }
}

/* Adviser sidebar and section navigation */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 240px;
    height: 100vh;
    padding: 25px 15px;
    background: #111A35;
    border-right: 1px solid #2B374B;
    z-index: 100;
}

.sidebar h2 {
    color: #39FF88;
    font-size: 21px;
    margin: 0 0 30px;
    padding: 0 10px;
}

.nav-link {
    display: block;
    padding: 14px 12px;
    margin-bottom: 8px;
    border-radius: 7px;
    color: #E8ECF4;
    text-decoration: none;
    transition: background .2s, color .2s;
}

.nav-link:hover,
.nav-link.active {
    background: #1B2940;
    color: #39FF88;
}

.logout-link {
    margin-top: 30px;
    border: 1px solid #34415A;
}

.container {
    margin-left: 260px;
    max-width: none;
    padding: 25px;
}

.dashboard-section {
    display: none;
}

.dashboard-section.active {
    display: block;
}

@media (max-width: 700px) {
    .sidebar {
        width: 175px;
        padding: 18px 10px;
    }

    .container {
        margin-left: 185px;
        padding: 15px;
    }
}
    </style>
</head>

<body>

<div class="sidebar">
    <h2>Adviser Panel</h2>

    <a href="#dashboard-overview" class="nav-link active"
       data-section="dashboard-overview">🏠 Dashboard</a>

    <a href="#student-projects" class="nav-link"
       data-section="student-projects">📁 Student Projects</a>

    <a href="#defense-requests" class="nav-link"
       data-section="defense-requests">📝 Defense Requests</a>

    <a href="logout.php" class="nav-link logout-link">🚪 Logout</a>
</div>

<div class="container">


    <div class="dashboard-section active" id="dashboard-overview">

    <div class="header">
        <div>
            <h1>Adviser Dashboard</h1>
            <p>Welcome, <?= h($name) ?>!</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($adviser): ?>
        <div class="card">
            <h2>Project Overview</h2>
            <p class="summary">
                Assigned Projects: <?= $totalProjects ?>
            </p>
        </div>
        </div>

        <div class="dashboard-section" id="student-projects">
    <div class="card">
        <h2>Student Projects</h2>
            <p>Review the projects assigned to you and update their status.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Document</th>
                            <th>Project Action</th>
                        </tr>
                    </thead>
                    <tbody>
            <?php if ($projects->num_rows > 0): ?>
            <?php while ($project = $projects->fetch_assoc()): ?>
                <tr>
                    <td><?= h($project['title']) ?></td>
                    <td><?= h($project['student_name']) ?></td>
                    <td><?= h($project['project_type']) ?></td>
                    <td class="status"><?= h($project['status']) ?></td>
                    <td><?= h($project['created_at']) ?></td>
                    <td>
                        <?php if (!empty($project['file_path']) && !empty($project['file_name'])): ?>
                            <a
                                href="download_project_file.php?project_id=<?= (int) $project['project_id'] ?>"
                                style="color:#39FF88;"
                            >
                                Download Document
                            </a>
                        <?php else: ?>
                                No document
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($project['status'] !== 'Completed'): ?>
                        
                            <form method="POST" action="adviser_dashboard.php">
                                <input type="hidden" name="action" value="update_project">

                                <input
                                    type="hidden"
                                    name="project_id"
                                    value="<?= (int) $project['project_id'] ?>"
                                >


                            <select name="status" required>
                                <option value="In Progress"
                                    <?= $project['status'] === 'In Progress' ? 'selected' : '' ?>>
                                    In Progress
                                </option>
                                <option value="Ready for Defense"
                                    <?= $project['status'] === 'Ready for Defense' ? 'selected' : '' ?>>
                                        Ready for Defense
                                </option>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                            <hr style="border-color:#2B374B; margin:15px 0;">

                            <form method="POST" action="adviser_dashboard.php">
                                <input type="hidden" name="action" value="review_document">

                                <input
                                    type="hidden"
                                    name="project_id"
                                    value="<?= (int) $project['project_id'] ?>"
                                >

                                <label>Document Review</label>

                                <select name="review_status" required>
                                    <option value="Under Review"
                                        <?= $project['review_status'] === 'Under Review' ? 'selected' : '' ?>>
                                        Under Review
                                    </option>

                                    <option value="Revision Required"
                                        <?= $project['review_status'] === 'Revision Required' ? 'selected' : '' ?>>
                                        Revision Required
                                    </option>

                                    <option value="Approved"
                                        <?= $project['review_status'] === 'Approved' ? 'selected' : '' ?>>
                                        Approved
                                    </option>
                                </select>

                                <textarea
                                    name="adviser_feedback"
                                    rows="3"
                                    maxlength="5000"
                                    placeholder="Enter feedback for the student..."
                                    style="width:100%; margin-top:8px;"
                                ><?= h($project['adviser_feedback'] ?? '') ?></textarea>

                                <button type="submit">Save Review</button>

                                <?php if (!empty($project['reviewed_at'])): ?>
                                    <p style="font-size:12px;">
                                        Last reviewed:
                                        <?= h($project['reviewed_at']) ?>
                                    </p>
                                <?php endif; ?>
                            </form>
                <?php else: ?>
                    No action available
                <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                        No projects have been assigned to you yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    
        <div class="dashboard-section" id="defense-requests">
    <div class="card">
        <h2>Defense Request Feedback</h2>
            <p>Write comments for students about their defense requests.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Project</th>
                            <th>Defense Type</th>
                            <th>Request Status</th>
                            <th>Adviser Review and Revision Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($defenseRequests->num_rows > 0): ?>
                        <?php while ($request = $defenseRequests->fetch_assoc()): ?>
                            <tr>
                                <td><?= h($request['student_name']) ?></td>
                                <td><?= h($request['title']) ?></td>
                                <td><?= h($request['defense_type']) ?></td>
                                <td><?= h($request['status']) ?></td>
                                
                            <td>
                                <form method="POST" action="adviser_dashboard.php">
                                    <input type="hidden" name="action" value="review_request">

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= (int) $request['request_id'] ?>"
                                    >

                                    <textarea
                                        name="adviser_comments"
                                        rows="4"
                                        maxlength="5000"
                                        placeholder="Enter feedback or revision notes..."
                                        style="width:100%; min-width:180px;"
                                        <?= $request['status'] !== 'Pending' ? 'readonly' : '' ?>
                                    ><?= h($request['adviser_comments'] ?? '') ?></textarea>

                                    <?php if ($request['status'] === 'Pending'): ?>
                                        <button
                                            type="submit"
                                            name="decision"
                                            value="approve"
                                            onclick="return confirm('Approve this defense request?');"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            type="submit"
                                            name="decision"
                                            value="reject"
                                            onclick="return confirm('Reject this request and send revision notes?');"
                                        >
                                            Reject
                                        </button>
                                    <?php else: ?>
                                        <p>Request already reviewed.</p>
                                    <?php endif; ?>
                                </form>
                            </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No defense requests have been submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
<?php endif; ?>

</div>

<script>
document.querySelectorAll('.nav-link[data-section]').forEach(link => {
    link.addEventListener('click', function (event) {
        event.preventDefault();

        const target = this.dataset.section;
        const targetSection = document.getElementById(target);

        if (!targetSection) return;

        document.querySelectorAll('.dashboard-section').forEach(section => {
            section.classList.remove('active');
        });

        document.querySelectorAll('.nav-link[data-section]').forEach(item => {
            item.classList.remove('active');
        });

        targetSection.classList.add('active');
        this.classList.add('active');
    });
});
</script>
</body>
</html> 