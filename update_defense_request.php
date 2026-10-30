<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$requestId = filter_input(
    INPUT_POST,
    'request_id',
    FILTER_VALIDATE_INT
);

$action = $_POST['action'] ?? '';

if (!$requestId || !in_array($action, ['approve', 'reject'], true)) {
    header("Location: admin_dashboard.php?request_error=1");
    exit;
}

// Only allow decisions on pending requests.
$check = $conn->prepare(
    "SELECT request_id
     FROM defense_requests
     WHERE request_id = ?
       AND status = 'Pending'"
);
$check->bind_param("i", $requestId);
$check->execute();

if (!$check->get_result()->fetch_assoc()) {
    header("Location: admin_dashboard.php?request_error=1");
    exit;
}

$status = ($action === 'approve') ? 'Approved' : 'Rejected';

$update = $conn->prepare(
    "UPDATE defense_requests
     SET status = ?
     WHERE request_id = ?
       AND status = 'Pending'"
);
$update->bind_param("si", $status, $requestId);
$update->execute();

if ($update->affected_rows === 1) {
    header("Location: admin_dashboard.php?request_updated=1");
} else {
    header("Location: admin_dashboard.php?request_error=1");
}

exit;
