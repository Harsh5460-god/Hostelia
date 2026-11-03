<?php
require_once "admin_check.php";
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.php");
    exit;
}

if (
    empty($_POST["csrf_token"]) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    die("Invalid request.");
}

$requestId = (int)($_POST["request_id"] ?? 0);
$action = $_POST["action"] ?? "";

if (!in_array($action, ["approve", "reject"], true)) {
    die("Invalid action.");
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        SELECT * FROM room_change_requests
        WHERE id = ? FOR UPDATE
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$request || $request["request_status"] !== "Pending") {
        throw new Exception("This request is no longer pending.");
    }

    $bookingId = (int)$request["booking_id"];

    $stmt = $conn->prepare("
        SELECT * FROM room_bookings
        WHERE id = ? FOR UPDATE
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking || $booking["booking_status"] !== "Confirmed") {
        throw new Exception("Booking is no longer eligible for a room change.");
    }

    if ($action === "reject") {
        $stmt = $conn->prepare("
            UPDATE room_change_requests
            SET request_status = 'Rejected', processed_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: admin.php");
        exit;
    }

    // Find an available room of the requested type.
    $stmt = $conn->prepare("
        SELECT id, capacity
        FROM hostel_rooms
        WHERE room_type = ? AND is_active = 1
        ORDER BY room_number
    ");
    $stmt->bind_param("s", $request["requested_room_type"]);
    $stmt->execute();
    $rooms = $stmt->get_result();

    $availableRoomId = 0;

    while ($room = $rooms->fetch_assoc()) {
        // Lock the candidate room so simultaneous approvals cannot overbook it.
        $lock = $conn->prepare("
            SELECT id FROM hostel_rooms WHERE id = ? FOR UPDATE
        ");
        $lock->bind_param("i", $room["id"]);
        $lock->execute();
        $lock->close();

        $countStmt = $conn->prepare("
            SELECT COUNT(*) AS occupied
            FROM room_allocations
            WHERE hostel_room_id = ?
        ");
        $countStmt->bind_param("i", $room["id"]);
        $countStmt->execute();
        $occupied = (int)$countStmt->get_result()->fetch_assoc()["occupied"];
        $countStmt->close();

        if ($occupied < (int)$room["capacity"]) {
            $availableRoomId = (int)$room["id"];
            break;
        }
    }
    $stmt->close();

    if (!$availableRoomId) {
        throw new Exception("No available room of the requested type.");
    }

    // Update the existing allocation; do not create a second allocation.
    $allocation = $conn->prepare("
        UPDATE room_allocations
        SET hostel_room_id = ?, allocated_at = NOW()
        WHERE booking_id = ? AND student_id = ?
    ");
    $allocation->bind_param(
        "iii",
        $availableRoomId,
        $bookingId,
        $request["student_id"]
    );
    $allocation->execute();

    if ($allocation->affected_rows < 0) {
        throw new Exception("Room allocation could not be updated.");
    }
    $allocation->close();

    // Update the booking's room type and rent.
    $newBaseRent = (float)$request["new_rent"]
                 - (float)$booking["facility_charge"];

    $newTotalRent = (float)$request["new_rent"];

    $updateBooking = $conn->prepare("
        UPDATE room_bookings
        SET room_type = ?, base_rent = ?, total_rent = ?
        WHERE id = ?
    ");
    $updateBooking->bind_param(
        "sddi",
        $request["requested_room_type"],
        $newBaseRent,
        $newTotalRent,
        $bookingId
    );
    $updateBooking->execute();
    $updateBooking->close();

    // Update the existing payment record and preserve the amount already paid.
    $paymentStmt = $conn->prepare("
        SELECT * FROM rent_payments
        WHERE booking_id = ?
        ORDER BY id DESC LIMIT 1 FOR UPDATE
    ");
    $paymentStmt->bind_param("i", $bookingId);
    $paymentStmt->execute();
    $payment = $paymentStmt->get_result()->fetch_assoc();
    $paymentStmt->close();

    if ($payment) {
        $amountPaid = (float)$payment["amount_paid"];
        $credit = max(0, $amountPaid - $newTotalRent);

        $paymentStatus = "Pending";
        if ($amountPaid >= $newTotalRent) {
            $paymentStatus = "Paid";
        } elseif ($amountPaid > 0) {
            $paymentStatus = "Partial";
        }

        $updatePayment = $conn->prepare("
            UPDATE rent_payments
            SET amount_due = ?, payment_status = ?
            WHERE id = ?
        ");
        $updatePayment->bind_param(
            "dsi",
            $newTotalRent,
            $paymentStatus,
            $payment["id"]
        );
        $updatePayment->execute();
        $updatePayment->close();

        // Store any overpayment as credit for future rent.
        if ($credit > 0) {
            $creditStmt = $conn->prepare("
                INSERT INTO rent_credits
                (booking_id, student_id, credit_amount, remaining_credit)
                VALUES (?, ?, ?, ?)
            ");
            $creditStmt->bind_param(
                "iidd",
                $bookingId,
                $request["student_id"],
                $credit,
                $credit
            );
            $creditStmt->execute();
            $creditStmt->close();
        }
    }

    $updateRequest = $conn->prepare("
        UPDATE room_change_requests
        SET request_status = 'Approved', processed_at = NOW()
        WHERE id = ?
    ");
    $updateRequest->bind_param("i", $requestId);
    $updateRequest->execute();
    $updateRequest->close();

    $conn->commit();

    header("Location: admin.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Room change failed: " . htmlspecialchars($e->getMessage()));
}
?>