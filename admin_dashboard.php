<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

$name = $_SESSION['name'] ?? 'Administrator';

function countRecords(mysqli $conn, string $sql): int
{
    return (int) $conn->query($sql)->fetch_assoc()['total'];
}

$totalProjects = countRecords(
    $conn,
    "SELECT COUNT(*) AS total FROM projects"
);

$pendingRequests = countRecords(
    $conn,
    "SELECT COUNT(*) AS total
     FROM defense_requests
     WHERE status = 'Pending'"
);

$totalSchedules = countRecords(
    $conn,
    "SELECT COUNT(*) AS total
     FROM defense_schedules
     WHERE status = 'Scheduled'"
);

$totalUsers = countRecords(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

$recentRequests = $conn->query(
    "SELECT p.title, p.project_type,
            dr.defense_type, dr.requested_date, dr.status
     FROM defense_requests dr
     INNER JOIN projects p ON p.project_id = dr.project_id
     ORDER BY dr.submitted_at DESC
     LIMIT 5"
);

function statusClass(string $status): string
{
    if (in_array($status, ['Approved', 'Scheduled', 'Completed'], true)) {
        return 'approved';
    }

    if ($status === 'Pending') {
        return 'pending';
    }

    return 'unavailable';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard | Thesis Defense System</title>
    <link rel="stylesheet" href="admin_dashboard.css?v=5">
</head>
<body>

<aside class="sidebar">
    <div class="logo">
        <h2>TDS</h2>
        <p>THESIS DEFENSE<br>SYSTEM</p>
    </div>

    <nav>
        <a href="#" class="nav-link active" data-section="dashboard">⌂ <span>Dashboard</span></a>
        <a href="#" class="nav-link" data-section="users">♟ <span>User Management</span></a>
        <a href="#" class="nav-link" data-section="projects">▣ <span>Project Management</span></a>
        <a href="#" class="nav-link" data-section="requests">◷ <span>Defense Requests</span></a>
        <a href="#" class="nav-link" data-section="schedule">▦ <span>Defense Schedule</span></a>
        <a href="#" class="nav-link" data-section="panels">♙ <span>Panel Assignment</span></a>
        <a href="#" class="nav-link" data-section="rooms">▤ <span>Room Management</span></a>
        <a href="#" class="nav-link" data-section="conflicts">⚠ <span>Conflict Detection</span></a>
        <a href="#" class="nav-link" data-section="reports">▧ <span>Reports</span></a>
    </nav>

    <div class="logout">
        <button id="logoutButton" type="button">Logout</button>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <div>
            <h1 id="pageTitle">Dashboard</h1>
            <p>Thesis and Capstone Defense Scheduling and Panel Management System</p>
        </div>

        <div class="profile">
            <div class="profile-info">
                <strong id="userName"><?= h($name) ?></strong>
                <small>Administrator</small>
            </div>
            <div class="profile-icon">
                <?= h(strtoupper(substr($name, 0, 1))) ?>
            </div>
        </div>
    </header>

    <!-- DASHBOARD -->
    <section class="content-section active-section" id="dashboard">
        <div class="statistics">
            <div class="stat-card">
                <p>Total Projects</p>
                <h2><?= $totalProjects ?></h2>
                <span>▣</span>
            </div>

            <div class="stat-card">
                <p>Pending Requests</p>
                <h2><?= $pendingRequests ?></h2>
                <span>◷</span>
            </div>

            <div class="stat-card">
                <p>Scheduled Defenses</p>
                <h2><?= $totalSchedules ?></h2>
                <span>▦</span>
            </div>

            <div class="stat-card">
                <p>Registered Users</p>
                <h2><?= $totalUsers ?></h2>
                <span>♟</span>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Recent Defense Requests</h2>
                    <button class="small-button" data-open="requests" type="button">
                        View All
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Type</th>
                                <th>Requested Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentRequests->num_rows > 0): ?>
                            <?php while ($request = $recentRequests->fetch_assoc()): ?>
                                <tr>
                                    <td><?= h($request['title']) ?></td>
                                    <td><?= h($request['project_type']) ?> — <?= h($request['defense_type']) ?></td>
                                    <td><?= h($request['requested_date']) ?></td>
                                    <td>
                                        <span class="status <?= h(statusClass($request['status'])) ?>">
                                            <?= h($request['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No defense requests have been submitted yet.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>

                <div class="quick-actions">
                    <button class="action-button" data-open="requests" type="button">Review Defense Requests</button>
                    <button class="action-button" data-open="schedule" type="button">Manage Defense Schedule</button>
                    <button class="action-button" data-open="panels" type="button">Assign Panel Members</button>
                    <button class="action-button" data-open="rooms" type="button">Manage Rooms</button>
                </div>
            </div>
        </div>
    </section>

    <!-- OTHER SECTIONS: preserved for the next integration stages -->
    
<section class="content-section" id="users">
    <div class="section-heading">
        <h2>User Management</h2>
        <p>View registered system user accounts.</p>

                <a href="add_user.php" class="btn">
        Add User
    </a>
    
    
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $userQuery = $conn->query(
                        "SELECT user_id, name, username, role, created_at
                         FROM users
                         ORDER BY user_id DESC"
                    );

                    if ($userQuery->num_rows > 0):
                        while ($user = $userQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= h($user['user_id']) ?></td>
                            <td><?= h($user['name']) ?></td>
                            <td><?= h($user['username']) ?></td>                           
                            <td><?= h($user['role']) ?></td>
                            <td><?= h($user['created_at']) ?></td>
                        
                        <td>
                                <a href="edit_user.php?id=<?= (int) $user['user_id'] ?>" class="btn">
                                    Edit
                                </a>

                            <form method="POST" action="delete_user.php"
                                  onsubmit="return confirm('Are you sure you want to delete this user?');"
                                  style="display:inline;">
                                <input type="hidden" name="user_id"
                                       value="<?= (int) $user['user_id'] ?>">
                                <button type="submit" class="btn">
                                    Delete
                                </button>
                            </form>
                        </td>

                        </tr>

                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="6">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>  

<section class="content-section" id="projects">
    <div class="section-heading">
        <h2>Project Management</h2>
        <p>View registered thesis and capstone projects.</p>

        <a href="add_project.php" class="btn">
            Add Project
        </a>
    </div>


    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project Title</th>
                        <th>Type</th>
                        <th>Student</th>
                        <th>Adviser</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $projectQuery = $conn->query(
                        "SELECT
                            p.project_id,
                            p.title,
                            p.project_type,
                            p.status,
                            s.student_number,
                            su.name AS student_name,
                            au.name AS adviser_name
                        FROM projects p
                        LEFT JOIN students s
                            ON p.student_id = s.student_id
                        LEFT JOIN users su
                            ON s.user_id = su.user_id
                        LEFT JOIN advisers a
                            ON p.adviser_id = a.adviser_id
                        LEFT JOIN users au
                            ON a.user_id = au.user_id
                        ORDER BY p.project_id DESC"
                    );

                    if ($projectQuery->num_rows > 0):
                        while ($project = $projectQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= h($project['project_id']) ?></td>
                            <td><?= h($project['title']) ?></td>
                            <td><?= h($project['project_type']) ?></td>
                            <td>
                                <?= h($project['student_name'] ?? 'Not assigned') ?>
                                <?php if (!empty($project['student_number'])): ?>
                                    <br>
                                    <small><?= h($project['student_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= h($project['adviser_name'] ?? 'Not assigned') ?></td>
                            <td><?= h($project['status']) ?></td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="6">No projects have been registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
   
<section class="content-section" id="requests">
    <div class="section-heading">
        <h2>Defense Requests</h2>
        <p>View and review submitted defense requests.</p>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Project</th>
                        <th>Student</th>
                        <th>Defense Type</th>
                        <th>Requested Date</th>
                        <th>Preferred Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $requestQuery = $conn->query(
                        "SELECT
                            dr.request_id,
                            dr.defense_type,
                            dr.requested_date,
                            dr.preferred_time,
                            dr.status,
                            p.title,
                            su.name AS student_name
                        FROM defense_requests dr
                        INNER JOIN projects p
                            ON dr.project_id = p.project_id
                        LEFT JOIN students s
                            ON p.student_id = s.student_id
                        LEFT JOIN users su
                            ON s.user_id = su.user_id
                        ORDER BY dr.submitted_at DESC"
                    );

                    if ($requestQuery->num_rows > 0):
                        while ($request = $requestQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= h($request['request_id']) ?></td>
                            <td><?= h($request['title']) ?></td>
                            <td><?= h($request['student_name'] ?? 'Not assigned') ?></td>
                            <td><?= h($request['defense_type']) ?></td>
                            <td><?= h($request['requested_date']) ?></td>
                            <td><?= h($request['preferred_time'] ?? 'Not specified') ?></td>
                            
                        <td>
                            <span class="status <?= h(statusClass($request['status'])) ?>">
                                <?= h($request['status']) ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($request['status'] === 'Pending'): ?>
                                <span>Waiting for adviser review</span>
                            <?php elseif ($request['status'] === 'Approved'): ?>
                                <span>Ready for scheduling</span>
                            <?php elseif ($request['status'] === 'Rejected'): ?>
                                <span>Rejected by adviser</span>
                            <?php elseif ($request['status'] === 'Scheduled'): ?>
                                <span>Schedule created</span>
                            <?php endif; ?>
                        </td>


                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="8">No defense requests have been submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

 

<section class="content-section" id="schedule">
    <div class="section-heading">
        <h2>Defense Schedule</h2>
        <p>View and manage scheduled thesis and capstone defenses.</p>
    </div>
    
<?php if (!empty($_SESSION['schedule_message'])): ?>
    <div class="card">
        <p>
            <?= h($_SESSION['schedule_message']) ?>
        </p>

    </div>

    <?php
    unset($_SESSION['schedule_message']);
    unset($_SESSION['schedule_message_type']);
    ?>
<?php endif; ?>


    <!-- CREATE SCHEDULE FORM -->
    <div class="card">
        <div class="card-header">
            <h2>Create New Schedule</h2>
        </div>

        <?php
        $approvedRequests = $conn->query(
            "SELECT
                dr.request_id,
                dr.defense_type,
                dr.requested_date,
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

        $activeRooms = $conn->query(
            "SELECT room_id, room_name, location
             FROM rooms
             WHERE is_active = 1
             ORDER BY room_name ASC"
        );
        ?>

        <form method="POST" action="save_schedule.php">

            <label for="request_id">Approved Defense Request</label>
            <select name="request_id" id="request_id" required>
                <option value="">Select an approved request</option>

                <?php while ($request = $approvedRequests->fetch_assoc()): ?>
                    <option value="<?= (int) $request['request_id'] ?>">
                        <?= h($request['title']) ?>
                        — <?= h($request['project_type']) ?>
                        — <?= h($request['defense_type']) ?>
                        — <?= h($request['requested_date']) ?>
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

                <?php while ($room = $activeRooms->fetch_assoc()): ?>
                    <option value="<?= (int) $room['room_id'] ?>">
                        <?= h($room['room_name']) ?>
                        <?php if (!empty($room['location'])): ?>
                            — <?= h($room['location']) ?>
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <br><br>

            <button type="submit" class="btn">
                Create Schedule
            </button>

        </form>
    </div>

    <!-- EXISTING SCHEDULE TABLE CONTINUES BELOW -->


    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Schedule ID</th>
                        <th>Project</th>
                        <th>Defense Type</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Room</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $scheduleQuery = $conn->query(
                        "SELECT
                            ds.schedule_id,
                            ds.defense_date,
                            ds.start_time,
                            ds.end_time,
                            ds.status,
                            p.title,
                            dr.defense_type,
                            r.room_name
                        FROM defense_schedules ds
                        INNER JOIN defense_requests dr
                            ON ds.request_id = dr.request_id
                        INNER JOIN projects p
                            ON dr.project_id = p.project_id
                        LEFT JOIN rooms r
                            ON ds.room_id = r.room_id
                        ORDER BY ds.defense_date DESC, ds.start_time DESC"
                    );

                    if ($scheduleQuery->num_rows > 0):
                        while ($schedule = $scheduleQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= h($schedule['schedule_id']) ?></td>
                            <td><?= h($schedule['title']) ?></td>
                            <td><?= h($schedule['defense_type']) ?></td>
                            <td><?= h($schedule['defense_date']) ?></td>
                            <td><?= h($schedule['start_time']) ?></td>
                            <td><?= h($schedule['end_time']) ?></td>
                            <td><?= h($schedule['room_name'] ?? 'Not assigned') ?></td>                           
                            <td>
                                <span class="status <?= h(statusClass($schedule['status'])) ?>">
                                    <?= h($schedule['status']) ?>
                                </span>

                                <form method="POST" action="update_schedule_status.php">
                                <input
                                    type="hidden"
                                    name="schedule_id"
                                    value="<?= (int) $schedule['schedule_id'] ?>"
                                >

                                <select name="status" required>
                                    <option value="Scheduled"
                                        <?= $schedule['status'] === 'Scheduled' ? 'selected' : '' ?>>
                                        Scheduled
                                    </option>

                                    <option value="Completed"
                                        <?= $schedule['status'] === 'Completed' ? 'selected' : '' ?>>
                                        Completed
                                    </option>

                                    <option value="Cancelled"
                                        <?= $schedule['status'] === 'Cancelled' ? 'selected' : '' ?>>
                                        Cancelled
                                    </option>
                                </select>

                                <button type="submit" class="btn">Update Status</button>
                            </form>
                        </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="8">No defense schedules have been created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<section class="content-section" id="panels">
    <div class="section-heading">
        <h2>Panel Assignment</h2>
        <p>Assign panel members to scheduled thesis and capstone defenses.</p>
    </div>

    <?php if (!empty($_SESSION['panel_message'])): ?>
        <div class="card">
            <p><?= h($_SESSION['panel_message']) ?></p>
        </div>

        <?php
        unset($_SESSION['panel_message']);
        ?>
    <?php endif; ?>

    <!-- CREATE PANEL ASSIGNMENT FORM -->
    <div class="card">
        <div class="card-header">
            <h2>Create Panel Assignment</h2>
        </div>

        <?php
        $availableSchedules = $conn->query(
            "SELECT
                ds.schedule_id,
                ds.defense_date,
                ds.start_time,
                ds.end_time,
                p.title,
                dr.defense_type
             FROM defense_schedules ds
             INNER JOIN defense_requests dr
                ON ds.request_id = dr.request_id
             INNER JOIN projects p
                ON dr.project_id = p.project_id
             WHERE ds.status = 'Scheduled'
             ORDER BY ds.defense_date ASC, ds.start_time ASC"
        );

        $availablePanelMembers = $conn->query(
            "SELECT panel_id, panel_name, department
             FROM panel_members
             WHERE is_active = 1
             ORDER BY panel_name ASC"
        );
        ?>

        <form method="POST" action="save_panel_assignment.php">

            <label for="schedule_id">Scheduled Defense</label>
            <select name="schedule_id" id="schedule_id" required>
                <option value="">Select a scheduled defense</option>

                <?php while ($item = $availableSchedules->fetch_assoc()): ?>
                    <option value="<?= (int) $item['schedule_id'] ?>">
                        <?= h($item['title']) ?>
                        — <?= h($item['defense_type']) ?>
                        — <?= h($item['defense_date']) ?>
                        <?= h(substr($item['start_time'], 0, 5)) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <br><br>

            <label for="panel_id">Panel Member</label>
            <select name="panel_id" id="panel_id" required>
                <option value="">Select a panel member</option>

                <?php while ($member = $availablePanelMembers->fetch_assoc()): ?>
                    <option value="<?= (int) $member['panel_id'] ?>">
                        <?= h($member['panel_name']) ?>
                        — <?= h($member['department']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <br><br>

            <label for="panel_role">Panel Role</label>
            <select name="panel_role" id="panel_role" required>
                <option value="">Select a role</option>
                <option value="Chair">Chair</option>
                <option value="Member">Member</option>
                <option value="Secretary">Secretary</option>
            </select>

            <br><br>

            <button type="submit" class="btn">
                Assign Panel Member
            </button>

        </form>
    </div>

    <!-- YOUR EXISTING PANEL ASSIGNMENT TABLE CONTINUES BELOW -->

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Schedule ID</th>
                        <th>Project</th>
                        <th>Defense Date</th>
                        <th>Panel Member</th>
                        <th>Department</th>
                        <th>Specialization</th>
                        <th>Role</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $panelQuery = $conn->query(
                        "SELECT
                            ds.schedule_id,
                            p.title,
                            ds.defense_date,
                            pm.panel_name,
                            pm.department,
                            pm.specialization,
                            pa.panel_role
                        FROM panel_assignments pa
                        INNER JOIN defense_schedules ds
                            ON pa.schedule_id = ds.schedule_id
                        INNER JOIN panel_members pm
                            ON pa.panel_id = pm.panel_id
                        INNER JOIN defense_requests dr
                            ON ds.request_id = dr.request_id
                        INNER JOIN projects p
                            ON dr.project_id = p.project_id
                        ORDER BY ds.defense_date DESC, ds.schedule_id DESC"
                    );

                    if ($panelQuery->num_rows > 0):
                        while ($panel = $panelQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= h($panel['schedule_id']) ?></td>
                            <td><?= h($panel['title']) ?></td>
                            <td><?= h($panel['defense_date']) ?></td>
                            <td><?= h($panel['panel_name']) ?></td>
                            <td><?= h($panel['department']) ?></td>
                            <td><?= h($panel['specialization']) ?></td>
                            <td><?= h($panel['panel_role']) ?></td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="7">No panel assignments have been created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="content-section" id="rooms">
    <div class="section-heading">
        <h2>Room Management</h2>
        <p>Manage rooms available for thesis and capstone defenses.</p>
    </div>

    <?php if (!empty($_SESSION['room_message'])): ?>
        <div class="card">
            <p><?= h($_SESSION['room_message']) ?></p>
        </div>
        <?php unset($_SESSION['room_message']); ?>
    <?php endif; ?>

    <!-- ADD ROOM FORM -->
    <div class="card">
        <div class="card-header">
            <h2>Add New Room</h2>
        </div>

        <form method="POST" action="save_room.php">
            <label for="room_name">Room Name</label>
            <input
                type="text"
                id="room_name"
                name="room_name"
                maxlength="100"
                placeholder="e.g. Computer Laboratory 2"
                required
            >

            <br><br>

            <label for="capacity">Capacity</label>
            <input
                type="number"
                id="capacity"
                name="capacity"
                min="1"
                required
            >

            <br><br>

            <label for="location">Location</label>
            <input
                type="text"
                id="location"
                name="location"
                maxlength="150"
                placeholder="e.g. Main Building"
                required
            >

            <br><br>

            <button type="submit" class="btn">Add Room</button>
        </form>
    </div>

    <!-- ROOM TABLE -->
    <div class="card">
        <div class="card-header">
            <h2>Registered Rooms</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Room ID</th>
                        <th>Room Name</th>
                        <th>Capacity</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $roomQuery = $conn->query(
                        "SELECT room_id, room_name, capacity, location, is_active
                         FROM rooms
                         ORDER BY room_id DESC"
                    );

                    if ($roomQuery && $roomQuery->num_rows > 0):
                        while ($room = $roomQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= (int) $room['room_id'] ?></td>
                            <td><?= h($room['room_name']) ?></td>
                            <td><?= (int) $room['capacity'] ?></td>
                            <td><?= h($room['location'] ?? 'Not specified') ?></td>
                            <td>
                                <?= $room['is_active'] ? 'Active' : 'Inactive' ?>
                            </td>
                            
                            <td>
                                <a
                                    href="edit_room.php?room_id=<?= (int) $room['room_id'] ?>"
                                    class="btn"
                                >
                                    Edit
                                </a>

                                <form method="POST"
                                    action="toggle_room.php"
                                    onsubmit="return confirm('Change this room status?');"
                                    style="display:inline;">

                                <input
                                    type="hidden"
                                    name="room_id"
                                    value="<?= (int) $room['room_id'] ?>"
                                >

                                <button type="submit" class="btn">
                                    <?= $room['is_active']
                                        ? 'Deactivate'
                                    : 'Activate' ?>
                                </button>
                            </form>
                        </td>

                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="6">
                                No rooms have been registered yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<section class="content-section" id="conflicts">
    <div class="section-heading">
        <h2>Conflict Detection</h2>
        <p>Check for overlapping defense schedules in the same room.</p>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Defense 1</th>
                        <th>Defense 2</th>
                        <th>Date</th>
                        <th>Time Overlap</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $conflictQuery = $conn->query(
                        "SELECT
                            r.room_name,
                            p1.title AS project_one,
                            p2.title AS project_two,
                            s1.defense_date,
                            s1.start_time AS start_one,
                            s1.end_time AS end_one,
                            s2.start_time AS start_two,
                            s2.end_time AS end_two
                        FROM defense_schedules s1
                        INNER JOIN defense_schedules s2
                            ON s1.room_id = s2.room_id
                            AND s1.defense_date = s2.defense_date
                            AND s1.schedule_id < s2.schedule_id
                        INNER JOIN rooms r
                            ON s1.room_id = r.room_id
                        INNER JOIN defense_requests d1
                            ON s1.request_id = d1.request_id
                        INNER JOIN projects p1
                            ON d1.project_id = p1.project_id
                        INNER JOIN defense_requests d2
                            ON s2.request_id = d2.request_id
                        INNER JOIN projects p2
                            ON d2.project_id = p2.project_id
                        WHERE s1.status = 'Scheduled'
                            AND s2.status = 'Scheduled'
                            AND s1.start_time < s2.end_time
                            AND s2.start_time < s1.end_time
                        ORDER BY s1.defense_date, s1.start_time"
                    );

                    if ($conflictQuery->num_rows > 0):
                        while ($conflict = $conflictQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= h($conflict['room_name']) ?></td>
                            <td><?= h($conflict['project_one']) ?></td>
                            <td><?= h($conflict['project_two']) ?></td>
                            <td><?= h($conflict['defense_date']) ?></td>
                            <td>
                                <?= h($conflict['start_one']) ?>–<?= h($conflict['end_one']) ?>
                                /
                                <?= h($conflict['start_two']) ?>–<?= h($conflict['end_two']) ?>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5">No room scheduling conflicts detected.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


    
<section class="content-section" id="reports">
    <div class="section-heading">
        <h2>Reports</h2>
        <p>View summaries of projects, defenses, panel assignments, and evaluations.</p>
    </div>

    <?php
    $reportProjects = $conn->query(
        "SELECT status, COUNT(*) AS total
         FROM projects
         GROUP BY status"
    );

    $reportSchedules = $conn->query(
        "SELECT status, COUNT(*) AS total
         FROM defense_schedules
         GROUP BY status"
    );

    $reportPanels = $conn->query(
        "SELECT COUNT(*) AS total
         FROM panel_assignments"
    )->fetch_assoc()['total'];

    $reportEvaluations = $conn->query(
        "SELECT COUNT(*) AS total
         FROM evaluations"
    )->fetch_assoc()['total'];
    
$evaluationQuery = $conn->query(
    "SELECT
        e.evaluation_id,
        p.title AS project_title,
        pm.panel_name,
        e.rating,
        e.comments,
        e.recommendation,
        e.evaluated_at
     FROM evaluations e
     INNER JOIN defense_schedules ds
        ON e.schedule_id = ds.schedule_id
     INNER JOIN defense_requests dr
        ON ds.request_id = dr.request_id
     INNER JOIN projects p
        ON dr.project_id = p.project_id
     LEFT JOIN panel_members pm
        ON e.panel_id = pm.panel_id
     ORDER BY e.evaluated_at DESC"
);
    ?>

    <div class="report-grid">
        <div class="card">
            <h3>Project Status Report</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Project Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($reportProjects->num_rows > 0): ?>
                            <?php while ($row = $reportProjects->fetch_assoc()): ?>
                                <tr>
                                    <td><?= h($row['status']) ?></td>
                                    <td><?= h($row['total']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No project records yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Defense Schedule Report</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Schedule Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($reportSchedules->num_rows > 0): ?>
                            <?php while ($row = $reportSchedules->fetch_assoc()): ?>
                                <tr>
                                    <td><?= h($row['status']) ?></td>
                                    <td><?= h($row['total']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No defense schedules yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Panel Assignment Report</h3>
            <p>Total panel assignments</p>
            <h2><?= h($reportPanels) ?></h2>
        </div>

        <div class="card">
            <h3>Evaluation Report</h3>
            <p>Total evaluations recorded</p>
            <h2><?= h($reportEvaluations) ?></h2>
        </div>
        
        <div class="card">
            <h3>Submitted Evaluations</h3>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Panel Member</th>
                        <th>Rating</th>
                        <th>Comments</th>
                        <th>Recommendation</th>
                        <th>Date Evaluated</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($evaluationQuery && $evaluationQuery->num_rows > 0): ?>
                        <?php while ($evaluation = $evaluationQuery->fetch_assoc()): ?>
                            <tr>
                                <td><?= h($evaluation['project_title']) ?></td>
                                <td><?= h($evaluation['panel_name'] ?? 'Unknown') ?></td>
                                <td><?= h($evaluation['rating']) ?></td>
                                <td><?= h($evaluation['comments'] ?? '') ?></td>
                                <td><?= h($evaluation['recommendation'] ?? 'Not specified') ?></td>
                                <td><?= h($evaluation['evaluated_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No evaluations have been submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div>
</section>

</main>

<script src="admindashboard.js"></script>
</body>
</html>
