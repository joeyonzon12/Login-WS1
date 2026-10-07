<?php
session_start();
require_once __DIR__ . '/auth.php';

if (isset($_GET['logout'])) {
    if (isset($_SESSION['user_id'])) log_activity((int) $_SESSION['user_id'], 'Signed out');
    session_unset(); session_destroy(); header('Location: index.php'); exit;
}

$user = require_login();
$role = $user['role'];
$roleLabel = ucfirst($role);
$initials = strtoupper(substr($user['full_name'], 0, 1) . substr(strrchr(' ' . $user['full_name'], ' '), 1, 1));
$database = database();
$statCards = [];

if ($role === 'admin') {
    $statCards = [
        ['label' => 'Total users', 'value' => (int) $database->query('SELECT COUNT(*) FROM users')->fetchColumn()],
        ['label' => 'Staff members', 'value' => (int) $database->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn()],
        ['label' => 'Activity logs', 'value' => (int) $database->query('SELECT COUNT(*) FROM activity_log')->fetchColumn()],
    ];
} elseif ($role === 'staff') {
    $statCards = [
        ['label' => 'User accounts', 'value' => (int) $database->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn()],
        ['label' => 'Activity logs', 'value' => (int) $database->query('SELECT COUNT(*) FROM activity_log')->fetchColumn()],
        ['label' => 'Your role', 'value' => 'Staff'],
    ];
} else {
    $statement = $database->prepare('SELECT COUNT(*) FROM activity_log WHERE user_id = :id');
    $statement->execute(['id' => $user['id']]);
    $statCards = [
        ['label' => 'My activity', 'value' => (int) $statement->fetchColumn()],
        ['label' => 'My role', 'value' => 'User'],
        ['label' => 'Profile status', 'value' => 'Active'],
    ];
}

$recentStatement = $database->prepare('SELECT action, created_at FROM activity_log WHERE user_id = :id ORDER BY id DESC LIMIT 4');
$recentStatement->execute(['id' => $user['id']]);
$recentActivities = $recentStatement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="dashboard-page">
    <div class="app-shell">
        <header class="sidebar">
            <a class="app-brand" href="dashboard.php"><span>Let good</span></a>
            <nav class="side-nav">
                <a class="active" href="dashboard.php">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <?php if ($role === 'admin'): ?><a href="admin_users.php">Manage Users</a><?php endif; ?>
                <?php if (in_array($role, ['admin', 'staff'], true)): ?><a href="activities.php">User Activity</a><?php endif; ?>
            </nav>
            <div class="sidebar-footer"><a href="dashboard.php?logout=1">Logout</a></div>
        </header>

            <section class="stats-grid">
                <?php foreach ($statCards as $card): ?><article class="stat-card"><div><p><?php echo htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8'); ?></p><strong><?php echo htmlspecialchars((string) $card['value'], ENT_QUOTES, 'UTF-8'); ?></strong></div></article><?php endforeach; ?>
            </section>

            <section class="dashboard-bottom">
                <article class="recent-card"><div class="card-heading"><div><p class="section-kicker">RECENT</p><h2>Your activity</h2></div><?php if (in_array($role, ['admin', 'staff'], true)): ?><a href="activities.php">View all</a><?php endif; ?></div>
                    <?php if ($recentActivities): ?><div class="activity-list"><?php foreach ($recentActivities as $activity): ?><div class="activity-row"><div><strong><?php echo htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($activity['created_at'], ENT_QUOTES, 'UTF-8'); ?></small></div></div><?php endforeach; ?></div><?php else: ?><p class="empty-state">Your account activity will appear here.</p><?php endif; ?>
                </article>
                <article class="profile-card"><p class="section-kicker">YOUR PROFILE</p><h2>Keep it current</h2><p>Update your information and profile picture anytime.</p><a href="profile.php">Open profile</a></article>
            </section>
        </main>
    </div>
    
</body>
</html>
