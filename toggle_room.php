<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

function toggleRoomError(string $message): void
{
    $_SESSION['room_message'] = $message;
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$roomId = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);

if (!$roomId || $roomId < 1) {
    toggleRoomError('Invalid room selected.');
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "SELECT is_active
         FROM rooms
         WHERE room_id = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $roomId);
    $stmt->execute();

    $room = $stmt->get_result()->fetch_assoc();

    if (!$room) {
        throw new Exception('The selected room does not exist.');
    }

    $newStatus = (int) $room['is_active'] === 1 ? 0 : 1;

    $update = $conn->prepare(
        "UPDATE rooms
         SET is_active = ?
         WHERE room_id = ?"
    );

    $update->bind_param('ii', $newStatus, $roomId);
    $update->execute();

    $conn->commit();

    $_SESSION['room_message'] = $newStatus
        ? 'Room activated successfully.'
        : 'Room deactivated successfully.';

    header("Location: admin_dashboard.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    if ($e instanceof Exception) {
        toggleRoomError($e->getMessage());
    }

    error_log($e->getMessage());
    toggleRoomError('Unable to change the room status. Please try again.');
}
