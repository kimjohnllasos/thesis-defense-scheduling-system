<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

// Load approved requests that do not have a schedule yet.
$requestQuery = $conn->query(
    "SELECT
        dr.request_id,
        dr.defense_type,
        dr.requested_date,
        dr.preferred_time,
        p.title,
        p.project_type
     FROM defense_requests dr
     INNER JOIN projects p
        ON dr.project_id = p.project_id
     LEFT JOIN defense_schedules ds
        ON dr.request_id = ds.request_id
     WHERE dr.status = 'Approved'
       AND ds.schedule_id IS NULL
     ORDER BY dr.requested_date ASC"
);

// Load active rooms.
$roomQuery = $conn->query(
    "SELECT room_id, room_name, location
     FROM rooms
     WHERE is_active = 1
     ORDER BY room_name ASC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Defense Schedule</title>
</head>
<body>

<h1>Create Defense Schedule</h1>

<p>Choose an approved defense request and assign its schedule.</p>

<form method="POST" action="save_schedule.php">

    <label for="request_id">Approved Defense Request</label>
    <select name="request_id" id="request_id" required>
        <option value="">Select a defense request</option>

        <?php while ($request = $requestQuery->fetch_assoc()): ?>
            <option value="<?= (int) $request['request_id'] ?>">
                <?= h($request['title']) ?>
                — <?= h($request['project_type']) ?>
                — <?= h($request['defense_type']) ?>
                — Requested: <?= h($request['requested_date']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <br><br>

    <label for="defense_date">Defense Date</label>
    <input
        type="date"
        name="defense_date"
        id="defense_date"
        min="<?= date('Y-m-d') ?>"
        required
    >

    <br><br>

    <label for="start_time">Start Time</label>
    <input
        type="time"
        name="start_time"
        id="start_time"
        required
    >

    <br><br>

    <label for="end_time">End Time</label>
    <input
        type="time"
        name="end_time"
        id="end_time"
        required
    >

    <br><br>

    <label for="room_id">Room</label>
    <select name="room_id" id="room_id" required>
        <option value="">Select a room</option>

        <?php while ($room = $roomQuery->fetch_assoc()): ?>
            <option value="<?= (int) $room['room_id'] ?>">
                <?= h($room['room_name']) ?>
                <?php if (!empty($room['location'])): ?>
                    — <?= h($room['location']) ?>
                <?php endif; ?>
            </option>
        <?php endwhile; ?>
    </select>

    <br><br>

    <button type="submit">Create Schedule</button>

    <a href="admin_dashboard.php">Cancel</a>

</form>

</body>
</html>
