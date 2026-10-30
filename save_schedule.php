<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

// Only accept form submissions.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

// Get and validate form inputs.
$requestId = filter_input(
    INPUT_POST,
    'request_id',
    FILTER_VALIDATE_INT
);

$roomId = filter_input(
    INPUT_POST,
    'room_id',
    FILTER_VALIDATE_INT
);

$defenseDate = $_POST['defense_date'] ?? '';
$startTime = $_POST['start_time'] ?? '';
$endTime = $_POST['end_time'] ?? '';

function scheduleError(string $message): void
{
    $_SESSION['schedule_message'] = $message;
    $_SESSION['schedule_message_type'] = 'error';

    error_log("SCHEDULE ERROR MESSAGE: " . $message);

    header("Location: admin_dashboard.php#schedule");
    exit;
}

if (
    !$requestId ||
    !$roomId ||
    $defenseDate === '' ||
    $startTime === '' ||
    $endTime === ''
) {
    scheduleError('Please complete all schedule fields.');
}

// Validate date format and prevent past dates.
$dateObject = DateTime::createFromFormat('!Y-m-d', $defenseDate);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $defenseDate ||
    $defenseDate < date('Y-m-d')
) {
    scheduleError('Please select a valid date that is not in the past.');
}

 // Validate time format and order.
if (
    !preg_match('/^\d{2}:\d{2}$/', $startTime) ||
    !preg_match('/^\d{2}:\d{2}$/', $endTime) ||
    $startTime >= $endTime ||
    !checkTime($startTime) ||
    !checkTime($endTime)
) {
    scheduleError(
        'Please enter valid times, with the end time later than the start time.'
    );
}

function checkTime(string $time): bool
{
    $parsedTime = DateTime::createFromFormat('!H:i', $time);

    return $parsedTime !== false
        && $parsedTime->format('H:i') === $time;
}

try {
    $conn->begin_transaction();

    // Lock and verify the approved request.
    $requestCheck = $conn->prepare(
        "SELECT request_id
         FROM defense_requests
         WHERE request_id = ?
         AND status = 'Approved'
         FOR UPDATE"
    );

    $requestCheck->bind_param('i', $requestId);
    $requestCheck->execute();

    if (!$requestCheck->get_result()->fetch_assoc()) {
        throw new Exception(
            'This request is not approved or has already been scheduled.'
        );
    }

    // Verify that the selected room exists and is active.
    $roomCheck = $conn->prepare(
        "SELECT room_id
         FROM rooms
         WHERE room_id = ?
           AND is_active = 1"
    );

    $roomCheck->bind_param('i', $roomId);
    $roomCheck->execute();

    if (!$roomCheck->get_result()->fetch_assoc()) {
        throw new Exception(
            'The selected room is unavailable. Please choose an active room.'
        );
    }

    // Prevent overlapping scheduled defenses in the same room.
    $conflictCheck = $conn->prepare(
        "SELECT schedule_id
         FROM defense_schedules
         WHERE room_id = ?
           AND defense_date = ?
           AND status = 'Scheduled'
           AND start_time < ?
           AND end_time > ?
         LIMIT 1
         FOR UPDATE"
    );

    $conflictCheck->bind_param(
        'isss',
        $roomId,
        $defenseDate,
        $endTime,
        $startTime
    );

    $conflictCheck->execute();

    if ($conflictCheck->get_result()->fetch_assoc()) {
        throw new Exception(
            'This room already has a defense scheduled during that time.'
        );
    }

    // Create the defense schedule.
    $insertSchedule = $conn->prepare(
        "INSERT INTO defense_schedules
            (request_id, room_id, defense_date,
             start_time, end_time, status)
         VALUES (?, ?, ?, ?, ?, 'Scheduled')"
    );

    $insertSchedule->bind_param(
        'iisss',
        $requestId,
        $roomId,
        $defenseDate,
        $startTime,
        $endTime
    );

    $insertSchedule->execute();
    
    error_log(
    "Schedule inserted. New schedule ID: " .
    $conn->insert_id
);

    // Update the request status after successful scheduling.
    $updateRequest = $conn->prepare(
        "UPDATE defense_requests
         SET status = 'Scheduled'
         WHERE request_id = ?
           AND status = 'Approved'"
    );

    $updateRequest->bind_param('i', $requestId);
    $updateRequest->execute();

    if ($updateRequest->affected_rows !== 1) {
        throw new Exception(
            'The request status could not be updated.'
        );
    }

    $conn->commit();

    $_SESSION['schedule_message'] =
        'Defense schedule created successfully.';

    $_SESSION['schedule_message_type'] = 'success';

    header("Location: admin_dashboard.php#schedule");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {
        scheduleError(
            'This defense request already has a schedule.'
        );
    }

    if ($e instanceof Exception) {
        scheduleError($e->getMessage());
    }

    error_log($e->getMessage());

    scheduleError(
        'An unexpected error occurred while creating the schedule.'
    );
}
