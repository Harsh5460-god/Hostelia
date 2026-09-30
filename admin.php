<?php

include "db.php";

$student_result = $conn->query("SELECT * FROM students ORDER BY registration_date DESC");

$student_count = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()["total"];

$complaint_count = $conn->query("SELECT COUNT(*) AS total FROM complaints")->fetch_assoc()["total"];

$pending_count = $conn->query("SELECT COUNT(*) AS total FROM complaints WHERE status='Pending'")->fetch_assoc()["total"];

$resolved_count = $conn->query("SELECT COUNT(*) AS total FROM complaints WHERE status='Resolved'")->fetch_assoc()["total"];

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

</head>

<body>

<header class="site-header">

    <div class="container">

        <div class="header-inner">

            <a href="index.html" class="logo">
                HOSTELIA
            </a>

            <nav class="main-nav">

                <a href="index.html">Home</a>
                <a href="rooms.html">Rooms</a>
                <a href="registration.html">Registration</a>
                <a href="complaints.html">Complaints</a>
                <a href="notices.html">Notices</a>
                <a href="admin.php">Admin</a>

            </nav>

        </div>

    </div>

</header>


<section class="page-section">

    <div class="container">

        <div class="section-heading">

            <p class="eyebrow">HOSTELIA MANAGEMENT</p>

            <h1>Admin Dashboard</h1>

            <p>
                Manage students and hostel complaints from one place.
            </p>

        </div>


        <!-- Statistics -->

        <div class="row g-4 mb-5">

            <div class="col-md-3">

                <div class="info-box">

                    <h3><?php echo $student_count; ?></h3>

                    <p>Total Students</p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="info-box">

                    <h3><?php echo $complaint_count; ?></h3>

                    <p>Total Complaints</p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="info-box">

                    <h3><?php echo $pending_count; ?></h3>

                    <p>Pending Complaints</p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="info-box">

                    <h3><?php echo $resolved_count; ?></h3>

                    <p>Resolved Complaints</p>

                </div>

            </div>

        </div>


        <!-- Registered Students -->

        <div class="content-box mb-5">

            <h2>Registered Students</h2>

            <div class="table-responsive">

                <table class="table">

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

                    <?php while ($student = $student_result->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo $student["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["full_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["student_id"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["email"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["phone"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["gender"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["room_type"]); ?>
                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- Complaints Management -->

        <div class="content-box mb-5">

            <h2>Hostel Complaints</h2>

            <div class="table-responsive">

                <table class="table">

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

                    $complaint_result = $conn->query(
                        "SELECT * FROM complaints ORDER BY complaint_date DESC"
                    );

                    while ($complaint = $complaint_result->fetch_assoc()) {

                    ?>

                        <tr>

                            <td>
                                <?php echo $complaint["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($complaint["student_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($complaint["room_number"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($complaint["complaint_type"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($complaint["priority"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($complaint["complaint_text"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($complaint["status"]); ?>
                            </td>

                            <td>

                                <form action="update_complaint.php" method="POST">

                                    <input
                                        type="hidden"
                                        name="complaint_id"
                                        value="<?php echo $complaint["id"]; ?>"
                                    >

                                    <select name="status" class="form-select">

                                        <option value="Pending"
                                            <?php
                                            if ($complaint["status"] == "Pending")
                                                echo "selected";
                                            ?>>
                                            Pending
                                        </option>

                                        <option value="In Progress"
                                            <?php
                                            if ($complaint["status"] == "In Progress")
                                                echo "selected";
                                            ?>>
                                            In Progress
                                        </option>

                                        <option value="Resolved"
                                            <?php
                                            if ($complaint["status"] == "Resolved")
                                                echo "selected";
                                            ?>>
                                            Resolved
                                        </option>

                                    </select>

                                    <button
                                        type="submit"
                                        class="btn btn-dark mt-2">
                                        Update
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div class="content-box mb-5">

    <h2>Add New Notice</h2>

    <form action="add_notice.php" method="POST">

        <div class="mb-3">

            <label class="form-label">
                Notice Title
            </label>

            <input
                type="text"
                name="title"
                class="form-control"
                placeholder="Enter notice title"
                required
            >

        </div>

        <div class="mb-3">

            <label class="form-label">
                Notice Details
            </label>

            <textarea
                name="notice_text"
                class="form-control"
                rows="5"
                placeholder="Enter notice details"
                required
            ></textarea>

        </div>

        <button type="submit" class="btn btn-dark">
            Publish Notice
        </button>

    </form>

</div>

</section>


<footer class="site-footer">

    <div class="container">

        <p>
            © 2026 Hostelia. Hostel Management System.
        </p>

    </div>

</footer>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="js/script.js"></script>

</body>

</html>


<?php

$conn->close();

?>