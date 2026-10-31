<?php
session_start();
require_once "db.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: admin.php");
    exit;
}

$error = "";

if (empty($_SESSION["admin_login_token"])) {
    $_SESSION["admin_login_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["admin_login_token"], $token)
    ) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        $stmt = $conn->prepare(
            "SELECT id, username, password
             FROM admin_users WHERE username = ? LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin["password"])) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = (int)$admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            unset($_SESSION["admin_login_token"]);

            header("Location: admin.php");
            exit;
        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <style>
        body {
            background:#f8f4ed;
            color:#493a2e;
            font-family:'Poppins',sans-serif;
        }

        .login-card {
            max-width:430px;
            margin:80px auto;
            padding:35px;
            background:#fffdf9;
            border:1px solid #e9dfd1;
            border-radius:14px;
            box-shadow:0 7px 22px rgba(72,51,33,.05);
        }

        h2 {
            font-family:'Playfair Display',serif;
            color:#493526;
        }

        .form-control {
            padding:12px;
            border-color:#e0d3c3;
        }

        .admin-btn {
            width:100%;
            padding:12px;
            background:#6f4b31;
            color:white;
            border:0;
            border-radius:8px;
        }

        .admin-btn:hover {
            background:#513722;
        }
    </style>
</head>

<body>
<div class="container">
    <div class="login-card">
        <h2 class="mb-2">Admin Login</h2>
        <p class="text-secondary mb-4">Sign in to manage Hostelia.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["admin_login_token"]) ?>">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username"
                       class="form-control" autocomplete="username" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password"
                       class="form-control" autocomplete="current-password" required>
            </div>

            <button type="submit" class="admin-btn">LOGIN</button>
        </form>

        <p class="text-center mt-4 mb-0">
            <a href="index.php" class="text-secondary">Back to Home</a>
        </p>
    </div>
</div>
</body>
</html>