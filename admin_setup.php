<?php
session_start();
require_once "db.php";

$count = $conn->query("SELECT COUNT(*) AS total FROM admin_users")->fetch_assoc()["total"];

if ((int)$count > 0) {
    die("Admin account already exists. Setup is disabled.");
}

$error = "";

if (empty($_SESSION["setup_token"])) {
    $_SESSION["setup_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["setup_token"]) ||
        !hash_equals($_SESSION["setup_token"], $_POST["setup_token"])
    ) {
        $error = "Invalid request. Refresh the page.";
    } else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm = $_POST["confirm_password"] ?? "";

        if ($username === "" || strlen($username) < 4) {
            $error = "Username must contain at least 4 characters.";
        } elseif (strlen($password) < 12) {
            $error = "Password must contain at least 12 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO admin_users (username, password) VALUES (?, ?)"
            );
            $stmt->bind_param("ss", $username, $hash);

            try {
                $stmt->execute();
                $stmt->close();

                unset($_SESSION["setup_token"]);
                header("Location: admin_login.php");
                exit;
            } catch (mysqli_sql_exception $e) {
                $error = "Could not create admin account. Try another username.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Admin | Hostelia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body { background:#f8f4ed; color:#493a2e; font-family:Poppins,sans-serif; }
        .setup-card { max-width:450px; margin:70px auto; padding:30px; background:#fffdf9; border:1px solid #e9dfd1; border-radius:14px; }
        h2 { font-family:'Playfair Display',serif; }
        .btn-admin { background:#6f4b31; color:white; }
        .btn-admin:hover { background:#513722; color:white; }
    </style>
</head>
<body>
<div class="container">
    <div class="setup-card">
        <h2 class="mb-2">Create Admin Account</h2>
        <p class="text-secondary">Set up your Hostelia administrator login.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="setup_token"
                   value="<?= htmlspecialchars($_SESSION["setup_token"]) ?>">

            <div class="mb-3">
                <label class="form-label">Admin Username</label>
                <input type="text" name="username" class="form-control"
                       minlength="4" maxlength="100" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       minlength="12" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password"
                       class="form-control" minlength="12" required>
            </div>

            <button class="btn btn-admin w-100">Create Admin Account</button>
        </form>
    </div>
</div>
</body>
</html>