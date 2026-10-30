<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';

$allowedStatuses = ['Scheduled', 'Completed', 'Cancelled'];

if (
    !$scheduleId ||
    !in_array($status, $allowedStatuses, true)
) {
    $_SESSION['schedule_message'] = 'Invalid schedule status.';
    header("Location: admin_dashboard.php");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE defense_schedules
     SET status = ?
     WHERE schedule_id = ?"
);

$stmt->bind_param("si", $status, $scheduleId);

if ($stmt->execute() && $stmt->affected_rows >= 0) {
    $_SESSION['schedule_message'] = 'Schedule status updated successfully.';
} else {
    $_SESSION['schedule_message'] = 'Unable to update schedule status.';
}

$stmt->close();

header("Location: admin_dashboard.php");
exit;
?>
