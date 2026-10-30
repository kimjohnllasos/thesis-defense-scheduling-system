<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Admin']);

function panelAssignmentError(string $message): void
{
    $_SESSION['panel_message'] = $message;
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$scheduleId = filter_input(
    INPUT_POST,
    'schedule_id',
    FILTER_VALIDATE_INT
);

$panelId = filter_input(
    INPUT_POST,
    'panel_id',
    FILTER_VALIDATE_INT
);

$panelRole = $_POST['panel_role'] ?? '';

if (
    !$scheduleId ||
    !$panelId ||
    !in_array(
        $panelRole,
        ['Chair', 'Member', 'Secretary'],
        true
    )
) {
    panelAssignmentError(
        'Please select a valid defense, panel member, and role.'
    );
}

try {
    $conn->begin_transaction();

    // Confirm that the defense is still scheduled.
    $scheduleCheck = $conn->prepare(
        "SELECT schedule_id
         FROM defense_schedules
         WHERE schedule_id = ?
           AND status = 'Scheduled'
         FOR UPDATE"
    );

    $scheduleCheck->bind_param('i', $scheduleId);
    $scheduleCheck->execute();

    if (!$scheduleCheck->get_result()->fetch_assoc()) {
        throw new Exception(
            'The selected defense is not currently scheduled.'
        );
    }

    // Confirm that the panel member is active.
    $memberCheck = $conn->prepare(
        "SELECT panel_id
         FROM panel_members
         WHERE panel_id = ?
           AND is_active = 1"
    );

    $memberCheck->bind_param('i', $panelId);
    $memberCheck->execute();

    if (!$memberCheck->get_result()->fetch_assoc()) {
        throw new Exception(
            'The selected panel member is unavailable.'
        );
    }

    // Prevent duplicate assignment to the same defense.
    $duplicateCheck = $conn->prepare(
        "SELECT assignment_id
         FROM panel_assignments
         WHERE schedule_id = ?
           AND panel_id = ?"
    );

    $duplicateCheck->bind_param(
        'ii',
        $scheduleId,
        $panelId
    );

    $duplicateCheck->execute();

    if ($duplicateCheck->get_result()->fetch_assoc()) {
        throw new Exception(
            'This panel member is already assigned to this defense.'
        );
    }
    
    // Allow only one Chair and one Secretary per defense.
    if ($panelRole === 'Chair' || $panelRole === 'Secretary') {
        $roleCheck = $conn->prepare(
            "SELECT assignment_id
             FROM panel_assignments
             WHERE schedule_id = ?
               AND panel_role = ?
             LIMIT 1"
        );

        $roleCheck->bind_param(
            'is',
            $scheduleId,
            $panelRole
        );

        $roleCheck->execute();

        if ($roleCheck->get_result()->fetch_assoc()) {
            throw new Exception(
                'This defense already has a ' .
                strtolower($panelRole) . '.'
            );
        }
    }


    // Save the assignment.
    $insert = $conn->prepare(
        "INSERT INTO panel_assignments
            (schedule_id, panel_id, panel_role)
         VALUES (?, ?, ?)"
    );

    $insert->bind_param(
        'iis',
        $scheduleId,
        $panelId,
        $panelRole
    );

    $insert->execute();

    $conn->commit();

    $_SESSION['panel_message'] =
        'Panel member assigned successfully.';

    header("Location: admin_dashboard.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    if (
        $e instanceof mysqli_sql_exception &&
        $e->getCode() === 1062
    ) {
        panelAssignmentError(
            'This panel member is already assigned to this defense.'
        );
    }

    if ($e instanceof Exception) {
        panelAssignmentError($e->getMessage());
    }

    error_log($e->getMessage());

    panelAssignmentError(
        'An unexpected error occurred while saving the assignment.'
    );
}
