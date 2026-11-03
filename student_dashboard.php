<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION["student_name"] ?? "Student", ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($_SESSION["student_email"] ?? "", ENT_QUOTES, "UTF-8");
$studentCode = htmlspecialchars($_SESSION["student_code"] ?? "", ENT_QUOTES, "UTF-8");
require_once "db.php";

if (empty($_SESSION["room_change_csrf"])) {
    $_SESSION["room_change_csrf"] = bin2hex(random_bytes(32));
}

$studentId = (int)$_SESSION["student_id"];

$roomStmt = $conn->prepare("
    SELECT rb.*, hr.room_number
    FROM room_bookings rb
    LEFT JOIN room_allocations ra ON ra.booking_id = rb.id
    LEFT JOIN hostel_rooms hr ON hr.id = ra.hostel_room_id
    WHERE rb.student_id = ?
      AND rb.booking_status IN ('Confirmed', 'Cancellation Requested')
    ORDER BY rb.booking_date DESC
    LIMIT 1
");
$roomStmt->bind_param("i", $studentId);
$roomStmt->execute();
$roomDetails = $roomStmt->get_result()->fetch_assoc();
$roomStmt->close();

$pendingChange = null;

if ($roomDetails) {
    $changeStmt = $conn->prepare("
        SELECT * FROM room_change_requests
        WHERE booking_id = ? AND request_status = 'Pending'
        ORDER BY id DESC LIMIT 1
    ");
    $changeStmt->bind_param("i", $roomDetails["id"]);
    $changeStmt->execute();
    $pendingChange = $changeStmt->get_result()->fetch_assoc();
    $changeStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f7f3eb;
            color: #40382f;
            font-family: 'Poppins', sans-serif;
        }

        /* NAVBAR */
        .navbar-custom {
            background: #fffdf8;
            padding: 17px 7%;
            border-bottom: 1px solid #e8dfd2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .brand {
            text-decoration: none;
            color: #40382f;
            font-family: 'Playfair Display', serif;
            font-size: 29px;
            font-weight: 700;
        }

        .brand span {
            color: #a47c50;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-right a {
            text-decoration: none;
            color: #625548;
            font-size: 13px;
            font-weight: 500;
        }

        .nav-right a:hover {
            color: #a47c50;
        }

        .logout-btn {
            background: #40382f;
            color: white !important;
            padding: 9px 17px;
            border-radius: 6px;
        }

        .logout-btn:hover {
            background: #70583e;
        }

        /* MAIN CONTENT */
        .main-container {
            max-width: 1120px;
            margin: 45px auto;
            padding: 0 20px;
        }

        /* WELCOME SECTION */
        .welcome-card {
            background: linear-gradient(120deg, #40382f, #70583e);
            color: white;
            padding: 42px;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 28px rgba(64, 56, 47, 0.13);
        }

        .welcome-card::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 50%;
            right: -55px;
            top: -85px;
            box-shadow: 0 0 0 30px rgba(255,255,255,0.04),
                        0 0 0 60px rgba(255,255,255,0.03);
        }

        .welcome-label {
            font-size: 11px;
            letter-spacing: 3px;
            color: #e5c9a4;
            font-weight: 600;
        }

        .welcome-card h1 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            margin: 12px 0;
        }

        .welcome-card p {
            color: #eee3d6;
            font-size: 14px;
            margin-bottom: 0;
        }

        .welcome-icon {
            font-size: 55px;
            position: absolute;
            right: 75px;
            bottom: 30px;
            opacity: 0.85;
        }

        /* SECTION TITLE */
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 25px;
            margin: 38px 0 18px;
            color: #40382f;
        }

        /* ACCOUNT CARDS */
        .account-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .account-card {
            background: #fffdf8;
            border: 1px solid #e7ded1;
            border-radius: 11px;
            padding: 22px;
            transition: 0.25s;
        }

        .account-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 22px rgba(64,56,47,0.08);
        }

        .account-icon {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            background: #f1e8dc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .account-card small {
            color: #928477;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .account-card h5 {
            font-size: 14px;
            margin-top: 7px;
            margin-bottom: 0;
            overflow-wrap: anywhere;
        }

        /* QUICK LINKS */
        .links-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .quick-card {
            background: #fffdf8;
            border: 1px solid #e7ded1;
            border-radius: 11px;
            padding: 23px;
            text-decoration: none;
            color: #40382f;
            transition: 0.25s;
            display: block;
        }

        .quick-card:hover {
            transform: translateY(-5px);
            border-color: #b8956c;
            box-shadow: 0 9px 24px rgba(64,56,47,0.09);
            color: #40382f;
        }

        .quick-icon {
            font-size: 27px;
            margin-bottom: 13px;
        }

        .quick-card h5 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .quick-card p {
            font-size: 12px;
            color: #928477;
            margin: 0;
            line-height: 1.6;
        }

        .card-arrow {
            float: right;
            color: #a47c50;
            font-size: 20px;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            padding: 28px 15px;
            margin-top: 55px;
            border-top: 1px solid #e7ded1;
            color: #928477;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .account-grid,
            .links-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .welcome-card {
                padding: 30px 25px;
            }

            .welcome-card h1 {
                font-size: 29px;
            }

            .welcome-icon {
                display: none;
            }
        }

        @media (max-width: 500px) {
            .account-grid,
            .links-grid {
                grid-template-columns: 1fr;
            }

            .navbar-custom {
                padding: 15px 20px;
            }

            .nav-right {
                gap: 12px;
            }

            .nav-right a {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar-custom">
    <a href="student_dashboard.php" class="brand">
        Hostelia<span>.</span>
    </a>

    <div class="nav-right">
        <a href="student_dashboard.php">Dashboard</a>
        <a href="booking_history.php">My Bookings</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<main class="main-container">

    <!-- WELCOME -->
    <section class="welcome-card">
        <div class="welcome-label">STUDENT PORTAL</div>

        <h1>Welcome, <?= $name ?>!</h1>

        <p>
            Your hostel life, made easier.
            Manage your stay and explore your student services.
        </p>

        <div class="welcome-icon">🏡</div>
    </section>

    <!-- ACCOUNT DETAILS -->
    <h2 class="section-heading">My Account</h2>

    <section class="account-grid">

        <div class="account-card">
            <div class="account-icon">👤</div>
            <small>Student Name</small>
            <h5><?= $name ?></h5>
        </div>

        <div class="account-card">
            <div class="account-icon">🎓</div>
            <small>Student ID</small>
            <h5><?= $studentCode ?: "Not available" ?></h5>
        </div>

        <div class="account-card">
            <div class="account-icon">✉️</div>
            <small>Email Address</small>
            <h5><?= $email ?: "Not available" ?></h5>
        </div>

    </section>
    <!-- ROOM DETAILS -->
<h2 class="section-heading">Room Details</h2>

<?php if ($roomDetails): ?>

<div class="account-card mb-4">

    <div class="d-flex justify-content-between flex-wrap gap-2">
        <div>
            <div class="account-icon">🛏️</div>
            <h5 style="font-size:20px;">Your Allocated Room</h5>
        </div>

        <span class="badge text-bg-success align-self-start">
            <?= htmlspecialchars($roomDetails["booking_status"]) ?>
        </span>
    </div>

    <hr>

    <div class="row g-3">
        <div class="col-md-4">
            <small>Room Number</small>
            <h5><?= htmlspecialchars($roomDetails["room_number"] ?? "Not allocated") ?></h5>
        </div>

        <div class="col-md-4">
            <small>Room Type</small>
            <h5><?= htmlspecialchars($roomDetails["room_type"]) ?></h5>
        </div>

        <div class="col-md-4">
            <small>Booking Date</small>
            <h5><?= date("d M Y", strtotime($roomDetails["booking_date"])) ?></h5>
        </div>

        <div class="col-md-4">
            <small>Monthly Rent</small>
            <h5>₹<?= number_format((float)$roomDetails["total_rent"], 2) ?></h5>
        </div>

        <div class="col-md-4">
            <small>Facilities</small>
            <h5><?= htmlspecialchars($roomDetails["selected_facilities"] ?: "None") ?></h5>
        </div>
    </div>

    <?php if ($roomDetails["booking_status"] === "Cancellation Requested"): ?>

        <p class="mt-3 mb-0 text-warning">
            Your cancellation request is awaiting admin approval. Your room remains allocated until approval.
        </p>

    <?php elseif ($pendingChange): ?>

        <p class="mt-3 mb-0 text-warning">
            Room change request to
            <strong><?= htmlspecialchars($pendingChange["requested_room_type"]) ?></strong>
            is awaiting admin approval.
        </p>

    <?php else: ?>

        <?php
        $roomOptions = [
            "Single Sharing" => ["Double Sharing", "Triple Sharing", "Quad Sharing"],
            "Double Sharing" => ["Single Sharing", "Triple Sharing", "Quad Sharing"],
            "Triple Sharing" => ["Single Sharing", "Double Sharing", "Quad Sharing"],
            "Quad Sharing" => ["Single Sharing", "Double Sharing", "Triple Sharing"]
        ];

        $options = $roomOptions[$roomDetails["room_type"]] ?? [];
        ?>

        <?php if (!empty($options)): ?>
            <hr>
            <h5>Upgrade / Downgrade Room</h5>
            <p class="text-muted" style="font-size:13px;">
                Select your preferred room type. The change will happen only after admin approval.
            </p>

            <form action="request_room_change.php" method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION["room_change_csrf"]) ?>">

                <input type="hidden" name="booking_id"
                       value="<?= (int)$roomDetails["id"] ?>">

                <select name="requested_room_type" class="form-select mb-3" required>
                    <option value="">Select Room Type</option>
                    <?php foreach ($options as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>">
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn"
                        style="background:#6f4b31;color:white;"
                        onclick="return confirm('Submit this room change request?');">
                    Submit Request
                </button>
            </form>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php else: ?>

<div class="account-card">
    <p class="mb-0">You currently do not have a confirmed room booking.</p>
</div>

<?php endif; ?>

    <!-- QUICK LINKS -->
    <h2 class="section-heading">Explore Services</h2>

    <section class="links-grid">

        <a href="notices.php" class="quick-card">
            <div class="quick-icon">📢</div>
            <h5>Notices <span class="card-arrow">›</span></h5>
            <p>Stay updated with hostel announcements.</p>
        </a>

        <a href="rooms.php" class="quick-card">
            <div class="quick-icon">🛏️</div>
            <h5>Explore Rooms <span class="card-arrow">›</span></h5>
            <p>View available hostel rooms and details.</p>
        </a>

        <a href="booking_history.php" class="quick-card">
            <div class="quick-icon">📋</div>
            <h5>My Bookings <span class="card-arrow">›</span></h5>
            <p>Check your booking and allocation status.</p>
        </a>

        <a href="complaints.php" class="quick-card">
            <div class="quick-icon">📝</div>
            <h5>Complaints <span class="card-arrow">›</span></h5>
            <p>Submit and manage hostel complaints.</p>
        </a>

        <a href="food_menu.php" class="quick-card">
            <div class="quick-icon">🍽️</div>
            <h5>Food Menu <span class="card-arrow">›</span></h5>
            <p>See this week's Breakfast, Lunch and Dinner menu.</p>
        </a>

        <a href="logout.php" class="quick-card">
            <div class="quick-icon">🚪</div>
            <h5>Logout <span class="card-arrow">›</span></h5>
            <p>Securely sign out of your student account.</p>
        </a>

    </section>

</main>

<footer class="footer">
    © <?= date("Y") ?> Hostelia Hostel Management System
    <br>
    Made for a better hostel experience.
</footer>

</body>
</html>