<?php
require_once "admin_check.php";
require_once "db.php";
// CSRF token for student deletion
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Delete student
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_student"])) {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Invalid request.");
    }

    $student_id = (int)$_POST["delete_student"];

    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: admin.php");
        exit;
    } else {
        echo "Student could not be deleted. There may be linked records.";
    }

    $stmt->close();
}

$student_result = $conn->query(
    "SELECT s.*,
        (
            SELECT hr.room_number
            FROM room_allocations ra
            JOIN hostel_rooms hr ON ra.hostel_room_id = hr.id
            WHERE ra.student_id = s.id
            ORDER BY ra.allocated_at DESC
            LIMIT 1
        ) AS allocated_room
     FROM students s
     ORDER BY s.registration_date DESC"
);

$student_count = $conn->query(
    "SELECT COUNT(*) AS total FROM students"
)->fetch_assoc()["total"];

$complaint_count = $conn->query(
    "SELECT COUNT(*) AS total FROM complaints WHERE status != 'Resolved'"
)->fetch_assoc()["total"];

$pending_count = $conn->query(
    "SELECT COUNT(*) AS total FROM complaints WHERE status='Pending'"
)->fetch_assoc()["total"];

$resolved_count = $conn->query(
    "SELECT COUNT(*) AS total FROM complaints WHERE status='Resolved'"
)->fetch_assoc()["total"];

$complaint_result = $conn->query(
    "SELECT * FROM complaints ORDER BY complaint_date DESC"
);
$booking_result = $conn->query(
    "SELECT
        rb.*,
        s.full_name,
        hr.room_number
     FROM room_bookings rb
     JOIN students s ON rb.student_id = s.id
     LEFT JOIN room_allocations ra ON rb.id = ra.booking_id
     LEFT JOIN hostel_rooms hr ON ra.hostel_room_id = hr.id
     ORDER BY rb.booking_date DESC"
);
$cancellation_result = $conn->query(
    "SELECT rb.*, s.full_name, s.student_id AS student_code
     FROM room_bookings rb
     JOIN students s ON rb.student_id = s.id
     WHERE rb.booking_status = 'Cancellation Requested'
     ORDER BY rb.booking_date DESC"
);
// Fetch rent payment records
$payment_result = $conn->query(
    "SELECT
        rp.*,
        s.full_name,
        s.student_id AS student_code,
        rb.room_type
     FROM rent_payments rp
     JOIN students s ON rp.student_id = s.id
     JOIN room_bookings rb ON rp.booking_id = rb.id
     ORDER BY rp.created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background: #f8f4ed;
            color: #493a2e;
            font-family: 'Poppins', sans-serif;
        }

        .admin-hero {
            padding: 48px 0 30px;
        }

        .admin-eyebrow {
            color: #9a704b;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }

        .admin-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 4vw, 46px);
            color: #493526;
            margin-bottom: 12px;
        }

        .admin-hero p {
            color: #76685d;
            font-size: 14px;
        }

        .admin-content {
            padding-bottom: 65px;
        }

        .admin-stat {
            height: 100%;
            background: #fffdf9;
            border: 1px solid #e9dfd1;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 7px 22px rgba(72, 51, 33, .05);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .admin-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(72, 51, 33, .09);
        }

        .admin-stat .stat-label {
            color: #76685d;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .admin-stat h2 {
            color: #493526;
            font-size: 34px;
            font-weight: 600;
            margin: 0;
        }

        .admin-stat .stat-mark {
            width: 38px;
            height: 4px;
            background: #b48a62;
            border-radius: 5px;
            margin-bottom: 18px;
        }

        .admin-panel {
            background: #fffdf9;
            border: 1px solid #e9dfd1;
            border-radius: 14px;
            padding: 26px;
            margin-bottom: 28px;
            box-shadow: 0 7px 22px rgba(72, 51, 33, .05);
        }

        .admin-panel h2 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            color: #493526;
            margin-bottom: 7px;
        }

        .panel-description {
            color: #897b6e;
            font-size: 13px;
            margin-bottom: 23px;
        }

        .admin-table {
            margin-bottom: 0;
            vertical-align: middle;
            font-size: 13px;
            --bs-table-bg: transparent;
            --bs-table-color: #55473b;
        }

        .admin-table thead th {
            background: #f3eadf;
            color: #674b34;
            font-weight: 600;
            white-space: nowrap;
            padding: 15px 12px;
            border-bottom: 1px solid #e4d6c5;
        }

        .admin-table tbody td {
            padding: 14px 12px;
            border-color: #eee5da;
        }

        .admin-table tbody tr:hover td {
            background: #faf5ed;
        }

        .admin-table .form-select {
            min-width: 130px;
            font-size: 12px;
            border-color: #e0d3c3;
            background-color: #fffdf9;
        }

        .admin-form .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #5c4735;
        }

        .admin-form .form-control {
            border: 1px solid #e0d3c3;
            border-radius: 8px;
            background: #fffefa;
            padding: 12px 14px;
            font-size: 13px;
        }

        .admin-form .form-control:focus,
        .admin-table .form-select:focus {
            border-color: #a87950;
            box-shadow: 0 0 0 .2rem rgba(168, 121, 80, .12);
        }

        .admin-btn {
            background: #6f4b31;
            color: #fff;
            border: 1px solid #6f4b31;
            border-radius: 8px;
            padding: 10px 17px;
            font-size: 13px;
            transition: background .2s ease;
        }

        .admin-btn:hover {
            background: #513722;
            color: #fff;
            border-color: #513722;
        }

        .status-badge {
            display: inline-block;
            border-radius: 20px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff0d4;
            color: #875c17;
        }

        .status-progress {
            background: #e5efff;
            color: #315d98;
        }

        .status-resolved {
            background: #e0f3e5;
            color: #28653b;
        }

        .empty-row {
            padding: 25px !important;
            text-align: center;
            color: #897b6e !important;
        }

        @media (max-width: 767px) {
            .admin-panel {
                padding: 19px 15px;
            }

            .admin-stat {
                padding: 20px;
            }

            .admin-stat h2 {
                font-size: 29px;
            }
        }
        .booking-actions {
    display: flex;
    flex-direction: column;
    gap: 9px;
    min-width: 105px;
}

.booking-action-btn {
    border: none;
    border-radius: 7px;
    padding: 9px 14px;
    font-size: 12px;
    font-weight: 600;
    transition: all .25s ease;
    cursor: pointer;
}

.approve-btn {
    background: #6f4b31;
    color: white;
}

.approve-btn:hover {
    background: #493526;
    transform: translateY(-2px);
}

.reject-btn {
    background: #f7e8e3;
    color: #a34432;
    border: 1px solid #e8c9be;
}

.reject-btn:hover {
    background: #a34432;
    color: white;
    transform: translateY(-2px);
}
.quick-link-card {
    display: flex;
    align-items: center;
    gap: 16px;
    height: 100%;
    padding: 20px;
    background: #fffaf3;
    border: 1px solid #e9dfd1;
    border-radius: 12px;
    text-decoration: none;
    transition: all .25s ease;
}

.quick-link-card:hover {
    background: #f3eadf;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(72, 51, 33, .08);
}

.quick-link-icon {
    font-size: 30px;
}

.quick-link-card h5 {
    color: #493526;
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 6px;
}

.quick-link-card p {
    color: #76685d;
    font-size: 12px;
    margin: 0;
}
    </style>
</head>

<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">

            <a href="index.php" class="logo">HOSTELIA</a>

            <nav class="main-nav">
                
                <a href="admin.php" class="active">Admin</a>
                <a href="admin_logout.php">Logout</a>
            </nav>

        </div>
    </div>
</header>

<main>
    <section class="admin-hero">
        <div class="container">
            <p class="admin-eyebrow">HOSTELIA MANAGEMENT</p>
            <h1>Admin Dashboard</h1>
            <p>Manage registered students, track complaints and publish hostel announcements.</p>
        </div>
    </section>

    <section class="container admin-content">

        <!-- Dashboard statistics -->
        <div class="row g-4 mb-5">

            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="stat-mark"></div>
                    <p class="stat-label">Total Students</p>
                    <h2><?= (int)$student_count ?></h2>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="stat-mark"></div>
                    <p class="stat-label">Total Complaints</p>
                    <h2><?= (int)$complaint_count ?></h2>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="stat-mark"></div>
                    <p class="stat-label">Pending Complaints</p>
                    <h2><?= (int)$pending_count ?></h2>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="stat-mark"></div>
                    <p class="stat-label">Resolved Complaints</p>
                    <h2><?= (int)$resolved_count ?></h2>
                </div>
            </div>

        </div>
<!-- Quick Links -->
<section class="admin-panel">
    <h2>Hostel Food Management</h2>
    <p class="panel-description">
        Update the weekly food menu shown to students.
    </p>

    <div class="row g-3">

        <div class="col-12 col-md-6 col-lg-4">
            <a href="manage_food.php" class="quick-link-card">
                <span class="quick-link-icon">📅</span>
                <div>
                    <h5>Weekly Hostel Food Menu</h5>
                    <p>Update the Breakfast, Lunch and Dinner menu for the week.</p>
                </div>
            </a>
        </div>

    </div>
</section>
        <!-- Registered students -->
         
<!-- Room Bookings Management -->
<section class="admin-panel">
    <h2>Room Bookings</h2>
    <p class="panel-description">
        Review student room requests and manage booking approvals.
    </p>

    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Room Type</th>
                    <th>Facilities</th>
                    <th>Total Rent</th>
                    <th>Room Number</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($booking_result && $booking_result->num_rows > 0): ?>
                <?php while ($booking = $booking_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($booking["full_name"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($booking["room_type"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($booking["selected_facilities"] ?? "None") ?>
                        </td>

                        <td>
                            ₹<?= number_format((float)$booking["total_rent"], 2) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($booking["room_number"] ?? "Not Allocated") ?>
                        </td>

                        <td>
                            <span class="status-badge status-pending">
                                <?= htmlspecialchars($booking["booking_status"]) ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($booking["booking_status"] === "Pending"): ?>

                                <form action="approve_booking.php" method="POST" class="mb-2">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="booking_id"
                                           value="<?= (int)$booking["id"] ?>">

                                    <button type="submit" class="booking-action-btn approve-btn">
    ✓ Approve
</button>
                                </form>

                                <form action="reject_booking.php" method="POST"
                                      onsubmit="return confirm('Reject this booking?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="booking_id"
                                           value="<?= (int)$booking["id"] ?>">

                                    <<button type="submit" class="booking-action-btn reject-btn">
    ✕ Reject
</button>
                                </form>

                            <?php else: ?>
                                <span class="text-muted">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="empty-row">
                        No room bookings found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Cancellation Requests -->
<section class="dashboard-section" id="cancellations">
    <h2>Cancellation Requests</h2>
    <p>Review student room cancellation requests.</p>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Booking ID</th>
                    <th>Room Type</th>
                    <th>Total Rent</th>
                    <th>Request Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($cancellation_result && $cancellation_result->num_rows > 0): ?>
                    <?php while ($cancel = $cancellation_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($cancel["full_name"]) ?>
                                <br>
                                <small>
                                    <?= htmlspecialchars($cancel["student_code"]) ?>
                                </small>
                            </td>

                            <td><?= (int)$cancel["id"] ?></td>

                            <td><?= htmlspecialchars($cancel["room_type"]) ?></td>

                            <td>₹<?= number_format($cancel["total_rent"], 2) ?></td>

                            <td><?= htmlspecialchars($cancel["booking_date"]) ?></td>

                            <td>
                                <form action="manage_cancellation.php"
                                      method="POST"
                                      class="d-flex gap-2">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden"
                                           name="booking_id"
                                           value="<?= (int)$cancel["id"] ?>">

                                    <button type="submit"
                                            name="action"
                                            value="approve"
                                            class="btn btn-success btn-sm">
                                        Approve
                                    </button>

                                    <button type="submit"
                                            name="action"
                                            value="reject"
                                            class="btn btn-danger btn-sm">
                                        Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            No cancellation requests found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


        <section class="admin-panel">
            <h2> Registered Students</h2>
            
            <p class="panel-description">Student registration details stored in the database.</p>

            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Preferred Room Type</th>
                            <th>Allocated Room</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($student_result && $student_result->num_rows > 0): ?>
                        <?php while ($student = $student_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$student["id"] ?></td>
                                <td><?= htmlspecialchars($student["full_name"]) ?></td>
                                <td><?= htmlspecialchars($student["student_id"]) ?></td>
                                <td><?= htmlspecialchars($student["email"]) ?></td>
                                <td><?= htmlspecialchars($student["phone"]) ?></td>
                                <td><?= htmlspecialchars($student["gender"]) ?></td>
                                <td><?= htmlspecialchars($student["room_type"]) ?></td>
                                <td>
    <?= !empty($student["allocated_room"])
        ? htmlspecialchars($student["allocated_room"])
        : "Not Booked" ?>
</td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-row">No registered students found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
<!-- Payment Management -->
<section class="dashboard-section" id="payments">
    <h2>Payment Management</h2>
    <p>View and update student rent payments.</p>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Booking ID</th>
                    <th>Room Type</th>
                    <th>Amount Due</th>
                    <th>Amount Paid</th>
                    <th>Remaining</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Update Payment</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($payment_result && $payment_result->num_rows > 0): ?>
                    <?php while ($payment = $payment_result->fetch_assoc()): ?>
                        <?php
                        $remaining = max(
                            0,
                            (float)$payment["amount_due"] -
                            (float)$payment["amount_paid"]
                        );
                        ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($payment["full_name"]) ?>
                                <br>
                                <small>
                                    <?= htmlspecialchars($payment["student_code"]) ?>
                                </small>
                            </td>

                            <td><?= (int)$payment["booking_id"] ?></td>

                            <td>
                                <?= htmlspecialchars($payment["room_type"]) ?>
                            </td>

                            <td>₹<?= number_format($payment["amount_due"], 2) ?></td>

                            <td>₹<?= number_format($payment["amount_paid"], 2) ?></td>

                            <td>₹<?= number_format($remaining, 2) ?></td>

                            <td><?= htmlspecialchars($payment["due_date"]) ?></td>

                            <td>
                                <?= htmlspecialchars($payment["payment_status"]) ?>
                            </td>

                            <td>
                                <?php if ($remaining > 0): ?>
                                    <form action="update_payment.php"
                                          method="POST"
                                          style="min-width:150px;">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="payment_id"
                                               value="<?= (int)$payment["id"] ?>">

                                        <input type="number"
                                               name="amount_received"
                                               min="0.01"
                                               max="<?= htmlspecialchars((string)$remaining) ?>"
                                               step="0.01"
                                               placeholder="Amount"
                                               required
                                               class="form-control mb-2">

                                        <button type="submit"
                                                class="btn btn-success btn-sm">
                                            Update Payment
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span>Fully Paid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">
                            No payment records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


        
<!-- Complaints management -->
<section class="admin-panel">
    <h2>Hostel Complaints</h2>
    <p class="panel-description">Review complaints and update their current status.</p>

    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Room</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Complaint</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>
            <?php
            $has_complaints = false;
            if ($complaint_result):
                while ($complaint = $complaint_result->fetch_assoc()):
                    if ($complaint["status"] === "Resolved") {
                        continue;
                    }

                    $has_complaints = true;
                    $status = $complaint["status"];

                    if ($status === "In Progress") {
                        $status_class = "status-progress";
                    } else {
                        $status_class = "status-pending";
                    }
            ?>
                <tr>
                    <td><?= (int)$complaint["id"] ?></td>
                    <td><?= htmlspecialchars($complaint["student_name"]) ?></td>
                    <td><?= htmlspecialchars($complaint["room_number"]) ?></td>
                    <td><?= htmlspecialchars($complaint["complaint_type"]) ?></td>
                    <td><?= htmlspecialchars($complaint["priority"]) ?></td>
                    <td><?= htmlspecialchars($complaint["complaint_text"]) ?></td>

                    <td>
                        <span class="status-badge <?= $status_class ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </td>

                    <td>
                        <form action="update_complaint.php" method="POST">
                            <input
                                type="hidden"
                                name="complaint_id"
                                value="<?= (int)$complaint["id"] ?>"
                            >

                            <select name="status" class="form-select" required>
                                <option value="Pending"
                                    <?= $status === "Pending" ? "selected" : "" ?>>
                                    Pending
                                </option>

                                <option value="In Progress"
                                    <?= $status === "In Progress" ? "selected" : "" ?>>
                                    In Progress
                                </option>

                                <option value="Resolved">
                                    Resolved
                                </option>
                            </select>

                            <button type="submit" class="btn admin-btn mt-2">
                                Update Status
                            </button>
                        </form>
                    </td>
                </tr>
            <?php
                endwhile;
            endif;

            if (!$has_complaints):
            ?>
                <tr>
                    <td colspan="8" class="empty-row">
                        No active complaints available.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


        <!-- Add a notice -->
        <section class="admin-panel">
            <!-- Manage Notices -->
<section class="admin-panel">
    <h2>Manage Notices</h2>
    <p class="panel-description">
        View and delete existing hostel announcements.
    </p>

    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Posted On</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($notice_result && $notice_result->num_rows > 0): ?>
                <?php while ($notice = $notice_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($notice["title"]) ?>
                        </td>

                        <td>
                            <?= !empty($notice["notice_date"])
                                ? date("d M Y", strtotime($notice["notice_date"]))
                                : "N/A" ?>
                        </td>

                        <td>
                            <form action="delete_notice.php"
                                  method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this notice?');">

                                <input type="hidden"
                                       name="notice_id"
                                       value="<?= (int)$notice["id"] ?>">

                                <button type="submit"
                                        class="btn btn-sm btn-danger">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="empty-row">
                        No notices available to manage.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
            <h2>Publish a Notice</h2>
            <p class="panel-description">Share an announcement with hostel students.</p>

            <form action="add_notice.php" method="POST" class="admin-form">

                <div class="mb-3">
                    <label for="notice-title" class="form-label">Notice Title</label>
                    <input
                        id="notice-title"
                        type="text"
                        name="title"
                        class="form-control"
                        placeholder="Enter notice title"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="notice-details" class="form-label">Notice Details</label>
                    <textarea
                        id="notice-details"
                        name="notice_text"
                        class="form-control"
                        rows="5"
                        placeholder="Write the announcement details here..."
                        required
                    ></textarea>
                </div>

                <button type="submit" class="btn admin-btn">
                    Publish Notice
                </button>

            </form>
        </section>

    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <p>© 2026 Hostelia. Hostel Management System.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>

</body>
</html>

<?php
$conn->close();
?>