
<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

function updateRoomError(string $message): void
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
$roomName = trim($_POST['room_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

if (
    !$roomId || $roomId < 1 ||
    $roomName === '' ||
    $location === '' ||
    !$capacity || $capacity < 1 ||
    strlen($roomName) > 100 ||
    strlen($location) > 150
) {
    updateRoomError('Please enter valid room details.');
}

try {
    $stmt = $conn->prepare(
        "UPDATE rooms
         SET room_name = ?, capacity = ?, location = ?
         WHERE room_id = ?"
    );

    $stmt->bind_param(
        'sisi',
        $roomName,
        $capacity,
        $location,
        $roomId
    );

    $stmt->execute();

    $_SESSION['room_message'] = $stmt->affected_rows >= 0
        ? 'Room details updated successfully.'
        : 'Unable to update room details.';

    header("Location: admin_dashboard.php");
    exit;

} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        updateRoomError('A room with that name already exists.');
    }

    error_log($e->getMessage());
    updateRoomError('Unable to update the room. Please try again.');
}
