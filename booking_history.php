
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit;
}

$studentId = (int) $_SESSION["student_id"];

$stmt = $conn->prepare("
    SELECT
        rb.id,
        rb.room_type,
        rb.selected_facilities,
        rb.base_rent,
        rb.facility_charge,
        rb.total_rent,
        rb.booking_status,
        rb.booking_date,
        COALESCE(SUM(rp.amount_due - rp.amount_paid), 0) AS pending_rent,
        CASE
            WHEN COUNT(rp.id) = 0 THEN 'Not Generated'
            WHEN SUM(rp.amount_due - rp.amount_paid) <= 0 THEN 'Paid'
            WHEN SUM(rp.amount_paid) > 0 THEN 'Partial'
            ELSE 'Pending'
        END AS payment_status
    FROM room_bookings rb
    LEFT JOIN rent_payments rp ON rp.booking_id = rb.id
    WHERE rb.student_id = ?
    GROUP BY rb.id
    ORDER BY rb.booking_date DESC
");

$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking History | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background: #f7f3eb;
            color: #40382f;
            font-family: 'Poppins', sans-serif;
        }

        .history-container {
            max-width: 1000px;
            margin: 45px auto;
        }

        .history-card {
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 22px;
            box-shadow: 0 5px 20px rgba(64, 56, 47, 0.05);
        }

        h2, h4 {
            font-family: 'Playfair Display', serif;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #eee7dc;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #817466;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #eee7dc;
            font-size: 12px;
            font-weight: 600;
        }

        .status-confirmed,
        .status-paid {
            background: #dcebdc;
            color: #315d35;
        }

        .status-pending {
            background: #fff0cf;
            color: #795b18;
        }

        .status-cancelled {
            background: #f4dddd;
            color: #8b3636;
        }

        .status-cancellation-requested {
            background: #fff0cf;
            color: #795b18;
        }

        .status-partial {
            background: #dce8f5;
            color: #315477;
        }

        .status-not-generated {
            background: #eee7dc;
            color: #817466;
        }

        .rent-total {
            font-size: 20px;
            font-weight: 600;
        }

        .cancel-btn {
            background: #9b4540;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
        }

        .cancel-btn:hover {
            background: #7e3430;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 55px 20px;
            background: #fffdf8;
            border-radius: 12px;
        }

        .back-btn {
            background: #40382f;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-btn:hover {
            color: white;
            background: #5d4c39;
        }

        @media (max-width: 576px) {
            .history-card {
                padding: 17px;
            }

            .detail-row {
                font-size: 13px;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>

<header class="site-header">
    <a href="index.php" class="logo home-logo">Hostelia</a>
    <p class="tagline">HOSTEL MANAGEMENT SYSTEM</p>

    <div class="header-auth">
        <a href="student_dashboard.php" class="auth-login">Dashboard</a>
    </div>
</header>

<main class="container history-container">

    <h2 class="mb-2">My Booking History</h2>

    <p class="text-secondary mb-4">
        View your room bookings and rent payment details.
    </p>

    <?php if (isset($_GET["cancellation"]) && $_GET["cancellation"] === "requested"): ?>
        <div class="alert alert-success">
            Your cancellation request has been submitted successfully.
        </div>
    <?php endif; ?>

    <?php if (count($bookings) > 0): ?>

        <?php foreach ($bookings as $booking): ?>

            <?php
                $facilityList = json_decode(
                    $booking["selected_facilities"],
                    true
                );

                if (!is_array($facilityList)) {
                    $facilityList = [];
                }

                $statusClass = strtolower(
                    str_replace(" ", "-", $booking["booking_status"])
                );

                $paymentClass = strtolower(
                    str_replace(" ", "-", $booking["payment_status"])
                );
            ?>

            <div class="history-card">

                <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">

                    <h4>
                        Booking #<?= safe($booking["id"]) ?>
                    </h4>

                    <span class="status status-<?= safe($statusClass) ?>">
                        <?= safe($booking["booking_status"]) ?>
                    </span>

                </div>

                <div class="detail-row">
                    <span class="detail-label">Room Type</span>
                    <strong><?= safe($booking["room_type"]) ?></strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Facilities</span>
                    <strong>
                        <?= count($facilityList) > 0
                            ? safe(implode(", ", $facilityList))
                            : "No extra facilities" ?>
                    </strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Base Rent</span>
                    <strong>₹<?= number_format((float)$booking["base_rent"], 2) ?></strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Facility Charges</span>
                    <strong>₹<?= number_format((float)$booking["facility_charge"], 2) ?></strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Total Monthly Rent</span>
                    <strong class="rent-total">
                        ₹<?= number_format((float)$booking["total_rent"], 2) ?>
                    </strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Booking Date</span>
                    <strong>
                        <?= date("d M Y", strtotime($booking["booking_date"])) ?>
                    </strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Payment Status</span>
                    <span class="status status-<?= safe($paymentClass) ?>">
                        <?= safe($booking["payment_status"]) ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Pending Rent</span>
                    <strong>
                        ₹<?= number_format(max(0, (float)$booking["pending_rent"]), 2) ?>
                    </strong>
                </div>

                <!-- Cancellation Action -->
                <div class="detail-row">
                    <span class="detail-label">Booking Action</span>

                    <?php if ($booking["booking_status"] === "Confirmed"): ?>

                        <form action="request_cancellation.php" method="POST"
                              onsubmit="return confirm('Are you sure you want to request cancellation of this booking?');">

                            <input type="hidden" name="booking_id"
                                   value="<?= (int)$booking["id"] ?>">

                            <button type="submit" class="cancel-btn">
                                Request Cancellation
                            </button>

                        </form>

                    <?php elseif ($booking["booking_status"] === "Cancellation Requested"): ?>

                        <span class="status status-cancellation-requested">
                            Cancellation Requested
                        </span>

                    <?php elseif ($booking["booking_status"] === "Cancelled"): ?>

                        <span class="status status-cancelled">
                            Booking Cancelled
                        </span>

                    <?php else: ?>

                        <span class="text-secondary">
                            Not Available
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-state">
            <h4>No Bookings Yet</h4>
            <p class="text-secondary">
                You haven't made any room bookings yet.
            </p>

            <a href="rooms.php" class="back-btn">
                Explore Rooms
            </a>
        </div>

    <?php endif; ?>

</main>

<footer>
    <div class="footer-inner">
        <div><strong>Hostelia</strong> — Hostel Management System</div>
        <div>© 2026 All Rights Reserved</div>
    </div>
</footer>

</body>
</html>
