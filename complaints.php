
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION["student_id"]);
$studentName = $_SESSION["student_name"] ?? "Student";
$initial = strtoupper(substr($studentName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints | Hostelia</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="site-header">

        <div class="logo">
            Hostelia
        </div>

        <p class="tagline">
            HOSTEL MANAGEMENT SYSTEM
        </p>
        <div class="header-auth">
    <?php if ($isLoggedIn): ?>

        <details class="profile-menu">
            <summary class="profile-trigger">
                <span class="profile-avatar">
                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="profile-name">
                    <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="profile-arrow">▾</span>
            </summary>

            <div class="profile-dropdown">
                <a href="student_dashboard.php">My Dashboard</a>
                <a href="settings.php">Settings</a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="logout-option">Logout</a>
            </div>
        </details>

    <?php else: ?>

        <a href="registration.php" class="auth-register">Register</a>
        <a href="login.php" class="auth-login">Student Login</a>

    <?php endif; ?>
</div>

        <nav class="main-nav">

            <a href="index.php" >HOME</a>
            <a href="about.php">ABOUT</a>
            <a href="rooms.php">ROOMS</a>
            <a href="complaints.PHP" class="active">COMPLAINTS</a>
            <a href="notices.php">NOTICES</a>
            

        </nav>

    </header>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="container main-content">

        <div class="content-area w-100">

            <span class="section-label">
                SUPPORT & ISSUES
            </span>

            <h2>
                Submit a Complaint
            </h2>

            <p class="intro-text">
                Report maintenance issues, facility concerns, or
                general grievances to help us resolve them promptly.
            </p>


            <!-- ================= COMPLAINT FORM ================= -->

            <form action="complaint.php"
                  method="POST"
                  class="bg-white p-4 border rounded shadow-sm">

                <div class="row g-3">


                    <!-- Student ID -->

                    <div class="col-md-6">

                        <label for="studentId"
                               class="form-label text-secondary small fw-medium">

                            Student ID

                        </label>

                        <input type="text"
                               class="form-control"
                               id="studentId"
                               name="student_id"
                               placeholder="e.g. STU001"
                               required>

                    </div>


                    <!-- Student Name -->

                    <div class="col-md-6">

                        <label for="studentName"
                               class="form-label text-secondary small fw-medium">

                            Student Name

                        </label>

                        <input type="text"
                               class="form-control"
                               id="studentName"
                               name="student_name"
                               placeholder="Enter your name"
                               required>

                    </div>


                    <!-- Room Number -->

                    <div class="col-md-6">

                        <label for="roomNumber"
                               class="form-label text-secondary small fw-medium">

                            Room Number

                        </label>

                        <input type="text"
                               class="form-control"
                               id="roomNumber"
                               name="room_number"
                               placeholder="e.g. A-204"
                               required>

                    </div>


                    <!-- Category -->

                    <div class="col-md-6">

                        <label for="category"
                               class="form-label text-secondary small fw-medium">

                            Issue Category

                        </label>

                        <select class="form-select"
                                id="category"
                                name="complaint_type"
                                required>

                            <option value="" selected disabled>
                                Select category
                            </option>

                            <option value="Plumbing">
                                Plumbing
                            </option>

                            <option value="Electrical">
                                Electrical
                            </option>

                            <option value="Furniture / Carpentry">
                                Furniture / Carpentry
                            </option>

                            <option value="Wi-Fi / Network">
                                Wi-Fi / Network
                            </option>

                            <option value="Cleanliness & Hygiene">
                                Cleanliness & Hygiene
                            </option>

                            <option value="Other">
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- Priority -->

                    <div class="col-md-6">

                        <label for="priority"
                               class="form-label text-secondary small fw-medium">

                            Priority Level

                        </label>

                        <select class="form-select"
                                id="priority"
                                name="priority"
                                required>

                            <option value="Low">
                                Low
                            </option>

                            <option value="Medium" selected>
                                Medium
                            </option>

                            <option value="High">
                                High (Urgent)
                            </option>

                        </select>

                    </div>


                    <!-- Complaint Details -->

                    <div class="col-12">

                        <label for="description"
                               class="form-label text-secondary small fw-medium">

                            Complaint Details

                        </label>

                        <textarea class="form-control"
                                  id="description"
                                  name="complaint_text"
                                  rows="4"
                                  placeholder="Describe the issue in detail..."
                                  required></textarea>

                    </div>


                    <!-- Submit -->

                    <div class="col-12 mt-4">

                        <button type="submit"
                                class="hero-button text-dark border-dark w-100 fw-semibold py-2">

                            SUBMIT COMPLAINT

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </main>


    <!-- ================= FOOTER ================= -->

    <footer>

        <div class="footer-inner">

            <div>

                <strong>
                    Hostelia
                </strong>

                <span>
                    — Hostel Management System
                </span>

            </div>

            <div>
                © 2026 All Rights Reserved
            </div>

        </div>

    </footer>


    <!-- jQuery -->

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js">
    </script>

    <!-- Bootstrap JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- Custom JS -->

    <script src="js/script.js">
    </script>

</body>

</html>