<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

$days  = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$meals = ["Breakfast", "Lunch", "Dinner"];
$mealIcons = ["Breakfast" => "🌅", "Lunch" => "🍛", "Dinner" => "🌙"];

// Work out the dates of the current week (Monday to Sunday)
$monday = new DateTime("monday this week");
$monday->setTime(0, 0, 0);

$weekDates = [];
foreach ($days as $i => $day) {
    $date = clone $monday;
    $date->modify("+{$i} days");
    $weekDates[$day] = $date;
}

$sunday    = $weekDates["Sunday"];
$todayName = (new DateTime("now"))->format("l");
$todayDate = (new DateTime("now"))->format("Y-m-d");

// Load the weekly menu
$menu = [];
$result = $conn->query(
    "SELECT day_name, meal_type, food_items
     FROM food_menu
     WHERE meal_type IN ('Breakfast', 'Lunch', 'Dinner')
     ORDER BY id"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $d = $row["day_name"];
        $m = $row["meal_type"];
        $menu[$d][$m] = isset($menu[$d][$m])
            ? $menu[$d][$m] . ", " . $row["food_items"]
            : $row["food_items"];
    }
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Menu | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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

        /* PAGE */
        .main-container {
            max-width: 1120px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .page-label {
            color: #a47c50;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 3px;
            margin-bottom: 8px;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(30px, 4vw, 42px);
            margin-bottom: 6px;
        }

        .week-range {
            color: #75695d;
            margin-bottom: 30px;
        }

        /* DAY CARDS */
        .day-card {
            height: 100%;
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 5px 20px rgba(64, 56, 47, 0.05);
        }

        .day-card.is-today {
            border: 2px solid #a47c50;
            background: #fffaf0;
        }

        .day-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 10px;
            border-bottom: 1px solid #eadfce;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .day-name {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 600;
            margin: 0;
        }

        .day-date {
            color: #8a7c6c;
            font-size: 13px;
        }

        .today-badge {
            background: #a47c50;
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 3px 9px;
            border-radius: 20px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .meal {
            margin-bottom: 14px;
        }

        .meal:last-child {
            margin-bottom: 0;
        }

        .meal-title {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #a47c50;
            margin-bottom: 3px;
        }

        .meal-items {
            font-size: 14px;
            line-height: 1.55;
            margin: 0;
        }

        .meal-empty {
            color: #aaa095;
            font-style: italic;
        }

        .footer {
            text-align: center;
            padding: 28px 15px;
            font-size: 13px;
            color: #75695d;
        }

        @media (max-width: 600px) {
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

    <div class="page-label">HOSTEL MESS</div>
    <h1 class="page-title">Weekly Food Menu</h1>
    <p class="week-range">
        This week:
        <?= h($monday->format("d M")) ?> – <?= h($sunday->format("d M Y")) ?>
    </p>

    <div class="row g-4">

        <?php foreach ($days as $day): ?>
            <?php
                $date    = $weekDates[$day];
                $isToday = ($date->format("Y-m-d") === $todayDate);
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="day-card<?= $isToday ? " is-today" : "" ?>">

                    <div class="day-head">
                        <h3 class="day-name">
                            <?= h($day) ?>
                            <?php if ($isToday): ?>
                                <span class="today-badge">TODAY</span>
                            <?php endif; ?>
                        </h3>
                        <span class="day-date"><?= h($date->format("d M")) ?></span>
                    </div>

                    <?php foreach ($meals as $meal): ?>
                        <div class="meal">
                            <div class="meal-title">
                                <?= $mealIcons[$meal] ?> <?= h($meal) ?>
                            </div>

                            <?php if (!empty($menu[$day][$meal])): ?>
                                <p class="meal-items"><?= h($menu[$day][$meal]) ?></p>
                            <?php else: ?>
                                <p class="meal-items meal-empty">Menu not updated yet</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        <?php endforeach; ?>

    </div>

</main>

<footer class="footer">
    © <?= date("Y") ?> Hostelia Hostel Management System
</footer>

</body>
</html>