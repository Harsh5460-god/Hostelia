```php
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$studentId = (int)$_SESSION["student_id"];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$stmt = $conn->prepare(
    "SELECT id, total_amount, delivery_charge, payment_method,
            payment_status, order_status, order_type, delivery_room,
            order_date
     FROM food_orders
     WHERE student_id = ?
     ORDER BY order_date DESC"
);

$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];

while ($order = $result->fetch_assoc()) {
    $orders[] = $order;
}

$stmt->close();

$progress = [
    "Order Placed" => 1,
    "Accepted" => 2,
    "Preparing" => 3,
    "Ready" => 4,
    "Delivered" => 5
];

function statusColor($status) {
    switch ($status) {
        case "Delivered":
            return "success";
        case "Rejected":
            return "danger";
        case "Ready":
            return "primary";
        case "Preparing":
            return "warning";
        case "Accepted":
            return "info";
        default:
            return "secondary";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Food Orders | Hostelia</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
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
    gap: 20px;
}

.nav-links a {
    text-decoration: none;
    color: #625548;
    font-size: 13px;
}

.container-custom {
    max-width: 1050px;
    margin: 40px auto;
    padding: 0 18px;
}

.page-heading {
    font-family: 'Playfair Display', serif;
    margin-bottom: 8px;
}

.subtitle {
    color: #928477;
    font-size: 13px;
    margin-bottom: 28px;
}

.order-card {
    background: #fffdf8;
    border: 1px solid #e7ded1;
    border-radius: 13px;
    padding: 24px;
    margin-bottom: 22px;
    box-shadow: 0 5px 17px rgba(64,56,47,0.04);
}

.order-header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    border-bottom: 1px solid #eee5d9;
    padding-bottom: 15px;
}

.order-id {
    font-weight: 600;
    font-size: 15px;
}

.order-date {
    color: #928477;
    font-size: 11px;
    margin-top: 5px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.detail-box {
    background: #f7f3eb;
    padding: 13px;
    border-radius: 8px;
}

.detail-box small {
    color: #928477;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.detail-box p {
    font-size: 12px;
    font-weight: 600;
    margin: 5px 0 0;
    overflow-wrap: anywhere;
}

.items-heading {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    margin-bottom: 12px;
}

.item-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 9px 0;
    border-bottom: 1px solid #f0e9df;
    font-size: 12px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 13px;
}

.grand-total {
    font-weight: 700;
    color: #8a6137;
    font-size: 17px;
}

.progress-track {
    display: flex;
    justify-content: space-between;
    margin: 25px 0 10px;
    position: relative;
    gap: 4px;
}

.progress-step {
    text-align: center;
    flex: 1;
    font-size: 10px;
    color: #a79a8b;
    position: relative;
}

.progress-step .circle {
    width: 25px;
    height: 25px;
    margin: 0 auto 7px;
    border-radius: 50%;
    background: #e8dfd3;
    color: #776858;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

.progress-step.active .circle {
    background: #40382f;
    color: white;
}

.progress-step.active {
    color: #40382f;
    font-weight: 600;
}

.rejected-message {
    background: #fbe9e7;
    color: #a33d35;
    padding: 12px;
    border-radius: 7px;
    font-size: 12px;
    margin-top: 15px;
}

.empty-card {
    background: #fffdf8;
    border: 1px solid #e7ded1;
    border-radius: 12px;
    padding: 45px 20px;
    text-align: center;
}

.empty-card .icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.btn-brown {
    display: inline-block;
    background: #40382f;
    color: white;
    padding: 10px 18px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 12px;
}

.btn-brown:hover {
    background: #70583e;
    color: white;
}

.footer {
    text-align: center;
    padding: 25px;
    color: #928477;
    font-size: 12px;
}

@media(max-width: 650px) {
    .details-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .order-card {
        padding: 17px;
    }

    .progress-step {
        font-size: 8px;
    }

    .navbar-custom {
        padding: 15px 20px;
    }

    .nav-links {
        gap: 12px;
    }
}
</style>
</head>

<body>

<nav class="navbar-custom">
    <a href="student_dashboard.php" class="brand">Hostelia<span>.</span></a>

    <div class="nav-links">
        <a href="food_order.php">Food Menu</a>
        <a href="student_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<main class="container-custom">

    <h1 class="page-heading">My Food Orders</h1>
    <p class="subtitle">Track your food orders and payment details.</p>

    <?php if (empty($orders)): ?>

        <div class="empty-card">
            <div class="icon">🍽️</div>
            <h4>No Orders Yet</h4>
            <p class="text-secondary small">
                You haven't placed any food orders yet.
            </p>
            <a href="food_order.php" class="btn-brown">
                Explore Food Menu
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <?php
            $orderId = (int)$order["id"];
            $status = $order["order_status"];

            $itemStmt = $conn->prepare(
                "SELECT item_name, price, quantity, subtotal
                 FROM food_order_items
                 WHERE order_id = ?"
            );

            $itemStmt->bind_param("i", $orderId);
            $itemStmt->execute();
            $itemsResult = $itemStmt->get_result();
            ?>

            <section class="order-card">

                <div class="order-header">

                    <div>
                        <div class="order-id">
                            Order #<?= $orderId ?>
                        </div>

                        <div class="order-date">
                            <?= e(date("d M Y, h:i A", strtotime($order["order_date"]))) ?>
                        </div>
                    </div>

                    <div>
                        <span class="badge text-bg-<?= e(statusColor($status)) ?>">
                            <?= e($status) ?>
                        </span>
                    </div>

                </div>

                <div class="details-grid">

                    <div class="detail-box">
                        <small>Order Type</small>
                        <p><?= e($order["order_type"]) ?></p>
                    </div>

                    <div class="detail-box">
                        <small>Payment Method</small>
                        <p><?= e($order["payment_method"]) ?></p>
                    </div>

                    <div class="detail-box">
                        <small>Payment Status</small>
                        <p><?= e($order["payment_status"]) ?></p>
                    </div>

                    <?php if ($order["order_type"] === "Room Delivery"): ?>
                        <div class="detail-box">
                            <small>Delivery Room</small>
                            <p><?= e($order["delivery_room"] ?: "Not available") ?></p>
                        </div>
                    <?php endif; ?>

                </div>

                <h5 class="items-heading">Ordered Items</h5>

                <?php while ($item = $itemsResult->fetch_assoc()): ?>

                    <div class="item-row">
                        <span>
                            <?= e($item["item_name"]) ?>
                            × <?= (int)$item["quantity"] ?>
                        </span>

                        <strong>
                            ₹<?= number_format((float)$item["subtotal"], 2) ?>
                        </strong>
                    </div>

                <?php endwhile; ?>

                <?php $itemStmt->close(); ?>

                <div class="total-row">
                    <span>Delivery Charge</span>
                    <span>₹<?= number_format((float)$order["delivery_charge"], 2) ?></span>
                </div>

                <div class="total-row grand-total">
                    <span>Total Amount</span>
                    <span>₹<?= number_format((float)$order["total_amount"], 2) ?></span>
                </div>

                <?php if ($status === "Rejected"): ?>

                    <div class="rejected-message">
                        Your order was rejected by the hostel food admin.
                        Please contact the hostel office if you need assistance.
                    </div>

                <?php else: ?>

                    <h6 class="mt-4 mb-2">Order Progress</h6>

                    <div class="progress-track">

                        <?php
                        $steps = [
                            "Order Placed",
                            "Accepted",
                            "Preparing",
                            "Ready",
                            "Delivered"
                        ];

                        $currentStep = $progress[$status] ?? 0;
                        ?>

                        <?php foreach ($steps as $index => $step): ?>
                            <div class="progress-step <?= ($index + 1 <= $currentStep) ? "active" : "" ?>">
                                <div class="circle">
                                    <?= ($index + 1 <= $currentStep) ? "✓" : ($index + 1) ?>
                                </div>
                                <?= e($step) ?>
                            </div>
                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

<footer class="footer">
    © <?= date("Y") ?> Hostelia Hostel Management System
</footer>

</body>
</html>
```