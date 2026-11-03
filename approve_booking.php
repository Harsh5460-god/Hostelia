
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

if ($booking_id <= 0) {
    die("Invalid booking ID.");
}

$conn->begin_transaction();

try {
    // Lock booking
    $stmt = $conn->prepare(
        "SELECT id, student_id, total_rent, booking_status
         FROM room_bookings
         WHERE id = ?
         FOR UPDATE"
    );
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        throw new Exception("Booking not found.");
    }

    // Only Pending bookings can be approved
    if ($booking["booking_status"] !== "Pending") {
        throw new Exception("Booking is not pending.");
    }

    // Check existing room allocation
    $stmt = $conn->prepare(
        "SELECT id FROM room_allocations
         WHERE booking_id = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $allocation = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Allocate a room only if one is not already allocated
    if (!$allocation) {
        $stmt = $conn->prepare(
            "SELECT hr.id
             FROM hostel_rooms hr
             WHERE hr.room_type = ?
               AND hr.is_active = 1
               AND (
                   SELECT COUNT(*)
                   FROM room_allocations ra
                   WHERE ra.hostel_room_id = hr.id
               ) < hr.capacity
             ORDER BY hr.id
             LIMIT 1
             FOR UPDATE"
        );

        $room_type_stmt = $conn->prepare(
            "SELECT room_type FROM room_bookings WHERE id = ?"
        );
        $room_type_stmt->bind_param("i", $booking_id);
        $room_type_stmt->execute();
        $room_data = $room_type_stmt->get_result()->fetch_assoc();
        $room_type_stmt->close();

        if (!$room_data) {
            throw new Exception("Room type not found.");
        }

        $stmt->bind_param("s", $room_data["room_type"]);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$room) {
            throw new Exception("No available room of this type.");
        }

        $stmt = $conn->prepare(
            "INSERT INTO room_allocations
             (booking_id, hostel_room_id, student_id)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param(
            "iii",
            $booking_id,
            $room["id"],
            $booking["student_id"]
        );
        $stmt->execute();
        $stmt->close();
    }

    // Confirm booking
    $stmt = $conn->prepare(
        "UPDATE room_bookings
         SET booking_status = 'Confirmed'
         WHERE id = ? AND booking_status = 'Pending'"
    );
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Could not confirm booking.");
    }
    $stmt->close();

    // Create payment only if it does not already exist
    $stmt = $conn->prepare(
        "SELECT id FROM rent_payments
         WHERE booking_id = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        $amount = (float)$booking["total_rent"];

        $stmt = $conn->prepare(
            "INSERT INTO rent_payments
             (booking_id, student_id, amount_due, amount_paid,
              due_date, payment_status)
             VALUES (?, ?, ?, 0.00,
                     DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Pending')"
        );

        $stmt->bind_param(
            "iid",
            $booking_id,
            $booking["student_id"],
            $amount
        );

        if (!$stmt->execute()) {
            throw new Exception("Payment insert failed: " . $stmt->error);
        }

        $stmt->close();
    }

    $conn->commit();

    header("Location: admin.php?booking=approved");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    die("Booking approval failed: " .
        htmlspecialchars($e->getMessage()));
}
