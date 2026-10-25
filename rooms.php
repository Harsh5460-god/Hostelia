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
    <title>Rooms | Hostelia</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
<style>
    .room-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 28px;
        margin-top: 30px;
    }

    .room-card {
        background: #fffdf8;
        border: 1px solid #e9dfd0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 18px rgba(70, 48, 29, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(70, 48, 29, 0.14);
    }

    .room-image {
        width: 100%;
        height: 230px;
        object-fit: cover;
        display: block;
    }

    .room-card-content {
        padding: 24px;
    }

    .room-card .number {
        color: #9a7653;
        font-size: 13px;
        font-weight: 600;
    }

    .room-card h3 {
        margin: 10px 0;
    }

    .room-card p {
        line-height: 1.8;
    }

    .room-book-link {
        display: inline-block;
        margin-top: 8px;
        color: #765337;
        font-weight: 600;
        text-decoration: none;
    }

    .room-book-link:hover {
        color: #3f2b1d;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .room-grid {
            grid-template-columns: 1fr;
        }

        .room-image {
            height: 220px;
        }
    }
</style>

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

        <a href="registration.html" class="auth-register">Register</a>
        <a href="login.php" class="auth-login">Student Login</a>

    <?php endif; ?>
</div>



        <nav class="main-nav">
            <a href="index.php">HOME</a>
            <a href="rooms.php" class="active">ROOMS</a>
            <a href="complaints.html">COMPLAINTS</a>
            <a href="notices.php">NOTICES</a>
            <a href="about.html">ABOUT</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container main-content">
        <div class="content-area w-100">
            <span class="section-label">ACCOMMODATION</span>
            <h2>Available Room Types</h2>
            <p class="intro-text">
                Choose from a range of room configurations designed to suit your preference and privacy needs.
            </p>


<div class="room-grid">

    <!-- Single Occupancy -->
    <div class="room-card">
        <img src="images/room-single.jpg"
             alt="Single occupancy hostel room"
             class="room-image">

        <div class="room-card-content">
            <span class="number">01</span>
            <h3>Single Occupancy</h3>
            <p>Private room with a single bed, study table, cupboard, and space for personal belongings.</p>
            <a href="registration.html" class="room-book-link">BOOK ROOM →</a>
        </div>
    </div>

    <!-- Double Sharing -->
    <div class="room-card">
        <img src="images/room-double.jpg"
             alt="Double sharing hostel room"
             class="room-image">

        <div class="room-card-content">
            <span class="number">02</span>
            <h3>Double Sharing</h3>
            <p>Comfortable shared room for two students with separate beds, study tables, and cupboards.</p>
            <a href="registration.html" class="room-book-link">BOOK ROOM →</a>
        </div>
    </div>

    <!-- Triple Sharing -->
    <div class="room-card">
        <img src="images/room-triple.jpg"
             alt="Triple sharing hostel room"
             class="room-image">

        <div class="room-card-content">
            <span class="number">03</span>
            <h3>Triple Sharing</h3>
            <p>Budget-friendly room for three students with basic furniture, storage space, and a shared study area.</p>
            <a href="registration.html" class="room-book-link">BOOK ROOM →</a>
        </div>
    </div>

    <!-- Deluxe Suite -->
    <div class="room-card">
        <img src="images/room-deluxe.jpg"
             alt="Deluxe hostel room"
             class="room-image">

        <div class="room-card-content">
            <span class="number">04</span>
            <h3>Deluxe Suite</h3>
            <p>A well-maintained private room with a comfortable bed, study desk, extra storage, and air conditioning.</p>
            <a href="registration.html" class="room-book-link">BOOK ROOM →</a>
        </div>
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