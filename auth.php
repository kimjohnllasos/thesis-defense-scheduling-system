<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireRole(array $roles): void
{
    if (
        empty($_SESSION['user_id']) ||
        !in_array($_SESSION['role'] ?? '', $roles, true)
    ) {
        header("Location: index.php?error=login");
        exit;
    }
}

function h($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
