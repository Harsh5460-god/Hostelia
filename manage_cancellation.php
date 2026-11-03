
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

$booking_id = (int)($_POST["booking_id"] ?? 0);
$action = $_POST["action"] ?? "";

if ($booking_id <= 0 || !in_array($action, ["approve", "reject"], true)) {
    die("Invalid request details.");
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "SELECT id, student_id
         FROM room_bookings
         WHERE id = ?
           AND booking_status = 'Cancellation Requested'
         FOR UPDATE"
    );

    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        throw new Exception("Cancellation request not found.");
    }

    if ($action === "approve") {
        // Release allocated room
        $stmt = $conn->prepare(
            "DELETE FROM room_allocations
             WHERE booking_id = ? AND student_id = ?"
        );
        $stmt->bind_param(
            "ii",
            $booking_id,
            $booking["student_id"]
        );
        $stmt->execute();
        $stmt->close();

        $new_status = "Cancelled";
    } else {
        $new_status = "Confirmed";
    }

    $stmt = $conn->prepare(
        "UPDATE room_bookings
         SET booking_status = ?,
             cancellation_date = CASE
                 WHEN ? = 'Cancelled' THEN NOW()
                 ELSE NULL
             END
         WHERE id = ?
           AND booking_status = 'Cancellation Requested'"
    );

    $stmt->bind_param(
        "ssi",
        $new_status,
        $new_status,
        $booking_id
    );
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Could not update booking.");
    }

    $stmt->close();

    $conn->commit();

    header("Location: admin.php?cancellation=updated");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    die("Cancellation update failed: " .
        htmlspecialchars($e->getMessage()));
}
