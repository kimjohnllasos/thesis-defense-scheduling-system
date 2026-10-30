<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Panel Member']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: panel_dashboard.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
$ratingInput = $_POST['rating'] ?? '';
$comments = trim($_POST['comments'] ?? '');
$recommendation = $_POST['recommendation'] ?? '';

if (
    !$scheduleId ||
    !is_numeric($ratingInput) ||
    (float) $ratingInput < 1 ||
    (float) $ratingInput > 100 ||
    !in_array($recommendation, ['Pass', 'Revise', 'Fail'], true)
) {
    header("Location: panel_dashboard.php?evaluation=invalid");
    exit;
}

$rating = (float) $ratingInput;

$stmt = $conn->prepare(
    "SELECT pm.panel_id
     FROM panel_members pm
     INNER JOIN panel_assignments pa
        ON pm.panel_id = pa.panel_id
     INNER JOIN defense_schedules ds
        ON pa.schedule_id = ds.schedule_id
     WHERE pm.user_id = ?
       AND pm.is_active = 1
       AND ds.schedule_id = ?
       AND ds.status = 'Completed'
     LIMIT 1"
);

$stmt->bind_param("ii", $userId, $scheduleId);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assignment) {
    header("Location: panel_dashboard.php?evaluation=not_allowed");
    exit;
}

$panelId = (int) $assignment['panel_id'];

$check = $conn->prepare(
    "SELECT evaluation_id
     FROM evaluations
     WHERE schedule_id = ? AND panel_id = ?
     LIMIT 1"
);
$check->bind_param("ii", $scheduleId, $panelId);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    header("Location: panel_dashboard.php?evaluation=already_submitted");
    exit;
}

$insert = $conn->prepare(
    "INSERT INTO evaluations
        (schedule_id, panel_id, rating, comments, recommendation)
     VALUES (?, ?, ?, ?, ?)"
);

$insert->bind_param(
    "iidss",
    $scheduleId,
    $panelId,
    $rating,
    $comments,
    $recommendation
);

if ($insert->execute()) {
    $insert->close();
    header("Location: panel_dashboard.php?evaluation=success");
    exit;
}

$insert->close();
error_log("Evaluation submission failed for schedule ID: " . $scheduleId);
header("Location: panel_dashboard.php?evaluation=error");
exit;
?>
