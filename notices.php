<?php

include "db.php";

$notice_result = $conn->query(
    "SELECT * FROM notices ORDER BY notice_date DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Notices | Hostelia</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet"
          href="css/style.css">

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
                <a href="notices.php">Notices</a>
                <a href="admin.php">Admin</a>

            </nav>

        </div>

    </div>

</header>


<section class="page-section">

    <div class="container">

        <div class="section-heading">

            <p class="eyebrow">
                HOSTELIA UPDATES
            </p>

            <h1>Notices</h1>

            <p>
                Stay updated with the latest hostel announcements.
            </p>

        </div>


        <?php if ($notice_result->num_rows > 0) { ?>

            <?php while ($notice = $notice_result->fetch_assoc()) { ?>

                <div class="content-box mb-4">

                    <h2>
                        <?php echo htmlspecialchars($notice["title"]); ?>
                    </h2>

                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars($notice["notice_text"])
                        );
                        ?>
                    </p>

                    <small>
                        Posted on:
                        <?php
                        echo date(
                            "d M Y, h:i A",
                            strtotime($notice["notice_date"])
                        );
                        ?>
                    </small>

                </div>

            <?php } ?>

        <?php } else { ?>

            <div class="content-box">

                <h2>No Notices Available</h2>

                <p>
                    There are currently no hostel notices.
                </p>

            </div>

        <?php } ?>

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