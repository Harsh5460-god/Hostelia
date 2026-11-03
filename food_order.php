```php
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$studentId = (int)$_SESSION["student_id"];

if (!isset($_SESSION["food_cart"])) {
    $_SESSION["food_cart"] = [];
}

if (empty($_SESSION["food_csrf"])) {
    $_SESSION["food_csrf"] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION["food_csrf"];
$message = "";
$error = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirectFood() {
    header("Location: food_order.php");
    exit;
}

// Handle cart and order actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["food_csrf"], $_POST["csrf_token"])) {
        die("Invalid request. Please refresh the page.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $itemId = (int)($_POST["item_id"] ?? 0);
        $quantity = max(1, min(20, (int)($_POST["quantity"] ?? 1)));

        $stmt = $conn->prepare(
            "SELECT id FROM food_items WHERE id = ? AND is_available = 1"
        );
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($item) {
            $current = (int)($_SESSION["food_cart"][$itemId] ?? 0);
            $_SESSION["food_cart"][$itemId] = min(20, $current + $quantity);
        }

        redirectFood();
    }

    if ($action === "update") {
        $quantities = $_POST["quantities"] ?? [];

        foreach ($quantities as $id => $qty) {
            $id = (int)$id;
            $qty = (int)$qty;

            if ($qty <= 0) {
                unset($_SESSION["food_cart"][$id]);
            } else {
                $_SESSION["food_cart"][$id] = min(20, $qty);
            }
        }

        redirectFood();
    }

    if ($action === "remove") {
        $itemId = (int)($_POST["item_id"] ?? 0);
        unset($_SESSION["food_cart"][$itemId]);
        redirectFood();
    }

    if ($action === "place_order") {

        $orderType = $_POST["order_type"] ?? "Pickup";
        $paymentMethod = $_POST["payment_method"] ?? "";

        if (!in_array($orderType, ["Pickup", "Room Delivery"], true)) {
            $error = "Please select a valid delivery option.";
        } elseif (
            ($orderType === "Pickup" &&
             !in_array($paymentMethod, ["Cash", "UPI at Counter"], true)) ||
            ($orderType === "Room Delivery" &&
             $paymentMethod !== "Pay on Delivery")
        ) {
            $error = "Please select a valid payment method.";
        } elseif (empty($_SESSION["food_cart"])) {
            $error = "Your cart is empty.";
        } else {

            $deliveryRoom = null;
            $deliveryCharge = 0.00;

            // Find student's allocated room
            if ($orderType === "Room Delivery") {

                $roomStmt = $conn->prepare(
                    "SELECT hr.room_number
                     FROM room_allocations ra
                     INNER JOIN hostel_rooms hr
                     ON ra.hostel_room_id = hr.id
                     WHERE ra.student_id = ?
                     ORDER BY ra.allocated_at DESC
                     LIMIT 1"
                );

                $roomStmt->bind_param("i", $studentId);
                $roomStmt->execute();
                $roomResult = $roomStmt->get_result()->fetch_assoc();
                $roomStmt->close();

                if (!$roomResult) {
                    $error = "You do not have an allocated room. Please choose Pickup or contact the hostel admin.";
                } else {
                    $deliveryRoom = $roomResult["room_number"];
                    $deliveryCharge = 15.00;
                }
            }

            if ($error === "") {

                $cartIds = array_map("intval", array_keys($_SESSION["food_cart"]));
                $cartIds = array_filter($cartIds, fn($id) => $id > 0);

                if (empty($cartIds)) {
                    $error = "Your cart is empty.";
                } else {

                    $conn->begin_transaction();

                    try {
                        $placeholders = implode(",", array_fill(0, count($cartIds), "?"));
                        $types = str_repeat("i", count($cartIds));

                        $sql = "SELECT id, item_name, price
                                FROM food_items
                                WHERE is_available = 1
                                AND id IN ($placeholders)
                                FOR UPDATE";

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param($types, ...$cartIds);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $items = [];

                        while ($row = $result->fetch_assoc()) {
                            $items[(int)$row["id"]] = $row;
                        }

                        $stmt->close();

                        $subtotal = 0.00;
                        $orderItems = [];

                        foreach ($_SESSION["food_cart"] as $id => $qty) {
                            $id = (int)$id;
                            $qty = (int)$qty;

                            if ($qty < 1 || $qty > 20 || !isset($items[$id])) {
                                throw new Exception(
                                    "An item in your cart is no longer available. Please review your cart."
                                );
                            }

                            $item = $items[$id];
                            $price = (float)$item["price"];
                            $lineTotal = $price * $qty;

                            $subtotal += $lineTotal;

                            $orderItems[] = [
                                "id" => $id,
                                "name" => $item["item_name"],
                                "price" => $price,
                                "quantity" => $qty,
                                "subtotal" => $lineTotal
                            ];
                        }

                        $totalAmount = $subtotal + $deliveryCharge;

                        $orderStmt = $conn->prepare(
                            "INSERT INTO food_orders
                            (student_id, total_amount, delivery_charge,
                             payment_method, order_type, delivery_room)
                            VALUES (?, ?, ?, ?, ?, ?)"
                        );

                        $orderStmt->bind_param(
                            "iddsss",
                            $studentId,
                            $totalAmount,
                            $deliveryCharge,
                            $paymentMethod,
                            $orderType,
                            $deliveryRoom
                        );

                        $orderStmt->execute();
                        $orderId = $conn->insert_id;
                        $orderStmt->close();

                        $itemStmt = $conn->prepare(
                            "INSERT INTO food_order_items
                            (order_id, food_item_id, item_name,
                             price, quantity, subtotal)
                            VALUES (?, ?, ?, ?, ?, ?)"
                        );

                        foreach ($orderItems as $item) {
                            $itemStmt->bind_param(
                                "iisdid",
                                $orderId,
                                $item["id"],
                                $item["name"],
                                $item["price"],
                                $item["quantity"],
                                $item["subtotal"]
                            );

                            $itemStmt->execute();
                        }

                        $itemStmt->close();
                        $conn->commit();

                        $_SESSION["food_cart"] = [];
                        $_SESSION["last_food_order"] = $orderId;

                        header("Location: food_order.php?success=1");
                        exit;

                    } catch (Throwable $ex) {
                        $conn->rollback();
                        $error = $ex->getMessage();
                    }
                }
            }
        }
    }
}

// Get available food items
$foodItems = [];
$result = $conn->query(
    "SELECT id, item_name, category, description, price, image
     FROM food_items
     WHERE is_available = 1
     ORDER BY FIELD(category, 'Breakfast', 'Lunch', 'Dinner', 'Snacks', 'Beverages'), item_name"
);

while ($row = $result->fetch_assoc()) {
    $foodItems[] = $row;
}

// Get cart items with current prices
$cartItems = [];
$cartSubtotal = 0.00;

if (!empty($_SESSION["food_cart"])) {
    $ids = array_map("intval", array_keys($_SESSION["food_cart"]));
    $ids = array_filter($ids, fn($id) => $id > 0);

    if (!empty($ids)) {
        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $types = str_repeat("i", count($ids));

        $stmt = $conn->prepare(
            "SELECT id, item_name, price
             FROM food_items
             WHERE is_available = 1 AND id IN ($placeholders)"
        );

        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $id = (int)$row["id"];
            $qty = (int)$_SESSION["food_cart"][$id];

            $row["quantity"] = $qty;
            $row["subtotal"] = (float)$row["price"] * $qty;

            $cartSubtotal += $row["subtotal"];
            $cartItems[] = $row;
        }

        $stmt->close();
    }
}

$studentName = e($_SESSION["student_name"] ?? "Student");

$categories = ["Breakfast", "Lunch", "Dinner", "Snacks", "Beverages"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Food Ordering | Hostelia</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; }

body {
    background: #f7f3eb;
    color: #40382f;
    font-family: 'Poppins', sans-serif;
}

.navbar-custom {
    background: #fffdf8;
    padding: 16px 7%;
    border-bottom: 1px solid #e7ded1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.brand {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    color: #40382f;
    text-decoration: none;
}

.brand span { color: #a47c50; }

.nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
}

.nav-links a {
    text-decoration: none;
    color: #625548;
    font-size: 13px;
}

.page-container {
    max-width: 1200px;
    margin: 35px auto;
    padding: 0 20px;
}

.hero {
    background: linear-gradient(120deg, #40382f, #70583e);
    color: white;
    padding: 35px;
    border-radius: 14px;
    margin-bottom: 30px;
}

.hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 34px;
}

.hero p {
    color: #eee3d6;
    margin-bottom: 0;
}

.layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 25px;
    align-items: start;
}

.section-title {
    font-family: 'Playfair Display', serif;
    margin-bottom: 20px;
}

.category-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-bottom: 25px;
}

.category-nav a {
    text-decoration: none;
    background: #fffdf8;
    border: 1px solid #e2d8c9;
    color: #625548;
    padding: 9px 15px;
    border-radius: 25px;
    font-size: 12px;
}

.category-nav a:hover {
    background: #40382f;
    color: white;
}

.food-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.food-card {
    background: #fffdf8;
    border: 1px solid #e7ded1;
    border-radius: 12px;
    overflow: hidden;
    transition: 0.2s;
}

.food-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(64,56,47,0.08);
}

.food-image {
    width: 100%;
    height: 155px;
    object-fit: cover;
    background: #eee5d8;
}

.food-placeholder {
    height: 155px;
    background: #eee5d8;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 45px;
}

.food-info { padding: 15px; }

.food-info h5 {
    font-size: 15px;
    font-weight: 600;
}

.food-info p {
    color: #928477;
    font-size: 11px;
    min-height: 35px;
}

.price {
    color: #8a6137;
    font-size: 17px;
    font-weight: 600;
}

.add-form {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.add-form input {
    width: 55px;
    border: 1px solid #ddd0bf;
    border-radius: 5px;
    padding: 7px;
}

.btn-brown {
    background: #40382f;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 9px 13px;
    font-size: 12px;
}

.btn-brown:hover {
    background: #70583e;
    color: white;
}

.cart-card {
    background: #fffdf8;
    border: 1px solid #e7ded1;
    border-radius: 12px;
    padding: 22px;
    position: sticky;
    top: 20px;
}

.cart-item {
    border-bottom: 1px solid #eee5d9;
    padding: 12px 0;
    font-size: 12px;
}

.cart-item strong { font-size: 13px; }

.cart-total {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
    font-weight: 600;
}

.form-label {
    font-size: 12px;
    font-weight: 600;
    margin-top: 14px;
}

.form-select {
    font-size: 12px;
    border-color: #ddd0bf;
    padding: 10px;
}

.note {
    background: #f4ede3;
    border-radius: 7px;
    padding: 11px;
    color: #70583e;
    font-size: 11px;
    margin-top: 12px;
}

.alert { font-size: 13px; }

.footer {
    text-align: center;
    padding: 25px;
    color: #928477;
    font-size: 12px;
}

@media(max-width: 900px) {
    .layout { grid-template-columns: 1fr; }
    .cart-card { position: static; }
}

@media(max-width: 550px) {
    .food-grid { grid-template-columns: 1fr; }
    .hero h1 { font-size: 27px; }
    .navbar-custom { padding: 15px 20px; }
    .nav-links { gap: 12px; }
}
</style>
</head>

<body>

<nav class="navbar-custom">
    <a href="student_dashboard.php" class="brand">Hostelia<span>.</span></a>

    <div class="nav-links">
        <a href="student_dashboard.php">Dashboard</a>
        <a href="booking_history.php">My Bookings</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<main class="page-container">

<section class="hero">
    <h1>Food Ordering 🍽️</h1>
    <p>Welcome, <?= $studentName ?>! Explore the menu and order your favourites.</p>
</section>

<?php if (isset($_GET["success"])): ?>
    <div class="alert alert-success">
        Your order has been placed successfully!
        <?php if (!empty($_SESSION["last_food_order"])): ?>
            Order ID: #<?= (int)$_SESSION["last_food_order"] ?>.
        <?php endif; ?>
        <a href="my_food_orders.php">Track your orders</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="layout">

<section>
    <h2 class="section-title">Explore Our Menu</h2>

    <div class="category-nav">
        <?php foreach ($categories as $category): ?>
            <a href="#<?= e(strtolower($category)) ?>">
                <?= e($category) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php foreach ($categories as $category): ?>
        <section id="<?= e(strtolower($category)) ?>" class="mb-5">
            <h3 class="section-title"><?= e($category) ?></h3>

            <div class="food-grid">

            <?php
            $found = false;
            foreach ($foodItems as $food):
                if ($food["category"] !== $category) continue;
                $found = true;

                $image = trim((string)$food["image"]);
                $imagePath = "";

                if ($image !== "") {
                    $imagePath = (
                        strpos($image, "uploads/") === 0
                    ) ? $image : "uploads/food/" . basename($image);
                }
            ?>

                <article class="food-card">

                    <?php if ($imagePath): ?>
                        <img
                            src="<?= e($imagePath) ?>"
                            class="food-image"
                            alt="<?= e($food["item_name"]) ?>"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >
                        <div class="food-placeholder" style="display:none;">🍽️</div>
                    <?php else: ?>
                        <div class="food-placeholder">🍽️</div>
                    <?php endif; ?>

                    <div class="food-info">
                        <h5><?= e($food["item_name"]) ?></h5>

                        <p><?= e($food["description"] ?? "Freshly prepared food.") ?></p>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price">₹<?= number_format((float)$food["price"], 2) ?></span>
                        </div>

                        <form method="POST" class="add-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="item_id" value="<?= (int)$food["id"] ?>">

                            <input type="number" name="quantity" value="1" min="1" max="20" aria-label="Quantity">

                            <button type="submit" class="btn-brown">
                                + Add to Cart
                            </button>
                        </form>
                    </div>
                </article>

            <?php endforeach; ?>

            <?php if (!$found): ?>
                <p class="text-secondary">No items available in this category.</p>
            <?php endif; ?>

            </div>
        </section>
    <?php endforeach; ?>
</section>

<!-- CART -->
<aside class="cart-card">
    <h3 class="section-title">🛒 Your Cart</h3>

    <?php if (empty($cartItems)): ?>

        <p class="text-secondary small">Your cart is empty. Add some delicious food!</p>

    <?php else: ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="update">

            <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="d-flex justify-content-between">
                        <strong><?= e($item["item_name"]) ?></strong>
                        <span>₹<?= number_format($item["subtotal"], 2) ?></span>
                    </div>

                    <div class="d-flex align-items-center gap-2 mt-2">
                        <label class="small">Qty:</label>
                        <input
                            type="number"
                            class="form-control form-control-sm"
                            style="width:75px"
                            name="quantities[<?= (int)$item["id"] ?>]"
                            value="<?= (int)$item["quantity"] ?>"
                            min="0"
                            max="20"
                        >
                        <button
                            class="btn btn-sm btn-outline-secondary"
                            type="submit"
                            formaction="food_order.php"
                            onclick="this.form.querySelector('[name=action]').value='update';"
                        >Update</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>

        <?php foreach ($cartItems as $item): ?>
            <form method="POST" class="mt-1">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="item_id" value="<?= (int)$item["id"] ?>">
                <button type="submit" class="btn btn-link text-danger p-0 small">
                    Remove <?= e($item["item_name"]) ?>
                </button>
            </form>
        <?php endforeach; ?>

        <div class="cart-total">
            <span>Subtotal</span>
            <span>₹<?= number_format($cartSubtotal, 2) ?></span>
        </div>

        <hr>

        <form method="POST" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="place_order">

            <label class="form-label">Delivery Option</label>

            <select name="order_type" id="orderType" class="form-select" required>
                <option value="Pickup">Pickup — Free</option>
                <option value="Room Delivery">Room Delivery — ₹15</option>
            </select>

            <div id="deliveryInfo" class="note" style="display:none;">
                <strong>Room Delivery</strong><br>
                Your allocated hostel room will be fetched automatically.
                Delivery charge: ₹15.
            </div>

            <label class="form-label">Payment Method</label>

            <select name="payment_method" id="paymentMethod" class="form-select" required>
                <option value="Cash">Cash at Counter</option>
                <option value="UPI at Counter">UPI at Counter</option>
            </select>

            <div id="paymentInfo" class="note">
                Pay by Cash or UPI at the hostel counter.
            </div>

            <div class="cart-total">
                <span>Estimated Total</span>
                <span id="estimatedTotal">₹<?= number_format($cartSubtotal, 2) ?></span>
            </div>

            <button type="submit" class="btn-brown w-100 mt-3">
                Place Order
            </button>
        </form>

    <?php endif; ?>

</aside>

</div>
</main>

<footer class="footer">
    © <?= date("Y") ?> Hostelia Hostel Management System
</footer>

<script>
const orderType = document.getElementById("orderType");
const paymentMethod = document.getElementById("paymentMethod");
const deliveryInfo = document.getElementById("deliveryInfo");
const paymentInfo = document.getElementById("paymentInfo");
const estimatedTotal = document.getElementById("estimatedTotal");

const subtotal = <?= json_encode(round($cartSubtotal, 2)) ?>;

function updateCheckout() {
    if (!orderType || !paymentMethod) return;

    if (orderType.value === "Room Delivery") {
        deliveryInfo.style.display = "block";
        paymentMethod.innerHTML =
            '<option value="Pay on Delivery">Pay on Delivery (Cash / UPI)</option>';

        paymentInfo.textContent =
            "Pay by Cash or UPI when your food is delivered to your room.";

        estimatedTotal.textContent =
            "₹" + (subtotal + 15).toFixed(2);
    } else {
        deliveryInfo.style.display = "none";
        paymentMethod.innerHTML =
            '<option value="Cash">Cash at Counter</option>' +
            '<option value="UPI at Counter">UPI at Counter</option>';

        paymentInfo.textContent =
            "Pay by Cash or UPI at the hostel counter.";

        estimatedTotal.textContent =
            "₹" + subtotal.toFixed(2);
    }
}

if (orderType) {
    orderType.addEventListener("change", updateCheckout);
    updateCheckout();
}
</script>

</body>
</html>
```