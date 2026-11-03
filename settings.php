<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php?redirect=student_dashboard.php");
    exit;
}

include "db.php";

$studentId = (int) $_SESSION["student_id"];
$messages  = ["profile" => "", "password" => ""];
$errors    = ["profile" => "", "password" => ""];

/* ---------- Update profile ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $fullName = trim($_POST["full_name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $phone    = trim($_POST["phone"] ?? "");

    if ($fullName === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["profile"] = "Please enter a valid name and email.";
    } elseif ($phone !== "" && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
        $errors["profile"] = "Please enter a valid phone number.";
    } else {
        // Email must not belong to another student
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $email, $studentId);
        $stmt->execute();
        $taken = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($taken) {
            $errors["profile"] = "That email is already used by another account.";
        } else {
            $stmt = $conn->prepare("UPDATE students SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $fullName, $email, $phone, $studentId);
            $stmt->execute();
            $stmt->close();

            $_SESSION["student_name"]  = $fullName;
            $_SESSION["student_email"] = $email;
            $messages["profile"] = "Profile updated successfully.";
        }
    }
}

/* ---------- Change password ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_password"])) {
    $current = $_POST["current_password"] ?? "";
    $new     = $_POST["new_password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    $stmt = $conn->prepare("SELECT password FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()["password"] ?? "";
    $stmt->close();

    if (!password_verify($current, $hash)) {
        $errors["password"] = "Current password is incorrect.";
    } elseif (strlen($new) < 8) {
        $errors["password"] = "New password must be at least 8 characters.";
    } elseif ($new !== $confirm) {
        $errors["password"] = "New passwords do not match.";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $newHash, $studentId);
        $stmt->execute();
        $stmt->close();
        session_regenerate_id(true);
        $messages["password"] = "Password changed successfully.";
    }
}

/* ---------- Load current data ---------- */
$stmt = $conn->prepare("SELECT full_name, student_id, email, phone FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {            // account no longer exists
    header("Location: logout.php");
    exit;
}

// If the form failed, keep what the user typed
if ($errors["profile"] !== "") {
    $user["full_name"] = $_POST["full_name"] ?? $user["full_name"];
    $user["email"]     = $_POST["email"] ?? $user["email"];
    $user["phone"]     = $_POST["phone"] ?? $user["phone"];
}

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }

$isLoggedIn  = true;
$studentName = $_SESSION["student_name"] ?? "Student";
$initial     = strtoupper(substr($studentName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/settings.css">
</head>
<body>

<header class="site-header">
    <a href="index.php" class="logo home-logo">Hostelia</a>
    <p class="tagline">HOSTEL MANAGEMENT SYSTEM</p>

    <div class="header-auth">
        <details class="profile-menu">
            <summary class="profile-trigger">
                <span class="profile-avatar"><?= e($initial) ?></span>
                <span class="profile-name"><?= e($studentName) ?></span>
                <span class="profile-arrow">&#9662;</span>
            </summary>

            <div class="profile-dropdown">
                <a href="student_dashboard.php">My Dashboard</a>
                <a href="settings.php">Settings</a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="logout-option">Logout</a>
            </div>
        </details>
    </div>


</header>

<main class="settings-page">
    <span class="section-label">ACCOUNT</span>
    <h1>Settings</h1>
    <p class="page-sub">Manage your profile and password.</p>

    <!-- Profile -->
    <form class="settings-card" method="post" action="settings.php">
        <h2>Profile</h2>

        <?php if ($messages["profile"]): ?><p class="notice success"><?= e($messages["profile"]) ?></p><?php endif; ?>
        <?php if ($errors["profile"]): ?><p class="notice error"><?= e($errors["profile"]) ?></p><?php endif; ?>

        <label>Student ID
            <input type="text" value="<?= e($user["student_id"]) ?>" disabled>
        </label>
        <label>Full name
            <input type="text" name="full_name" value="<?= e($user["full_name"]) ?>" required maxlength="100">
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e($user["email"]) ?>" required maxlength="100">
        </label>
        <label>Phone
            <input type="text" name="phone" value="<?= e($user["phone"]) ?>" maxlength="15">
        </label>
        <button type="submit" name="update_profile" class="settings-btn">Save profile</button>
    </form>

    <!-- Password -->
    <form class="settings-card" method="post" action="settings.php">
        <h2>Change password</h2>

        <?php if ($messages["password"]): ?><p class="notice success"><?= e($messages["password"]) ?></p><?php endif; ?>
        <?php if ($errors["password"]): ?><p class="notice error"><?= e($errors["password"]) ?></p><?php endif; ?>

        <label>Current password
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <label>New password
            <input type="password" name="new_password" minlength="8" autocomplete="new-password" required>
        </label>
        <label>Confirm new password
            <input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
        </label>
        <button type="submit" name="update_password" class="settings-btn">Update password</button>
    </form>

    <a class="settings-link" href="student_dashboard.php">&larr; Back to dashboard</a>
</main>

<footer>
    <div class="footer-inner">
        <div><strong>Hostelia</strong> &mdash; Hostel Management System</div>
        <div>&copy; 2026 All Rights Reserved</div>
    </div>
</footer>

</body>
</html>