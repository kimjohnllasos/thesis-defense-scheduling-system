<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Panel Member']);

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT panel_id, panel_name, department, specialization
     FROM panel_members
     WHERE user_id = ? AND is_active = 1
     LIMIT 1"
);

$stmt->bind_param("i", $userId);
$stmt->execute();
$panel = $stmt->get_result()->fetch_assoc();

$stmt->close();

$assignedDefenses = [];

if ($panel) {
    $panelId = (int) $panel['panel_id'];

    $defenseStmt = $conn->prepare(
        "SELECT
            ds.schedule_id,
            ds.defense_date,
            ds.start_time,
            ds.end_time,
            ds.status AS schedule_status,
            p.title AS project_title,
            dr.defense_type,
            r.room_name,
            student_user.name AS student_name,
            pa.panel_role
         FROM panel_assignments pa
         INNER JOIN defense_schedules ds
            ON pa.schedule_id = ds.schedule_id
         INNER JOIN defense_requests dr
            ON ds.request_id = dr.request_id
         INNER JOIN projects p
            ON dr.project_id = p.project_id
         INNER JOIN students s
            ON p.student_id = s.student_id
         INNER JOIN users student_user
            ON s.user_id = student_user.user_id
         INNER JOIN rooms r
            ON ds.room_id = r.room_id
         WHERE pa.panel_id = ?
         ORDER BY ds.defense_date ASC, ds.start_time ASC"
    );

    $defenseStmt->bind_param("i", $panelId);
    $defenseStmt->execute();

    $defenseResult = $defenseStmt->get_result();

    while ($row = $defenseResult->fetch_assoc()) {
        $assignedDefenses[] = $row;
    }

    $defenseStmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Member Dashboard</title>

    <style>
        :root {
            --navy: #0B132B;
            --dark-blue: #111A35;
            --border: #2D3A52;
            --green: #39FF88;
            --text: #F5F7FB;
            --muted: #B8C3D9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--navy);
            color: var(--text);
        }

        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 250px;
            flex-shrink: 0;
            padding: 25px 18px;
            background: #101A32;
            border-right: 1px solid var(--border);
        }

        .brand {
            color: var(--green);
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .brand-subtitle {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 35px;
        }

        .nav-link, .logout-link {
            display: block;
            padding: 13px;
            margin-bottom: 8px;
            border-radius: 8px;
            color: var(--text);
            text-decoration: none;
        }

        .nav-link.active, .nav-link:hover, .logout-link:hover {
            background: #1D2A43;
            color: var(--green);
        }

        .logout-link {
            margin-top: 30px;
            text-align: center;
            border: 1px solid var(--border);
        }

        .main-content {
            flex: 1;
            min-width: 0;
            padding: 35px;
        }

        .page-header { margin-bottom: 25px; }
        .page-header h1 { margin-top: 0; }
        .muted, .profile-details { color: var(--muted); }

        .card {
            padding: 24px;
            margin-bottom: 24px;
            background: var(--dark-blue);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .card h2 { margin-top: 0; color: var(--green); }

        .profile-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 25px;
        }

        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--border);
        }

        th { background: #0A1022; color: var(--green); }
        tbody tr:hover { background: #1A2740; }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin: 6px 0;
            background: #0A1022;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 6px;
            font: inherit;
        }

        textarea { resize: vertical; }

        button {
            padding: 11px 15px;
            background: var(--green);
            color: #07111F;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #20E879; }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 6px;
            background: #26354C;
            color: var(--muted);
        }

        @media (max-width: 800px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; }
            .main-content { padding: 20px 14px; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">TDS</div>
        <div class="brand-subtitle">THESIS DEFENSE SYSTEM</div>

        <a class="nav-link active" href="panel_dashboard.php">▦ My Assigned Defenses</a>
        <a class="logout-link" href="logout.php">Logout</a>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Panel Member Dashboard</h1>
            <p class="muted">View your assigned defenses and submit evaluations.</p>
        </header>

        <?php if (!$panel): ?>
            <section class="card">
                <p>Your panel member profile was not found. Please contact the administrator.</p>
            </section>
        <?php else: ?>
            <section class="card">
                <h2>Welcome, <?= h($panel['panel_name']) ?>!</h2>
                <div class="profile-details">
                    <p>Department: <?= h($panel['department'] ?? 'Not set') ?></p>
                    <p>Specialization: <?= h($panel['specialization'] ?? 'Not set') ?></p>
                </div>
            </section>

            <section class="card">
                <h2>My Assigned Defenses</h2>

                <?php if (empty($assignedDefenses)): ?>
                    <p class="muted">No defenses have been assigned to you yet.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Project Title</th>
                                    <th>Student</th>
                                    <th>Defense Type</th>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Room</th>
                                    <th>Your Role</th>
                                    <th>Status</th>
                                    <th>Evaluation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedDefenses as $defense): ?>
                                    <tr>
                                        <td><?= h($defense['project_title']) ?></td>
                                        <td><?= h($defense['student_name']) ?></td>
                                        <td><?= h($defense['defense_type']) ?></td>
                                        <td><?= h($defense['defense_date']) ?></td>
                                        <td><?= h(date('g:i A', strtotime($defense['start_time']))) ?></td>
                                        <td><?= h(date('g:i A', strtotime($defense['end_time']))) ?></td>
                                        <td><?= h($defense['room_name']) ?></td>
                                        <td><?= h($defense['panel_role']) ?></td>
                                        <td><span class="status"><?= h($defense['schedule_status']) ?></span></td>
                                        <td>
                                            <?php if ($defense['schedule_status'] === 'Completed'): ?>
                                                <form method="POST" action="submit_evaluation.php">
                                                    <input type="hidden" name="schedule_id" value="<?= (int) $defense['schedule_id'] ?>">

                                                    <label>Rating (1–100)</label>
                                                    <input type="number" name="rating" min="1" max="100" step="0.01" required>

                                                    <label>Comments</label>
                                                    <textarea name="comments" rows="3" maxlength="5000" placeholder="Feedback and suggested revisions"></textarea>

                                                    <label>Recommendation</label>
                                                    <select name="recommendation" required>
                                                        <option value="Pass">Pass</option>
                                                        <option value="Revise" selected>Revise</option>
                                                        <option value="Fail">Fail</option>
                                                    </select>

                                                    <button type="submit">Submit Evaluation</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">Available after completion</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
