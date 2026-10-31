
<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $room_type = trim($_POST["room_type"] ?? "");
    $address = trim($_POST["address"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Check that both passwords match
    if ($password !== $confirm_password) {
        exit(
            "Passwords do not match. " .
            "<a href='registration.php'>Go back</a>"
        );
    }

    // Require at least 8 characters
    if (strlen($password) < 8) {
        exit(
            "Password must be at least 8 characters long. " .
            "<a href='registration.php'>Go back</a>"
        );
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit(
            "Please enter a valid email address. " .
            "<a href='registration.php'>Go back</a>"
        );
    }

    // Securely hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {

        $sql = "INSERT INTO students
                (full_name, student_id, email, password, phone,
                 gender, room_type, address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssssssss",
            $full_name,
            $student_id,
            $email,
            $hashed_password,
            $phone,
            $gender,
            $room_type,
            $address
        );

        $stmt->execute();

        // Registration successful: continue to login
        $stmt->close();
        $conn->close();

        header("Location: login.php");
        exit;

    } catch (mysqli_sql_exception $e) {

        if ($e->getCode() == 1062) {

            echo "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport'
                      content='width=device-width, initial-scale=1.0'>
                <title>Registration Error | Hostelia</title>
                <style>
                    body {
                        background: #f7f3eb;
                        font-family: Arial, sans-serif;
                        text-align: center;
                        padding-top: 100px;
                        color: #40382f;
                    }
                    .error-box {
                        width: 500px;
                        max-width: 90%;
                        box-sizing: border-box;
                        margin: auto;
                        background: #fbf8f2;
                        padding: 40px;
                        border: 1px solid #d8cfc2;
                    }
                    p { color: #75695d; }
                    a {
                        display: inline-block;
                        margin-top: 20px;
                        padding: 12px 22px;
                        background: #40382f;
                        color: white;
                        text-decoration: none;
                    }
                    a:hover { background: #5d4c39; }
                </style>
            </head>
            <body>
                <div class='error-box'>
                    <h2>Student ID Already Registered</h2>
                    <p>
                        This Student ID or another unique field
                        is already registered. Please check your details.
                    </p>
                    <a href='registration.php'>TRY AGAIN</a>
                </div>
            </body>
            </html>
            ";

        } else {
            error_log("Hostelia registration error: " . $e->getMessage());

            echo "
                <h2>Registration Failed</h2>
                <p>Something went wrong while submitting your registration.</p>
                <a href='registration.php'>Try Again</a>
            ";
        }

    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
    }

} else {
    header("Location: registration.php");
    exit;
}

?>
