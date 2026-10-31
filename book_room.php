<?php
session_start();
require_once "db.php";

// Student must be logged in
if (!isset($_SESSION["student_id"])) {
    $roomParam = urlencode($_GET["room"] ?? "");
    header("Location: login.php?redirect=" . urlencode("book_room.php?room=" . ($roomParam)));
    exit;
}

$studentDbId = (int) $_SESSION["student_id"];
$error = "";
$success = "";
$selectedRoom = $_GET["room"] ?? "";
$selectedFacilities = [];

if (empty($_SESSION["booking_csrf"])) {
    $_SESSION["booking_csrf"] = bin2hex(random_bytes(32));
}

// Fetch logged-in student
$stmt = $conn->prepare(
    "SELECT full_name, student_id, email, phone
     FROM students WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $studentDbId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch room types and base rents
$rooms = [];
$result = $conn->query(
    "SELECT room_type, monthly_rent
     FROM rooms ORDER BY monthly_rent DESC"
);

while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

// Fetch facility prices
$facilities = [];
$result = $conn->query(
    "SELECT facility_name, monthly_charge
     FROM facility_prices ORDER BY id"
);

while ($row = $result->fetch_assoc()) {
    $facilities[] = $row;
}

// Process booking request
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selectedRoom = trim($_POST["room_type"] ?? "");
    $selectedFacilities = $_POST["facility"] ?? [];
    $csrf = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION["booking_csrf"], $csrf)
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } elseif (!is_array($selectedFacilities)) {
        $error = "Invalid facility selection.";
    } else {

        // Verify room type and base rent from database
        $roomStmt = $conn->prepare(
            "SELECT room_type, monthly_rent
             FROM rooms WHERE room_type = ? LIMIT 1"
        );
        $roomStmt->bind_param("s", $selectedRoom);
        $roomStmt->execute();
        $roomData = $roomStmt->get_result()->fetch_assoc();
        $roomStmt->close();

        if (!$roomData) {
            $error = "Please select a valid room type.";
        } else {

            $baseRent = (float) $roomData["monthly_rent"];
            $facilityTotal = 0;
            $validFacilities = [];

            // Validate every selected facility against database prices
            $priceMap = [];

            foreach ($facilities as $facility) {
                $priceMap[$facility["facility_name"]] =
                    (float) $facility["monthly_charge"];
            }

            foreach ($selectedFacilities as $facilityName) {
                if (
                    !is_string($facilityName) ||
                    !array_key_exists($facilityName, $priceMap)
                ) {
                    $error = "Invalid facility selection.";
                    break;
                }

                $validFacilities[] = $facilityName;
                $facilityTotal += $priceMap[$facilityName];
            }

            $totalRent = $baseRent + $facilityTotal;

            if ($error === "") {
                try {
                    $conn->begin_transaction();

                    // A student can have only one active booking
                    $checkStmt = $conn->prepare(
                        "SELECT id FROM room_bookings
                         WHERE student_id = ?
                         AND booking_status IN
                         ('Pending', 'Confirmed', 'Cancellation Requested')
                         LIMIT 1 FOR UPDATE"
                    );

                    $checkStmt->bind_param("i", $studentDbId);
                    $checkStmt->execute();

                    $existingBooking = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();

                    if ($existingBooking) {
                        throw new Exception(
                            "You already have an active booking. " .
                            "Cancel your previous booking and clear any pending rent first."
                        );
                    }

                    // Lock rooms of the selected type while checking capacity
                    $inventoryStmt = $conn->prepare(
                        "SELECT id, room_number, capacity
                         FROM hostel_rooms
                         WHERE room_type = ?
                         AND is_active = 1
                         ORDER BY id
                         FOR UPDATE"
                    );

                    $inventoryStmt->bind_param("s", $selectedRoom);
                    $inventoryStmt->execute();

                    $inventoryResult = $inventoryStmt->get_result();
                    $availableRoom = null;

                    while ($inventoryRoom = $inventoryResult->fetch_assoc()) {

                        $countStmt = $conn->prepare(
                            "SELECT COUNT(*) AS occupied
                             FROM room_allocations
                             WHERE hostel_room_id = ?"
                        );

                        $countStmt->bind_param("i", $inventoryRoom["id"]);
                        $countStmt->execute();

                        $occupied = (int) $countStmt
                            ->get_result()
                            ->fetch_assoc()["occupied"];

                        $countStmt->close();

                        if ($occupied < (int) $inventoryRoom["capacity"]) {
                            $availableRoom = $inventoryRoom;
                            break;
                        }
                    }

                    $inventoryStmt->close();

                    if (!$availableRoom) {
                        throw new Exception(
                            "Sorry, no demo room is currently available for this room type."
                        );
                    }

                    // Save booking request
                    $facilitiesJson = json_encode(
                        $validFacilities,
                        JSON_UNESCAPED_UNICODE
                    );

                    $insertStmt = $conn->prepare(
                        "INSERT INTO room_bookings
                        (student_id, room_type, selected_facilities,
                         base_rent, facility_charge, total_rent, booking_status)
                         VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
                    );

                    $insertStmt->bind_param(
                        "issddd",
                        $studentDbId,
                        $selectedRoom,
                        $facilitiesJson,
                        $baseRent,
                        $facilityTotal,
                        $totalRent
                    );

                    $insertStmt->execute();
                    $bookingId = $conn->insert_id;
                    $insertStmt->close();

                    // Reserve one seat in the selected demo room
                    $allocationStmt = $conn->prepare(
                        "INSERT INTO room_allocations
                         (booking_id, hostel_room_id, student_id)
                         VALUES (?, ?, ?)"
                    );

                    $allocationStmt->bind_param(
                        "iii",
                        $bookingId,
                        $availableRoom["id"],
                        $studentDbId
                    );

                    $allocationStmt->execute();
                    $allocationStmt->close();

                    $conn->commit();

                    $success =
                        "Your booking request has been submitted successfully! " .
                        "Your demo room number is " .
                        $availableRoom["room_number"] .
                        ". Your request is waiting for admin confirmation.";

                    // Prevent accidental resubmission on refresh
                    $_SESSION["booking_csrf"] = bin2hex(random_bytes(32));

                } catch (Throwable $e) {

                    $conn->rollback();

                    if ($e instanceof mysqli_sql_exception) {
                        error_log("Hostelia booking error: " . $e->getMessage());
                        $error = "Unable to submit your booking. Please try again.";
                    } else {
                        $error = $e->getMessage();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book a Room | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background: #f7f3eb;
            font-family: 'Poppins', sans-serif;
            color: #40382f;
        }

        .booking-wrapper {
            max-width: 900px;
            margin: 45px auto;
        }

        .booking-card {
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(64, 56, 47, 0.05);
        }

        .booking-card h3 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
        }

        .form-label {
            color: #75695d;
            font-size: 14px;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            padding: 12px;
            border-color: #d8cfc2;
            background-color: #fffdf8;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #80684e;
            box-shadow: 0 0 0 0.2rem rgba(128, 104, 78, 0.12);
        }

        .facility-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            margin-bottom: 10px;
            border: 1px solid #e2d8c9;
            border-radius: 8px;
            background: #fff;
        }

        .facility-option label {
            cursor: pointer;
            margin-left: 8px;
        }

        .facility-price {
            color: #75695d;
            font-size: 13px;
        }

        .rent-summary {
            background: #f7f3eb;
            border: 1px solid #e2d8c9;
            border-radius: 10px;
            padding: 22px;
        }

        .rent-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .rent-total {
            border-top: 1px solid #d8cfc2;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 21px;
            font-weight: 600;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #40382f;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            background: #5d4c39;
        }

        .student-info {
            background: #f7f3eb;
            padding: 18px;
            border-radius: 8px;
            border: 1px solid #e2d8c9;
        }

        @media (max-width: 576px) {
            .booking-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<header class="site-header">
    <a href="index.php" class="logo home-logo">Hostelia</a>

    <p class="tagline">HOSTEL MANAGEMENT SYSTEM</p>

    <div class="header-auth">
        <a href="student_dashboard.php" class="auth-login">
            Dashboard
        </a>
    </div>
</header>

<main class="container booking-wrapper">

    <div class="mb-4">
        <span class="section-label">ROOM RESERVATION</span>
        <h2 style="font-family: 'Playfair Display', serif;">
            Book Your Room
        </h2>
        <p class="text-secondary">
            Choose your preferred room and facilities.
        </p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
            <div class="mt-3">
                <a href="student_dashboard.php" class="btn btn-dark">
                    Go to Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success === ""): ?>

    <form method="POST" action="book_room.php">

        <input type="hidden"
               name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["booking_csrf"], ENT_QUOTES, "UTF-8") ?>">

        <!-- Student details -->
        <div class="booking-card">

            <h3>Student Details</h3>

            <div class="student-info">
                <div class="row g-3">

                    <div class="col-md-6">
                        <small class="text-secondary">Full Name</small>
                        <div>
                            <strong>
                                <?= htmlspecialchars($student["full_name"]) ?>
                            </strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="text-secondary">Student ID</small>
                        <div>
                            <strong>
                                <?= htmlspecialchars($student["student_id"]) ?>
                            </strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="text-secondary">Email</small>
                        <div>
                            <?= htmlspecialchars($student["email"]) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="text-secondary">Phone</small>
                        <div>
                            <?= htmlspecialchars($student["phone"]) ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Room selection -->
        <div class="booking-card">

            <h3>Select Room Type</h3>

            <label for="room_type" class="form-label">
                Preferred Room Type
            </label>

            <select name="room_type"
                    id="room_type"
                    class="form-select"
                    required>

                <option value="">Select room type</option>

                <?php foreach ($rooms as $room): ?>
                    <option
                        value="<?= htmlspecialchars($room["room_type"]) ?>"
                        data-rent="<?= htmlspecialchars($room["monthly_rent"]) ?>"
                        <?= $selectedRoom === $room["room_type"] ? "selected" : "" ?>>

                        <?= htmlspecialchars($room["room_type"]) ?>
                        — ₹<?= number_format((float)$room["monthly_rent"]) ?>/month

                    </option>
                <?php endforeach; ?>

            </select>

        </div>

        <!-- Facility selection -->
        <div class="booking-card">

            <h3>Select Facilities</h3>

            <p class="text-secondary small">
                Select the facilities you want. Charges will be added to monthly rent.
            </p>

            <?php foreach ($facilities as $facility): ?>

                <div class="facility-option">

                    <div>
                        <input
                            type="checkbox"
                            class="form-check-input facility-checkbox"
                            id="facility_<?= htmlspecialchars(md5($facility["facility_name"])) ?>"
                            name="facility[]"
                            value="<?= htmlspecialchars($facility["facility_name"]) ?>"
                            data-price="<?= htmlspecialchars($facility["monthly_charge"]) ?>">

                        <label for="facility_<?= htmlspecialchars(md5($facility["facility_name"])) ?>">
                            <?= htmlspecialchars($facility["facility_name"]) ?>
                        </label>
                    </div>

                    <span class="facility-price">
                        + ₹<?= number_format((float)$facility["monthly_charge"]) ?>/month
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

        <!-- Rent summary -->
        <div class="booking-card">

            <h3>Monthly Rent Summary</h3>

            <div class="rent-summary">

                <div class="rent-row">
                    <span>Base Room Rent</span>
                    <strong id="baseRent">₹0</strong>
                </div>

                <div class="rent-row">
                    <span>Facility Charges</span>
                    <strong id="facilityRent">₹0</strong>
                </div>

                <div class="rent-row rent-total">
                    <span>Total Monthly Rent</span>
                    <strong id="totalRent">₹0</strong>
                </div>

            </div>

            <p class="small text-secondary mt-3">
                Final rent is calculated and verified using database prices.
            </p>

            <button type="submit" class="submit-btn mt-3">
                SUBMIT BOOKING REQUEST
            </button>

        </div>

    </form>

    <?php endif; ?>

</main>

<footer>
    <div class="footer-inner">
        <div>
            <strong>Hostelia</strong> — Hostel Management System
        </div>
        <div>© 2026 All Rights Reserved</div>
    </div>
</footer>

<script>
    const roomSelect = document.getElementById("room_type");
    const facilityCheckboxes =
        document.querySelectorAll(".facility-checkbox");

    const baseRentElement = document.getElementById("baseRent");
    const facilityRentElement = document.getElementById("facilityRent");
    const totalRentElement = document.getElementById("totalRent");

    function formatRupees(amount) {
        return "₹" + amount.toLocaleString("en-IN");
    }

    function calculateRent() {

        if (!roomSelect) return;

        const selectedOption =
            roomSelect.options[roomSelect.selectedIndex];

        const baseRent = Number(selectedOption.dataset.rent || 0);

        let facilityTotal = 0;

        facilityCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                facilityTotal += Number(checkbox.dataset.price || 0);
            }
        });

        const total = baseRent + facilityTotal;

        baseRentElement.textContent = formatRupees(baseRent);
        facilityRentElement.textContent = formatRupees(facilityTotal);
        totalRentElement.textContent = formatRupees(total);
    }

    if (roomSelect) {
        roomSelect.addEventListener("change", calculateRent);

        facilityCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener("change", calculateRent);
        });

        calculateRent();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>