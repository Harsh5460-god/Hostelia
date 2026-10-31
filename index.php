
<?php
session_start();

$isLoggedIn = isset($_SESSION["student_id"]);
$studentName = $_SESSION["student_name"] ?? "Student";
$initial = strtoupper(substr($studentName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hostel | Hostel Management</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="site-header">

        <div class="logo">
            Hostelia
        </div>

        <p class="tagline">
    HOSTEL MANAGEMENT SYSTEM
</p>



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

            <a href="index.php" class="active">HOME</a>
            <a href="about.php">ABOUT</a>
            <a href="rooms.php">ROOMS</a>
            <a href="complaints.PHP">COMPLAINTS</a>
            <a href="notices.php">NOTICES</a>
            

        </nav>

    </header>


    <!-- ================= HERO ================= -->

    <section class="hero">

        <div class="hero-overlay"></div>

        <div class="hero-content">

            <span class="hero-category">
                STUDENT LIVING
            </span>

            <h1>
                A Better Place<br>
                <span>To Stay</span>
            </h1>

            <p>
                Simple, comfortable and organized hostel
                management for students.
            </p>

            <a href="registration.php" class="hero-button">
                REGISTER NOW
            </a>

        </div>

    </section>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="container main-content">

        <!-- Left Side -->

        <div class="content-area">

            <span class="section-label">
                WELCOME
            </span>

            <h2>
                Your Hostel,<br>
                Managed Better.
            </h2>

            <p class="intro-text">
                Hostelia is a simple hostel management platform
                designed to make student accommodation easier
                and more organized.
            </p>


            <!-- Information Cards -->

            <div class="info-grid">

                <div class="info-box">

                    <span class="number">
                        01
                    </span>

                    <h3>
                        Room Management
                    </h3>

                    <p>
                        Check room availability and view
                        important room information.
                    </p>

                    <a href="rooms.php">
                        EXPLORE ROOMS →
                    </a>

                </div>


                <div class="info-box">

                    <span class="number">
                        02
                    </span>

                    <h3>
                        Student Registration
                    </h3>

                    <p>
                        Register for hostel accommodation
                        through a simple online form.
                    </p>

                    <a href="registration.php">
                        REGISTER NOW →
                    </a>

                </div>

            </div>

        </div>


        <!-- ================= SIDEBAR ================= -->

        <aside class="sidebar">

            <div class="sidebar-box">

                <span class="section-label">
                    ABOUT HOSTELIA
                </span>

                <div class="sidebar-line"></div>

                <h3>
                    Comfortable living.
                    Simple management.
                </h3>

                <p>
                    Our website helps students and hostel
                    administrators manage everyday hostel
                    activities in one place.
                </p>

                <a href="about.php" class="read-more">
                    READ MORE →
                </a>

            </div>


            <!-- Notice -->

            <div class="notice-box">

                <span class="section-label">
                    LATEST NOTICE
                </span>

                <div class="sidebar-line"></div>

                <h4>
                    Hostel Registration
                </h4>

                <p>
                    Students can register for hostel
                    accommodation online.
                </p>

                <a href="notices.php">
                    VIEW  →
                </a>

            </div>

        </aside>

    </main>


    <!-- ================= FOOTER ================= -->

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


    <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <!-- <script src="js/script.js"></script> -->

</body>

</html>