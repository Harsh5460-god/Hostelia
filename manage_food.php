<?php
require_once "admin_check.php";
require_once "db.php";

$days  = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$meals = ["Breakfast", "Lunch", "Dinner"];

if (empty($_SESSION["food_menu_csrf"])) {
    $_SESSION["food_menu_csrf"] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION["food_menu_csrf"];

$message = "";
$messageType = "success";

if (isset($_GET["saved"])) {
    $message = "Weekly food menu updated successfully!";
}

// Save the whole weekly menu in one go
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_menu"])) {

    if (!hash_equals($csrf, (string)($_POST["csrf_token"] ?? ""))) {
        die("Invalid request. Please refresh the page.");
    }

    $input = $_POST["menu"] ?? [];

    if (!is_array($input)) {
        $message = "Invalid menu data.";
        $messageType = "danger";
    } else {
        try {
            $conn->begin_transaction();

            $deleteStmt = $conn->prepare(
                "DELETE FROM food_menu WHERE day_name = ? AND meal_type = ?"
            );
            $insertStmt = $conn->prepare(
                "INSERT INTO food_menu (day_name, meal_type, food_items)
                 VALUES (?, ?, ?)"
            );

            foreach ($days as $day) {
                foreach ($meals as $meal) {

                    // Only touch cells that were actually submitted
                    if (!isset($input[$day][$meal]) || !is_string($input[$day][$meal])) {
                        continue;
                    }

                    $text = trim($input[$day][$meal]);
                    $text = mb_substr($text, 0, 500);

                    // Replace whatever was saved for this day + meal
                    $deleteStmt->bind_param("ss", $day, $meal);
                    $deleteStmt->execute();

                    // An empty box means "no menu" for that meal
                    if ($text !== "") {
                        $insertStmt->bind_param("sss", $day, $meal, $text);
                        $insertStmt->execute();
                    }
                }
            }

            $deleteStmt->close();
            $insertStmt->close();

            $conn->commit();

            header("Location: manage_food.php?saved=1");
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
            error_log("Hostelia weekly menu save error: " . $e->getMessage());
            $message = "Unable to save the menu. Please try again.";
            $messageType = "danger";
        }
    }
}

// Load the current menu into $menu[day][meal]
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
        // If older data has several rows for one meal, show them together
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

    <title>Weekly Hostel Food Menu | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <style>
        body {
            background: #f7f3eb;
            color: #40382f;
            font-family: 'Poppins', sans-serif;
        }

        h2, h4 {
            font-family: 'Playfair Display', serif;
        }

        .food-container {
            max-width: 1150px;
            margin: 45px auto;
            padding: 0 15px;
        }

        .food-card {
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(64, 56, 47, 0.05);
        }

        .form-control {
            border-color: #d8cbbb;
            padding: 10px;
            font-size: 14px;
        }

        .btn-brown {
            background: #40382f;
            color: white;
        }

        .btn-brown:hover {
            background: #5d4c39;
            color: white;
        }

        .menu-table {
            vertical-align: middle;
            min-width: 820px;
        }

        .menu-table thead th {
            background: #eee7dc;
            color: #40382f;
            text-align: center;
        }

        .menu-table .day-cell {
            font-weight: 600;
            background: #faf6ee;
            width: 120px;
            white-space: nowrap;
        }
    </style>
</head>

<body>

<div class="container food-container">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2>Weekly Hostel Food Menu</h2>
            <p class="text-secondary mb-0">
                Update the Breakfast, Lunch and Dinner menu for each day.
                Students see this menu for the current week.
            </p>
        </div>

        <a href="admin.php" class="btn btn-brown">
            Back to Dashboard
        </a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-<?= h($messageType) ?>">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="manage_food.php">

        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

        <div class="food-card">

            <div class="table-responsive">
                <table class="table table-bordered menu-table mb-0">

                    <thead>
                        <tr>
                            <th>Day</th>
                            <?php foreach ($meals as $meal): ?>
                                <th><?= h($meal) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($days as $day): ?>
                            <tr>
                                <td class="day-cell"><?= h($day) ?></td>

                                <?php foreach ($meals as $meal): ?>
                                    <td>
                                        <textarea
                                            class="form-control"
                                            name="menu[<?= h($day) ?>][<?= h($meal) ?>]"
                                            rows="2"
                                            maxlength="500"
                                            placeholder="e.g. Poha, Tea, Banana"
                                            aria-label="<?= h($day . " " . $meal) ?>"
                                        ><?= h($menu[$day][$meal] ?? "") ?></textarea>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

            <p class="text-secondary small mt-3 mb-0">
                Separate dishes with commas. Leave a box empty if no meal is
                served.
            </p>

        </div>

        <button type="submit" name="save_menu" class="btn btn-brown px-4 py-2">
            Save Weekly Menu
        </button>

    </form>

</div>

</body>
</html>