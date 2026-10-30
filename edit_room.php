<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

$name = $_SESSION['name'] ?? 'Administrator';

$room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);

if (!$room_id || $room_id < 1) {
    $_SESSION['room_message'] = 'Invalid room selected.';
    header('Location: admin_dashboard.php#rooms');
    exit;
}

$stmt = $conn->prepare(
    "SELECT room_id, room_name, capacity, location
     FROM rooms
     WHERE room_id = ?"
);
$stmt->bind_param("i", $room_id);
$stmt->execute();

$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();

if (!$room) {
    $_SESSION['room_message'] = 'Room not found.';
    header('Location: admin_dashboard.php#rooms');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room | Thesis Defense System</title>

    <link rel="stylesheet" href="admin_dashboard.css?v=3">

    <style>
        /* Edit Room page layout */
        .edit-room-section {
            display: block;
            padding: 30px;
            min-height: calc(100vh - 90px);
        }

        .edit-room-section .section-heading {
            margin-bottom: 25px;
        }

        .edit-room-section .section-heading h2 {
            color: #39FF88;
            font-size: 25px;
        }

        .edit-room-section .section-heading p {
            color: #D9DCE5;
            margin-top: 8px;
        }

        .edit-room-card {
            max-width: 800px;
            background: #151e30;
            border: 1px solid #2b374b;
            border-radius: 10px;
            padding: 28px;
        }

        .edit-room-card h2 {
            color: #39FF88;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .edit-room-card .form-description {
            color: #D9DCE5;
            font-size: 13px;
            margin-bottom: 24px;
        }

        .edit-room-card label {
            display: block;
            color: #f1f5f9;
            font-size: 14px;
            font-weight: 600;
            margin: 20px 0 9px;
        }

        .edit-room-card input {
            display: block;
            width: 100%;
            padding: 12px;
            background: #0b1120;
            color: #f1f5f9;
            border: 1px solid #35445c;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }

        .edit-room-card input:focus {
            outline: 1px solid #39FF88;
            border-color: #39FF88;
        }

        .edit-room-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .edit-room-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border: none;
            border-radius: 7px;
            background: #39FF88;
            color: #0B132B !important;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .edit-room-actions .btn:hover {
            background: #28D968;
            color: #0B132B !important;
        }

        .edit-room-back {
            display: inline-block;
            margin-top: 20px;
            color: #39FF88;
            font-size: 13px;
            text-decoration: none;
        }

        .edit-room-back:hover {
            text-decoration: underline;
        }

        @media (max-width: 750px) {
            .edit-room-section {
                padding: 18px;
            }

            .edit-room-card {
                padding: 20px;
            }
        }

        @media (max-width: 520px) {
            .edit-room-section {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">
        <h2>TDS</h2>
        <p>THESIS DEFENSE<br>SYSTEM</p>
    </div>

    <nav>
        <a href="admin_dashboard.php#dashboard" class="nav-link">⌂ <span>Dashboard</span></a>
        <a href="admin_dashboard.php#users" class="nav-link">♟ <span>User Management</span></a>
        <a href="admin_dashboard.php#projects" class="nav-link">▣ <span>Project Management</span></a>
        <a href="admin_dashboard.php#requests" class="nav-link">◷ <span>Defense Requests</span></a>
        <a href="admin_dashboard.php#schedule" class="nav-link">▦ <span>Defense Schedule</span></a>
        <a href="admin_dashboard.php#panels" class="nav-link">♙ <span>Panel Assignment</span></a>
        <a href="admin_dashboard.php#rooms" class="nav-link active">▤ <span>Room Management</span></a>
        <a href="admin_dashboard.php#conflicts" class="nav-link">⚠ <span>Conflict Detection</span></a>
        <a href="admin_dashboard.php#reports" class="nav-link">▧ <span>Reports</span></a>
    </nav>

    <div class="logout">
        <form action="logout.php" method="POST">
            <button type="submit">Logout</button>
        </form>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <div>
            <h1>Edit Room</h1>
            <p>Thesis and Capstone Defense Scheduling and Panel Management System</p>
        </div>

        <div class="profile">
            <div class="profile-info">
                <strong><?= h($name) ?></strong>
                <small>Administrator</small>
            </div>

            <div class="profile-icon">
                <?= h(strtoupper(substr($name, 0, 1))) ?>
            </div>
        </div>
    </header>

    <section class="content-section active-section edit-room-section">
        <div class="section-heading">
            <h2>Room Management</h2>
            <p>Edit the information for the selected room.</p>
        </div>

        <div class="edit-room-card">
            <h2>Room Information</h2>
            <p class="form-description">
                Update the room name, seating capacity, or building location.
            </p>

            <form method="POST" action="update_room.php">
                <input
                    type="hidden"
                    name="room_id"
                    value="<?= (int) $room['room_id'] ?>"
                >

                <label for="room_name">Room Name</label>
                <input
                    type="text"
                    id="room_name"
                    name="room_name"
                    maxlength="100"
                    value="<?= h($room['room_name']) ?>"
                    required
                >

                <label for="capacity">Capacity</label>
                <input
                    type="number"
                    id="capacity"
                    name="capacity"
                    min="1"
                    value="<?= (int) $room['capacity'] ?>"
                    required
                >

                <label for="location">Location</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    maxlength="150"
                    value="<?= h($room['location'] ?? '') ?>"
                    required
                >

                <div class="edit-room-actions">
                    <button type="submit" class="btn">
                        Save Changes
                    </button>

                    <a href="admin_dashboard.php#rooms" class="btn">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <a href="admin_dashboard.php#rooms" class="edit-room-back">
            ← Back to dashboard
        </a>
    </section>
</main>

</body>
</html>
