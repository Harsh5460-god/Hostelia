<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: student_dashboard.php");
    exit;
}

if (
    empty($_POST["csrf_token"]) ||
    empty($_SESSION["room_change_csrf"]) ||
    !hash_equals($_SESSION["room_change_csrf"], $_POST["csrf_token"])
) {
    die("Invalid request.");
}

$studentId = (int)$_SESSION["student_id"];
$bookingId = (int)($_POST["booking_id"] ?? 0);
$requestedType = trim($_POST["requested_room_type"] ?? "");

$allowed = [
    "Single Sharing" => ["Double Sharing", "Triple Sharing", "Quad Sharing"],
    "Double Sharing" => ["Single Sharing", "Triple Sharing", "Quad Sharing"],
    "Triple Sharing" => ["Single Sharing", "Double Sharing", "Quad Sharing"],
    "Quad Sharing" => ["Single Sharing", "Double Sharing", "Triple Sharing"]
];

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        SELECT * FROM room_bookings
        WHERE id = ? AND student_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $bookingId, $studentId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking || $booking["booking_status"] !== "Confirmed") {
        throw new Exception("Only confirmed bookings can be changed.");
    }

    $currentType = $booking["room_type"];

    if (
        !isset($allowed[$currentType]) ||
        !in_array($requestedType, $allowed[$currentType], true)
    ) {
        throw new Exception("Invalid room type selection.");
    }

    $check = $conn->prepare("
        SELECT id FROM room_change_requests
        WHERE booking_id = ? AND request_status = 'Pending'
        LIMIT 1
    ");
    $check->bind_param("i", $bookingId);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        throw new Exception("You already have a pending room change request.");
    }
    $check->close();

    $rentStmt = $conn->prepare("
        SELECT monthly_rent FROM rooms WHERE room_type = ?
    ");
    $rentStmt->bind_param("s", $requestedType);
    $rentStmt->execute();
    $rentRow = $rentStmt->get_result()->fetch_assoc();
    $rentStmt->close();

    if (!$rentRow) {
        throw new Exception("Selected room type is unavailable.");
    }

    $oldRent = (float)$booking["total_rent"];
    $newRent = (float)$rentRow["monthly_rent"]
             + (float)$booking["facility_charge"];

    $insert = $conn->prepare("
        INSERT INTO room_change_requests
        (booking_id, student_id, current_room_type,
         requested_room_type, old_rent, new_rent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insert->bind_param(
        "iissdd",
        $bookingId,
        $studentId,
        $currentType,
        $requestedType,
        $oldRent,
        $newRent
    );

    $insert->execute();
    $insert->close();

    $conn->commit();

    header("Location: student_dashboard.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Room change request failed: " . htmlspecialchars($e->getMessage()));
}
?>