
<?php
require_once "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: booking_history.php");
    exit;
}

$student_id = (int)$_SESSION["student_id"];
$booking_id = (int)($_POST["booking_id"] ?? 0);

if ($booking_id <= 0) {
    die("Invalid booking ID.");
}

$stmt = $conn->prepare(
    "UPDATE room_bookings
     SET booking_status = 'Cancellation Requested'
     WHERE id = ?
       AND student_id = ?
       AND booking_status = 'Confirmed'"
);

$stmt->bind_param("ii", $booking_id, $student_id);
$stmt->execute();

if ($stmt->affected_rows !== 1) {
    $stmt->close();
    die("Cancellation request could not be submitted.");
}

$stmt->close();

header("Location: booking_history.php?cancellation=requested");
exit;
