<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['Adviser']);

$userId = (int) $_SESSION['user_id'];

$profile = $conn->prepare(
    "SELECT adviser_id FROM advisers WHERE user_id = ?"
);
$profile->bind_param("i", $userId);
$profile->execute();

$adviser = $profile->get_result()->fetch_assoc();

if (!$adviser) {
    http_response_code(403);
    exit('Adviser profile not found.');
}

$projectId = filter_input(
    INPUT_GET,
    'project_id',
    FILTER_VALIDATE_INT
);

if (!$projectId || $projectId < 1) {
    http_response_code(400);
    exit('Invalid project ID.');
}

$stmt = $conn->prepare(
    "SELECT file_name, file_path
     FROM projects
     WHERE project_id = ?
       AND adviser_id = ?"
);
$adviserId = (int) $adviser['adviser_id'];
$stmt->bind_param("ii", $projectId, $adviserId);
$stmt->execute();

$file = $stmt->get_result()->fetch_assoc();

if (!$file || empty($file['file_path']) || empty($file['file_name'])) {
    http_response_code(404);
    exit('Document not found.');
}

$uploadDirectory = realpath(__DIR__ . '/uploads/project_files');
$filePath = realpath(__DIR__ . '/' . $file['file_path']);

if (
    $uploadDirectory === false ||
    $filePath === false ||
    !is_file($filePath) ||
    !is_readable($filePath) ||
    strpos($filePath, $uploadDirectory . DIRECTORY_SEPARATOR) !== 0
) {
    http_response_code(404);
    exit('The document could not be found.');
}

$downloadName = basename($file['file_name']);
$downloadName = str_replace(["\r", "\n", '"'], '', $downloadName);

header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($filePath));
header(
    "Content-Disposition: attachment; filename=\""
    . addcslashes($downloadName, "\\")
    . "\""
);

readfile($filePath);
exit;
