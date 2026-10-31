
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION["student_id"]);
$studentName = $_SESSION["student_name"] ?? "Student";
$initial = strtoupper(substr($studentName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Hostelia</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Header -->
    <header class="site-header">
        <div class="logo">Hostelia</div>
        <p class="tagline">HOSTEL MANAGEMENT SYSTEM</p>
        <div class="header-auth">
    <?php if ($isLoggedIn): ?>

        <details class="profile-menu">
            <summary class="profile-trigger">
                <span class="profile-avatar">
                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="profile-name">
                    <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="profile-arrow">▾</span>
            </summary>

            <div class="profile-dropdown">
                <a href="student_dashboard.php">My Dashboard</a>
                <a href="settings.php">Settings</a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="logout-option">Logout</a>
            </div>
        </details>

    <?php else: ?>

        <a href="registration.php" class="auth-register">Register</a>
        <a href="login.php" class="auth-login">Student Login</a>

    <?php endif; ?>
</div>

        <nav class="main-nav">

            <a href="index.php" >HOME</a>
            <a href="about.php" class="active">ABOUT</a>
            <a href="rooms.php">ROOMS</a>
            <a href="complaints.PHP">COMPLAINTS</a>
            <a href="notices.php">NOTICES</a>
            

        </nav>
    </header>

    <!-- Main Content -->
    <main class="container main-content">
        <div class="content-area w-100">
            <span class="section-label">OUR MISSION</span>
            <h2>Modern Living,<br>Simplified Management.</h2>
            <p class="intro-text">
                Hostelia bridges the gap between student comfort and administrative efficiency. Built with simplicity in mind, our system replaces cumbersome paperwork with clean, structured digital workflows.
            </p>

            <!-- Features Grid -->
            <div class="info-grid">
                <div class="info-box bg-white p-4 border rounded shadow-sm">
                    <span class="number">01</span>
                    <h3>Seamless Onboarding</h3>
                    <p>Students can easily register, submit required details, and track room allocations in real-time without administrative delays.</p>
                </div>

                <div class="info-box bg-white p-4 border rounded shadow-sm">
                    <span class="number">02</span>
                    <h3>Quick Maintenance</h3>
                    <p>Dedicated complaint routing ensures maintenance teams receive requests promptly, reducing resolution times for residents.</p>
                </div>

                <div class="info-box bg-white p-4 border rounded shadow-sm">
                    <span class="number">03</span>
                    <h3>Centralized Notices</h3>
                    <p>Important updates, schedule changes, and emergency notifications are posted directly to a single, easily accessible notice board.</p>
                </div>

                <div class="info-box bg-white p-4 border rounded shadow-sm">
                    <span class="number">04</span>
                    <h3>24/7 Accessibility</h3>
                    <p>Accessible across desktop and mobile devices, allowing students and staff to manage hostel tasks anytime, anywhere.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-inner">
            <div>
                <strong>Hostelia</strong>
                <span> — Hostel Management System</span>
            </div>
            <div>
                © 2026 All Rights Reserved
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>

</html>