```php
<?php
require_once "admin_check.php";
require_once "db.php";

if (empty($_SESSION["food_admin_csrf"])) {
    $_SESSION["food_admin_csrf"] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION["food_admin_csrf"];
$message = "";
$error = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Update order or payment status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($csrf, $_POST["csrf_token"])
    ) {
        die("Invalid request. Please refresh the page.");
    }

    $action = $_POST["action"] ?? "";
    $orderId = (int)($_POST["order_id"] ?? 0);

    if ($orderId <= 0) {
        $error = "Invalid order.";
    } elseif ($action === "update_status") {

        $newStatus = $_POST["order_status"] ?? "";

        $allowedStatuses = [
            "Accepted",
            "Preparing",
            "Ready",
            "Delivered",
            "Rejected"
        ];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            $error = "Invalid order status.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE food_orders
SET
    order_status = ?,
    delivered_at = CASE
        WHEN ? = 'Delivered' THEN COALESCE(delivered_at, NOW())
        ELSE NULL
    END
WHERE id = ?"
            );

           $stmt->bind_param("ssi", $newStatus, $newStatus, $orderId);
            $stmt->execute();

            if ($stmt->affected_rows >= 0) {
                $message = "Order status updated successfully.";
            } else {
                $error = "Could not update order status.";
            }

            $stmt->close();
        }

    } elseif ($action === "mark_paid") {

        $stmt = $conn->prepare(
            "UPDATE food_orders
             SET payment_status = 'Paid'
             WHERE id = ?
             AND order_status != 'Rejected'"
        );

        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            $message = "Payment marked as Paid.";
        } else {
            $error = "Could not update payment status.";
        }

        $stmt->close();

    } else {
        $error = "Invalid action.";
    }
}

// Fetch all orders with student details
$sql = "
    SELECT
        fo.id,
        fo.student_id,
        fo.total_amount,
        fo.delivery_charge,
        fo.payment_method,
        fo.payment_status,
        fo.order_status,
        fo.order_type,
        fo.delivery_room,
        fo.order_date,
        s.full_name,
        s.student_id AS student_code
    FROM food_orders fo
    INNER JOIN students s ON fo.student_id = s.id
    ORDER BY fo.order_date DESC
";

$result = $conn->query($sql);

$orders = [];

while ($order = $result->fetch_assoc()) {
    $orders[] = $order;
}

// Fetch items for each order
$itemsStmt = $conn->prepare(
    "SELECT item_name, price, quantity, subtotal
     FROM food_order_items
     WHERE order_id = ?"
);

function statusColor($status) {
    switch ($status) {
        case "Delivered": return "success";
        case "Rejected": return "danger";
        case "Ready": return "primary";
        case "Preparing": return "warning";
        case "Accepted": return "info";
        default: return "secondary";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Food Orders | Hostelia Admin</title>

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
    padding: 17px 7%;
    border-bottom: 1px solid #e7ded1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.brand {
    font-family: 'Playfair Display', serif;
    font-size: 27px;
    font-weight: 700;
    color: #40382f;
    text-decoration: none;
}

.brand span { color: #a47c50; }

.nav-links {
    display: flex;
    gap: 18px;
}

.nav-links a {
    text-decoration: none;
    color: #625548;
    font-size: 13px;
}

.container-custom {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 18px;
}

.page-title {
    font-family: 'Playfair Display', serif;
    margin-bottom: 7px;
}

.subtitle {
    color: #928477;
    font-size: 13px;
    margin-bottom: 25px;
}

.order-card {
    background: #fffdf8;
    border: 1px solid #e7ded1;
    border-radius: 12px;
    padding: 23px;
    margin-bottom: 20px;
    box-shadow: 0 5px 16px rgba(64,56,47,0.04);
}

.order-header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    border-bottom: 1px solid #eee5d9;
    padding-bottom: 14px;
}

.order-id {
    font-size: 16px;
    font-weight: 600;
}

.order-date {
    color: #928477;
    font-size: 11px;
    margin-top: 5px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin: 18px 0;
}

.detail-box {
    background: #f7f3eb;
    border-radius: 8px;
    padding: 13px;
}

.detail-box small {
    color: #928477;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.detail-box p {
    font-size: 12px;
    font-weight: 600;
    margin: 5px 0 0;
    overflow-wrap: anywhere;
}

.items-title {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    margin: 18px 0 10px;
}

.item-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f0e9df;
    font-size: 12px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 13px;
    font-weight: 600;
}

.actions {
    display: flex;
    align-items: end;
    flex-wrap: wrap;
    gap: 12px;
    border-top: 1px solid #eee5d9;
    padding-top: 18px;
    margin-top: 18px;
}

.actions form {
    margin: 0;
}

.actions label {
    display: block;
    font-size: 11px;
    margin-bottom: 5px;
}

.actions select {
    min-width: 150px;
    border: 1px solid #ddd0bf;
    border-radius: 5px;
    padding: 9px;
    font-size: 12px;
}

.btn-brown {
    background: #40382f;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 15px;
    font-size: 12px;
}

.btn-brown:hover {
    background: #70583e;
    color: white;
}

.btn-paid {
    background: #56734e;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 15px;
    font-size: 12px;
}

.btn-paid:hover {
    background: #3e5938;
}

.footer {
    text-align: center;
    padding: 25px;
    color: #928477;
    font-size: 12px;
}

@media(max-width: 800px) {
    .details-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 500px) {
    .details-grid {
        grid-template-columns: 1fr 1fr;
    }

    .order-card {
        padding: 16px;
    }
}
</style>
</head>

<body>

<nav class="navbar-custom">
    <a href="admin.php" class="brand">Hostelia<span>.</span></a>

    <div class="nav-links">
        <a href="admin.php">Dashboard</a>
        <a href="manage_food_items.php">Food Menu</a>
        <a href="admin_logout.php">Logout</a>
    </div>
</nav>

<main class="container-custom">

    <h1 class="page-title">Manage Food Orders</h1>

    <p class="subtitle">
        View student orders, update preparation status and manage payments.
    </p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>

        <div class="order-card text-center py-5">
            <div style="font-size:45px">🍽️</div>
            <h4 class="mt-3">No Food Orders Yet</h4>
            <p class="text-secondary small">
                Student food orders will appear here.
            </p>
        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <?php
            $orderId = (int)$order["id"];

            $itemsStmt->bind_param("i", $orderId);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();
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

                    <span class="badge text-bg-<?= e(statusColor($order["order_status"])) ?>">
                        <?= e($order["order_status"]) ?>
                    </span>

                </div>

                <div class="details-grid">

                    <div class="detail-box">
                        <small>Student</small>
                        <p><?= e($order["full_name"]) ?></p>
                    </div>

                    <div class="detail-box">
                        <small>Student ID</small>
                        <p><?= e($order["student_code"]) ?></p>
                    </div>

                    <div class="detail-box">
                        <small>Order Type</small>
                        <p><?= e($order["order_type"]) ?></p>
                    </div>

                    <div class="detail-box">
                        <small>Payment Method</small>
                        <p><?= e($order["payment_method"]) ?></p>
                    </div>

                    <?php if ($order["order_type"] === "Room Delivery"): ?>
                        <div class="detail-box">
                            <small>Delivery Room</small>
                            <p><?= e($order["delivery_room"] ?: "Not available") ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="detail-box">
                        <small>Payment Status</small>
                        <p><?= e($order["payment_status"]) ?></p>
                    </div>

                </div>

                <h5 class="items-title">Ordered Items</h5>

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

                <div class="total-row">
                    <span>Delivery Charge</span>
                    <span>₹<?= number_format((float)$order["delivery_charge"], 2) ?></span>
                </div>

                <div class="total-row">
                    <span>Total Amount</span>
                    <span>₹<?= number_format((float)$order["total_amount"], 2) ?></span>
                </div>

                <div class="actions">

                    <?php if (!in_array($order["order_status"], ["Delivered", "Rejected"], true)): ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?= $orderId ?>">

                            <label>Update Order Status</label>

                            <select name="order_status" required>
                                <?php
                                $statuses = [
                                    "Accepted",
                                    "Preparing",
                                    "Ready",
                                    "Delivered",
                                    "Rejected"
                                ];
                                ?>

                                <?php foreach ($statuses as $status): ?>
                                    <option
                                        value="<?= e($status) ?>"
                                        <?= $order["order_status"] === $status ? "selected" : "" ?>
                                    >
                                        <?= e($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn-brown">
                                Update Status
                            </button>
                        </form>

                    <?php endif; ?>

                    <?php if ($order["payment_status"] !== "Paid" &&
                              $order["order_status"] !== "Rejected"): ?>

                        <form method="POST"
                              onsubmit="return confirm('Confirm that payment has actually been received?');">

                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="mark_paid">
                            <input type="hidden" name="order_id" value="<?= $orderId ?>">

                            <label>Payment</label>

                            <button type="submit" class="btn-paid">
                                Mark as Paid
                            </button>
                        </form>

                    <?php else: ?>

                        <div>
                            <label>Payment</label>
                            <span class="badge text-bg-success">
                                <?= e($order["payment_status"]) ?>
                            </span>
                        </div>

                    <?php endif; ?>

                </div>

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