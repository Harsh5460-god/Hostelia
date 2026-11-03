<?php
session_start();

// Clear session data and the session cookie
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}

session_destroy();

// Stop the browser from showing cached logged-in pages with the Back button
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="6;url=login.php">
    <title>Logged Out | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        .logout-wrapper {
            max-width: 480px;
            margin: 60px auto 70px;
            padding: 42px 38px;
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(64, 56, 47, 0.08);
            text-align: center;
            font-family: 'Poppins', sans-serif;
            color: #40382f;
        }

        .logout-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f3eadf;
            color: #765337;
            font-size: 30px;
        }

        .logout-wrapper h2 {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 600;
            margin: 8px 0 10px;
        }

        .logout-wrapper p {
            color: #8a7563;
            font-size: 15px;
            margin-bottom: 26px;
        }

        .logout-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .logout-actions a {
            padding: 12px 26px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-login-again {
            background: #40382f;
            color: #fff;
        }

        .btn-login-again:hover {
            background: #5d4c39;
            color: #fff;
        }

        .btn-home {
            border: 1px solid #d8cfc2;
            color: #6c5036;
            background: transparent;
        }

        .btn-home:hover {
            background: #f3eadf;
            color: #6c5036;
        }

        .redirect-note {
            margin: 22px 0 0;
            font-size: 12px;
            color: #a89683;
        }
    </style>
</head>
<body>

<header class="site-header">
    <a href="index.php" class="logo home-logo">Hostelia</a>
    <p class="tagline">HOSTEL MANAGEMENT SYSTEM</p>

    <div class="header-auth">
        <a href="registration.php" class="auth-register">Register</a>
        <a href="login.php" class="auth-login">Student Login</a>
    </div>

    
</header>

<main class="container">
    <div class="logout-wrapper">
        <div class="logout-icon">&#10003;</div>
        <span class="section-label">SEE YOU SOON</span>
        <h2>You've been logged out</h2>
        <p>Your session has ended safely. Thanks for using Hostelia.</p>

        <div class="logout-actions">
            <a href="login.php" class="btn-login-again">Login Again</a>
            <a href="index.php" class="btn-home">Back to Home</a>
        </div>

        <p class="redirect-note">Redirecting to the login page in a few seconds&hellip;</p>
    </div>
</main>

<footer>
    <div class="footer-inner">
        <div><strong>Hostelia</strong> &mdash; Hostel Management System</div>
        <div>&copy; 2026 All Rights Reserved</div>
    </div>
</footer>

</body>
</html>