<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ===== BACKGROUND IMAGE (Better.png, matches owner_dashboard.php) ===== */
        body {
            background-image: url('Images/Better.png') !important;
            background-size: cover !important;
            background-position: right center !important;
            background-attachment: fixed !important;
            background-repeat: no-repeat !important;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 0;
            pointer-events: none;
        }

        /* Hide video elements (no longer used) */
        .video-background, .overlay, .unmute-btn { display: none !important; }

        .navbar, .dashboard-container {
            position: relative;
            z-index: 1;
        }
        .navbar {
            backdrop-filter: none !important;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <h2>Dashboard</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="dashboard-container">
        <div class="welcome">
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
        </div>
        <p>You have successfully logged in.</p>
    </div>

</body>
</html>