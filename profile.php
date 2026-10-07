<?php
session_start();
require_once __DIR__ . '/auth.php';

$sessionUser = require_login();
$profileStatement = database()->prepare('SELECT * FROM users WHERE id = :id');
$profileStatement->execute(['id' => $sessionUser['id']]);
$user = $profileStatement->fetch();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthDate = $_POST['birth_date'] ?? '';
    $language = $_POST['language'] ?? 'English';
    $country = trim($_POST['country'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $avatarPath = $user['avatar_path'];

    if ($fullName === '' || $email === '' || $username === '') {
        $error = 'Please complete your name, email, and username.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($gender, ['', 'Female', 'Male', 'Prefer not to say'], true)) {
        $error = 'Please select a valid gender.';
    } elseif ($birthDate !== '' && !DateTime::createFromFormat('Y-m-d', $birthDate)) {
        $error = 'Please enter a valid birth date.';
    } elseif ($newPassword !== '') {
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Your new password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        }
    }

    if ($error === '' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $avatar = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mimeType = is_uploaded_file($avatar['tmp_name']) ? mime_content_type($avatar['tmp_name']) : false;
        if ($avatar['error'] !== UPLOAD_ERR_OK || $avatar['size'] > 2 * 1024 * 1024 || !isset($allowedTypes[$mimeType])) {
            $error = 'Please upload a JPG, PNG, or WebP image no larger than 2 MB.';
        } else {
            $avatarDirectory = __DIR__ . '/uploads/avatars';
            if (!is_dir($avatarDirectory) && !mkdir($avatarDirectory, 0755, true) && !is_dir($avatarDirectory)) {
                $error = 'Unable to save your profile picture.';
            } else {
                $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                if (!move_uploaded_file($avatar['tmp_name'], $avatarDirectory . '/' . $fileName)) {
                    $error = 'Unable to save your profile picture.';
                } else {
                    $avatarPath = 'uploads/avatars/' . $fileName;
                }
            }
        }
    }

    if ($error === '') {
        try {
            $sql = 'UPDATE users SET full_name = :full_name, email = :email, username = :username,
                    gender = :gender, birth_date = :birth_date, language = :language, country = :country,
                    email_notifications = :email_notifications, private_account = :private_account,
                    updated_at = :updated_at';
            $parameters = [
                'full_name' => $fullName,
                'email' => $email,
                'username' => $username,
                'gender' => $gender ?: null,
                'birth_date' => $birthDate ?: null,
                'language' => $language,
                'country' => $country ?: null,
                'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                'private_account' => isset($_POST['private_account']) ? 1 : 0,
                'updated_at' => application_timestamp(),
                'avatar_path' => $avatarPath,
                'id' => $user['id'],
            ];
            $sql .= ', avatar_path = :avatar_path';
            if ($newPassword !== '') {
                $sql .= ', password_hash = :password_hash';
                $parameters['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            database()->prepare($sql)->execute($parameters);
            log_activity((int) $user['id'], $newPassword !== '' ? 'Updated their profile and password' : 'Updated their profile');
            $profileStatement->execute(['id' => $user['id']]);
            $user = $profileStatement->fetch();
            $_SESSION['username'] = $user['username'];
            $success = 'Your profile changes have been saved.';
        } catch (PDOException $exception) {
            $error = (string) $exception->getCode() === '23000'
                ? 'That username or email address is already in use.'
                : 'Unable to save your profile. Please try again.';
        }
    }
}

$initials = strtoupper(substr($user['full_name'], 0, 1) . substr(strrchr(' ' . $user['full_name'], ' '), 1, 1));
$roleLabel = ucfirst($user['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <div class="profile-shell">
        <header class="profile-topbar">
            <a class="brand-name" href="dashboard.php">Let good time roll</a>
            <span class="crumb">/ &nbsp; <?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?> Profile</span>
            <a class="top-link" href="dashboard.php">Dashboard</a>
        </header>

        <div class="profile-layout">
            <aside class="profile-sidebar">
                <h2><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="username">@<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                <nav>
                    <a class="active" href="profile.php">Your Info</a>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="dashboard.php?logout=1">Logout</a>
                </nav>
            </aside>

            <main class="profile-main">
                <div class="profile-heading">
                    <div><p class="eyebrow"><?php echo htmlspecialchars(strtoupper($roleLabel), ENT_QUOTES, 'UTF-8'); ?> ACCOUNT SETTINGS</p><h1><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?> Profile</h1><p>Update your personal details, preferences, and password.</p></div>
                </div>

                <?php if ($error !== ''): ?><div class="notice error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <?php if ($success !== ''): ?><div class="notice success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

                <form method="post" class="profile-form" enctype="multipart/form-data">
                    <section class="form-column">
                        <label>Profile picture<input id="avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
                        <label>Full name<input name="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required></label>
                        <label>Email<input type="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required></label>
                        <label>Username<input name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required></label>
                        <label>Gender<select name="gender"><option value="">Select gender</option><?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?><option value="<?php echo $option; ?>" <?php echo $user['gender'] === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></label>
                        <label>Birth date<input type="date" name="birth_date" value="<?php echo htmlspecialchars((string) ($user['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                        <label>Language<select name="language"><?php foreach (['English', 'Filipino'] as $option): ?><option value="<?php echo $option; ?>" <?php echo $user['language'] === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></label>
                        <label>Country<input name="country" placeholder="Philippines" value="<?php echo htmlspecialchars((string) ($user['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    </section>

                    <section class="form-column password-column">
                        <h2>Change password</h2>
                        <p class="helper">Leave these blank if you do not want to change your password.</p>
                        <label>Current password<input type="password" name="current_password" autocomplete="current-password"></label>
                        <label>New password<input type="password" name="new_password" autocomplete="new-password"></label>
                        <label>Confirm new password<input type="password" name="confirm_password" autocomplete="new-password"></label>
                        <div class="toggle-row"><div><strong>Email notifications</strong><span>Receive account updates by email</span></div><label class="switch"><input type="checkbox" name="email_notifications" <?php echo $user['email_notifications'] ? 'checked' : ''; ?>><span></span></label></div>
                        <div class="toggle-row"><div><strong>Private account</strong><span>Limit visibility of your profile</span></div><label class="switch"><input type="checkbox" name="private_account" <?php echo $user['private_account'] ? 'checked' : ''; ?>><span></span></label></div>
                        <button type="submit">Save changes</button>
                    </section>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
