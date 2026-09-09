<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
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
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="brand">
                <img src="OIP.webp" alt="Logo">
                <h2>Let good time roll</h2>
            </div>

            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="#reports">Reports</a>
                <a href="#settings">Settings</a>
                <a href="dashboard.php?logout=1">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <p class="greeting">Good day</p>
                    <h1>Welcome back, <?php echo $username; ?>!</h1>
                </div>
            </header>
        </main>
    </div>
</body>
</html>
