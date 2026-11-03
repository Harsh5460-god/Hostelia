
<?php
require_once "admin_check.php";
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.php");
    exit;
}

if (
    !isset($_POST["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    die("Invalid request.");
}

$booking_id = (int)($_POST["booking_id"] ?? 0);

if ($booking_id <= 0) {
    die("Invalid booking ID.");
}

$stmt = $conn->prepare(
    "UPDATE room_bookings
     SET booking_status = 'Cancelled'
     WHERE id = ? AND booking_status = 'Pending'"
);

$stmt->bind_param("i", $booking_id);
$stmt->execute();

$stmt->close();

header("Location: admin.php?booking=rejected");
exit;
