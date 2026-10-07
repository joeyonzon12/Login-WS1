<?php
session_start();
require_once __DIR__ . '/auth.php';

$admin = require_role('admin');
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0); $fullName = trim($_POST['full_name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? ''); $role = $_POST['role'] ?? ''; $newPassword = $_POST['new_password'] ?? '';
    if (!$id || $fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $username === '' || !in_array($role, ['admin', 'user', 'staff'], true)) $error = 'Please provide valid user details.';
    else {
        try {
            $sql = 'UPDATE users SET full_name = :full_name, email = :email, username = :username, role = :role, updated_at = :updated_at';
            $params = ['full_name' => $fullName, 'email' => $email, 'username' => $username, 'role' => $role, 'updated_at' => application_timestamp(), 'id' => $id];
            if ($newPassword !== '') { $sql .= ', password_hash = :password_hash'; $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT); }
            $sql .= ' WHERE id = :id'; database()->prepare($sql)->execute($params);
            log_activity((int) $admin['id'], 'Edited user ID ' . $id); $success = 'User updated successfully.';
        } catch (PDOException $exception) { $error = (string) $exception->getCode() === '23000' ? 'Username or email already exists.' : 'Unable to update user.'; }
    }
}
$users = database()->query('SELECT id, full_name, email, username, role FROM users ORDER BY id DESC')->fetchAll();
$totalUsers = count($users);
$staffCount = count(array_filter($users, static fn(array $user): bool => $user['role'] === 'staff'));
$adminCount = count(array_filter($users, static fn(array $user): bool => $user['role'] === 'admin'));
$initials = strtoupper(substr($admin['full_name'], 0, 1) . substr(strrchr(' ' . $admin['full_name'], ' '), 1, 1));
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Users</title><link rel="stylesheet" href="dashboard.css"></head>
<body class="dashboard-page"><div class="app-shell">
    <header class="sidebar">
        <a class="app-brand" href="dashboard.php"><span>Let good<br>time roll</span></a>
        <nav class="side-nav"><a href="dashboard.php">Dashboard</a><a href="profile.php">My Profile</a><a class="active" href="admin_users.php">Manage Users</a><a href="activities.php">User Activity</a></nav>
        <div class="sidebar-footer"><a href="dashboard.php?logout=1">Logout</a></div>
    </header>
    <main class="dashboard-main users-main">
        <header class="dashboard-topbar"><div><p class="section-kicker">ADMINISTRATION</p><h1>Manage users</h1></div><a class="account-chip" href="profile.php"><span><strong><?php echo htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8'); ?></strong><small>Admin account</small></span></a></header>
        <section class="user-summary"><article><div><small>Total users</small><strong><?php echo $totalUsers; ?></strong></div></article><article><div><small>Administrators</small><strong><?php echo $adminCount; ?></strong></div></article><article><div><small>Staff members</small><strong><?php echo $staffCount; ?></strong></div></article></section>
        <?php if ($error): ?><div class="users-notice error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><?php if ($success): ?><div class="users-notice success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <section class="users-card"><div class="users-card-heading"><div><p class="section-kicker">DIRECTORY</p><h2>All users</h2><p>Edit details directly, then save only the member you changed.</p></div><span class="record-count"><?php echo $totalUsers; ?> members</span></div><div class="users-list">
            <?php foreach ($users as $managedUser): ?><form method="post" class="user-row"><input type="hidden" name="id" value="<?php echo (int) $managedUser['id']; ?>"><div class="user-identity"><div><strong><?php echo htmlspecialchars($managedUser['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small>@<?php echo htmlspecialchars($managedUser['username'], ENT_QUOTES, 'UTF-8'); ?></small></div></div><label>Full name<input name="full_name" value="<?php echo htmlspecialchars($managedUser['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required></label><label>Email<input type="email" name="email" value="<?php echo htmlspecialchars($managedUser['email'], ENT_QUOTES, 'UTF-8'); ?>" required></label><label>Username<input name="username" value="<?php echo htmlspecialchars($managedUser['username'], ENT_QUOTES, 'UTF-8'); ?>" required></label><label>Access role<select name="role"><?php foreach (['admin', 'staff', 'user'] as $role): ?><option value="<?php echo $role; ?>" <?php echo $managedUser['role'] === $role ? 'selected' : ''; ?>><?php echo ucfirst($role); ?></option><?php endforeach; ?></select></label><label class="password-field">New password<input type="password" name="new_password" placeholder="Keep current"></label><button class="save-user" type="submit">Save changes</button></form><?php endforeach; ?>
        </div></section>
    </main>
</div></body></html>
