<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

date_default_timezone_set('Asia/Manila');

function application_timestamp(): string
{
    return date('Y-m-d g:ia');
}

function database_error_message(PDOException $exception): string
{
    $driverCode = (int) ($exception->errorInfo[1] ?? $exception->getCode());

    if ($driverCode === 1045) {
        return 'Database username or password is incorrect. In InfinityFree Client Area, copy the Account Password and use it as DB_PASSWORD.';
    }

    if ($driverCode === 1044 || $driverCode === 1049) {
        return 'Database name is incorrect or has not been created yet. Copy the exact MySQL DB Name from InfinityFree.';
    }

    if ($driverCode === 2002) {
        return 'Database host cannot be reached. Confirm that the MySQL Host Name is sql200.byetcluster.com.';
    }

    return 'Please verify the MySQL Host Name, DB Name, DB User, and Account Password in InfinityFree.';
}

function database(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = 'mysql:host=' . DB_HOST
        . ';port=' . DB_PORT
        . ';dbname=' . DB_NAME
        . ';charset=utf8mb4';

    try {
        $connection = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $connection;
    } catch (PDOException $e) {
        // Keep the real error in InfinityFree's PHP error log, not on the page.
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database setup error: ' . database_error_message($e));
    }
}

function log_activity(int $userId, string $action): void
{
    try {
        $statement = database()->prepare(
            'INSERT INTO activity_log (user_id, action, created_at)
             VALUES (:user_id, :action, :created_at)'
        );

        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'created_at' => application_timestamp(),
        ]);
    } catch (PDOException $e) {
        // Logging must never prevent a user from signing in.
        error_log('Activity log error: ' . $e->getMessage());
    }
}
