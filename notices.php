<?php
session_start();
include "db.php";

$isLoggedIn = isset($_SESSION["student_id"]);
$studentName = $_SESSION["student_name"] ?? "Student";
$initial = strtoupper(substr($studentName, 0, 1));

$notice_result = $conn->query(
    "SELECT * FROM notices ORDER BY notice_date DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notices | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        .notices-hero {
            padding: 65px 0 35px;
            text-align: center;
        }

        .notices-hero .eyebrow {
            color: #9a704b;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 3px;
            margin-bottom: 12px;
        }

        .notices-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(36px, 5vw, 52px);
            color: #493526;
            margin-bottom: 14px;
        }

        .notices-hero p {
            color: #76685d;
            font-size: 15px;
        }

        .notice-list {
            max-width: 900px;
            margin: 0 auto;
            padding-bottom: 70px;
        }

        .notice-card {
            background: #fffdf9;
            border: 1px solid #e9dfd1;
            border-left: 5px solid #a87950;
            border-radius: 12px;
            padding: 28px 30px;
            margin-bottom: 22px;
            box-shadow: 0 8px 25px rgba(72, 51, 33, 0.06);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .notice-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(72, 51, 33, 0.10);
        }

        .notice-card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 25px;
            color: #493526;
            margin-bottom: 14px;
        }

        .notice-card .notice-text {
            color: #65584e;
            font-size: 14px;
            line-height: 1.9;
            overflow-wrap: anywhere;
        }

        .notice-date {
            display: inline-block;
            margin-top: 12px;
            padding: 7px 12px;
            border-radius: 20px;
            background: #f3eadf;
            color: #805a3b;
            font-size: 12px;
        }

        .empty-notices {
            text-align: center;
            background: #fffdf9;
            border: 1px solid #e9dfd1;
            border-radius: 14px;
            padding: 45px 20px;
        }

        .empty-notices h2 {
            font-family: 'Playfair Display', serif;
            color: #493526;
        }

        .empty-notices p {
            color: #76685d;
            margin-bottom: 0;
        }

        .notices-hero .section-description {
            max-width: 600px;
            margin: 0 auto;
        }

        @media (max-width: 576px) {
            .notice-card {
                padding: 22px 18px;
            }

            .notice-card h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">

            <a href="index.php" class="logo">Hostelia</a>

            <div class="header-auth">
                <?php if ($isLoggedIn): ?>
                    <a href="student_dashboard.php"
                       class="profile-link"
                       title="<?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="profile-avatar">
                            <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="profile-name">
                            <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </a>
                    <a href="logout.php" class="auth-login">Logout</a>
                <?php else: ?>
                    <a href="registration.html" class="auth-register">Register</a>
                    <a href="login.php" class="auth-login">Student Login</a>
                <?php endif; ?>
            </div>

            <nav class="main-nav">
                <a href="index.php">Home</a>
                <a href="rooms.php">Rooms</a>
                <a href="complaints.html">Complaints</a>
                <a href="notices.php" class="active">Notices</a>
                <a href="admin.php">Admin</a>
            </nav>

        </div>
    </div>
</header>

<main>
    <section class="notices-hero">
        <div class="container">
            <p class="eyebrow">HOSTELIA • NOTICE BOARD</p>
            <h1>Latest Notices</h1>
            <p class="section-description">
                Stay informed about hostel announcements, important updates
                and upcoming activities.
            </p>
        </div>
    </section>

    <section class="container notice-list">

        <?php if ($notice_result && $notice_result->num_rows > 0): ?>

            <?php while ($notice = $notice_result->fetch_assoc()): ?>

                <article class="notice-card">
                    <h2>
                        <?= htmlspecialchars($notice["title"], ENT_QUOTES, 'UTF-8') ?>
                    </h2>

                    <p class="notice-text">
                        <?= nl2br(htmlspecialchars($notice["notice_text"], ENT_QUOTES, 'UTF-8')) ?>
                    </p>

                    <span class="notice-date">
                        <strong>Posted:</strong>
                        <?php
                        echo !empty($notice["notice_date"])
                            ? date("d M Y, h:i A", strtotime($notice["notice_date"]))
                            : "Date unavailable";
                        ?>
                    </span>
                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-notices">
                <h2>No Notices Yet</h2>
                <p>There are currently no hostel announcements. Please check again later.</p>
            </div>

        <?php endif; ?>

    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <p>© 2026 Hostelia. Hostel Management System.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>

</body>
</html>

<?php
$conn->close();
?>