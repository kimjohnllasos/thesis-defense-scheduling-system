<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

function roomError(string $message): void
{
    $_SESSION['room_message'] = $message;
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$roomName = trim($_POST['room_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

if (
    $roomName === '' ||
    $location === '' ||
    !$capacity ||
    $capacity < 1 ||
    strlen($roomName) > 100 ||
    strlen($location) > 150
) {
    roomError('Please enter a valid room name, capacity, and location.');
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO rooms (room_name, capacity, location, is_active)
         VALUES (?, ?, ?, 1)"
    );

    $stmt->bind_param('sis', $roomName, $capacity, $location);
    $stmt->execute();

    $_SESSION['room_message'] = 'Room added successfully.';

    header("Location: admin_dashboard.php");
    exit;

} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        roomError('A room with that name already exists.');
    }

    error_log($e->getMessage());
    roomError('Unable to add the room. Please try again.');
}
