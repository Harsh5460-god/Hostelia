
<?php
session_start();

require_once "db.php";

$isLoggedIn = isset($_SESSION["student_id"]);
$studentName = $_SESSION["student_name"] ?? "Student";
$initial = strtoupper(substr($studentName, 0, 1));

// Fetch rooms from database
$sql = "SELECT room_type, monthly_rent, facilities FROM rooms";
$result = $conn->query($sql);

$roomData = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roomData[$row["room_type"]] = $row;
    }
}

// Keep existing images and descriptions
$roomDetails = [
    "Single Sharing" => [
        "image" => "images/room-single.jpg",
        "alt" => "Single sharing hostel room",
        "description" => "Private room with a single bed, study table, cupboard, and personal space."
    ],
    "Double Sharing" => [
        "image" => "images/room-double.jpg",
        "alt" => "Double sharing hostel room",
        "description" => "Comfortable shared room for two students with separate beds, study tables, and cupboards."
    ],
    "Triple Sharing" => [
        "image" => "images/room-triple.jpg",
        "alt" => "Triple sharing hostel room",
        "description" => "Budget-friendly room for three students with basic furniture, storage space, and a shared study area."
    ],
    "Quad Sharing" => [
        "image" => "images/room-deluxe.jpg",
        "alt" => "Quad sharing hostel room",
        "description" => "Spacious room designed to accommodate four students with shared furniture and essential amenities."
    ]
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

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

    .room-price {
        font-size: 22px;
        font-weight: 600;
        color: #765337;
        margin: 12px 0;
    }

    .room-price small {
        font-size: 13px;
        font-weight: 400;
        color: #777;
    }

    .room-facilities {
        font-size: 14px;
        line-height: 1.8;
        color: #555;
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
    .book-btn {
    display: inline-block;
    background-color: #493a2e;
    color: #ffffff;
    text-decoration: none;
    padding: 10px 22px;
    border-radius: 6px;
    font-weight: 600;
    transition: 0.3s;
}

.book-btn:hover {
    background-color: #6b5544;
    color: #ffffff;
    text-decoration: none;
}
</style>

</head>

<body>

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
        <a href="index.php">HOME</a>
        <a href="about.php">ABOUT</a>
        <a href="rooms.php" class="active">ROOMS</a>
        <a href="complaints.PHP">COMPLAINTS</a>
        <a href="notices.php">NOTICES</a>
    </nav>

</header>

<main class="container main-content">

    <div class="content-area w-100">

        <span class="section-label">ACCOMMODATION</span>

        <h2>Available Room Types</h2>

        <p class="intro-text">
            Choose from a range of room configurations designed to suit your preference and privacy needs.
        </p>

        <div class="room-grid">

            <?php
            $number = 1;

            foreach ($roomDetails as $roomType => $details):

                // Skip room types not found in database
                if (!isset($roomData[$roomType])) {
                    continue;
                }

                $room = $roomData[$roomType];

                $roomName = $room["room_type"];
                $rent = $room["monthly_rent"];
                $facilities = $room["facilities"];

$bookingUrl = "book_room.php?room=" . urlencode($roomName);

if (!$isLoggedIn) {
    $bookingUrl = "login.php?redirect=" . urlencode($bookingUrl);
}
            ?>

                <div class="room-card">

                    <img
                        src="<?= htmlspecialchars($details["image"], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($details["alt"], ENT_QUOTES, 'UTF-8') ?>"
                        class="room-image"
                    >

                    <div class="room-card-content">

                        <span class="number">
                            <?= str_pad($number, 2, "0", STR_PAD_LEFT) ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($details["description"], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <div class="room-price">
                            ₹<?= number_format((float)$rent, 0) ?>
                            <small>/ month per student</small>
                        </div>

                        <div class="room-facilities">
                            <strong>Facilities:</strong><br>
                            <?= htmlspecialchars($facilities, ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <a href="<?= htmlspecialchars($bookingUrl) ?>" class="book-btn">
    BOOK ROOM
</a>

                    </div>

                </div>

            <?php
                $number++;
            endforeach;
            ?>

        </div>

        <?php if (empty($roomData)): ?>
            <p class="text-center mt-4">
                No rooms are currently available.
            </p>
        <?php endif; ?>

    </div>

</main>

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

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>

</body>
</html>
