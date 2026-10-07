<?php
session_start();
require_once __DIR__ . '/auth.php';

$viewer = require_role('admin', 'staff');
$role = $viewer['role'];
$roleLabel = ucfirst($role);
$initials = strtoupper(substr($viewer['full_name'], 0, 1) . substr(strrchr(' ' . $viewer['full_name'], ' '), 1, 1));
$activities = database()->query('SELECT activity_log.id, activity_log.action, activity_log.created_at, users.full_name, users.username, users.role FROM activity_log JOIN users ON users.id = activity_log.user_id ORDER BY activity_log.id DESC LIMIT 100')->fetchAll();
$totalActivities = count($activities);
$today = date('Y-m-d');
$todayActivities = count(array_filter($activities, static fn(array $activity): bool => str_starts_with($activity['created_at'], $today)));
$activeUsers = count(array_unique(array_column($activities, 'username')));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="dashboard-page">
    <div class="app-shell">
        <header class="sidebar">
            <a class="app-brand" href="dashboard.php"><span>Let good<br>time roll</span></a>
            <nav class="side-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <?php if ($role === 'admin'): ?><a href="admin_users.php">Manage Users</a><?php endif; ?>
                <a class="active" href="activities.php">User Activity</a>
            </nav>
            <div class="sidebar-footer"><a href="dashboard.php?logout=1">Logout</a></div>
        </header>

        <main class="dashboard-main activity-main">
            <header class="dashboard-topbar">
                <div><p class="section-kicker">MONITORING</p><h1>User activity</h1></div>
                <a class="account-chip" href="profile.php"><span><strong><?php echo htmlspecialchars($viewer['username'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?> account</small></span></a>
            </header>

            <section class="activity-stats">
                <article><div><small>Total activity</small><strong><?php echo $totalActivities; ?></strong></div></article>
                <article><div><small>Actions today</small><strong><?php echo $todayActivities; ?></strong></div></article>
                <article><div><small>Users in log</small><strong><?php echo $activeUsers; ?></strong></div></article>
            </section>

            <section class="activity-table-card">
                <div class="activity-table-header"><div><p class="section-kicker">LATEST EVENTS</p><h2>Activity log</h2></div><span class="record-count"><?php echo $totalActivities; ?> records</span></div>
                <div class="activity-table-scroll"><table class="activity-table"><thead><tr><th>User</th><th>Role</th><th>Activity</th><th>Time</th></tr></thead><tbody>
                <?php if ($activities): ?>
                    <?php foreach ($activities as $activity): ?><tr><td><div class="activity-user"><div><strong><?php echo htmlspecialchars($activity['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small>@<?php echo htmlspecialchars($activity['username'], ENT_QUOTES, 'UTF-8'); ?></small></div></div></td><td><span class="table-role <?php echo htmlspecialchars($activity['role'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($activity['role']), ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8'); ?></td><td><span class="event-time"><?php echo htmlspecialchars($activity['created_at'], ENT_QUOTES, 'UTF-8'); ?></span></td></tr><?php endforeach; ?>
                <?php else: ?><tr><td colspan="4"><div class="empty-state activity-empty">No activity has been recorded yet.</div></td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
        </main>
    </div>
</body>
</html>
