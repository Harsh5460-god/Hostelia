
<?php
require_once "admin_check.php";
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.php");
    exit;
}

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    die("Invalid request.");
}

$payment_id = (int)($_POST["payment_id"] ?? 0);
$amount_received = filter_var(
    $_POST["amount_received"] ?? null,
    FILTER_VALIDATE_FLOAT
);

if (
    $payment_id <= 0 ||
    $amount_received === false ||
    $amount_received <= 0
) {
    die("Invalid payment details.");
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "SELECT amount_due, amount_paid
         FROM rent_payments
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        throw new Exception("Payment record not found.");
    }

    $new_paid = (float)$payment["amount_paid"] + $amount_received;
    $amount_due = (float)$payment["amount_due"];

    if ($amount_received > ($amount_due - (float)$payment["amount_paid"])) {
        throw new Exception("Received amount exceeds remaining due.");
    }

    $new_status = ($new_paid >= $amount_due)
        ? "Paid"
        : "Partial";

    $stmt = $conn->prepare(
        "UPDATE rent_payments
         SET amount_paid = ?,
             payment_status = ?,
             payment_date = CURDATE()
         WHERE id = ?"
    );

    $stmt->bind_param(
        "dsi",
        $new_paid,
        $new_status,
        $payment_id
    );

    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header("Location: admin.php?payment=updated");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    die("Payment update failed: " .
        htmlspecialchars($e->getMessage()));
}
