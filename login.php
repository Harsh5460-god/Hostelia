
<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
        $error = "Please enter a valid email and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, student_id, email, password
             FROM students
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student && password_verify($password, $student["password"])) {

            session_regenerate_id(true);

            $_SESSION["student_id"] = $student["id"];
            $_SESSION["student_name"] = $student["full_name"];
            $_SESSION["student_code"] = $student["student_id"];
            $_SESSION["student_email"] = $student["email"];

            $conn->close();

            header("Location: student_dashboard.php");
            exit;

        } else {
            $error = "Incorrect email or password.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background: #f7f3eb;
            font-family: 'Poppins', sans-serif;
            color: #40382f;
        }

        .login-wrapper {
            max-width: 480px;
            margin: 55px auto;
            padding: 38px;
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(64, 56, 47, 0.08);
        }

        .login-wrapper h2 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 10px;
        }

        .login-wrapper .form-control {
            padding: 12px;
            border-color: #d8cfc2;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            border: 0;
            background: #40382f;
            color: white;
            font-weight: 500;
        }

        .login-btn:hover {
            background: #5d4c39;
        }

        .login-links a {
            color: #6c5036;
        }
    </style>
</head>

<body>


<header class="site-header">
    <a href="index.php" class="logo home-logo">
        Hostelia
    </a>

    <p class="tagline">HOSTEL MANAGEMENT SYSTEM</p>

    <div class="header-auth">
        <a href="registration.html" class="auth-register">Register</a>
        <a href="login.php" class="auth-login">Student Login</a>
    </div>
</header>


<main class="container">
    <div class="login-wrapper">

        <span class="section-label">WELCOME BACK</span>
        <h2>Student Login</h2>
        <p class="text-secondary mb-4">
            Log in to access your Hostelia student account.
        </p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email"
                       class="form-control"
                       id="email"
                       name="email"
                       placeholder="student@example.com"
                       autocomplete="username"
                       required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password"
                       class="form-control"
                       id="password"
                       name="password"
                       placeholder="Enter your password"
                       autocomplete="current-password"
                       required>
            </div>

            <button type="submit" class="login-btn">
                LOGIN
            </button>
        </form>

        <div class="login-links text-center mt-4">
            Don't have an account?
            <a href="registration.html">Register here</a>
        </div>

    </div>
</main>

<footer>
    <div class="footer-inner">
        <div><strong>Hostelia</strong> — Hostel Management System</div>
        <div>© 2026 All Rights Reserved</div>
    </div>
</footer>

</body>
</html>
