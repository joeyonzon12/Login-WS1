<?php
session_start();
require_once __DIR__ . '/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $username === '' || $password === '') {
        $error = 'Please complete every field.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $statement = database()->prepare(
                'INSERT INTO users (full_name, email, username, password_hash, role, created_at, updated_at)
                 VALUES (:full_name, :email, :username, :password_hash, :role, :created_at, :updated_at)'
            );
            $statement->execute([
                'full_name' => $fullName,
                'email' => $email,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
                'created_at' => application_timestamp(),
                'updated_at' => application_timestamp(),
            ]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) database()->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            log_activity((int) $_SESSION['user_id'], 'Created an account');
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $exception) {
            if ((int) $exception->getCode() === 23000) {
                $error = 'That username or email address is already registered.';
            } else {
                throw $exception;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="post" action="register.php">
            <img src="OIP.webp" alt="Let good time roll logo" class="auth-logo">
            <h2>Create Account</h2>

            <?php if ($error !== ''): ?>
                <p class="form-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register</button>

            <p>Already have an account? <a href="index.php">Login</a></p>
        </form>
    </div>
</body>
</html>
