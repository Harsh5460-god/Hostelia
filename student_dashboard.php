
<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION["student_name"] ?? "Student", ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($_SESSION["student_email"] ?? "", ENT_QUOTES, "UTF-8");
$studentCode = htmlspecialchars($_SESSION["student_code"] ?? "", ENT_QUOTES, "UTF-8");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background: #f7f3eb;
            color: #40382f;
            font-family: 'Poppins', sans-serif;
        }

        .dashboard-card {
            max-width: 850px;
            margin: 45px auto;
            padding: 35px;
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
        }

        .dashboard-card h1 {
            font-family: 'Playfair Display', serif;
        }

        .dashboard-link {
            display: inline-block;
            padding: 11px 18px;
            margin: 8px 8px 0 0;
            background: #40382f;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .dashboard-link:hover {
            background: #5d4c39;
            color: white;
        }
    </style>
</head>
<body>

<header class="site-header">
    <a href="index.php" class="logo home-logo">Hostelia</a>
    <p class="tagline">HOSTEL MANAGEMENT SYSTEM</p>
</header>

<main class="container">
    <section class="dashboard-card">
        <span class="section-label">STUDENT PORTAL</span>
        <h1>Welcome, <?= $name ?>!</h1>
        <p class="text-secondary">You have successfully logged in to Hostelia.</p>

        <hr>

        <h4>My Account</h4>
        <p><strong>Student ID:</strong> <?= $studentCode ?></p>
        <p><strong>Email:</strong> <?= $email ?></p>

        <hr>

        <h4>Quick Links</h4>
        <a class="dashboard-link" href="notices.php">View Notices</a>
        <a class="dashboard-link" href="rooms.php">View Rooms</a>
        <a class="dashboard-link" href="complaints.php">Complaints</a>
        <a class="dashboard-link" href="logout.php">Logout</a>
        <a href="booking_history.php">My Booking History</a>
    </section>
</main>

</body>
</html>
