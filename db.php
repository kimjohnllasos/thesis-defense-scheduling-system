<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';
    $database = getenv('DB_NAME') ?: 'thesis_defense_system';
    $port = (int) (getenv('DB_PORT') ?: 3306);

    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Database connection failed. Check your database settings.");
}
?>
