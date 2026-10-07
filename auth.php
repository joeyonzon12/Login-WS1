<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function require_login(): array
{
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
    $statement = database()->prepare('SELECT id, full_name, email, username, role, avatar_path, created_at FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $statement->fetch();
    if (!$user) {
        session_unset(); session_destroy(); header('Location: index.php'); exit;
    }
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to open this page.');
    }
    return $user;
}
