<?php
session_start();
include "db.php";

$student_result = $conn->query(
    "SELECT * FROM students ORDER BY registration_date DESC"
);

$student_count = $conn->query(
    "SELECT COUNT(*) AS total FROM students"
)->fetch_assoc()["total"];

$complaint_count = $conn->query(
    "SELECT COUNT(*) AS total FROM complaints"
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
$notice_result = $conn->query(
    "SELECT * FROM notices ORDER BY notice_date DESC"
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
    </style>
</head>

<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">

            <a href="index.php" class="logo">HOSTELIA</a>

            <nav class="main-nav">
                <a href="index.php">Home</a>
                <a href="rooms.php">Rooms</a>
                <a href="complaints.php">Complaints</a>
                <a href="notices.php">Notices</a>
                <a href="admin.php" class="active">Admin</a>
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

        <!-- Registered students -->
        <section class="admin-panel">
            <h2>Registered Students</h2>
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
                            <th>Room Type</th>
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
                    <?php if ($complaint_result && $complaint_result->num_rows > 0): ?>
                        <?php while ($complaint = $complaint_result->fetch_assoc()): ?>
                            <?php
                                $status = $complaint["status"];

                                if ($status === "Resolved") {
                                    $status_class = "status-resolved";
                                } elseif ($status === "In Progress") {
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

                                            <option value="Resolved"
                                                <?= $status === "Resolved" ? "selected" : "" ?>>
                                                Resolved
                                            </option>
                                        </select>

                                        <button type="submit" class="btn admin-btn mt-2">
                                            Update Status
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-row">No complaints have been submitted yet.</td>
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